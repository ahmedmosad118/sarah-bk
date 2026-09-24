<?php

namespace App\Http\Controllers\Api;

use App\Core\CRUD\CRUDController;
use App\Core\CRUD\Field;
use App\Core\CRUD\InputMaker;
use App\Models\Measurement;
use App\Models\MeasurementItem;
use App\Models\Opportunity;
use App\Models\Scope;
use App\Models\ScopeItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScopeController extends CRUDController
{
    protected string $model = Scope::class;
    protected array $searchable = ['scope_number', 'title', 'general_inclusions', 'general_exclusions', 'notes'];
    protected array $with = [
        'opportunity.customer',
        'measurement',
        'preparedUser',
        'reviewedUser',
        'approvedUser',
        'creator',
        'items.measurementItems',
        'media'
    ];
    protected string $defaultSortBy = 'id';
    protected string $defaultSortOrder = 'desc';

    protected function inputMaker(): InputMaker
    {
        return InputMaker::make()
            ->title('scopes.title')
            ->singularTitle('scopes.singular')
            ->model(Scope::class)
            ->fields([
                Field::select('opportunity_id', 'scopes.opportunity', [])
                    ->required()
                    ->col(6),

                Field::select('measurement_id', 'scopes.measurement', [])
                    ->required()
                    ->col(6),

                Field::text('scope_number', 'scopes.scopeNumber')
                    ->col(6),

                Field::number('version', 'scopes.version')
                    ->default(1)
                    ->col(6),

                Field::select('status', 'scopes.status', [
                    ['value' => 'Draft', 'label' => 'scopes.statusDraft'],
                    ['value' => 'Under Review', 'label' => 'scopes.statusUnderReview'],
                    ['value' => 'Approved', 'label' => 'scopes.statusApproved'],
                    ['value' => 'Superseded', 'label' => 'scopes.statusSuperseded'],
                ])->default('Draft')->col(6),

                Field::text('title', 'scopes.scopeTitle')
                    ->col(6),

                Field::select('prepared_by', 'scopes.preparedBy', [])
                    ->col(6),

                Field::date('prepared_at', 'scopes.preparedAt')
                    ->col(6),

                Field::textarea('general_inclusions', 'scopes.generalInclusions')
                    ->col(12),

                Field::textarea('general_exclusions', 'scopes.generalExclusions')
                    ->col(12),

                Field::textarea('notes', 'scopes.notes')
                    ->col(12),
            ]);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission(['scopes.view', 'scope.view']);

        $query = $this->query();

        // 1. Search Query
        if ($search = $request->input('search')) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('scope_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhere('general_inclusions', 'like', "%{$search}%")
                    ->orWhere('general_exclusions', 'like', "%{$search}%")
                    ->orWhereHas('opportunity', function (Builder $oq) use ($search) {
                        $oq->where('title', 'like', "%{$search}%")
                            ->orWhereHas('customer', function (Builder $cq) use ($search) {
                                $cq->where('name', 'like', "%{$search}%")
                                    ->orWhere('company_name', 'like', "%{$search}%");
                            });
                    })
                    ->orWhereHas('measurement', function (Builder $mq) use ($search) {
                        $mq->where('measurement_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('items', function (Builder $iq) use ($search) {
                        $iq->where('item_name', 'like', "%{$search}%")
                            ->orWhere('trade_category', 'like', "%{$search}%")
                            ->orWhere('specification', 'like', "%{$search}%");
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

        if ($request->filled('measurement_id')) {
            $query->where('measurement_id', $request->measurement_id);
        }

        if ($request->filled('prepared_by')) {
            $query->where('prepared_by', $request->prepared_by);
        }

        // 3. Sorting
        $rawSortBy = (string) $request->input('sort_by', $this->defaultSortBy);
        $allowedSorts = ['id', 'scope_number', 'version', 'status', 'prepared_at', 'created_at'];
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
            'total' => Scope::count(),
            'draft' => Scope::where('status', 'Draft')->count(),
            'under_review' => Scope::where('status', 'Under Review')->count(),
            'approved' => Scope::where('status', 'Approved')->count(),
            'superseded' => Scope::where('status', 'Superseded')->count(),
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
        $this->authorizePermission(['scopes.view', 'scope.view']);
        return parent::show($id);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission(['scopes.create', 'scope.create']);

        $validated = $request->validate([
            'opportunity_id' => ['required', 'integer', 'exists:opportunities,id'],
            'measurement_id' => ['required', 'integer', 'exists:measurements,id'],
            'scope_number' => ['nullable', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:255'],
            'general_inclusions' => ['nullable', 'string', 'max:10000'],
            'general_exclusions' => ['nullable', 'string', 'max:10000'],
            'prepared_by' => ['nullable', 'integer', 'exists:users,id'],
            'prepared_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['nullable', 'array'],
            'items.*.trade_category' => ['required', 'string', 'max:100'],
            'items.*.item_name' => ['required', 'string', 'max:250'],
            'items.*.specification' => ['nullable', 'string', 'max:10000'],
            'items.*.inclusions' => ['nullable', 'string', 'max:5000'],
            'items.*.exclusions' => ['nullable', 'string', 'max:5000'],
            'items.*.notes' => ['nullable', 'string', 'max:2000'],
            'items.*.sort_order' => ['nullable', 'integer'],
            'items.*.measurement_item_ids' => ['nullable', 'array'],
            'items.*.measurement_item_ids.*' => ['integer', 'exists:measurement_items,id'],
        ]);

        $opportunity = Opportunity::findOrFail($validated['opportunity_id']);
        $measurement = Measurement::with('items')->findOrFail($validated['measurement_id']);

        // Strict Rule: Measurement MUST be Approved
        if (!$measurement->isApproved()) {
            throw ValidationException::withMessages([
                'measurement_id' => [__('messages.scope_requires_approved_measurement')],
            ]);
        }

        // Strict Rule: Measurement MUST belong to the specified Opportunity
        if ($measurement->opportunity_id !== $opportunity->id) {
            throw ValidationException::withMessages([
                'measurement_id' => [__('messages.measurement_opportunity_mismatch')],
            ]);
        }

        // Strict Rule: Validate all linked measurement_item_ids belong to this measurement
        $validMeasurementItemIds = $measurement->items->pluck('id')->all();
        $itemsData = $validated['items'] ?? [];

        foreach ($itemsData as $idx => $item) {
            $linkedIds = $item['measurement_item_ids'] ?? [];
            foreach ($linkedIds as $mItemId) {
                if (!in_array((int) $mItemId, $validMeasurementItemIds, true)) {
                    throw ValidationException::withMessages([
                        "items.{$idx}.measurement_item_ids" => [__('messages.scope_measurement_item_mismatch')],
                    ]);
                }
            }
        }

        $scope = DB::transaction(function () use ($validated, $opportunity, $measurement, $itemsData, $request) {
            $number = $validated['scope_number'] ?? $this->generateScopeNumber($opportunity->id);

            $scope = Scope::create([
                'opportunity_id' => $opportunity->id,
                'measurement_id' => $measurement->id,
                'scope_number' => $number,
                'version' => 1,
                'status' => 'Draft',
                'title' => $validated['title'] ?? "نطاق أعمال مشروع {$opportunity->title}",
                'general_inclusions' => $validated['general_inclusions'] ?? null,
                'general_exclusions' => $validated['general_exclusions'] ?? null,
                'prepared_by' => $validated['prepared_by'] ?? auth()->id(),
                'prepared_at' => $validated['prepared_at'] ?? Carbon::today()->toDateString(),
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($itemsData as $idx => $item) {
                $scopeItem = $scope->items()->create([
                    'trade_category' => trim((string) ($item['trade_category'] ?? 'عام')),
                    'item_name' => trim((string) ($item['item_name'] ?? '')),
                    'specification' => $item['specification'] ?? null,
                    'inclusions' => $item['inclusions'] ?? null,
                    'exclusions' => $item['exclusions'] ?? null,
                    'notes' => $item['notes'] ?? null,
                    'sort_order' => $item['sort_order'] ?? $idx,
                ]);

                if (!empty($item['measurement_item_ids'])) {
                    $scopeItem->measurementItems()->sync($item['measurement_item_ids']);
                }
            }

            activity('commercial')
                ->event('created')
                ->performedOn($scope)
                ->causedBy(auth()->user())
                ->log(__('activity.scope_created', ['number' => $scope->scope_number]));

            return $scope;
        });

        return response()->json([
            'success' => true,
            'message' => __('messages.scope_created_success'),
            'data' => $scope->fresh($this->with),
        ], 200);
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission(['scopes.update', 'scope.update']);

        $scope = Scope::with('items')->findOrFail($id);

        if (!$scope->canBeEdited()) {
            throw ValidationException::withMessages([
                'status' => [__('messages.approved_scope_cannot_be_edited')],
            ]);
        }

        $validated = $request->validate([
            'measurement_id' => ['nullable', 'integer', 'exists:measurements,id'],
            'scope_number' => ['nullable', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:255'],
            'general_inclusions' => ['nullable', 'string', 'max:10000'],
            'general_exclusions' => ['nullable', 'string', 'max:10000'],
            'prepared_by' => ['nullable', 'integer', 'exists:users,id'],
            'prepared_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['nullable', 'array'],
            'items.*.trade_category' => ['required', 'string', 'max:100'],
            'items.*.item_name' => ['required', 'string', 'max:250'],
            'items.*.specification' => ['nullable', 'string', 'max:10000'],
            'items.*.inclusions' => ['nullable', 'string', 'max:5000'],
            'items.*.exclusions' => ['nullable', 'string', 'max:5000'],
            'items.*.notes' => ['nullable', 'string', 'max:2000'],
            'items.*.sort_order' => ['nullable', 'integer'],
            'items.*.measurement_item_ids' => ['nullable', 'array'],
            'items.*.measurement_item_ids.*' => ['integer', 'exists:measurement_items,id'],
        ]);

        $measurementId = $validated['measurement_id'] ?? $scope->measurement_id;
        $measurement = Measurement::with('items')->findOrFail($measurementId);

        if (!$measurement->isApproved()) {
            throw ValidationException::withMessages([
                'measurement_id' => [__('messages.scope_requires_approved_measurement')],
            ]);
        }

        if ($measurement->opportunity_id !== $scope->opportunity_id) {
            throw ValidationException::withMessages([
                'measurement_id' => [__('messages.measurement_opportunity_mismatch')],
            ]);
        }

        $validMeasurementItemIds = $measurement->items->pluck('id')->all();
        $itemsData = $validated['items'] ?? [];

        foreach ($itemsData as $idx => $item) {
            $linkedIds = $item['measurement_item_ids'] ?? [];
            foreach ($linkedIds as $mItemId) {
                if (!in_array((int) $mItemId, $validMeasurementItemIds, true)) {
                    throw ValidationException::withMessages([
                        "items.{$idx}.measurement_item_ids" => [__('messages.scope_measurement_item_mismatch')],
                    ]);
                }
            }
        }

        $scope = DB::transaction(function () use ($scope, $validated, $measurementId, $itemsData, $request) {
            if (isset($validated['measurement_id'])) {
                $scope->measurement_id = $measurementId;
            }
            if (!empty($validated['scope_number'])) {
                $scope->scope_number = $validated['scope_number'];
            }
            if (isset($validated['title'])) {
                $scope->title = $validated['title'];
            }
            if (isset($validated['general_inclusions'])) {
                $scope->general_inclusions = $validated['general_inclusions'];
            }
            if (isset($validated['general_exclusions'])) {
                $scope->general_exclusions = $validated['general_exclusions'];
            }
            if (isset($validated['prepared_by'])) {
                $scope->prepared_by = $validated['prepared_by'];
            }
            if (isset($validated['prepared_at'])) {
                $scope->prepared_at = $validated['prepared_at'];
            }
            if (isset($validated['notes'])) {
                $scope->notes = $validated['notes'];
            }

            if (array_key_exists('items', $validated)) {
                // Remove existing items and pivot relations
                $scope->items()->delete();

                foreach ($itemsData as $idx => $item) {
                    $scopeItem = $scope->items()->create([
                        'trade_category' => trim((string) ($item['trade_category'] ?? 'عام')),
                        'item_name' => trim((string) ($item['item_name'] ?? '')),
                        'specification' => $item['specification'] ?? null,
                        'inclusions' => $item['inclusions'] ?? null,
                        'exclusions' => $item['exclusions'] ?? null,
                        'notes' => $item['notes'] ?? null,
                        'sort_order' => $item['sort_order'] ?? $idx,
                    ]);

                    if (!empty($item['measurement_item_ids'])) {
                        $scopeItem->measurementItems()->sync($item['measurement_item_ids']);
                    }
                }
            }

            $scope->save();

            activity('commercial')
                ->event('updated')
                ->performedOn($scope)
                ->causedBy(auth()->user())
                ->log(__('activity.scope_updated', ['number' => $scope->scope_number]));

            return $scope;
        });

        return response()->json([
            'success' => true,
            'message' => __('messages.scope_updated_success'),
            'data' => $scope->fresh($this->with),
        ]);
    }

    public function destroy(int|string|Request $ids): JsonResponse
    {
        $this->authorizePermission(['scopes.delete', 'scope.delete']);

        $idList = is_array($ids) ? $ids : (is_numeric($ids) ? [(int) $ids] : explode(',', (string) $ids));

        $scopes = Scope::whereIn('id', $idList)->get();

        foreach ($scopes as $s) {
            if ($s->isApproved()) {
                throw ValidationException::withMessages([
                    'status' => [__('messages.approved_scope_cannot_be_deleted')],
                ]);
            }
        }

        foreach ($scopes as $s) {
            $number = $s->scope_number;
            $s->delete();

            activity('commercial')
                ->event('deleted')
                ->performedOn($s)
                ->causedBy(auth()->user())
                ->log(__('activity.scope_deleted', ['number' => $number]));
        }

        return response()->json([
            'success' => true,
            'message' => __('messages.scope_deleted_success'),
        ]);
    }

    /**
     * Submit scope for engineering/management review.
     */
    public function submitReview(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission(['scopes.update', 'scope.update']);

        $scope = Scope::findOrFail($id);

        if (!$scope->isDraft()) {
            throw ValidationException::withMessages([
                'status' => [__('messages.only_draft_scope_can_be_submitted_for_review')],
            ]);
        }

        $scope->status = 'Under Review';
        $scope->reviewed_by = auth()->id();
        $scope->reviewed_at = Carbon::now();
        $scope->save();

        activity('commercial')
            ->event('updated')
            ->performedOn($scope)
            ->causedBy(auth()->user())
            ->log(__('activity.scope_submitted_for_review', ['number' => $scope->scope_number]));

        return response()->json([
            'success' => true,
            'message' => __('messages.scope_submitted_review_success'),
            'data' => $scope->fresh($this->with),
        ]);
    }

    /**
     * Approve scope as authoritative technical work package commitment.
     */
    public function approve(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission(['scopes.approve', 'scope.approve']);

        $scope = Scope::with('items')->findOrFail($id);

        if (!$scope->canBeApproved()) {
            throw ValidationException::withMessages([
                'status' => [__('messages.scope_cannot_be_approved_in_current_state')],
            ]);
        }

        if ($scope->items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => [__('messages.cannot_approve_empty_scope')],
            ]);
        }

        $scope = DB::transaction(function () use ($scope) {
            // Supersede any previously approved Scope for this opportunity
            Scope::where('opportunity_id', $scope->opportunity_id)
                ->where('id', '!=', $scope->id)
                ->where('status', 'Approved')
                ->update(['status' => 'Superseded']);

            $scope->status = 'Approved';
            $scope->approved_by = auth()->id();
            $scope->approved_at = Carbon::now();
            $scope->save();

            activity('commercial')
                ->event('updated')
                ->performedOn($scope)
                ->causedBy(auth()->user())
                ->log(__('activity.scope_approved', ['number' => $scope->scope_number]));

            return $scope;
        });

        return response()->json([
            'success' => true,
            'message' => __('messages.scope_approved_success'),
            'data' => $scope->fresh($this->with),
        ]);
    }

    /**
     * Create a new draft revision from an approved scope.
     */
    public function createRevision(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission(['scopes.create', 'scope.create']);

        $parent = Scope::with('items.measurementItems')->findOrFail($id);

        $revision = DB::transaction(function () use ($parent) {
            $newVersion = $parent->version + 1;
            $newNumber = $parent->nextRevisionNumber();

            $newScope = Scope::create([
                'opportunity_id' => $parent->opportunity_id,
                'measurement_id' => $parent->measurement_id,
                'scope_number' => $newNumber,
                'version' => $newVersion,
                'status' => 'Draft',
                'title' => $parent->title,
                'general_inclusions' => $parent->general_inclusions,
                'general_exclusions' => $parent->general_exclusions,
                'prepared_by' => auth()->id(),
                'prepared_at' => Carbon::today()->toDateString(),
                'notes' => "إصدار ومراجعة رقم ({$newVersion}) مستخرجة من نطاق الأعمال #{$parent->scope_number}.",
                'created_by' => auth()->id(),
            ]);

            foreach ($parent->items as $item) {
                $newScopeItem = $newScope->items()->create([
                    'trade_category' => $item->trade_category,
                    'item_name' => $item->item_name,
                    'specification' => $item->specification,
                    'inclusions' => $item->inclusions,
                    'exclusions' => $item->exclusions,
                    'notes' => $item->notes,
                    'sort_order' => $item->sort_order,
                ]);

                if ($item->measurementItems->isNotEmpty()) {
                    $newScopeItem->measurementItems()->sync($item->measurementItems->pluck('id')->all());
                }
            }

            activity('commercial')
                ->event('created')
                ->performedOn($newScope)
                ->causedBy(auth()->user())
                ->log("تم إنشاء مراجعة نطاق أعمال جديدة رقم #{$newScope->scope_number} (الإصدار {$newVersion}).");

            return $newScope;
        });

        return response()->json([
            'success' => true,
            'message' => __('messages.scope_revision_created_success'),
            'data' => $revision->fresh($this->with),
        ], 200);
    }

    /**
     * Create scope directly originating from an approved measurement.
     */
    public function createFromMeasurement(Request $request, int|string $measurementId): JsonResponse
    {
        $this->authorizePermission(['scopes.create', 'scope.create']);

        $measurement = Measurement::with('items', 'opportunity')->findOrFail($measurementId);

        if (!$measurement->isApproved()) {
            throw ValidationException::withMessages([
                'measurement_id' => [__('messages.scope_requires_approved_measurement')],
            ]);
        }

        $request->merge([
            'opportunity_id' => $measurement->opportunity_id,
            'measurement_id' => $measurement->id,
        ]);

        return $this->store($request);
    }

    /**
     * Generate sequential scope code.
     */
    protected function generateScopeNumber(int $opportunityId): string
    {
        $count = Scope::where('opportunity_id', $opportunityId)->count();
        $next = $count + 1;
        $padded = str_pad((string) $next, 4, '0', STR_PAD_LEFT);
        return "SC-OPP{$opportunityId}-{$padded}";
    }
}
