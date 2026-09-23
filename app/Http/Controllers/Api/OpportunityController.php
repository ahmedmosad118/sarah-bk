<?php

namespace App\Http\Controllers\Api;

use App\Core\CRUD\CRUDController;
use App\Core\CRUD\Field;
use App\Core\CRUD\InputMaker;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\SiteVisit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OpportunityController extends CRUDController
{
    protected string $model = Opportunity::class;
    protected array $searchable = ['title', 'description', 'notes'];
    protected array $with = ['customer', 'lead', 'assignedUser', 'creator'];
    protected string $defaultSortBy = 'id';
    protected string $defaultSortOrder = 'desc';

    protected function inputMaker(): InputMaker
    {
        return InputMaker::make()
            ->title('opportunities.title')
            ->singularTitle('opportunities.singular')
            ->model(Opportunity::class)
            ->fields([
                Field::select('customer_id', 'opportunities.customer', [])
                    ->required()
                    ->col(6),

                Field::text('title', 'opportunities.opportunityTitle')
                    ->required()
                    ->col(6),

                Field::select('lead_id', 'opportunities.lead', [])
                    ->col(6),

                Field::select('stage', 'opportunities.stage', [
                    ['value' => 'New', 'label' => 'opportunities.stageNew'],
                    ['value' => 'Qualified', 'label' => 'opportunities.stageQualified'],
                    ['value' => 'Proposal', 'label' => 'opportunities.stageProposal'],
                    ['value' => 'Negotiation', 'label' => 'opportunities.stageNegotiation'],
                    ['value' => 'Won', 'label' => 'opportunities.stageWon'],
                    ['value' => 'Lost', 'label' => 'opportunities.stageLost'],
                ])->default('New')->col(6),

                Field::number('estimated_value', 'opportunities.estimatedValue')
                    ->col(6),

                Field::select('assigned_to', 'opportunities.assignedTo', [])
                    ->col(6),

                Field::date('expected_start_date', 'opportunities.expectedStartDate')
                    ->col(6),

                Field::date('expected_close_date', 'opportunities.expectedCloseDate')
                    ->col(6),

                Field::textarea('description', 'opportunities.description')
                    ->col(12),

                Field::textarea('notes', 'opportunities.notes')
                    ->col(12),
            ]);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission('opportunities.view');

        $query = $this->query();

        // 1. Search Query (supports title, description, notes, customer name, phone, email, company)
        if ($search = $request->input('search')) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('customer', function (Builder $cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // 2. Filter by Stage
        if ($request->filled('stage')) {
            $query->where('stage', $request->stage);
        }

        // 3. Filter by Customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // 4. Filter by Assigned User
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        // 5. Filter by Source Lead
        if ($request->filled('lead_id')) {
            $query->where('lead_id', $request->lead_id);
        }

        // 6. Sorting (Safe SQL Injection Whitelisting)
        $rawSortBy = (string) $request->input('sort_by', $this->defaultSortBy);
        $allowedSorts = ['id', 'title', 'stage', 'estimated_value', 'expected_start_date', 'expected_close_date', 'created_at'];
        $sortBy = in_array($rawSortBy, $allowedSorts, true) ? $rawSortBy : $this->defaultSortBy;

        $rawSortOrder = strtolower((string) $request->input('sort_order', $this->defaultSortOrder));
        $sortOrder = in_array($rawSortOrder, ['asc', 'desc'], true) ? $rawSortOrder : 'desc';

        $query->orderBy($sortBy, $sortOrder);

        // 7. Pagination (DOS Protection: Capped 1 - 100)
        $rawPerPage = (int) $request->input('per_page', 15);
        $perPage = min(max($rawPerPage, 1), 100);
        $paginated = $query->paginate($perPage);

        // High Performance Stats Summary via SQL aggregation
        $stats = [
            'total' => Opportunity::count(),
            'new' => Opportunity::where('stage', 'New')->count(),
            'qualified' => Opportunity::where('stage', 'Qualified')->count(),
            'proposal' => Opportunity::where('stage', 'Proposal')->count(),
            'negotiation' => Opportunity::where('stage', 'Negotiation')->count(),
            'won' => Opportunity::where('stage', 'Won')->count(),
            'lost' => Opportunity::where('stage', 'Lost')->count(),
            'total_estimated_value' => (float) Opportunity::sum('estimated_value'),
            'won_estimated_value' => (float) Opportunity::where('stage', 'Won')->sum('estimated_value'),
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
        $this->authorizePermission('opportunities.view');
        return parent::show($id);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission('opportunities.create');
        return parent::store($request);
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission('opportunities.update');
        return parent::update($request, $id);
    }

    public function destroy(int|string|Request $ids): JsonResponse
    {
        $this->authorizePermission('opportunities.delete');
        return parent::destroy($ids);
    }

    /**
     * Convert an eligible Lead to a new qualified Opportunity atomically.
     */
    public function convertFromLead(Request $request, int|string $leadId): JsonResponse
    {
        $this->authorizePermission(['opportunities.convert', 'leads.convert']);

        $lead = Lead::findOrFail($leadId);

        // Prevent duplicate conversions
        if ($lead->status === 'Converted' || Opportunity::where('lead_id', $lead->id)->exists()) {
            throw ValidationException::withMessages([
                'lead_id' => [__('messages.lead_already_converted')],
            ]);
        }

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'stage' => ['nullable', 'string', 'in:New,Qualified,Proposal,Negotiation,Won,Lost'],
            'estimated_value' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'expected_start_date' => ['nullable', 'date'],
            'expected_close_date' => ['nullable', 'date', 'after_or_equal:expected_start_date'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $opportunity = DB::transaction(function () use ($lead, $validated) {
            $opp = Opportunity::create([
                'customer_id' => $lead->customer_id,
                'lead_id' => $lead->id,
                'title' => $validated['title'] ?? $lead->title,
                'description' => array_key_exists('description', $validated) ? $validated['description'] : $lead->description,
                'stage' => $validated['stage'] ?? 'New',
                'estimated_value' => array_key_exists('estimated_value', $validated) ? $validated['estimated_value'] : $lead->estimated_value,
                'expected_start_date' => array_key_exists('expected_start_date', $validated) ? $validated['expected_start_date'] : $lead->expected_start_date,
                'expected_close_date' => $validated['expected_close_date'] ?? null,
                'assigned_to' => array_key_exists('assigned_to', $validated) ? $validated['assigned_to'] : $lead->assigned_to,
                'created_by' => auth()->id(),
                'notes' => array_key_exists('notes', $validated) ? $validated['notes'] : $lead->notes,
            ]);

            // Transition lead status to Converted
            $lead->status = 'Converted';
            $lead->save();

            // Activity log on lead
            activity('commercial')
                ->event('updated')
                ->performedOn($lead)
                ->causedBy(auth()->user())
                ->log(__('activity.lead_converted', ['title' => $lead->title]));

            // Activity log on opportunity
            activity('commercial')
                ->event('created')
                ->performedOn($opp)
                ->causedBy(auth()->user())
                ->log(__('activity.opportunity_converted_from_lead', ['lead' => $lead->title, 'title' => $opp->title]));

            // Link any existing Site Visits tied to this Lead to the newly created Opportunity
            $linkedVisitsCount = SiteVisit::where('lead_id', $lead->id)
                ->whereNull('opportunity_id')
                ->update(['opportunity_id' => $opp->id]);

            if ($linkedVisitsCount > 0) {
                activity('commercial')
                    ->event('updated')
                    ->performedOn($opp)
                    ->causedBy(auth()->user())
                    ->log("تم ربط {$linkedVisitsCount} معاينات موقع تابعة للعميل المحتمل بالفرصة التجارية الجديدة تلقائياً.");
            }

            return $opp;
        });

        return response()->json([
            'success' => true,
            'message' => __('messages.opportunity_converted_success'),
            'data' => $opportunity->fresh($this->with),
        ], 201);
    }

    /**
     * Assign Opportunity to a specific tenant user.
     */
    public function assign(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission('opportunities.assign');

        $validated = $request->validate([
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
        ]);

        $opportunity = Opportunity::findOrFail($id);
        $opportunity->assigned_to = $validated['assigned_to'];
        $opportunity->save();

        $assignedUser = User::find($validated['assigned_to']);

        activity('commercial')
            ->event('updated')
            ->performedOn($opportunity)
            ->causedBy(auth()->user())
            ->log(__('activity.opportunity_assigned', ['title' => $opportunity->title, 'user' => $assignedUser?->name]));

        return response()->json([
            'success' => true,
            'message' => __('messages.opportunity_assigned_success'),
            'data' => $opportunity->fresh($this->with),
        ]);
    }

    /**
     * Update opportunity stage with pipeline tracking.
     */
    public function changeStage(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission('opportunities.update');

        $validated = $request->validate([
            'stage' => ['required', 'string', 'in:New,Qualified,Proposal,Negotiation,Won,Lost'],
            'loss_reason' => ['nullable', 'string', 'max:100'],
            'competitor_name' => ['nullable', 'string', 'max:200'],
            'loss_notes' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $opportunity = Opportunity::findOrFail($id);
        $oldStage = $opportunity->stage;
        $opportunity->stage = $validated['stage'];
        if (array_key_exists('loss_reason', $validated)) {
            $opportunity->loss_reason = $validated['loss_reason'];
        }
        if (array_key_exists('competitor_name', $validated)) {
            $opportunity->competitor_name = $validated['competitor_name'];
        }
        if (array_key_exists('loss_notes', $validated)) {
            $opportunity->loss_notes = $validated['loss_notes'];
        }
        if (isset($validated['notes'])) {
            $opportunity->notes = $validated['notes'];
        }
        $opportunity->save();

        activity('commercial')
            ->event('updated')
            ->performedOn($opportunity)
            ->causedBy(auth()->user())
            ->withProperties(['old_stage' => $oldStage, 'new_stage' => $opportunity->stage, 'loss_reason' => $opportunity->loss_reason])
            ->log(__('activity.opportunity_stage_changed', ['title' => $opportunity->title, 'stage' => $opportunity->stage]));

        return response()->json([
            'success' => true,
            'message' => __('messages.opportunity_stage_updated_success'),
            'data' => $opportunity->fresh($this->with),
        ]);
    }

    protected function beforeSave(Model $model, Request $request, bool $isUpdate): void
    {
        /** @var Opportunity $model */
        if (!$isUpdate) {
            if (empty($model->created_by)) {
                $model->created_by = auth()->id();
            }
            if (empty($model->stage)) {
                $model->stage = 'New';
            }
        }
    }

    protected function customValidationRules(bool $isUpdate = false, mixed $currentId = null): array
    {
        $rules = [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'lead_id' => ['nullable', 'integer', 'exists:leads,id'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'stage' => ['nullable', 'string', 'in:New,Qualified,Proposal,Negotiation,Won,Lost'],
            'loss_reason' => ['nullable', 'string', 'max:100'],
            'competitor_name' => ['nullable', 'string', 'max:200'],
            'loss_notes' => ['nullable', 'string', 'max:2000'],
            'estimated_value' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'expected_start_date' => ['nullable', 'date'],
            'expected_close_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'documents' => ['nullable'],
            'documents.*' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,webp', 'max:5120'],
        ];

        // Ensure expected_close_date is not before expected_start_date when both are provided
        if (request()->filled('expected_start_date') && request()->filled('expected_close_date')) {
            $rules['expected_close_date'][] = 'after_or_equal:expected_start_date';
        }

        return $rules;
    }

    protected function afterSave(Model $model, Request $request, bool $isUpdate): void
    {
        /** @var Opportunity $model */
        if ($request->hasFile('documents')) {
            $files = is_array($request->file('documents')) ? $request->file('documents') : [$request->file('documents')];
            foreach ($files as $file) {
                $ext = strtolower($file->getClientOriginalExtension());
                if (in_array($ext, ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phar', 'pht', 'exe', 'sh', 'bat', 'cmd'], true)) {
                    continue;
                }
                $model->addMedia($file)->toMediaCollection('documents');
            }
        }
    }

    private function authorizePermission(string|array $permission): void
    {
        $user = auth()->user();
        if (!$user) {
            return;
        }

        if ($user->hasRole('Owner') || $user->hasRole('Super Admin')) {
            return;
        }

        $perms = is_array($permission) ? $permission : [$permission];
        $hasAny = false;
        foreach ($perms as $perm) {
            if ($user->hasPermissionTo($perm)) {
                $hasAny = true;
                break;
            }
        }

        if (!$hasAny) {
            abort(403, __('messages.permissions_denied_any', ['permissions' => implode(', ', $perms)]));
        }
    }
}
