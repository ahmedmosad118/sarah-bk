<?php

namespace App\Http\Controllers\Api;

use App\Core\CRUD\CRUDController;
use App\Core\CRUD\Field;
use App\Core\CRUD\InputMaker;
use App\Models\Boq;
use App\Models\Estimation;
use App\Models\Opportunity;
use App\Services\EstimationCalculationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EstimationController extends CRUDController
{
    protected string $model = Estimation::class;

    protected array $searchable = ['estimation_number', 'title', 'notes'];

    protected array $with = [
        'opportunity.customer',
        'boq.scope',
        'preparedUser',
        'reviewedUser',
        'approvedUser',
        'creator',
        'items.boqItem',
        'items.costs',
        'media',
    ];

    protected string $defaultSortBy = 'id';

    protected string $defaultSortOrder = 'desc';

    public function __construct(
        protected EstimationCalculationService $calculationService
    ) {}

    protected function inputMaker(): InputMaker
    {
        return InputMaker::make()
            ->title('estimation.title')
            ->singularTitle('estimation.singular')
            ->model(Estimation::class)
            ->fields([
                Field::select('opportunity_id', 'estimation.opportunity', [])
                    ->required()
                    ->col(6),

                Field::select('boq_id', 'estimation.boq', [])
                    ->required()
                    ->col(6),

                Field::text('estimation_number', 'estimation.estimationNumber')
                    ->col(6),

                Field::text('title', 'estimation.estimationTitle')
                    ->col(6),

                Field::select('prepared_by', 'estimation.preparedBy', [])
                    ->col(6),

                Field::date('prepared_at', 'estimation.preparedAt')
                    ->col(6),

                Field::number('overhead_rate', 'estimation.overheadRate')
                    ->col(6),

                Field::textarea('notes', 'estimation.notes')
                    ->col(12),
            ]);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission('estimations.view');

        $query = $this->query();

        // 1. Search Query
        if ($search = $request->input('search')) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('estimation_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('opportunity', function (Builder $oq) use ($search) {
                        $oq->where('title', 'like', "%{$search}%")
                            ->orWhereHas('customer', function (Builder $cq) use ($search) {
                                $cq->where('name', 'like', "%{$search}%")
                                    ->orWhere('company_name', 'like', "%{$search}%");
                            });
                    })
                    ->orWhereHas('boq', function (Builder $bq) use ($search) {
                        $bq->where('boq_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('items', function (Builder $iq) use ($search) {
                        $iq->where('item_code', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
            });
        }

        // 2. Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('opportunity_id')) {
            $query->where('opportunity_id', $request->opportunity_id);
        }

        if ($request->filled('boq_id')) {
            $query->where('boq_id', $request->boq_id);
        }

        if ($request->filled('prepared_by')) {
            $query->where('prepared_by', $request->prepared_by);
        }

        // 3. Sorting
        $rawSortBy = (string) $request->input('sort_by', $this->defaultSortBy);
        $allowedSorts = ['id', 'estimation_number', 'version', 'status', 'total_cost', 'prepared_at', 'created_at'];
        $sortBy = in_array($rawSortBy, $allowedSorts, true) ? $rawSortBy : $this->defaultSortBy;

        $rawSortOrder = strtolower((string) $request->input('sort_order', $this->defaultSortOrder));
        $sortOrder = in_array($rawSortOrder, ['asc', 'desc'], true) ? $rawSortOrder : 'desc';

        $query->orderBy($sortBy, $sortOrder);

        // 4. Pagination
        $rawPerPage = (int) $request->input('per_page', 15);
        $perPage = min(max($rawPerPage, 1), 100);
        $paginated = $query->paginate($perPage);

        // 5. Aggregate Stats
        $stats = [
            'total' => Estimation::count(),
            'draft' => Estimation::where('status', 'Draft')->count(),
            'under_review' => Estimation::where('status', 'Under Review')->count(),
            'approved' => Estimation::where('status', 'Approved')->count(),
            'superseded' => Estimation::where('status', 'Superseded')->count(),
            'total_estimated_cost' => (float) Estimation::where('status', 'Approved')->sum('total_cost'),
        ];

        return response()->json([
            'success' => true,
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
            'stats' => $stats,
            'schema' => $this->inputMaker()->toSchema(),
        ]);
    }

    public function show(int|string $id): JsonResponse
    {
        $this->authorizePermission('estimations.view');

        $estimation = $this->query()->findOrFail($id);

        // Check if referenced source BOQ is stale
        $latestApprovedBoq = Boq::where('opportunity_id', $estimation->opportunity_id)
            ->where('status', 'Approved')
            ->orderBy('version', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $isStaleSource = (bool) ($latestApprovedBoq && $latestApprovedBoq->id !== $estimation->boq_id);

        return response()->json([
            'success' => true,
            'data' => $estimation,
            'is_stale_source' => $isStaleSource,
            'latest_approved_boq' => $latestApprovedBoq ? [
                'id' => $latestApprovedBoq->id,
                'boq_number' => $latestApprovedBoq->boq_number,
                'version' => $latestApprovedBoq->version,
            ] : null,
            'schema' => $this->inputMaker()->toSchema(),
        ]);
    }

    /**
     * Determine if authenticated user is authorized to assign or reassign document preparer.
     */
    protected function canAssignPreparer(string $permission = 'estimations.approve'): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->hasRole('Owner') || $user->hasRole('Super Admin') || $user->hasPermissionTo($permission);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission('estimations.create');

        // Guardrails: status / version / calculated totals are never trusted from client
        $validated = $request->validate([
            'opportunity_id' => ['required', 'integer', 'exists:opportunities,id'],
            'boq_id' => ['required', 'integer', 'exists:boqs,id'],
            'estimation_number' => ['nullable', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:255'],
            'prepared_by' => ['nullable', 'integer', 'exists:users,id'],
            'prepared_at' => ['nullable', 'date'],
            'overhead_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['nullable', 'array'],
            'items.*.boq_item_id' => ['required', 'integer', 'exists:boq_items,id'],
            'items.*.notes' => ['nullable', 'string', 'max:2000'],
            'items.*.costs' => ['nullable', 'array'],
            'items.*.costs.*.cost_type' => ['required', 'string', 'in:material,labor,equipment,subcontractor,other'],
            'items.*.costs.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.costs.*.cost_rate' => ['required', 'numeric', 'min:0'],
            'items.*.costs.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $opportunity = Opportunity::findOrFail($validated['opportunity_id']);
        $boq = Boq::with('items')->findOrFail($validated['boq_id']);

        // Identity check: default prepared_by to authenticated user
        $preparedBy = auth()->id();
        if ($request->filled('prepared_by')) {
            $requestedPreparer = (int) $request->input('prepared_by');
            if ($requestedPreparer !== (int) auth()->id()) {
                if (! $this->canAssignPreparer('estimations.approve')) {
                    throw ValidationException::withMessages([
                        'prepared_by' => [__('messages.unauthorized_to_assign_preparer')],
                    ]);
                }
                $preparedBy = $requestedPreparer;
            }
        }

        // Rule 1: BOQ MUST be Approved
        if (! $boq->isApproved()) {
            throw ValidationException::withMessages([
                'boq_id' => [__('messages.estimation_requires_approved_boq')],
            ]);
        }

        // Rule 2: BOQ MUST belong to the specified Opportunity
        if ((int) $boq->opportunity_id !== (int) $opportunity->id) {
            throw ValidationException::withMessages([
                'boq_id' => [__('messages.boq_opportunity_mismatch')],
            ]);
        }

        // Rule 2.1: Check if BOQ is stale (superseded by a newer approved BOQ)
        $hasNewerApprovedBoq = Boq::where('opportunity_id', $opportunity->id)
            ->where('status', 'Approved')
            ->where('id', '!=', $boq->id)
            ->where(function ($q) use ($boq) {
                $q->where('version', '>', $boq->version)
                    ->orWhere('id', '>', $boq->id);
            })
            ->exists();

        if ($hasNewerApprovedBoq) {
            throw ValidationException::withMessages([
                'boq_id' => [__('messages.cannot_estimate_stale_boq')],
            ]);
        }

        // Rule 3: BOQ must have items to cost
        if ($boq->items->isEmpty()) {
            throw ValidationException::withMessages([
                'boq_id' => [__('messages.cannot_estimate_empty_boq')],
            ]);
        }

        $maxAttempts = 3;
        $estimation = null;
        $overheadRate = isset($validated['overhead_rate']) ? (float) $validated['overhead_rate'] : 0.00;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $estimation = DB::transaction(function () use ($validated, $opportunity, $boq, $preparedBy, $overheadRate) {
                    $number = $validated['estimation_number'] ?? $this->generateEstimationNumber($opportunity->id);

                    $estimation = Estimation::create([
                        'opportunity_id' => $opportunity->id,
                        'boq_id' => $boq->id,
                        'estimation_number' => $number,
                        'version' => 1,
                        'status' => 'Draft',
                        'title' => $validated['title'] ?? (__('messages.estimation_title_prefix').' '.$opportunity->title),
                        'prepared_by' => $preparedBy,
                        'prepared_at' => $validated['prepared_at'] ?? Carbon::today()->toDateString(),
                        'overhead_rate' => $overheadRate,
                        'notes' => $validated['notes'] ?? null,
                        'created_by' => auth()->id(),
                    ]);

                    // Index optional initial costs by boq_item_id
                    $initialItemsData = collect($validated['items'] ?? [])->keyBy('boq_item_id');

                    // Generate estimation lines for ALL source BOQ items (quantities and units strictly read-only)
                    foreach ($boq->items as $boqItem) {
                        $itemInput = $initialItemsData->get($boqItem->id);

                        $estItem = $estimation->items()->create([
                            'boq_item_id' => $boqItem->id,
                            'item_code' => $boqItem->item_code,
                            'description' => $boqItem->description,
                            'unit' => $boqItem->unit,
                            'quantity' => $boqItem->quantity,
                            'notes' => $itemInput['notes'] ?? null,
                            'sort_order' => $boqItem->sort_order,
                        ]);

                        if (! empty($itemInput['costs']) && is_array($itemInput['costs'])) {
                            $this->calculationService->syncItemCosts($estItem, $itemInput['costs']);
                        }
                    }

                    // Calculate document totals
                    $this->calculationService->recalculateEstimationTotals($estimation, $overheadRate);

                    activity('commercial')
                        ->event('created')
                        ->performedOn($estimation)
                        ->causedBy(auth()->user())
                        ->log(__('activity.estimation_created', ['number' => $estimation->estimation_number]));

                    return $estimation;
                });
                break;
            } catch (UniqueConstraintViolationException|QueryException $e) {
                if ($attempt < $maxAttempts && empty($validated['estimation_number']) && str_contains($e->getMessage(), 'estimations_number_unique')) {
                    continue;
                }
                throw $e;
            }
        }

        return response()->json([
            'success' => true,
            'message' => __('messages.estimation_created_success'),
            'data' => $estimation->fresh($this->with),
        ], 200);
    }

    /**
     * Create Estimation directly originating from an approved BOQ.
     */
    public function createFromBoq(Request $request, int|string $boqId): JsonResponse
    {
        $this->authorizePermission('estimations.create');

        $boq = Boq::with('opportunity')->findOrFail($boqId);

        if (! $boq->isApproved()) {
            throw ValidationException::withMessages([
                'boq_id' => [__('messages.estimation_requires_approved_boq')],
            ]);
        }

        $request->merge([
            'opportunity_id' => $boq->opportunity_id,
            'boq_id' => $boq->id,
        ]);

        return $this->store($request);
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission('estimations.update');

        $estimation = Estimation::with('items')->findOrFail($id);

        if (! $estimation->canBeEdited()) {
            throw ValidationException::withMessages([
                'status' => [__('messages.approved_estimation_cannot_be_edited')],
            ]);
        }

        // Note: boq_id is immutable after creation; client cannot silently switch source.
        // Source quantity and unit are never accepted from client input.
        $validated = $request->validate([
            'estimation_number' => ['nullable', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:255'],
            'prepared_by' => ['nullable', 'integer', 'exists:users,id'],
            'prepared_at' => ['nullable', 'date'],
            'overhead_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'zero_cost_reason' => ['nullable', 'string', 'in:Client-Supplied Material,Included in Another Item,Pending Vendor Quote,Other — see notes'],
            'items' => ['nullable', 'array'],
            'items.*.boq_item_id' => ['required', 'integer', 'exists:boq_items,id'],
            'items.*.notes' => ['nullable', 'string', 'max:2000'],
            'items.*.costs' => ['nullable', 'array'],
            'items.*.costs.*.cost_type' => ['required', 'string', 'in:material,labor,equipment,subcontractor,other'],
            'items.*.costs.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.costs.*.cost_rate' => ['required', 'numeric', 'min:0'],
            'items.*.costs.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        // Integrity: verify every boq_item_id belongs to this Estimation's source BOQ
        $itemsData = $validated['items'] ?? null;
        if (is_array($itemsData)) {
            $validBoqItemIds = Boq::findOrFail($estimation->boq_id)->items()->pluck('id')->all();
            foreach ($itemsData as $idx => $itemInput) {
                if (! in_array((int) $itemInput['boq_item_id'], $validBoqItemIds, true)) {
                    throw ValidationException::withMessages([
                        "items.{$idx}.boq_item_id" => [__('messages.estimation_boq_item_mismatch')],
                    ]);
                }
            }
        }

        // Identity check: modifying prepared_by requires manager/approval permission
        if ($request->filled('prepared_by')) {
            $requestedPreparer = (int) $request->input('prepared_by');
            if ($requestedPreparer !== (int) $estimation->prepared_by) {
                if (! $this->canAssignPreparer('estimations.approve')) {
                    throw ValidationException::withMessages([
                        'prepared_by' => [__('messages.unauthorized_to_assign_preparer')],
                    ]);
                }
            }
        }

        $estimation = DB::transaction(function () use ($estimation, $validated, $itemsData, $request) {
            if (! empty($validated['estimation_number'])) {
                $estimation->estimation_number = $validated['estimation_number'];
            }
            if (isset($validated['title'])) {
                $estimation->title = $validated['title'];
            }
            if (isset($validated['prepared_by'])) {
                $estimation->prepared_by = $validated['prepared_by'];
            }
            if (isset($validated['prepared_at'])) {
                $estimation->prepared_at = $validated['prepared_at'];
            }
            if (isset($validated['overhead_rate'])) {
                $estimation->overhead_rate = (float) $validated['overhead_rate'];
            }
            if (isset($validated['notes'])) {
                $estimation->notes = $validated['notes'];
            }
            if ($request->has('zero_cost_reason')) {
                $estimation->zero_cost_reason = $validated['zero_cost_reason'];
            }

            if (is_array($itemsData)) {
                $existingItemsByBoqItem = $estimation->items()->get()->keyBy('boq_item_id');

                foreach ($itemsData as $itemInput) {
                    $boqItemId = (int) $itemInput['boq_item_id'];
                    $estItem = $existingItemsByBoqItem->get($boqItemId);

                    if ($estItem) {
                        if (isset($itemInput['notes'])) {
                            $estItem->update(['notes' => $itemInput['notes']]);
                        }

                        if (isset($itemInput['costs']) && is_array($itemInput['costs'])) {
                            $this->calculationService->syncItemCosts($estItem, $itemInput['costs']);
                        }
                    }
                }
            }

            // Recalculate document totals
            $this->calculationService->recalculateEstimationTotals($estimation, (float) $estimation->overhead_rate);

            $estimation->save();

            activity('commercial')
                ->event('updated')
                ->performedOn($estimation)
                ->causedBy(auth()->user())
                ->log(__('activity.estimation_updated', ['number' => $estimation->estimation_number]));

            return $estimation;
        });

        return response()->json([
            'success' => true,
            'message' => __('messages.estimation_updated_success'),
            'data' => $estimation->fresh($this->with),
        ]);
    }

    public function destroy(int|string|Request $ids): JsonResponse
    {
        $this->authorizePermission('estimations.delete');

        $idList = is_array($ids) ? $ids : (is_numeric($ids) ? [(int) $ids] : explode(',', (string) $ids));

        $estimations = Estimation::whereIn('id', $idList)->get();

        if ($estimations->isEmpty()) {
            abort(404, __('messages.estimation_not_found'));
        }

        foreach ($estimations as $est) {
            if ($est->isApproved()) {
                throw ValidationException::withMessages([
                    'status' => [__('messages.approved_estimation_cannot_be_deleted')],
                ]);
            }
        }

        foreach ($estimations as $est) {
            $number = $est->estimation_number;
            $est->delete();

            activity('commercial')
                ->event('deleted')
                ->performedOn($est)
                ->causedBy(auth()->user())
                ->log(__('activity.estimation_deleted', ['number' => $number]));
        }

        return response()->json([
            'success' => true,
            'message' => __('messages.estimation_deleted_success'),
        ]);
    }

    /**
     * Submit Estimation for technical/management review.
     */
    public function submitReview(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission('estimations.update');

        $estimation = Estimation::findOrFail($id);

        if (! $estimation->isDraft()) {
            throw ValidationException::withMessages([
                'status' => [__('messages.only_draft_estimation_can_be_submitted_for_review')],
            ]);
        }

        if ($estimation->items()->count() === 0) {
            throw ValidationException::withMessages([
                'items' => [__('messages.cannot_submit_empty_estimation')],
            ]);
        }

        $estimation->status = 'Under Review';
        $estimation->reviewed_by = auth()->id();
        $estimation->reviewed_at = Carbon::now();
        $estimation->save();

        activity('commercial')
            ->event('updated')
            ->performedOn($estimation)
            ->causedBy(auth()->user())
            ->log(__('activity.estimation_submitted_for_review', ['number' => $estimation->estimation_number]));

        return response()->json([
            'success' => true,
            'message' => __('messages.estimation_submitted_review_success'),
            'data' => $estimation->fresh($this->with),
        ]);
    }

    /**
     * Approve Estimation as authoritative internal costing baseline.
     */
    public function approve(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission('estimations.approve');

        return DB::transaction(function () use ($id, $request) {
            // 1. Lock the Estimation record for update
            $estimation = Estimation::with(['items.costs', 'boq'])
                ->where('id', $id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $estimation->canBeApproved()) {
                throw ValidationException::withMessages([
                    'status' => [__('messages.estimation_cannot_be_approved_in_current_state')],
                ]);
            }

            // 2. Lock sibling approved estimations for this opportunity to serialize concurrent approvals
            $siblingApproved = Estimation::where('opportunity_id', $estimation->opportunity_id)
                ->where('id', '!=', $estimation->id)
                ->where('status', 'Approved')
                ->lockForUpdate()
                ->get();

            // 3. Source BOQ must still be Approved and locked against race conditions
            $sourceBoq = Boq::where('id', $estimation->boq_id)->lockForUpdate()->first();

            if (! $sourceBoq || ! $sourceBoq->isApproved()) {
                throw ValidationException::withMessages([
                    'boq_id' => [__('messages.cannot_approve_stale_source_boq')],
                ]);
            }

            // 4. Source BOQ must not be superseded by a newer approved BOQ
            $latestApprovedBoq = Boq::where('opportunity_id', $estimation->opportunity_id)
                ->where('status', 'Approved')
                ->orderBy('version', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            if ($latestApprovedBoq && (int) $latestApprovedBoq->id !== (int) $estimation->boq_id) {
                throw ValidationException::withMessages([
                    'boq_id' => [__('messages.cannot_approve_stale_source_boq')],
                ]);
            }

            // 5. Cannot approve an older estimation revision if a newer approved estimation exists
            $hasNewerApproved = Estimation::where('opportunity_id', $estimation->opportunity_id)
                ->where('status', 'Approved')
                ->where('version', '>', $estimation->version)
                ->exists();

            if ($hasNewerApproved) {
                throw ValidationException::withMessages([
                    'version' => [__('messages.cannot_approve_superseded_estimation')],
                ]);
            }

            // 6. Estimation must have items
            if ($estimation->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => [__('messages.cannot_approve_empty_estimation')],
                ]);
            }

            // 7. Incomplete check: Every single item must have at least one cost entry or documented note
            $unpricedItems = $estimation->items->filter(function ($item) {
                return $item->costs->isEmpty() && empty(trim((string) $item->notes));
            });

            if ($unpricedItems->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'items' => [__('messages.all_items_must_be_costed')],
                    'total_cost' => [__('messages.cannot_approve_zero_cost_estimation')],
                ]);
            }

            // 8. Zero-cost check: If total_cost is 0.00, require zero_cost_reason & explicit justification
            $totalCost = (float) $estimation->total_cost;
            if ($totalCost <= 0) {
                $zeroCostReason = $request->input('zero_cost_reason') ?? $estimation->zero_cost_reason;
                $validReasons = [
                    'Client-Supplied Material',
                    'Included in Another Item',
                    'Pending Vendor Quote',
                    'Other — see notes',
                ];

                if (! $zeroCostReason || ! in_array($zeroCostReason, $validReasons, true)) {
                    throw ValidationException::withMessages([
                        'zero_cost_reason' => [__('messages.zero_cost_reason_required')],
                        'total_cost' => [__('messages.cannot_approve_zero_cost_estimation')],
                    ]);
                }

                if ($zeroCostReason === 'Other — see notes') {
                    $docNotes = trim((string) ($request->input('notes') ?? $estimation->notes));
                    $hasMeaningfulDocNotes = strlen($docNotes) >= 10;
                    $hasMeaningfulItemNotes = $estimation->items->contains(function ($it) {
                        return strlen(trim((string) $it->notes)) >= 10;
                    });

                    // Cost component descriptions (e.g. "Labor", "Cement") alone must never count as justification!
                    if (! $hasMeaningfulDocNotes && ! $hasMeaningfulItemNotes) {
                        throw ValidationException::withMessages([
                            'notes' => [__('messages.zero_cost_requires_justification')],
                            'total_cost' => [__('messages.zero_cost_requires_justification')],
                        ]);
                    }
                }

                $estimation->zero_cost_reason = $zeroCostReason;
            }

            // Supersede any previously approved Estimation for this opportunity
            foreach ($siblingApproved as $prevApproved) {
                $prevApproved->update(['status' => 'Superseded']);
            }

            $estimation->status = 'Approved';
            $estimation->approved_by = auth()->id();
            $estimation->approved_at = Carbon::now();
            $estimation->save();

            activity('commercial')
                ->event('updated')
                ->performedOn($estimation)
                ->causedBy(auth()->user())
                ->log(__('activity.estimation_approved', ['number' => $estimation->estimation_number]));

            return response()->json([
                'success' => true,
                'message' => __('messages.estimation_approved_success'),
                'data' => $estimation->fresh($this->with),
            ]);
        });
    }

    /**
     * Create a new draft revision from an approved Estimation (same frozen source BOQ).
     */
    public function createRevision(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission('estimations.create');

        $parent = Estimation::with(['items.costs'])->findOrFail($id);

        if (! $parent->isApproved()) {
            throw ValidationException::withMessages([
                'status' => [__('messages.only_approved_estimation_can_be_revised')],
            ]);
        }

        $revision = DB::transaction(function () use ($parent) {
            $newVersion = $parent->version + 1;
            $newNumber = $parent->nextRevisionNumber();

            $newEst = Estimation::create([
                'opportunity_id' => $parent->opportunity_id,
                'boq_id' => $parent->boq_id,
                'estimation_number' => $newNumber,
                'version' => $newVersion,
                'status' => 'Draft',
                'title' => $parent->title,
                'prepared_by' => auth()->id(),
                'prepared_at' => Carbon::today()->toDateString(),
                'notes' => __('messages.estimation_revision_note', [
                    'version' => $newVersion,
                    'number' => $parent->estimation_number,
                ]),
                'direct_cost' => $parent->direct_cost,
                'overhead_rate' => $parent->overhead_rate,
                'overhead_amount' => $parent->overhead_amount,
                'total_cost' => $parent->total_cost,
                'created_by' => auth()->id(),
            ]);

            foreach ($parent->items as $parentItem) {
                $newEstItem = $newEst->items()->create([
                    'boq_item_id' => $parentItem->boq_item_id,
                    'item_code' => $parentItem->item_code,
                    'description' => $parentItem->description,
                    'unit' => $parentItem->unit,
                    'quantity' => $parentItem->quantity,
                    'material_cost' => $parentItem->material_cost,
                    'labor_cost' => $parentItem->labor_cost,
                    'equipment_cost' => $parentItem->equipment_cost,
                    'subcontractor_cost' => $parentItem->subcontractor_cost,
                    'other_cost' => $parentItem->other_cost,
                    'unit_cost' => $parentItem->unit_cost,
                    'total_cost' => $parentItem->total_cost,
                    'notes' => $parentItem->notes,
                    'sort_order' => $parentItem->sort_order,
                ]);

                foreach ($parentItem->costs as $cost) {
                    $newEstItem->costs()->create([
                        'cost_type' => $cost->cost_type,
                        'description' => $cost->description,
                        'cost_rate' => $cost->cost_rate,
                        'amount' => $cost->amount,
                        'notes' => $cost->notes,
                    ]);
                }
            }

            activity('commercial')
                ->event('created')
                ->performedOn($newEst)
                ->causedBy(auth()->user())
                ->log(__('messages.estimation_revision_activity', [
                    'number' => $newEst->estimation_number,
                    'version' => $newVersion,
                ]));

            return $newEst;
        });

        return response()->json([
            'success' => true,
            'message' => __('messages.estimation_revision_created_success'),
            'data' => $revision->fresh($this->with),
        ], 200);
    }

    protected function generateEstimationNumber(int $opportunityId): string
    {
        $latestNumber = Estimation::where('opportunity_id', $opportunityId)
            ->where('estimation_number', 'like', "EST-OPP{$opportunityId}-%")
            ->orderByRaw('LENGTH(estimation_number) desc, estimation_number desc')
            ->value('estimation_number');

        if ($latestNumber && preg_match('/EST-OPP\d+-(\d+)/', $latestNumber, $matches)) {
            $seq = (int) $matches[1] + 1;
        } else {
            $seq = Estimation::where('opportunity_id', $opportunityId)->count() + 1;
        }

        $candidate = "EST-OPP{$opportunityId}-".str_pad((string) $seq, 3, '0', STR_PAD_LEFT);

        while (Estimation::where('estimation_number', $candidate)->exists()) {
            $seq++;
            $candidate = "EST-OPP{$opportunityId}-".str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
        }

        return $candidate;
    }
}
