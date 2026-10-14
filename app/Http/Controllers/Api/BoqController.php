<?php

namespace App\Http\Controllers\Api;

use App\Core\CRUD\CRUDController;
use App\Core\CRUD\Field;
use App\Core\CRUD\InputMaker;
use App\Models\Boq;
use App\Models\Opportunity;
use App\Models\Scope;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BoqController extends CRUDController
{
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
        'media'
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
        if (!$scope) {
            return [];
        }

        $coveredIds = $boq->items()->pluck('scope_item_id')->map(fn ($v) => (int) $v)->all();

        return array_values(array_filter(
            $this->buildItemsFromScope($scope)['skipped'],
            fn (array $sk) => !in_array((int) $sk['scope_item_id'], $coveredIds, true)
        ));
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

        // Strict Rule: Scope MUST be Approved
        if (!$scope->isApproved()) {
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

        $generated = $this->buildItemsFromScope($scope);

        $boq = DB::transaction(function () use ($validated, $opportunity, $scope, $generated) {
            $number = $validated['boq_number'] ?? $this->generateBoqNumber($opportunity->id);

            $boq = Boq::create([
                'opportunity_id' => $opportunity->id,
                'scope_id' => $scope->id,
                'boq_number' => $number,
                'version' => 1,
                'status' => 'Draft',
                'title' => $validated['title'] ?? "جدول كميات {$opportunity->title}",
                'prepared_by' => $validated['prepared_by'] ?? auth()->id(),
                'prepared_at' => $validated['prepared_at'] ?? Carbon::today()->toDateString(),
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

        if (!$boq->canBeEdited()) {
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
                if (!in_array((int) $item['scope_item_id'], $validScopeItemIds, true)) {
                    throw ValidationException::withMessages([
                        "items.{$idx}.scope_item_id" => [__('messages.boq_scope_item_mismatch')],
                    ]);
                }
            }
        }

        $boq = DB::transaction(function () use ($boq, $validated, $itemsData) {
            if (!empty($validated['boq_number'])) {
                $boq->boq_number = $validated['boq_number'];
            }
            if (isset($validated['title'])) {
                $boq->title = $validated['title'];
            }
            if (isset($validated['prepared_by'])) {
                $boq->prepared_by = $validated['prepared_by'];
            }
            if (isset($validated['prepared_at'])) {
                $boq->prepared_at = $validated['prepared_at'];
            }
            if (isset($validated['notes'])) {
                $boq->notes = $validated['notes'];
            }

            if (is_array($itemsData)) {
                // Remove existing lines (unique boq+scope_item allows clean rebuild)
                $boq->items()->delete();

                foreach ($itemsData as $idx => $item) {
                    $derived = $this->deriveLineForScopeItem($boq->scope_id, (int) $item['scope_item_id']);

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

        if (!$boq->isDraft()) {
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

        $boq = Boq::with('items')->findOrFail($id);

        if (!$boq->canBeApproved()) {
            throw ValidationException::withMessages([
                'status' => [__('messages.boq_cannot_be_approved_in_current_state')],
            ]);
        }

        if ($boq->items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => [__('messages.cannot_approve_empty_boq')],
            ]);
        }

        $boq = DB::transaction(function () use ($boq) {
            // Supersede any previously approved BOQ for this opportunity
            Boq::where('opportunity_id', $boq->opportunity_id)
                ->where('id', '!=', $boq->id)
                ->where('status', 'Approved')
                ->update(['status' => 'Superseded']);

            $boq->status = 'Approved';
            $boq->approved_by = auth()->id();
            $boq->approved_at = Carbon::now();
            $boq->save();

            activity('commercial')
                ->event('updated')
                ->performedOn($boq)
                ->causedBy(auth()->user())
                ->log(__('activity.boq_approved', ['number' => $boq->boq_number]));

            return $boq;
        });

        return response()->json([
            'success' => true,
            'message' => __('messages.boq_approved_success'),
            'data' => $boq->fresh($this->with),
        ]);
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
        if (!$parent->isApproved()) {
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
                'notes' => "إصدار ومراجعة رقم ({$newVersion}) مستخرجة من جدول الكميات #{$parent->boq_number}.",
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
                ->log("تم إنشاء مراجعة جدول كميات جديدة رقم #{$newBoq->boq_number} (الإصدار {$newVersion}).");

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

        if (!$scope->isApproved()) {
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
        $count = Boq::where('opportunity_id', $opportunityId)->count();
        $next = $count + 1;
        $padded = str_pad((string) $next, 4, '0', STR_PAD_LEFT);
        return "BOQ-OPP{$opportunityId}-{$padded}";
    }

    /**
     * Build BOQ lines from an approved scope.
     *
     * Dual-authority derivation:
     * - description/specification inherited from the ScopeItem
     * - unit + quantity derived strictly from MeasurementItems linked to that
     *   ScopeItem via the existing scope_item_measurement_item pivot
     *   (quantity = SUM of net_quantity, only when units are homogeneous).
     *
     * Scope items with mixed units or with no linked measurement items are
     * NOT generated; they are reported in 'skipped' for explicit user action.
     *
     * @return array{lines: array, skipped: array}
     */
    protected function buildItemsFromScope(Scope $scope): array
    {
        $scope->loadMissing(['items.measurementItems']);

        $lines = [];
        $skipped = [];
        $groupSeq = [];
        $groupCounter = 0;
        $sortOrder = 0;

        foreach ($scope->items as $scopeItem) {
            // Defensive Lineage Guard: Only consume measurement items belonging to the scope's exact measurement revision
            $linked = $scopeItem->measurementItems->where('measurement_id', $scope->measurement_id);

            if ($linked->isEmpty()) {
                $skipped[] = [
                    'scope_item_id' => $scopeItem->id,
                    'item_name' => $scopeItem->item_name,
                    'trade_category' => $scopeItem->trade_category,
                    'reason' => 'no_linked_measurement_items',
                    'message' => __('messages.boq_scope_item_has_no_quantities', ['item' => $scopeItem->item_name]),
                ];
                continue;
            }

            $units = $linked->pluck('unit')->unique()->values()->all();

            if (count($units) !== 1) {
                $skipped[] = [
                    'scope_item_id' => $scopeItem->id,
                    'item_name' => $scopeItem->item_name,
                    'trade_category' => $scopeItem->trade_category,
                    'reason' => 'mixed_units',
                    'units' => $units,
                    'message' => __('messages.boq_scope_item_mixed_units', ['item' => $scopeItem->item_name]),
                ];
                continue;
            }

            $trade = (string) ($scopeItem->trade_category ?: 'عام');
            if (!isset($groupSeq[$trade])) {
                $groupCounter++;
                $groupSeq[$trade] = ['group' => $groupCounter, 'seq' => 0];
            }
            $groupSeq[$trade]['seq']++;

            $lines[] = [
                'scope_item_id' => $scopeItem->id,
                'item_code' => $groupSeq[$trade]['group'] . '.' . $groupSeq[$trade]['seq'],
                'description' => $this->composeDescription($scopeItem->item_name, $scopeItem->specification),
                'unit' => $units[0],
                'quantity' => round((float) $linked->sum('net_quantity'), 2),
                'notes' => $scopeItem->notes,
                'sort_order' => $sortOrder++,
            ];
        }

        return ['lines' => $lines, 'skipped' => $skipped];
    }

    /**
     * Re-derive unit + quantity for a single scope item (server-side truth).
     * Client-supplied quantity/unit values are never trusted.
     *
     * @throws \Illuminate\Validation\ValidationException When the scope item is foreign, unlinked, or mixed-unit.
     */
    protected function deriveLineForScopeItem(int $scopeId, int $scopeItemId): array
    {
        $scopeItem = \App\Models\ScopeItem::with('measurementItems')->findOrFail($scopeItemId);

        if ((int) $scopeItem->scope_id !== (int) $scopeId) {
            throw ValidationException::withMessages([
                'scope_item_id' => [__('messages.boq_scope_item_mismatch')],
            ]);
        }

        $scope = Scope::findOrFail($scopeId);
        // Defensive Lineage Guard: Only consume measurement items belonging to the scope's exact measurement revision
        $linked = $scopeItem->measurementItems->where('measurement_id', $scope->measurement_id);

        if ($linked->isEmpty()) {
            throw ValidationException::withMessages([
                'scope_item_id' => [__('messages.boq_scope_item_has_no_quantities', ['item' => $scopeItem->item_name])],
            ]);
        }

        $units = $linked->pluck('unit')->unique()->values()->all();

        if (count($units) !== 1) {
            throw ValidationException::withMessages([
                'scope_item_id' => [__('messages.boq_scope_item_mixed_units', ['item' => $scopeItem->item_name])],
            ]);
        }

        return [
            'description' => $this->composeDescription($scopeItem->item_name, $scopeItem->specification),
            'unit' => $units[0],
            'quantity' => round((float) $linked->sum('net_quantity'), 2),
        ];
    }

    /**
     * Compose the final BOQ line description from scope work title + specification.
     */
    protected function composeDescription(?string $itemName, ?string $specification): string
    {
        $name = trim((string) ($itemName ?? ''));
        $spec = trim((string) ($specification ?? ''));

        if ($name !== '' && $spec !== '') {
            return $name . "\n" . $spec;
        }

        return $name !== '' ? $name : $spec;
    }
}
