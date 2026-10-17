<?php

namespace App\Http\Controllers\Api;

use App\Core\CRUD\CRUDController;
use App\Core\CRUD\Field;
use App\Core\CRUD\InputMaker;
use App\Models\Boq;
use App\Models\Opportunity;
use App\Models\Scope;
use App\Services\BoqGenerationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BoqController extends CRUDController
{
    protected BoqGenerationService $generationService;

    public function __construct(BoqGenerationService $generationService)
    {
        $this->generationService = $generationService;
    }

    protected string $model = Boq::class;

    protected array $searchable = ['boq_number', 'title', 'notes'];

    protected array $with = [
        'opportunity.customer',
        'scope.measurement',
        'preparedUser',
        'reviewedUser',
        'approvedUser',
        'creator',
        'items.scopeItem',
        'media',
    ];

    protected string $defaultSortBy = 'id';

    protected string $defaultSortOrder = 'desc';

    protected function inputMaker(): InputMaker
    {
        return InputMaker::make()
            ->title('boq.title')
            ->singularTitle('boq.singular')
            ->model(Boq::class)
            ->fields([
                Field::select('opportunity_id', 'boq.opportunity', [])
                    ->required()
                    ->col(6),

                Field::select('scope_id', 'boq.scope', [])
                    ->required()
                    ->col(6),

                // NOTE: status / version are intentionally NOT exposed as form inputs
                // (ARCHITECTURAL_GUARDRAILS: status transitions via dedicated endpoints only).
                Field::text('boq_number', 'boq.boqNumber')
                    ->col(6),

                Field::text('title', 'boq.boqTitle')
                    ->col(6),

                Field::select('prepared_by', 'boq.preparedBy', [])
                    ->col(6),

                Field::date('prepared_at', 'boq.preparedAt')
                    ->col(6),

                Field::textarea('notes', 'boq.notes')
                    ->col(12),
            ]);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission(['boqs.view', 'boq.view']);

        $query = $this->query();

        // 1. Search Query
        if ($search = $request->input('search')) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('boq_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('opportunity', function (Builder $oq) use ($search) {
                        $oq->where('title', 'like', "%{$search}%")
                            ->orWhereHas('customer', function (Builder $cq) use ($search) {
                                $cq->where('name', 'like', "%{$search}%")
                                    ->orWhere('company_name', 'like', "%{$search}%");
                            });
                    })
                    ->orWhereHas('scope', function (Builder $sq) use ($search) {
                        $sq->where('scope_number', 'like', "%{$search}%");
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

        if ($request->filled('scope_id')) {
            $query->where('scope_id', $request->scope_id);
        }

        if ($request->filled('prepared_by')) {
            $query->where('prepared_by', $request->prepared_by);
        }

        // 3. Sorting
        $rawSortBy = (string) $request->input('sort_by', $this->defaultSortBy);
        $allowedSorts = ['id', 'boq_number', 'version', 'status', 'prepared_at', 'created_at'];
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
            'total' => Boq::count(),
            'draft' => Boq::where('status', 'Draft')->count(),
            'under_review' => Boq::where('status', 'Under Review')->count(),
            'approved' => Boq::where('status', 'Approved')->count(),
            'superseded' => Boq::where('status', 'Superseded')->count(),
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
        $this->authorizePermission(['boqs.view', 'boq.view']);

        $boq = $this->query()->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $boq,
            'unresolved_items' => $this->resolveUnresolvedItems($boq),
            'schema' => $this->inputMaker()->toSchema(),
        ]);
    }

    /**
     * Scope items of the BOQ's source scope that have NO BOQ line, with the
     * reason (mixed units / no linked measurement items). Re-derived from the
     * same rules used at generation time, so the manual-intervention warning
     * stays visible after creation without persisting extra state.
     */
    protected function resolveUnresolvedItems(Boq $boq): array
    {
        $scope = Scope::with('items.measurementItems')->find($boq->scope_id);
        if (! $scope) {
            return [];
        }

        $coveredIds = $boq->items()->pluck('scope_item_id')->map(fn ($v) => (int) $v)->all();

        return array_values(array_filter(
            $this->generationService->buildItemsFromScope($scope)['skipped'],
            fn (array $sk) => ! in_array((int) $sk['scope_item_id'], $coveredIds, true)
        ));
    }

    /**
     * Determine if authenticated user is authorized to assign or reassign document preparer.
     */
    protected function canAssignPreparer(string $permission = 'boqs.approve'): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->hasRole('Owner') || $user->hasRole('Super Admin') || $user->hasPermissionTo($permission);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission(['boqs.create', 'boq.create']);

        // NOTE: status / version / approval metadata are NEVER accepted as input (guardrails).
        $validated = $request->validate([
            'opportunity_id' => ['required', 'integer', 'exists:opportunities,id'],
            'scope_id' => ['required', 'integer', 'exists:scopes,id'],
            'boq_number' => ['nullable', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:255'],
            'prepared_by' => ['nullable', 'integer', 'exists:users,id'],
            'prepared_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $opportunity = Opportunity::findOrFail($validated['opportunity_id']);
        $scope = Scope::with('items.measurementItems')->findOrFail($validated['scope_id']);

        // Identity check: default prepared_by to authenticated user
        $preparedBy = auth()->id();
        if ($request->filled('prepared_by')) {
            $requested = (int) $request->input('prepared_by');
            if ($requested !== (int) auth()->id()) {
                if (! $this->canAssignPreparer('boqs.approve')) {
                    throw ValidationException::withMessages([
                        'prepared_by' => [__('messages.unauthorized_to_assign_preparer')],
                    ]);
                }
                $preparedBy = $requested;
            }
        }

        // Preparation date: default to trusted server-side date; custom date requires manager permission
        $preparedAt = Carbon::today()->toDateString();
        if ($request->filled('prepared_at')) {
            $requestedDate = Carbon::parse($request->input('prepared_at'))->toDateString();
            if ($requestedDate !== $preparedAt) {
                if (! $this->canAssignPreparer('boqs.approve')) {
                    throw ValidationException::withMessages([
                        'prepared_at' => [__('messages.unauthorized_to_assign_prepared_at')],
                    ]);
                }
                $preparedAt = $requestedDate;
            }
        }

        // Strict Rule: Scope MUST be Approved
        if (! $scope->isApproved()) {
            throw ValidationException::withMessages([
                'scope_id' => [__('messages.boq_requires_approved_scope')],
            ]);
        }

        // Strict Rule: Scope MUST belong to the specified Opportunity
        if ($scope->opportunity_id !== $opportunity->id) {
            throw ValidationException::withMessages([
                'scope_id' => [__('messages.scope_opportunity_mismatch')],
            ]);
        }

        $generated = $this->generationService->buildItemsFromScope($scope);

        $maxAttempts = 3;
        $boq = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $boq = DB::transaction(function () use ($validated, $opportunity, $scope, $generated, $preparedBy, $preparedAt) {
                    $number = $validated['boq_number'] ?? $this->generateBoqNumber($opportunity->id);

                    $boq = Boq::create([
                        'opportunity_id' => $opportunity->id,
                        'scope_id' => $scope->id,
                        'boq_number' => $number,
                        'version' => 1,
                        'status' => 'Draft',
                        'title' => $validated['title'] ?? (__('messages.boq_title_prefix').' '.$opportunity->title),
                        'prepared_by' => $preparedBy,
                        'prepared_at' => $preparedAt,
                        'notes' => $validated['notes'] ?? null,
                        'created_by' => auth()->id(),
                    ]);

                    foreach ($generated['lines'] as $line) {
                        $boq->items()->create($line);
                    }

                    activity('commercial')
                        ->event('created')
                        ->performedOn($boq)
                        ->causedBy(auth()->user())
                        ->log(__('activity.boq_created', ['number' => $boq->boq_number]));

                    return $boq;
                });
                break;
            } catch (UniqueConstraintViolationException|QueryException $e) {
                if ($attempt < $maxAttempts && empty($validated['boq_number']) && str_contains($e->getMessage(), 'boqs_number_unique')) {
                    continue;
                }
                throw $e;
            }
        }

        return response()->json([
            'success' => true,
            'message' => __('messages.boq_created_success'),
            'data' => $boq->fresh($this->with),
            'skipped_items' => $generated['skipped'],
        ], 200);
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission(['boqs.update', 'boq.update']);

        $boq = Boq::with('items')->findOrFail($id);

        if (! $boq->canBeEdited()) {
            throw ValidationException::withMessages([
                'status' => [__('messages.approved_boq_cannot_be_edited')],
            ]);
        }

        // NOTE: scope_id is intentionally immutable after creation (historical source
        // revision cannot be silently replaced). Any scope_id input is ignored.
        // NOTE: quantity / unit are always re-derived from source, never trusted.
        $validated = $request->validate([
            'boq_number' => ['nullable', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:255'],
            'prepared_by' => ['nullable', 'integer', 'exists:users,id'],
            'prepared_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['nullable', 'array'],
            'items.*.scope_item_id' => ['required', 'integer', 'exists:scope_items,id'],
            'items.*.item_code' => ['nullable', 'string', 'max:20'],
            'items.*.description' => ['nullable', 'string', 'max:10000'],
            'items.*.notes' => ['nullable', 'string', 'max:2000'],
            'items.*.sort_order' => ['nullable', 'integer'],
        ]);

        // Strict Rule: every referenced scope item MUST belong to this BOQ's scope
        $itemsData = $validated['items'] ?? null;
        if (is_array($itemsData)) {
            $validScopeItemIds = $boq->scope()->first()->items()->pluck('scope_items.id')->all();
            foreach ($itemsData as $idx => $item) {
                if (! in_array((int) $item['scope_item_id'], $validScopeItemIds, true)) {
                    throw ValidationException::withMessages([
                        "items.{$idx}.scope_item_id" => [__('messages.boq_scope_item_mismatch')],
                    ]);
                }
            }
        }

        // Identity check: modifying prepared_by requires manager/approval permission
        if ($request->filled('prepared_by')) {
            $requested = (int) $request->input('prepared_by');
            if ($requested !== (int) $boq->prepared_by) {
                if (! $this->canAssignPreparer('boqs.approve')) {
                    throw ValidationException::withMessages([
                        'prepared_by' => [__('messages.unauthorized_to_assign_preparer')],
                    ]);
                }
            }
        }

        // Preparation date check: modifying prepared_at requires manager/approval permission
        if ($request->filled('prepared_at')) {
            $requestedDate = Carbon::parse($request->input('prepared_at'))->toDateString();
            $currentDate = $boq->prepared_at ? Carbon::parse($boq->prepared_at)->toDateString() : null;
            if ($requestedDate !== $currentDate) {
                if (! $this->canAssignPreparer('boqs.approve')) {
                    throw ValidationException::withMessages([
                        'prepared_at' => [__('messages.unauthorized_to_assign_prepared_at')],
                    ]);
                }
            }
        }

        $boq = DB::transaction(function () use ($boq, $validated, $itemsData) {
            if (! empty($validated['boq_number'])) {
                $boq->boq_number = $validated['boq_number'];
            }
            if (isset($validated['title'])) {
                $boq->title = $validated['title'];
            }
            if (isset($validated['prepared_by'])) {
                $boq->prepared_by = $validated['prepared_by'];
            }
            if (isset($validated['prepared_at'])) {
                $boq->prepared_at = Carbon::parse($validated['prepared_at'])->toDateString();
            }
            if (isset($validated['notes'])) {
                $boq->notes = $validated['notes'];
            }

            if (is_array($itemsData)) {
                // Remove existing lines (unique boq+scope_item allows clean rebuild)
                $boq->items()->delete();

                foreach ($itemsData as $idx => $item) {
                    $derived = $this->generationService->deriveLineForScopeItem($boq->scope_id, (int) $item['scope_item_id']);

                    $boq->items()->create([
                        'scope_item_id' => (int) $item['scope_item_id'],
                        'item_code' => $item['item_code'] ?? $derived['item_code'] ?? (string) ($idx + 1),
                        'description' => $item['description'] ?? $derived['description'],
                        'unit' => $derived['unit'],
                        'quantity' => $derived['quantity'],
                        'notes' => $item['notes'] ?? null,
                        'sort_order' => $item['sort_order'] ?? $idx,
                    ]);
                }
            }

            $boq->save();

            activity('commercial')
                ->event('updated')
                ->performedOn($boq)
                ->causedBy(auth()->user())
                ->log(__('activity.boq_updated', ['number' => $boq->boq_number]));

            return $boq;
        });

        return response()->json([
            'success' => true,
            'message' => __('messages.boq_updated_success'),
            'data' => $boq->fresh($this->with),
        ]);
    }

    public function destroy(int|string|Request $ids): JsonResponse
    {
        $this->authorizePermission(['boqs.delete', 'boq.delete']);

        $idList = is_array($ids) ? $ids : (is_numeric($ids) ? [(int) $ids] : explode(',', (string) $ids));

        $boqs = Boq::whereIn('id', $idList)->get();

        foreach ($boqs as $b) {
            if ($b->isApproved()) {
                throw ValidationException::withMessages([
                    'status' => [__('messages.approved_boq_cannot_be_deleted')],
                ]);
            }
        }

        foreach ($boqs as $b) {
            $number = $b->boq_number;
            $b->delete();

            activity('commercial')
                ->event('deleted')
                ->performedOn($b)
                ->causedBy(auth()->user())
                ->log(__('activity.boq_deleted', ['number' => $number]));
        }

        return response()->json([
            'success' => true,
            'message' => __('messages.boq_deleted_success'),
        ]);
    }

    /**
     * Submit BOQ for engineering/management review.
     */
    public function submitReview(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission(['boqs.update', 'boq.update']);

        $boq = Boq::findOrFail($id);

        if (! $boq->isDraft()) {
            throw ValidationException::withMessages([
                'status' => [__('messages.only_draft_boq_can_be_submitted_for_review')],
            ]);
        }

        $boq->status = 'Under Review';
        $boq->reviewed_by = auth()->id();
        $boq->reviewed_at = Carbon::now();
        $boq->save();

        activity('commercial')
            ->event('updated')
            ->performedOn($boq)
            ->causedBy(auth()->user())
            ->log(__('activity.boq_submitted_for_review', ['number' => $boq->boq_number]));

        return response()->json([
            'success' => true,
            'message' => __('messages.boq_submitted_review_success'),
            'data' => $boq->fresh($this->with),
        ]);
    }

    /**
     * Approve BOQ as the authoritative pricable quantity document.
     */
    public function approve(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission(['boqs.approve', 'boq.approve']);

        return DB::transaction(function () use ($id) {
            $boq = Boq::with('items')
                ->where('id', $id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $boq->canBeApproved()) {
                throw ValidationException::withMessages([
                    'status' => [__('messages.boq_cannot_be_approved_in_current_state')],
                ]);
            }

            // Source scope must still be Approved and locked
            $sourceScope = Scope::where('id', $boq->scope_id)->lockForUpdate()->first();
            if (! $sourceScope || ! $sourceScope->isApproved()) {
                throw ValidationException::withMessages([
                    'scope_id' => [__('messages.boq_requires_approved_scope')],
                ]);
            }

            if ($boq->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => [__('messages.cannot_approve_empty_boq')],
                ]);
            }

            // Supersede any previously approved BOQ for this opportunity (locked)
            $siblingApproved = Boq::where('opportunity_id', $boq->opportunity_id)
                ->where('id', '!=', $boq->id)
                ->where('status', 'Approved')
                ->lockForUpdate()
                ->get();

            foreach ($siblingApproved as $prevApproved) {
                $prevApproved->update(['status' => 'Superseded']);
            }

            $boq->status = 'Approved';
            $boq->approved_by = auth()->id();
            $boq->approved_at = Carbon::now();
            $boq->save();

            activity('commercial')
                ->event('updated')
                ->performedOn($boq)
                ->causedBy(auth()->user())
                ->log(__('activity.boq_approved', ['number' => $boq->boq_number]));

            return response()->json([
                'success' => true,
                'message' => __('messages.boq_approved_success'),
                'data' => $boq->fresh($this->with),
            ]);
        });
    }

    /**
     * Create a new draft revision from an approved BOQ (same frozen source scope).
     */
    public function createRevision(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission(['boqs.create', 'boq.create']);

        $parent = Boq::with('items')->findOrFail($id);

        // Strict Rule: revisions branch only from an Approved BOQ
        // (Draft / Under Review are still directly editable).
        if (! $parent->isApproved()) {
            throw ValidationException::withMessages([
                'status' => [__('messages.only_approved_boq_can_be_revised')],
            ]);
        }

        $revision = DB::transaction(function () use ($parent) {
            $newVersion = $parent->version + 1;
            $newNumber = $parent->nextRevisionNumber();

            $newBoq = Boq::create([
                'opportunity_id' => $parent->opportunity_id,
                'scope_id' => $parent->scope_id,
                'boq_number' => $newNumber,
                'version' => $newVersion,
                'status' => 'Draft',
                'title' => $parent->title,
                'prepared_by' => auth()->id(),
                'prepared_at' => Carbon::today()->toDateString(),
                'notes' => __('messages.boq_revision_note', [
                    'version' => $newVersion,
                    'number' => $parent->boq_number,
                ]),
                'created_by' => auth()->id(),
            ]);

            foreach ($parent->items as $item) {
                $newBoq->items()->create([
                    'scope_item_id' => $item->scope_item_id,
                    'item_code' => $item->item_code,
                    'description' => $item->description,
                    'unit' => $item->unit,
                    'quantity' => $item->quantity,
                    'notes' => $item->notes,
                    'sort_order' => $item->sort_order,
                ]);
            }

            activity('commercial')
                ->event('created')
                ->performedOn($newBoq)
                ->causedBy(auth()->user())
                ->log(__('messages.boq_revision_activity', [
                    'number' => $newBoq->boq_number,
                    'version' => $newVersion,
                ]));

            return $newBoq;
        });

        return response()->json([
            'success' => true,
            'message' => __('messages.boq_revision_created_success'),
            'data' => $revision->fresh($this->with),
        ], 200);
    }

    /**
     * Create BOQ directly originating from an approved scope.
     */
    public function createFromScope(Request $request, int|string $scopeId): JsonResponse
    {
        $this->authorizePermission(['boqs.create', 'boq.create']);

        $scope = Scope::with('opportunity')->findOrFail($scopeId);

        if (! $scope->isApproved()) {
            throw ValidationException::withMessages([
                'scope_id' => [__('messages.boq_requires_approved_scope')],
            ]);
        }

        $request->merge([
            'opportunity_id' => $scope->opportunity_id,
            'scope_id' => $scope->id,
        ]);

        return $this->store($request);
    }

    /**
     * Generate sequential BOQ code.
     */
    protected function generateBoqNumber(int $opportunityId): string
    {
        $latestNumber = Boq::where('opportunity_id', $opportunityId)
            ->where('boq_number', 'like', "BOQ-OPP{$opportunityId}-%")
            ->orderByRaw('LENGTH(boq_number) desc, boq_number desc')
            ->value('boq_number');

        if ($latestNumber && preg_match('/BOQ-OPP\d+-(\d+)/', $latestNumber, $matches)) {
            $seq = (int) $matches[1] + 1;
        } else {
            $seq = Boq::where('opportunity_id', $opportunityId)->count() + 1;
        }

        $padded = str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
        $candidate = "BOQ-OPP{$opportunityId}-{$padded}";

        while (Boq::where('boq_number', $candidate)->exists()) {
            $seq++;
            $padded = str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            $candidate = "BOQ-OPP{$opportunityId}-{$padded}";
        }

        return $candidate;
    }
}
