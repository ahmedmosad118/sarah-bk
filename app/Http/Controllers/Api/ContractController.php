<?php

namespace App\Http\Controllers\Api;

use App\Core\CRUD\CRUDController;
use App\Core\CRUD\Field;
use App\Core\CRUD\InputMaker;
use App\Models\Contract;
use App\Models\ContractPaymentMilestone;
use App\Models\Opportunity;
use App\Models\Quotation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContractController extends CRUDController
{
    protected string $model = Contract::class;

    protected array $with = [
        'opportunity.customer',
        'quotation:id,opportunity_id,quotation_number,version,status,client_status,grand_total,title',
        'milestones',
        'preparedUser:id,name',
        'reviewedUser:id,name',
        'approvedUser:id,name',
        'signedUser:id,name',
        'creator:id,name',
        'parentContract:id,contract_number,version',
    ];

    protected string $defaultSortBy = 'id';

    protected string $defaultSortOrder = 'desc';

    protected function inputMaker(): InputMaker
    {
        return InputMaker::make()
            ->fields([
                Field::text('contract_number', 'contract.number')
                    ->col(6),

                Field::number('contract_value', 'contract.value')
                    ->col(6),

                Field::date('start_date', 'contract.startDate')
                    ->col(6),

                Field::date('expected_completion_date', 'contract.expectedCompletionDate')
                    ->col(6),

                Field::textarea('value_adjustment_reason', 'contract.valueAdjustmentReason')
                    ->col(12),

                Field::textarea('terms_and_conditions', 'contract.termsAndConditions')
                    ->col(12),

                Field::textarea('notes', 'contract.notes')
                    ->col(12),
            ]);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission('contracts.view');

        $query = $this->query();

        // 1. Search Query
        if ($search = $request->input('search')) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('contract_number', 'like', "%{$search}%")
                    ->orWhere('terms_and_conditions', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('opportunity', function (Builder $oq) use ($search) {
                        $oq->where('title', 'like', "%{$search}%")
                            ->orWhereHas('customer', function (Builder $cq) use ($search) {
                                $cq->where('name', 'like', "%{$search}%")
                                    ->orWhere('company_name', 'like', "%{$search}%");
                            });
                    })
                    ->orWhereHas('quotation', function (Builder $qq) use ($search) {
                        $qq->where('quotation_number', 'like', "%{$search}%")
                            ->orWhere('title', 'like', "%{$search}%");
                    });
            });
        }

        // 2. Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('signature_status')) {
            $query->where('signature_status', $request->signature_status);
        }

        if ($request->filled('opportunity_id')) {
            $query->where('opportunity_id', $request->opportunity_id);
        }

        if ($request->filled('quotation_id')) {
            $query->where('quotation_id', $request->quotation_id);
        }

        // 3. Sorting
        $rawSortBy = (string) $request->input('sort_by', $this->defaultSortBy);
        $allowedSorts = [
            'id', 'contract_number', 'version', 'status', 'signature_status',
            'contract_value', 'start_date', 'expected_completion_date', 'created_at',
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
            'total' => Contract::count(),
            'draft' => Contract::where('status', 'Draft')->count(),
            'under_review' => Contract::where('status', 'Under Review')->count(),
            'approved' => Contract::where('status', 'Approved')->count(),
            'superseded' => Contract::where('status', 'Superseded')->count(),
            'not_sent' => Contract::where('signature_status', 'Not Sent')->count(),
            'sent_for_signature' => Contract::where('signature_status', 'Sent for Signature')->count(),
            'signed' => Contract::where('signature_status', 'Signed')->count(),
            'void' => Contract::where('signature_status', 'Void')->count(),
            'total_contract_value' => (float) Contract::where('status', 'Approved')->sum('contract_value'),
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
        $this->authorizePermission('contracts.view');

        $contract = $this->query()->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $contract,
            'schema' => $this->inputMaker()->toSchema(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission('contracts.create');

        // Strictly reject protected server-controlled fields
        $this->guardAgainstSensitiveFields($request);

        $validated = $request->validate([
            'opportunity_id' => ['required', 'integer', 'exists:opportunities,id'],
            'quotation_id' => ['required', 'integer', 'exists:quotations,id'],
            'contract_number' => ['nullable', 'string', 'max:50'],
            'contract_value' => ['nullable', 'numeric', 'min:0'],
            'value_adjustment_reason' => ['nullable', 'string', 'max:5000'],
            'start_date' => ['nullable', 'date'],
            'expected_completion_date' => ['nullable', 'date'],
            'terms_and_conditions' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'prepared_by' => ['nullable', 'integer', 'exists:users,id'],
            'prepared_at' => ['nullable', 'date'],
            'milestones' => ['nullable', 'array'],
            'milestones.*.milestone_label' => ['required', 'string', 'max:255'],
            'milestones.*.percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'milestones.*.fixed_amount' => ['nullable', 'numeric', 'min:0'],
            'milestones.*.trigger_description' => ['nullable', 'string'],
            'milestones.*.sort_order' => ['nullable', 'integer'],
        ]);

        // Date range invariant: expected completion must not precede start date
        if (! empty($validated['start_date']) && ! empty($validated['expected_completion_date'])) {
            if ($validated['expected_completion_date'] < $validated['start_date']) {
                throw ValidationException::withMessages([
                    'expected_completion_date' => [__('messages.contract_dates_invalid')],
                ]);
            }
        }

        $opportunity = Opportunity::findOrFail($validated['opportunity_id']);
        $quotation = Quotation::findOrFail($validated['quotation_id']);

        // Commercial Invariant 1: Quotation MUST be Approved internally
        if (! $quotation->isApproved()) {
            throw ValidationException::withMessages([
                'quotation_id' => [__('messages.quotation_must_be_approved')],
            ]);
        }

        // Commercial Invariant 2: Quotation MUST be Accepted by client
        if (! $quotation->isAccepted()) {
            throw ValidationException::withMessages([
                'quotation_id' => [__('messages.quotation_must_be_accepted_by_client')],
            ]);
        }

        // Commercial Invariant 3: Quotation MUST belong to the specified Opportunity
        if ((int) $quotation->opportunity_id !== (int) $opportunity->id) {
            throw ValidationException::withMessages([
                'quotation_id' => [__('messages.quotation_opportunity_mismatch')],
            ]);
        }

        // Financial Inheritance: Default contract_value is quotation grand_total from database
        $quotationGrandTotal = round((float) $quotation->grand_total, 2);
        $finalContractValue = isset($validated['contract_value'])
            ? round((float) $validated['contract_value'], 2)
            : $quotationGrandTotal;

        // If contract_value differs from quotation.grand_total, justification reason is strictly required
        $adjustmentReason = null;
        if (bccomp((string) $finalContractValue, (string) $quotationGrandTotal, 2) !== 0) {
            $rawReason = trim((string) ($validated['value_adjustment_reason'] ?? ''));
            if ($rawReason === '' || mb_strlen($rawReason) < 10) {
                throw ValidationException::withMessages([
                    'value_adjustment_reason' => [__('messages.contract_value_adjustment_reason_required')],
                ]);
            }
            $adjustmentReason = $rawReason;
        }

        // Prepared by attribution
        $preparedBy = auth()->id();
        if (isset($validated['prepared_by'])) {
            $preparedBy = (int) $validated['prepared_by'];
        }

        $preparedAt = ! empty($validated['prepared_at'])
            ? Carbon::parse($validated['prepared_at'])->toDateString()
            : now()->toDateString();

        $contract = DB::transaction(function () use (
            $opportunity,
            $quotation,
            $finalContractValue,
            $adjustmentReason,
            $preparedBy,
            $preparedAt,
            $validated
        ) {
            // Generate sequence number CT-OPP{opp_id}-{seq}
            $contractNumber = $validated['contract_number'] ?? null;
            if (empty($contractNumber)) {
                $contractNumber = $this->generateUniqueContractNumber($opportunity->id);
            }

            $contract = Contract::create([
                'opportunity_id' => $opportunity->id,
                'quotation_id' => $quotation->id,
                'parent_contract_id' => null,
                'contract_number' => $contractNumber,
                'version' => 1,
                'status' => 'Draft',
                'signature_status' => 'Not Sent',
                'contract_value' => $finalContractValue,
                'value_adjustment_reason' => $adjustmentReason,
                'start_date' => $validated['start_date'] ?? null,
                'expected_completion_date' => $validated['expected_completion_date'] ?? null,
                'terms_and_conditions' => $validated['terms_and_conditions'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'prepared_by' => $preparedBy,
                'prepared_at' => $preparedAt,
                'created_by' => auth()->id(),
            ]);

            // Save Milestones if provided
            if (! empty($validated['milestones']) && is_array($validated['milestones'])) {
                foreach ($validated['milestones'] as $idx => $m) {
                    $this->createMilestoneRecord($contract, $m, $idx);
                }
            }

            activity('commercial')
                ->event('created')
                ->performedOn($contract)
                ->causedBy(auth()->user())
                ->log(__('activity.contract_created', ['number' => $contract->contract_number]));

            return $contract;
        });

        return response()->json([
            'success' => true,
            'message' => __('messages.contract_created_success'),
            'data' => $contract->fresh($this->with),
        ], 201);
    }

    public function createFromQuotation(Request $request, int|string $quotationId): JsonResponse
    {
        $this->authorizePermission('contracts.create');

        $quotation = Quotation::with('opportunity')->findOrFail($quotationId);

        if (! $quotation->isApproved()) {
            throw ValidationException::withMessages([
                'quotation_id' => [__('messages.quotation_must_be_approved')],
            ]);
        }

        if (! $quotation->isAccepted()) {
            throw ValidationException::withMessages([
                'quotation_id' => [__('messages.quotation_must_be_accepted_by_client')],
            ]);
        }

        $request->merge([
            'opportunity_id' => $quotation->opportunity_id,
            'quotation_id' => $quotation->id,
            'contract_value' => $quotation->grand_total,
            'terms_and_conditions' => $quotation->payment_terms_text,
        ]);

        return $this->store($request);
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission('contracts.update');

        $contract = Contract::findOrFail($id);

        if (! $contract->canBeEdited()) {
            throw ValidationException::withMessages([
                'status' => [__('messages.cannot_edit_approved_contract')],
            ]);
        }

        // Strictly reject protected server-controlled fields
        $this->guardAgainstSensitiveFields($request);

        $validated = $request->validate([
            'contract_value' => ['nullable', 'numeric', 'min:0'],
            'value_adjustment_reason' => ['nullable', 'string', 'max:5000'],
            'start_date' => ['nullable', 'date'],
            'expected_completion_date' => ['nullable', 'date'],
            'terms_and_conditions' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'prepared_by' => ['nullable', 'integer', 'exists:users,id'],
            'prepared_at' => ['nullable', 'date'],
            'milestones' => ['nullable', 'array'],
            'milestones.*.milestone_label' => ['required', 'string', 'max:255'],
            'milestones.*.percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'milestones.*.fixed_amount' => ['nullable', 'numeric', 'min:0'],
            'milestones.*.trigger_description' => ['nullable', 'string'],
            'milestones.*.sort_order' => ['nullable', 'integer'],
        ]);

        $startDate = array_key_exists('start_date', $validated) ? $validated['start_date'] : $contract->start_date?->toDateString();
        $expectedCompletionDate = array_key_exists('expected_completion_date', $validated) ? $validated['expected_completion_date'] : $contract->expected_completion_date?->toDateString();

        if (! empty($startDate) && ! empty($expectedCompletionDate) && $expectedCompletionDate < $startDate) {
            throw ValidationException::withMessages([
                'expected_completion_date' => [__('messages.contract_dates_invalid')],
            ]);
        }

        $quotation = Quotation::findOrFail($contract->quotation_id);
        $quotationGrandTotal = round((float) $quotation->grand_total, 2);

        $finalContractValue = array_key_exists('contract_value', $validated) && ! is_null($validated['contract_value'])
            ? round((float) $validated['contract_value'], 2)
            : round((float) $contract->contract_value, 2);

        $adjustmentReason = $contract->value_adjustment_reason;
        if (bccomp((string) $finalContractValue, (string) $quotationGrandTotal, 2) !== 0) {
            $rawReason = trim((string) ($validated['value_adjustment_reason'] ?? $contract->value_adjustment_reason ?? ''));
            if ($rawReason === '' || mb_strlen($rawReason) < 10) {
                throw ValidationException::withMessages([
                    'value_adjustment_reason' => [__('messages.contract_value_adjustment_reason_required')],
                ]);
            }
            $adjustmentReason = $rawReason;
        } else {
            // Cleared if value matches quotation grand total
            $adjustmentReason = null;
        }

        $contract = DB::transaction(function () use ($contract, $validated, $finalContractValue, $adjustmentReason, $startDate, $expectedCompletionDate) {
            $contract->contract_value = $finalContractValue;
            $contract->value_adjustment_reason = $adjustmentReason;
            $contract->start_date = $startDate;
            $contract->expected_completion_date = $expectedCompletionDate;

            if (array_key_exists('terms_and_conditions', $validated)) {
                $contract->terms_and_conditions = $validated['terms_and_conditions'];
            }
            if (array_key_exists('notes', $validated)) {
                $contract->notes = $validated['notes'];
            }
            if (array_key_exists('prepared_by', $validated) && ! is_null($validated['prepared_by'])) {
                $contract->prepared_by = (int) $validated['prepared_by'];
            }
            if (array_key_exists('prepared_at', $validated) && ! empty($validated['prepared_at'])) {
                $contract->prepared_at = Carbon::parse($validated['prepared_at'])->toDateString();
            }

            $contract->save();

            // Sync milestones if passed
            if (array_key_exists('milestones', $validated)) {
                $contract->milestones()->delete();
                if (is_array($validated['milestones'])) {
                    foreach ($validated['milestones'] as $idx => $m) {
                        $this->createMilestoneRecord($contract, $m, $idx);
                    }
                }
            }

            activity('commercial')
                ->event('updated')
                ->performedOn($contract)
                ->causedBy(auth()->user())
                ->log(__('activity.contract_updated', ['number' => $contract->contract_number]));

            return $contract;
        });

        return response()->json([
            'success' => true,
            'message' => __('messages.contract_updated_success'),
            'data' => $contract->fresh($this->with),
        ]);
    }

    public function destroy(int|string|Request $ids): JsonResponse
    {
        $this->authorizePermission('contracts.delete');

        $idList = is_array($ids) ? $ids : (is_numeric($ids) ? [(int) $ids] : explode(',', (string) $ids));

        $contracts = Contract::whereIn('id', $idList)->get();

        if ($contracts->isEmpty()) {
            abort(404, __('messages.contract_not_found'));
        }

        foreach ($contracts as $contract) {
            if (! $contract->canBeEdited()) {
                throw ValidationException::withMessages([
                    'status' => [__('messages.cannot_delete_approved_contract')],
                ]);
            }
        }

        foreach ($contracts as $contract) {
            $number = $contract->contract_number;
            $contract->delete();

            activity('commercial')
                ->event('deleted')
                ->performedOn($contract)
                ->causedBy(auth()->user())
                ->log(__('activity.contract_deleted', ['number' => $number]));
        }

        return response()->json([
            'success' => true,
            'message' => __('messages.contract_deleted_success'),
        ]);
    }

    public function submitReview(int|string $id): JsonResponse
    {
        $this->authorizePermission('contracts.update');

        $contract = Contract::findOrFail($id);

        if (! $contract->isDraft()) {
            throw ValidationException::withMessages([
                'status' => [__('messages.contract_only_draft_can_submit_review')],
            ]);
        }

        $contract->status = 'Under Review';
        $contract->reviewed_by = auth()->id();
        $contract->reviewed_at = now();
        $contract->save();

        activity('commercial')
            ->event('submitted_for_review')
            ->performedOn($contract)
            ->causedBy(auth()->user())
            ->log(__('activity.contract_submitted_for_review', ['number' => $contract->contract_number]));

        return response()->json([
            'success' => true,
            'message' => __('messages.contract_submitted_review_success'),
            'data' => $contract->fresh($this->with),
        ]);
    }

    public function review(int|string $id): JsonResponse
    {
        return $this->submitReview($id);
    }

    public function approve(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission('contracts.approve');

        $contract = DB::transaction(function () use ($id) {
            $target = Contract::with(['milestones', 'quotation'])->findOrFail($id);

            if (! $target->canBeApproved()) {
                throw ValidationException::withMessages([
                    'status' => [__('messages.contract_cannot_be_approved_in_current_state')],
                ]);
            }

            // 1. Strict Opportunity Lock to guarantee single-baseline concurrency serialization
            $opportunity = Opportunity::where('id', $target->opportunity_id)
                ->lockForUpdate()
                ->firstOrFail();

            // 2. Re-read contract and source Quotation from DB fresh
            $target->refresh();
            $target->load(['milestones', 'quotation']);

            // 3. Re-verify source quotation state
            $quotation = Quotation::findOrFail($target->quotation_id);
            if (! $quotation->isApproved()) {
                throw ValidationException::withMessages([
                    'quotation_id' => [__('messages.quotation_must_be_approved')],
                ]);
            }
            if (! $quotation->isAccepted()) {
                throw ValidationException::withMessages([
                    'quotation_id' => [__('messages.quotation_must_be_accepted_by_client')],
                ]);
            }
            if ((int) $quotation->opportunity_id !== (int) $opportunity->id) {
                throw ValidationException::withMessages([
                    'quotation_id' => [__('messages.quotation_opportunity_mismatch')],
                ]);
            }

            // 4. Financial justification verification
            $quotationGrandTotal = round((float) $quotation->grand_total, 2);
            $contractVal = round((float) $target->contract_value, 2);
            if (bccomp((string) $contractVal, (string) $quotationGrandTotal, 2) !== 0) {
                $reason = trim((string) $target->value_adjustment_reason);
                if ($reason === '' || mb_strlen($reason) < 10) {
                    throw ValidationException::withMessages([
                        'value_adjustment_reason' => [__('messages.contract_value_adjustment_reason_required')],
                    ]);
                }
            }

            // 5. Strict Milestones Invariant Verification
            $this->validateMilestonesStrictly($target);

            // 6. Dates Invariant Verification
            if ($target->start_date && $target->expected_completion_date) {
                if ($target->expected_completion_date < $target->start_date) {
                    throw ValidationException::withMessages([
                        'expected_completion_date' => [__('messages.contract_dates_invalid')],
                    ]);
                }
            }

            // 7. Check sibling approved contracts for this Opportunity
            $existingApproved = Contract::where('opportunity_id', $opportunity->id)
                ->where('id', '!=', $target->id)
                ->where('status', 'Approved')
                ->get();

            foreach ($existingApproved as $prevApproved) {
                $isRevision = ((int) $target->parent_contract_id === (int) $prevApproved->id)
                    || ($target->version > $prevApproved->version && (int) $target->parent_contract_id !== null);

                if ($isRevision) {
                    $prevApproved->status = 'Superseded';
                    $prevApproved->save();
                } else {
                    // Conflicting standalone contract for same opportunity
                    throw ValidationException::withMessages([
                        'status' => [__('messages.cannot_approve_conflicting_contract')],
                    ]);
                }
            }

            // 8. Sign-off and approve
            $target->status = 'Approved';
            $target->approved_by = auth()->id();
            $target->approved_at = now();
            $target->save();

            activity('commercial')
                ->event('approved')
                ->performedOn($target)
                ->causedBy(auth()->user())
                ->log(__('activity.contract_approved', ['number' => $target->contract_number]));

            return $target;
        });

        return response()->json([
            'success' => true,
            'message' => __('messages.contract_approved_success'),
            'data' => $contract->fresh($this->with),
        ]);
    }

    public function createRevision(int|string $id): JsonResponse
    {
        $this->authorizePermission('contracts.create');

        $contract = Contract::with('milestones')->findOrFail($id);

        if (! $contract->isApproved()) {
            throw ValidationException::withMessages([
                'status' => [__('messages.contract_revision_only_from_approved')],
            ]);
        }

        $revision = DB::transaction(function () use ($contract) {
            $nextVersion = ((int) $contract->version) + 1;
            $nextNumber = $contract->nextRevisionNumber();

            $newContract = Contract::create([
                'opportunity_id' => $contract->opportunity_id,
                'quotation_id' => $contract->quotation_id,
                'parent_contract_id' => $contract->id,
                'contract_number' => $nextNumber,
                'version' => $nextVersion,
                'status' => 'Draft',
                'signature_status' => 'Not Sent',
                'contract_value' => $contract->contract_value,
                'value_adjustment_reason' => $contract->value_adjustment_reason,
                'start_date' => $contract->start_date?->toDateString(),
                'expected_completion_date' => $contract->expected_completion_date?->toDateString(),
                'terms_and_conditions' => $contract->terms_and_conditions,
                'notes' => __('messages.contract_revision_note', [
                    'version' => $nextVersion,
                    'number' => $contract->contract_number,
                ]),
                'prepared_by' => auth()->id(),
                'prepared_at' => now()->toDateString(),
                'created_by' => auth()->id(),
            ]);

            // Clone milestones
            foreach ($contract->milestones as $m) {
                ContractPaymentMilestone::create([
                    'contract_id' => $newContract->id,
                    'milestone_label' => $m->milestone_label,
                    'percentage' => $m->percentage,
                    'fixed_amount' => $m->fixed_amount,
                    'trigger_description' => $m->trigger_description,
                    'sort_order' => $m->sort_order,
                ]);
            }

            activity('commercial')
                ->event('revision_created')
                ->performedOn($newContract)
                ->causedBy(auth()->user())
                ->log(__('activity.contract_revision_created', [
                    'number' => $newContract->contract_number,
                    'from' => $contract->contract_number,
                ]));

            return $newContract;
        });

        return response()->json([
            'success' => true,
            'message' => __('messages.contract_revision_created_success'),
            'data' => $revision->fresh($this->with),
        ], 201);
    }

    public function sendForSignature(int|string $id): JsonResponse
    {
        $this->authorizePermission(['contracts.sign', 'contracts.update']);

        $contract = Contract::findOrFail($id);

        if (! $contract->isApproved()) {
            throw ValidationException::withMessages([
                'signature_status' => [__('messages.contract_send_signature_only_when_approved')],
            ]);
        }

        $contract->signature_status = 'Sent for Signature';
        $contract->save();

        activity('commercial')
            ->event('sent_for_signature')
            ->performedOn($contract)
            ->causedBy(auth()->user())
            ->log(__('activity.contract_sent_for_signature', ['number' => $contract->contract_number]));

        return response()->json([
            'success' => true,
            'message' => __('messages.contract_sent_for_signature_success'),
            'data' => $contract->fresh($this->with),
        ]);
    }

    public function markSigned(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission('contracts.sign');

        $contract = Contract::findOrFail($id);

        if (! $contract->isApproved()) {
            throw ValidationException::withMessages([
                'signature_status' => [__('messages.contract_sign_only_when_sent_or_approved')],
            ]);
        }

        $contract->signature_status = 'Signed';
        $contract->signed_by = auth()->id();
        $contract->signed_at = now();
        $contract->save();

        activity('commercial')
            ->event('signed')
            ->performedOn($contract)
            ->causedBy(auth()->user())
            ->log(__('activity.contract_signed', ['number' => $contract->contract_number]));

        return response()->json([
            'success' => true,
            'message' => __('messages.contract_signed_success'),
            'data' => $contract->fresh($this->with),
        ]);
    }

    public function voidSignature(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission(['contracts.sign', 'contracts.approve']);

        $contract = Contract::findOrFail($id);

        if ($contract->isSigned()) {
            throw ValidationException::withMessages([
                'signature_status' => [__('messages.cannot_void_signed_contract')],
            ]);
        }

        $contract->signature_status = 'Void';
        $contract->save();

        activity('commercial')
            ->event('signature_voided')
            ->performedOn($contract)
            ->causedBy(auth()->user())
            ->log(__('activity.contract_signature_voided', ['number' => $contract->contract_number]));

        return response()->json([
            'success' => true,
            'message' => __('messages.contract_voided_success'),
            'data' => $contract->fresh($this->with),
        ]);
    }

    /**
     * Strict Guard: Reject client attempts to inject server-controlled status or signature fields.
     */
    protected function guardAgainstSensitiveFields(Request $request): void
    {
        $sensitiveFields = [
            'status',
            'signature_status',
            'reviewed_by',
            'reviewed_at',
            'approved_by',
            'approved_at',
            'signed_by',
            'signed_at',
        ];

        foreach ($sensitiveFields as $field) {
            if ($request->has($field)) {
                throw ValidationException::withMessages([
                    $field => [__('messages.cannot_mutate_workflow_or_signature_directly')],
                ]);
            }
        }
    }

    /**
     * Create single milestone record with method check.
     */
    protected function createMilestoneRecord(Contract $contract, array $m, int $idx): ContractPaymentMilestone
    {
        $pct = isset($m['percentage']) && $m['percentage'] !== '' ? round((float) $m['percentage'], 2) : null;
        $fix = isset($m['fixed_amount']) && $m['fixed_amount'] !== '' ? round((float) $m['fixed_amount'], 2) : null;

        if ((! is_null($pct) && ! is_null($fix)) || (is_null($pct) && is_null($fix))) {
            throw ValidationException::withMessages([
                "milestones.{$idx}" => [__('messages.contract_milestone_dual_method_invalid')],
            ]);
        }

        return ContractPaymentMilestone::create([
            'contract_id' => $contract->id,
            'milestone_label' => $m['milestone_label'],
            'percentage' => $pct,
            'fixed_amount' => $fix,
            'trigger_description' => $m['trigger_description'] ?? null,
            'sort_order' => isset($m['sort_order']) ? (int) $m['sort_order'] : $idx,
        ]);
    }

    /**
     * Strict Milestone validation rule:
     * - At least 1 milestone.
     * - Exactly one method per milestone.
     * - Single method across entire contract (all percentage or all fixed).
     * - Percentage total == 100.00%
     * - Fixed amount total == contract_value
     */
    protected function validateMilestonesStrictly(Contract $contract): void
    {
        $milestones = $contract->milestones;

        if ($milestones->isEmpty()) {
            throw ValidationException::withMessages([
                'milestones' => [__('messages.contract_milestones_empty')],
            ]);
        }

        $hasPercentage = false;
        $hasFixed = false;
        $totalPercentage = '0.00';
        $totalFixed = '0.00';

        foreach ($milestones as $index => $m) {
            $pctFilled = ! is_null($m->percentage);
            $fixFilled = ! is_null($m->fixed_amount);

            if (($pctFilled && $fixFilled) || (! $pctFilled && ! $fixFilled)) {
                throw ValidationException::withMessages([
                    "milestones.{$index}" => [__('messages.contract_milestone_dual_method_invalid')],
                ]);
            }

            if ($pctFilled) {
                $hasPercentage = true;
                $totalPercentage = bcadd((string) $totalPercentage, (string) $m->percentage, 2);
            }

            if ($fixFilled) {
                $hasFixed = true;
                $totalFixed = bcadd((string) $totalFixed, (string) $m->fixed_amount, 2);
            }
        }

        if ($hasPercentage && $hasFixed) {
            throw ValidationException::withMessages([
                'milestones' => [__('messages.contract_milestone_mixed_methods_invalid')],
            ]);
        }

        if ($hasPercentage) {
            if (bccomp((string) $totalPercentage, '100.00', 2) !== 0) {
                throw ValidationException::withMessages([
                    'milestones' => [__('messages.contract_milestones_percentage_sum_invalid')],
                ]);
            }
        }

        if ($hasFixed) {
            $expectedValue = round((float) $contract->contract_value, 2);
            if (bccomp((string) $totalFixed, (string) $expectedValue, 2) !== 0) {
                throw ValidationException::withMessages([
                    'milestones' => [__('messages.contract_milestones_fixed_amount_sum_invalid')],
                ]);
            }
        }
    }

    /**
     * Generate unique contract number CT-OPP{opp_id}-{seq}
     */
    protected function generateUniqueContractNumber(int $opportunityId): string
    {
        $prefix = "CT-OPP{$opportunityId}-";
        $attempts = 0;

        do {
            $count = Contract::where('opportunity_id', $opportunityId)->count() + 1 + $attempts;
            $sequence = str_pad((string) $count, 3, '0', STR_PAD_LEFT);
            $candidate = "{$prefix}{$sequence}";
            $exists = Contract::where('contract_number', $candidate)->exists();
            $attempts++;
        } while ($exists && $attempts < 100);

        if ($exists) {
            $candidate = "{$prefix}".uniqid();
        }

        return $candidate;
    }
}
