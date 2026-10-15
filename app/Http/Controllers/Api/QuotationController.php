<?php

namespace App\Http\Controllers\Api;

use App\Core\CRUD\CRUDController;
use App\Core\CRUD\Field;
use App\Core\CRUD\InputMaker;
use App\Models\Boq;
use App\Models\Estimation;
use App\Models\EstimationItem;
use App\Models\Opportunity;
use App\Models\Quotation;
use App\Models\Setting;
use App\Services\QuotationCalculationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuotationController extends CRUDController
{
    protected string $model = Quotation::class;

    protected array $with = [
        'opportunity.customer',
        'estimation:id,opportunity_id,estimation_number,version,status,title',
        'items',
        'preparedUser:id,name',
        'reviewedUser:id,name',
        'approvedUser:id,name',
        'sentUser:id,name',
        'creator:id,name',
    ];

    protected string $defaultSortBy = 'id';

    protected string $defaultSortOrder = 'desc';

    public function __construct(
        protected QuotationCalculationService $calculationService
    ) {}

    protected function inputMaker(): InputMaker
    {
        return InputMaker::make()
            ->fields([
                Field::text('title', 'quotation.title')
                    ->col(6),

                Field::text('quotation_number', 'quotation.number')
                    ->col(6),

                Field::number('markup_rate', 'quotation.markupRate')
                    ->col(4),

                Field::select('discount_type', 'quotation.discountType', [
                    ['value' => 'percentage', 'label' => 'نسبة مئوية (%)'],
                    ['value' => 'fixed', 'label' => 'مبلغ مقطوع'],
                ])->col(4),

                Field::number('discount_rate', 'quotation.discountRate')
                    ->col(4),

                Field::number('discount_amount', 'quotation.discountAmount')
                    ->col(4),

                Field::number('tax_rate', 'quotation.taxRate')
                    ->col(4),

                Field::date('valid_until', 'quotation.validUntil')
                    ->col(4),

                Field::textarea('payment_terms_text', 'quotation.paymentTerms')
                    ->col(12),

                Field::textarea('notes', 'quotation.notes')
                    ->col(12),

                Field::textarea('client_notes', 'quotation.clientNotes')
                    ->col(12),
            ]);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission('quotations.view');

        $query = $this->query();

        // 1. Search Query
        if ($search = $request->input('search')) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('quotation_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('payment_terms_text', 'like', "%{$search}%")
                    ->orWhereHas('opportunity', function (Builder $oq) use ($search) {
                        $oq->where('title', 'like', "%{$search}%")
                            ->orWhereHas('customer', function (Builder $cq) use ($search) {
                                $cq->where('name', 'like', "%{$search}%")
                                    ->orWhere('company_name', 'like', "%{$search}%");
                            });
                    })
                    ->orWhereHas('estimation', function (Builder $eq) use ($search) {
                        $eq->where('estimation_number', 'like', "%{$search}%");
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

        if ($request->filled('client_status')) {
            $query->where('client_status', $request->client_status);
        }

        if ($request->filled('opportunity_id')) {
            $query->where('opportunity_id', $request->opportunity_id);
        }

        if ($request->filled('estimation_id')) {
            $query->where('estimation_id', $request->estimation_id);
        }

        // 3. Sorting
        $rawSortBy = (string) $request->input('sort_by', $this->defaultSortBy);
        $allowedSorts = [
            'id', 'quotation_number', 'version', 'status', 'client_status',
            'subtotal', 'grand_total', 'valid_until', 'created_at',
        ];
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
            'total' => Quotation::count(),
            'draft' => Quotation::where('status', 'Draft')->count(),
            'under_review' => Quotation::where('status', 'Under Review')->count(),
            'approved' => Quotation::where('status', 'Approved')->count(),
            'superseded' => Quotation::where('status', 'Superseded')->count(),
            'sent' => Quotation::where('client_status', 'Sent')->count(),
            'accepted' => Quotation::where('client_status', 'Accepted')->count(),
            'rejected' => Quotation::where('client_status', 'Rejected')->count(),
            'total_quoted_value' => (float) Quotation::where('status', 'Approved')->sum('grand_total'),
        ];

        // Ensure zero internal cost leakage
        $sanitizedItems = $this->calculationService->stripConfidentialCostData($paginated->items());

        return response()->json([
            'success' => true,
            'data' => $sanitizedItems,
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
        $this->authorizePermission('quotations.view');

        $quotation = $this->query()->findOrFail($id);

        // Check if referenced source Estimation is stale (newer approved estimation exists)
        $latestApprovedEst = Estimation::where('opportunity_id', $quotation->opportunity_id)
            ->where('status', 'Approved')
            ->orderBy('version', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $isStaleSource = (bool) ($latestApprovedEst && $latestApprovedEst->id !== $quotation->estimation_id);

        $sanitizedData = $this->calculationService->stripConfidentialCostData($quotation);

        return response()->json([
            'success' => true,
            'data' => $sanitizedData,
            'is_stale_source' => $isStaleSource,
            'latest_approved_estimation' => $latestApprovedEst ? [
                'id' => $latestApprovedEst->id,
                'estimation_number' => $latestApprovedEst->estimation_number,
                'version' => $latestApprovedEst->version,
            ] : null,
            'schema' => $this->inputMaker()->toSchema(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission('quotations.create');

        // Guardrails: status, version, client_status are strictly server-controlled
        $validated = $request->validate([
            'opportunity_id' => ['required', 'integer', 'exists:opportunities,id'],
            'estimation_id' => ['required', 'integer', 'exists:estimations,id'],
            'quotation_number' => ['nullable', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:255'],
            'markup_rate' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'discount_type' => ['nullable', 'string', 'in:percentage,fixed'],
            'discount_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'valid_until' => ['nullable', 'date'],
            'payment_terms_text' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'client_notes' => ['nullable', 'string', 'max:5000'],
            'prepared_by' => ['nullable', 'integer', 'exists:users,id'],
            'prepared_at' => ['nullable', 'date'],
            'items' => ['nullable', 'array'],
            'items.*.estimation_item_id' => ['required', 'integer', 'exists:estimation_items,id'],
            'items.*.markup_rate' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'items.*.notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $opportunity = Opportunity::findOrFail($validated['opportunity_id']);
        $estimation = Estimation::with('items')->findOrFail($validated['estimation_id']);

        // Rule 1: Estimation MUST be Approved
        if (! $estimation->isApproved()) {
            throw ValidationException::withMessages([
                'estimation_id' => [__('messages.quotation_requires_approved_estimation')],
            ]);
        }

        // Rule 2: Estimation MUST belong to the specified Opportunity
        if ((int) $estimation->opportunity_id !== (int) $opportunity->id) {
            throw ValidationException::withMessages([
                'estimation_id' => [__('messages.estimation_opportunity_mismatch')],
            ]);
        }

        // Rule 2.1: Check if Estimation is stale (superseded by a newer approved Estimation)
        $hasNewerApprovedEst = Estimation::where('opportunity_id', $opportunity->id)
            ->where('status', 'Approved')
            ->where('id', '!=', $estimation->id)
            ->where(function ($q) use ($estimation) {
                $q->where('version', '>', $estimation->version)
                    ->orWhere('id', '>', $estimation->id);
            })
            ->exists();

        if ($hasNewerApprovedEst) {
            throw ValidationException::withMessages([
                'estimation_id' => [__('messages.cannot_quote_stale_estimation')],
            ]);
        }

        // Rule 2.2: Check if Estimation's source BOQ has been superseded by a newer approved BOQ
        $latestApprovedBoq = Boq::where('opportunity_id', $opportunity->id)
            ->where('status', 'Approved')
            ->orderBy('version', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if ($latestApprovedBoq && (int) $latestApprovedBoq->id !== (int) $estimation->boq_id) {
            throw ValidationException::withMessages([
                'estimation_id' => [__('messages.cannot_quote_stale_estimation_boq')],
            ]);
        }

        // Rule 3: Estimation must have items
        if ($estimation->items->isEmpty()) {
            throw ValidationException::withMessages([
                'estimation_id' => [__('messages.cannot_quote_empty_estimation')],
            ]);
        }

        // Rule 4: Cross-estimation integrity check for item overrides
        $validEstimationItemIds = $estimation->items->pluck('id')->all();
        if (! empty($validated['items'])) {
            foreach ($validated['items'] as $index => $itemInput) {
                if (! in_array((int) $itemInput['estimation_item_id'], $validEstimationItemIds, true)) {
                    throw ValidationException::withMessages([
                        "items.{$index}.estimation_item_id" => [__('messages.estimation_item_mismatch')],
                    ]);
                }
            }
        }

        $quotation = DB::transaction(function () use ($validated, $opportunity, $estimation) {
            $number = $validated['quotation_number'] ?? $this->generateQuotationNumber($opportunity->id);
            $markupRate = isset($validated['markup_rate']) ? (float) $validated['markup_rate'] : 20.00;
            $discountType = $validated['discount_type'] ?? 'percentage';
            $discountRate = isset($validated['discount_rate']) ? (float) $validated['discount_rate'] : 0.00;
            $discountAmount = isset($validated['discount_amount']) ? (float) $validated['discount_amount'] : 0.00;

            if (isset($validated['discount_value'])) {
                if ($discountType === 'percentage' && ! isset($validated['discount_rate'])) {
                    $discountRate = (float) $validated['discount_value'];
                } elseif ($discountType === 'fixed' && ! isset($validated['discount_amount'])) {
                    $discountAmount = (float) $validated['discount_value'];
                }
            }

            $defaultTaxRate = (float) Setting::get('vat_rate', 14.00);
            $taxRate = isset($validated['tax_rate']) ? (float) $validated['tax_rate'] : $defaultTaxRate;

            $quotation = Quotation::create([
                'opportunity_id' => $opportunity->id,
                'estimation_id' => $estimation->id,
                'quotation_number' => $number,
                'version' => 1,
                'status' => 'Draft',
                'client_status' => 'Not Sent',
                'title' => $validated['title'] ?? "عرض سعر — {$opportunity->title}",
                'markup_rate' => $markupRate,
                'discount_type' => $discountType,
                'discount_rate' => $discountRate,
                'discount_amount' => $discountAmount,
                'tax_rate' => $taxRate,
                'valid_until' => $validated['valid_until'] ?? Carbon::today()->addDays(30)->toDateString(),
                'payment_terms_text' => $validated['payment_terms_text'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'client_notes' => $validated['client_notes'] ?? null,
                'prepared_by' => $validated['prepared_by'] ?? auth()->id(),
                'prepared_at' => $validated['prepared_at'] ?? Carbon::today()->toDateString(),
                'created_by' => auth()->id(),
            ]);

            // Index optional item-level customizations by estimation_item_id
            $initialItemsData = collect($validated['items'] ?? [])->keyBy('estimation_item_id');

            // Generate Quotation lines for ALL items in the approved Estimation
            foreach ($estimation->items as $estItem) {
                $itemInput = $initialItemsData->get($estItem->id);
                $itemMarkup = isset($itemInput['markup_rate']) && $itemInput['markup_rate'] !== null && $itemInput['markup_rate'] !== ''
                    ? (float) $itemInput['markup_rate']
                    : null;

                $quotation->items()->create([
                    'estimation_item_id' => $estItem->id,
                    'item_code' => $estItem->item_code,
                    'description' => $estItem->description,
                    'unit' => $estItem->unit,
                    'quantity' => $estItem->quantity,
                    'markup_rate' => $itemMarkup,
                    'notes' => $itemInput['notes'] ?? null,
                    'sort_order' => $estItem->sort_order,
                ]);
            }

            // Recalculate document financial totals
            $this->calculationService->recalculateQuotationTotals(
                $quotation,
                $markupRate,
                $discountType,
                $discountRate,
                $discountAmount,
                $taxRate
            );

            activity('commercial')
                ->event('created')
                ->performedOn($quotation)
                ->causedBy(auth()->user())
                ->log(__('activity.quotation_created', ['number' => $quotation->quotation_number]));

            return $quotation;
        });

        $sanitized = $this->calculationService->stripConfidentialCostData($quotation->fresh($this->with));

        return response()->json([
            'success' => true,
            'message' => __('messages.quotation_created_success'),
            'data' => $sanitized,
        ], 200);
    }

    /**
     * Create Quotation directly originating from an approved Estimation.
     */
    public function createFromEstimation(Request $request, int|string $estimationId): JsonResponse
    {
        $this->authorizePermission('quotations.create');

        $estimation = Estimation::with('opportunity')->findOrFail($estimationId);

        if (! $estimation->isApproved()) {
            throw ValidationException::withMessages([
                'estimation_id' => [__('messages.quotation_requires_approved_estimation')],
            ]);
        }

        $request->merge([
            'opportunity_id' => $estimation->opportunity_id,
            'estimation_id' => $estimation->id,
        ]);

        return $this->store($request);
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission('quotations.update');

        $quotation = Quotation::with('items')->findOrFail($id);

        // Immutability Guard: Cannot update Approved or Superseded Quotations directly
        if (! $quotation->canBeEdited()) {
            throw ValidationException::withMessages([
                'status' => [__('messages.cannot_edit_approved_quotation')],
            ]);
        }

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'markup_rate' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'discount_type' => ['nullable', 'string', 'in:percentage,fixed'],
            'discount_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'valid_until' => ['nullable', 'date'],
            'payment_terms_text' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'client_notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['nullable', 'array'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.estimation_item_id' => ['nullable', 'integer'],
            'items.*.markup_rate' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'items.*.notes' => ['nullable', 'string', 'max:2000'],
        ]);

        // Cross-estimation integrity check for item updates
        if (! empty($validated['items'])) {
            $validEstimationItemIds = EstimationItem::where('estimation_id', $quotation->estimation_id)->pluck('id')->all();
            foreach ($validated['items'] as $index => $itemInput) {
                if (isset($itemInput['estimation_item_id']) && ! in_array((int) $itemInput['estimation_item_id'], $validEstimationItemIds, true)) {
                    throw ValidationException::withMessages([
                        "items.{$index}.estimation_item_id" => [__('messages.estimation_item_mismatch')],
                    ]);
                }
            }
        }

        $quotation = DB::transaction(function () use ($validated, $quotation) {
            $quotation->fill([
                'title' => $validated['title'] ?? $quotation->title,
                'valid_until' => $validated['valid_until'] ?? $quotation->valid_until,
                'payment_terms_text' => array_key_exists('payment_terms_text', $validated) ? $validated['payment_terms_text'] : $quotation->payment_terms_text,
                'notes' => array_key_exists('notes', $validated) ? $validated['notes'] : $quotation->notes,
                'client_notes' => array_key_exists('client_notes', $validated) ? $validated['client_notes'] : $quotation->client_notes,
            ]);

            // Update item-level markup overrides if supplied
            if (! empty($validated['items'])) {
                $itemsById = $quotation->items->keyBy('id');
                $itemsByEstItemId = $quotation->items->keyBy('estimation_item_id');

                foreach ($validated['items'] as $itemInput) {
                    $item = null;
                    if (! empty($itemInput['id'])) {
                        $item = $itemsById->get($itemInput['id']);
                    } elseif (! empty($itemInput['estimation_item_id'])) {
                        $item = $itemsByEstItemId->get($itemInput['estimation_item_id']);
                    }

                    if ($item) {
                        $itemMarkup = isset($itemInput['markup_rate']) && $itemInput['markup_rate'] !== null && $itemInput['markup_rate'] !== ''
                            ? (float) $itemInput['markup_rate']
                            : null;

                        $item->markup_rate = $itemMarkup;
                        if (array_key_exists('notes', $itemInput)) {
                            $item->notes = $itemInput['notes'];
                        }
                        $item->save();
                    }
                }
            }

            // Recalculate totals
            $markupRate = isset($validated['markup_rate']) ? (float) $validated['markup_rate'] : (float) $quotation->markup_rate;
            $discountType = $validated['discount_type'] ?? $quotation->discount_type;
            $discountRate = isset($validated['discount_rate']) ? (float) $validated['discount_rate'] : (float) $quotation->discount_rate;
            $discountAmount = isset($validated['discount_amount']) ? (float) $validated['discount_amount'] : (float) $quotation->discount_amount;

            if (isset($validated['discount_value'])) {
                if ($discountType === 'percentage' && ! isset($validated['discount_rate'])) {
                    $discountRate = (float) $validated['discount_value'];
                } elseif ($discountType === 'fixed' && ! isset($validated['discount_amount'])) {
                    $discountAmount = (float) $validated['discount_value'];
                }
            }

            $taxRate = isset($validated['tax_rate']) ? (float) $validated['tax_rate'] : (float) $quotation->tax_rate;

            $this->calculationService->recalculateQuotationTotals(
                $quotation,
                $markupRate,
                $discountType,
                $discountRate,
                $discountAmount,
                $taxRate
            );

            activity('commercial')
                ->event('updated')
                ->performedOn($quotation)
                ->causedBy(auth()->user())
                ->log(__('activity.quotation_updated', ['number' => $quotation->quotation_number]));

            return $quotation;
        });

        $sanitized = $this->calculationService->stripConfidentialCostData($quotation->fresh($this->with));

        return response()->json([
            'success' => true,
            'message' => __('messages.quotation_updated_success'),
            'data' => $sanitized,
        ]);
    }

    public function destroy(int|string|Request $ids): JsonResponse
    {
        $this->authorizePermission('quotations.delete');

        $idList = is_array($ids) ? $ids : (is_numeric($ids) ? [(int) $ids] : explode(',', (string) $ids));

        $quotations = Quotation::whereIn('id', $idList)->get();

        if ($quotations->isEmpty()) {
            abort(404, __('messages.quotation_not_found'));
        }

        foreach ($quotations as $quotation) {
            if (! $quotation->canBeEdited()) {
                throw ValidationException::withMessages([
                    'status' => [__('messages.cannot_delete_approved_quotation')],
                ]);
            }
        }

        foreach ($quotations as $quotation) {
            $number = $quotation->quotation_number;
            $quotation->delete();

            activity('commercial')
                ->event('deleted')
                ->performedOn($quotation)
                ->causedBy(auth()->user())
                ->log(__('activity.quotation_deleted', ['number' => $number]));
        }

        return response()->json([
            'success' => true,
            'message' => __('messages.quotation_deleted_success'),
        ]);
    }

    public function submitReview(int|string $id): JsonResponse
    {
        $this->authorizePermission('quotations.update');

        $quotation = Quotation::findOrFail($id);

        if (! $quotation->isDraft()) {
            throw ValidationException::withMessages([
                'status' => [__('messages.quotation_only_draft_can_submit_review')],
            ]);
        }

        $quotation->status = 'Under Review';
        $quotation->reviewed_by = auth()->id();
        $quotation->reviewed_at = now();
        $quotation->save();

        activity('commercial')
            ->event('submitted_for_review')
            ->performedOn($quotation)
            ->causedBy(auth()->user())
            ->log(__('activity.quotation_submitted_for_review', ['number' => $quotation->quotation_number]));

        return response()->json([
            'success' => true,
            'message' => __('messages.quotation_submitted_for_review_success'),
            'data' => $this->calculationService->stripConfidentialCostData($quotation->fresh($this->with)),
        ]);
    }

    public function approve(int|string $id): JsonResponse
    {
        $this->authorizePermission('quotations.approve');

        $quotation = Quotation::with(['estimation.boq'])->findOrFail($id);

        if (! $quotation->canBeApproved()) {
            throw ValidationException::withMessages([
                'status' => [__('messages.quotation_already_approved_or_invalid')],
            ]);
        }

        // 1. Source Estimation must still be Approved
        if (! $quotation->estimation || ! $quotation->estimation->isApproved()) {
            throw ValidationException::withMessages([
                'estimation_id' => [__('messages.cannot_approve_stale_source_estimation')],
            ]);
        }

        // 2. Source Estimation must not be superseded by a newer approved Estimation
        $latestApprovedEst = Estimation::where('opportunity_id', $quotation->opportunity_id)
            ->where('status', 'Approved')
            ->orderBy('version', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if ($latestApprovedEst && (int) $latestApprovedEst->id !== (int) $quotation->estimation_id) {
            throw ValidationException::withMessages([
                'estimation_id' => [__('messages.cannot_approve_stale_source_estimation')],
            ]);
        }

        // 3. Source Estimation's BOQ must not be superseded by a newer approved BOQ
        $latestApprovedBoq = Boq::where('opportunity_id', $quotation->opportunity_id)
            ->where('status', 'Approved')
            ->orderBy('version', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if ($latestApprovedBoq && (int) $latestApprovedBoq->id !== (int) $quotation->estimation->boq_id) {
            throw ValidationException::withMessages([
                'estimation_id' => [__('messages.cannot_approve_stale_source_estimation')],
            ]);
        }

        // 4. Cannot approve an older quotation revision if a newer approved quotation exists
        $hasNewerApprovedQuot = Quotation::where('opportunity_id', $quotation->opportunity_id)
            ->where('status', 'Approved')
            ->where('version', '>', $quotation->version)
            ->exists();

        if ($hasNewerApprovedQuot) {
            throw ValidationException::withMessages([
                'version' => [__('messages.cannot_approve_superseded_quotation')],
            ]);
        }

        DB::transaction(function () use ($quotation) {
            // Supersede any previously approved quotation for the same Opportunity
            Quotation::where('opportunity_id', $quotation->opportunity_id)
                ->where('id', '!=', $quotation->id)
                ->where('status', 'Approved')
                ->update(['status' => 'Superseded']);

            $quotation->status = 'Approved';
            $quotation->approved_by = auth()->id();
            $quotation->approved_at = now();
            $quotation->save();

            activity('commercial')
                ->event('approved')
                ->performedOn($quotation)
                ->causedBy(auth()->user())
                ->log(__('activity.quotation_approved', ['number' => $quotation->quotation_number]));
        });

        return response()->json([
            'success' => true,
            'message' => __('messages.quotation_approved_success'),
            'data' => $this->calculationService->stripConfidentialCostData($quotation->fresh($this->with)),
        ]);
    }

    public function createRevision(int|string $id): JsonResponse
    {
        $this->authorizePermission('quotations.create');

        $quotation = Quotation::with('items')->findOrFail($id);

        if (! $quotation->isApproved()) {
            throw ValidationException::withMessages([
                'status' => [__('messages.quotation_revision_only_from_approved')],
            ]);
        }

        $revision = DB::transaction(function () use ($quotation) {
            $nextVersion = ((int) $quotation->version) + 1;
            $nextNumber = $quotation->nextRevisionNumber();

            $newQuotation = Quotation::create([
                'opportunity_id' => $quotation->opportunity_id,
                'estimation_id' => $quotation->estimation_id,
                'quotation_number' => $nextNumber,
                'version' => $nextVersion,
                'status' => 'Draft',
                'client_status' => 'Not Sent',
                'title' => $quotation->title,
                'markup_rate' => $quotation->markup_rate,
                'discount_type' => $quotation->discount_type,
                'discount_rate' => $quotation->discount_rate,
                'discount_amount' => $quotation->discount_amount,
                'discount_value' => $quotation->discount_value,
                'tax_rate' => $quotation->tax_rate,
                'subtotal' => $quotation->subtotal,
                'taxable_amount' => $quotation->taxable_amount,
                'tax_amount' => $quotation->tax_amount,
                'grand_total' => $quotation->grand_total,
                'valid_until' => Carbon::today()->addDays(30)->toDateString(),
                'payment_terms_text' => $quotation->payment_terms_text,
                'notes' => $quotation->notes,
                'client_notes' => $quotation->client_notes,
                'prepared_by' => auth()->id(),
                'prepared_at' => Carbon::today()->toDateString(),
                'created_by' => auth()->id(),
            ]);

            // Copy all quotation items
            foreach ($quotation->items as $item) {
                $newQuotation->items()->create([
                    'estimation_item_id' => $item->estimation_item_id,
                    'item_code' => $item->item_code,
                    'description' => $item->description,
                    'unit' => $item->unit,
                    'quantity' => $item->quantity,
                    'markup_rate' => $item->markup_rate,
                    'unit_selling_price' => $item->unit_selling_price,
                    'line_total' => $item->line_total,
                    'notes' => $item->notes,
                    'sort_order' => $item->sort_order,
                ]);
            }

            // Recalculate totals
            $this->calculationService->recalculateQuotationTotals($newQuotation);

            activity('commercial')
                ->event('revision_created')
                ->performedOn($newQuotation)
                ->causedBy(auth()->user())
                ->log(__('activity.quotation_revision_created', [
                    'number' => $newQuotation->quotation_number,
                    'from' => $quotation->quotation_number,
                ]));

            return $newQuotation;
        });

        return response()->json([
            'success' => true,
            'message' => __('messages.quotation_revision_created_success'),
            'data' => $this->calculationService->stripConfidentialCostData($revision->fresh($this->with)),
        ], 200);
    }

    /**
     * Officially record that the offer was sent/dispatched to the client.
     * Requires the Quotation to be internally Approved!
     */
    public function markSent(int|string $id): JsonResponse
    {
        $this->authorizePermission('quotations.send');

        $quotation = Quotation::findOrFail($id);

        if (! $quotation->isApproved()) {
            throw ValidationException::withMessages([
                'status' => [__('messages.cannot_send_unapproved_quotation')],
            ]);
        }

        $quotation->client_status = 'Sent';
        $quotation->sent_by = auth()->id();
        $quotation->sent_at = now();
        $quotation->save();

        activity('commercial')
            ->event('sent')
            ->performedOn($quotation)
            ->causedBy(auth()->user())
            ->log(__('activity.quotation_sent_to_client', ['number' => $quotation->quotation_number]));

        return response()->json([
            'success' => true,
            'message' => __('messages.quotation_marked_sent_success'),
            'data' => $this->calculationService->stripConfidentialCostData($quotation->fresh($this->with)),
        ]);
    }

    /**
     * Record client response (Accepted / Rejected) along with feedback notes.
     * Requires the Quotation to have been sent first.
     */
    public function recordResponse(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission('quotations.update');

        $decision = $request->input('response') ?? $request->input('client_status');
        $notes = $request->input('notes') ?? $request->input('client_response_notes');

        if (! in_array($decision, ['Accepted', 'Rejected'], true)) {
            throw ValidationException::withMessages([
                'response' => ['The response field must be Accepted or Rejected.'],
            ]);
        }

        $quotation = Quotation::findOrFail($id);

        if (! $quotation->isSent()) {
            throw ValidationException::withMessages([
                'client_status' => [__('messages.cannot_record_response_for_unsent_quotation')],
            ]);
        }

        $quotation->client_status = $decision;
        $quotation->responded_at = now();
        $quotation->client_response_notes = $notes;
        $quotation->save();

        activity('commercial')
            ->event('client_response')
            ->performedOn($quotation)
            ->causedBy(auth()->user())
            ->log(__('activity.quotation_client_responded', [
                'number' => $quotation->quotation_number,
                'response' => $quotation->client_status,
            ]));

        return response()->json([
            'success' => true,
            'message' => __('messages.quotation_response_recorded_success'),
            'data' => $this->calculationService->stripConfidentialCostData($quotation->fresh($this->with)),
        ]);
    }

    protected function generateQuotationNumber(int $opportunityId): string
    {
        $existingCount = Quotation::where('opportunity_id', $opportunityId)->count();
        $seq = $existingCount + 1;
        $candidate = "QT-OPP{$opportunityId}-".str_pad((string) $seq, 3, '0', STR_PAD_LEFT);

        while (Quotation::where('quotation_number', $candidate)->exists()) {
            $seq++;
            $candidate = "QT-OPP{$opportunityId}-".str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
        }

        return $candidate;
    }
}
