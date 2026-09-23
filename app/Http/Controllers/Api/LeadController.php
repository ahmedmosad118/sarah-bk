<?php

namespace App\Http\Controllers\Api;

use App\Core\CRUD\CRUDController;
use App\Core\CRUD\Field;
use App\Core\CRUD\InputMaker;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeadController extends CRUDController
{
    protected string $model = Lead::class;
    protected array $searchable = ['title', 'description', 'notes'];
    protected array $with = ['customer', 'assignedUser', 'creator'];
    protected string $defaultSortBy = 'id';
    protected string $defaultSortOrder = 'desc';

    protected function inputMaker(): InputMaker
    {
        return InputMaker::make()
            ->title('leads.title')
            ->singularTitle('leads.singular')
            ->model(Lead::class)
            ->fields([
                Field::select('customer_id', 'leads.customer', [])
                    ->required()
                    ->col(6),

                Field::text('title', 'leads.leadTitle')
                    ->required()
                    ->col(6),

                Field::select('source', 'leads.source', [
                    ['value' => 'Facebook', 'label' => 'leads.sourceFacebook'],
                    ['value' => 'Instagram', 'label' => 'leads.sourceInstagram'],
                    ['value' => 'Google', 'label' => 'leads.sourceGoogle'],
                    ['value' => 'Website', 'label' => 'leads.sourceWebsite'],
                    ['value' => 'WhatsApp', 'label' => 'leads.sourceWhatsApp'],
                    ['value' => 'Referral', 'label' => 'leads.sourceReferral'],
                    ['value' => 'Phone', 'label' => 'leads.sourcePhone'],
                    ['value' => 'Walk-in', 'label' => 'leads.sourceWalkIn'],
                    ['value' => 'Other', 'label' => 'leads.sourceOther'],
                ])->default('Other')->col(6),

                Field::select('assigned_to', 'leads.assignedTo', [])
                    ->col(6),

                Field::textarea('description', 'leads.description')
                    ->col(12),

                Field::textarea('notes', 'leads.notes')
                    ->col(12),

                Field::select('status', 'common.status', [
                    ['value' => 'New', 'label' => 'leads.statusNew'],
                    ['value' => 'Contacted', 'label' => 'leads.statusContacted'],
                    ['value' => 'Qualified', 'label' => 'leads.statusQualified'],
                    ['value' => 'Unqualified', 'label' => 'leads.statusUnqualified'],
                    ['value' => 'Converted', 'label' => 'leads.statusConverted'],
                    ['value' => 'Lost', 'label' => 'leads.statusLost'],
                ])->default('New')->hiddenInForm(),

                Field::number('estimated_value', 'leads.estimatedValue')
                    ->hiddenInForm(),

                Field::date('expected_start_date', 'leads.expectedStartDate')
                    ->hiddenInForm(),
            ]);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission('leads.view');

        $query = $this->query();

        // 1. Search Query (supports title, description, notes, and related customer fields)
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

        // 2. Filter by Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 3. Filter by Source
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        // 4. Filter by Customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // 5. Filter by Assigned User
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        // 6. Sorting (SQL Injection Whitelisting & Protection)
        $rawSortBy = (string) $request->input('sort_by', $this->defaultSortBy);
        $sortBy = preg_match('/^[a-zA-Z0-9_]+$/', $rawSortBy) ? $rawSortBy : $this->defaultSortBy;

        $rawSortOrder = strtolower((string) $request->input('sort_order', $this->defaultSortOrder));
        $sortOrder = in_array($rawSortOrder, ['asc', 'desc'], true) ? $rawSortOrder : 'desc';

        $query->orderBy($sortBy, $sortOrder);

        // 7. Pagination (DOS Protection: Capped 1 - 100)
        $rawPerPage = (int) $request->input('per_page', 15);
        $perPage = min(max($rawPerPage, 1), 100);
        $paginated = $query->paginate($perPage);

        // High Performance Stats Summary using direct SQL counts
        $stats = [
            'total' => Lead::count(),
            'new' => Lead::where('status', 'New')->count(),
            'contacted' => Lead::where('status', 'Contacted')->count(),
            'qualified' => Lead::where('status', 'Qualified')->count(),
            'unqualified' => Lead::where('status', 'Unqualified')->count(),
            'converted' => Lead::where('status', 'Converted')->count(),
            'lost' => Lead::where('status', 'Lost')->count(),
            'total_estimated_value' => (float) Lead::sum('estimated_value'),
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
        $this->authorizePermission('leads.view');
        return parent::show($id);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission('leads.create');
        return parent::store($request);
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission('leads.update');
        return parent::update($request, $id);
    }

    public function destroy(int|string|Request $ids): JsonResponse
    {
        $this->authorizePermission('leads.delete');
        return parent::destroy($ids);
    }

    /**
     * Convert lead status to Converted.
     */
    public function convert(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission('leads.convert');

        $lead = Lead::findOrFail($id);
        $lead->status = 'Converted';
        $lead->save();

        activity('commercial')
            ->event('updated')
            ->performedOn($lead)
            ->causedBy(auth()->user())
            ->log(__('activity.lead_converted', ['title' => $lead->title]));

        return response()->json([
            'success' => true,
            'message' => __('messages.lead_converted_success'),
            'data' => $lead->fresh($this->with),
        ]);
    }

    /**
     * Convert lead to Opportunity atomically with duplicate prevention.
     */
    public function convertToOpportunity(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission(['leads.convert', 'opportunities.convert']);

        $lead = Lead::findOrFail($id);

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

            $lead->status = 'Converted';
            $lead->save();

            activity('commercial')
                ->event('updated')
                ->performedOn($lead)
                ->causedBy(auth()->user())
                ->log(__('activity.lead_converted', ['title' => $lead->title]));

            activity('commercial')
                ->event('created')
                ->performedOn($opp)
                ->causedBy(auth()->user())
                ->log(__('activity.opportunity_converted_from_lead', ['lead' => $lead->title, 'title' => $opp->title]));

            return $opp;
        });

        return response()->json([
            'success' => true,
            'message' => __('messages.opportunity_converted_success'),
            'data' => $opportunity->fresh(['customer', 'lead', 'assignedUser', 'creator']),
        ], 201);
    }

    /**
     * Assign lead to a specific tenant user.
     */
    public function assign(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission('leads.assign');

        $validated = $request->validate([
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
        ]);

        $lead = Lead::findOrFail($id);
        $lead->assigned_to = $validated['assigned_to'];
        $lead->save();

        $assignedUser = User::find($validated['assigned_to']);

        activity('commercial')
            ->event('updated')
            ->performedOn($lead)
            ->causedBy(auth()->user())
            ->log(__('activity.lead_assigned', ['title' => $lead->title, 'user' => $assignedUser?->name]));

        return response()->json([
            'success' => true,
            'message' => __('messages.lead_assigned_success'),
            'data' => $lead->fresh($this->with),
        ]);
    }

    /**
     * Qualify and enrich lead with commercial specifications and pipeline progression.
     */
    public function qualify(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission(['leads.update', 'leads.convert']);

        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:New,Contacted,Qualified,Unqualified,Converted,Lost'],
            'loss_reason' => ['nullable', 'string', 'max:100'],
            'competitor_name' => ['nullable', 'string', 'max:200'],
            'loss_notes' => ['nullable', 'string', 'max:2000'],
            'estimated_value' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'expected_start_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $lead = Lead::findOrFail($id);
        $lead->fill(array_filter($validated, fn($val) => $val !== null));
        $lead->save();

        activity('commercial')
            ->event('updated')
            ->performedOn($lead)
            ->causedBy(auth()->user())
            ->log(__('activity.lead_qualified', ['title' => $lead->title, 'status' => $lead->status]));

        return response()->json([
            'success' => true,
            'message' => __('messages.lead_qualified_success'),
            'data' => $lead->fresh($this->with),
        ]);
    }

    protected function beforeSave(Model $model, Request $request, bool $isUpdate): void
    {
        /** @var Lead $model */
        if (!$isUpdate) {
            if (empty($model->created_by)) {
                $model->created_by = auth()->id();
            }
            if (empty($model->status)) {
                $model->status = 'New';
            }
            if (empty($model->source)) {
                $model->source = 'Other';
            }
        }
    }

    protected function customValidationRules(bool $isUpdate = false, mixed $currentId = null): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'source' => ['nullable', 'string', 'in:Facebook,Instagram,Google,Website,WhatsApp,Referral,Phone,Walk-in,Other'],
            'status' => ['nullable', 'string', 'in:New,Contacted,Qualified,Unqualified,Converted,Lost'],
            'loss_reason' => ['nullable', 'string', 'max:100'],
            'competitor_name' => ['nullable', 'string', 'max:200'],
            'loss_notes' => ['nullable', 'string', 'max:2000'],
            'estimated_value' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'expected_start_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'documents' => ['nullable'],
            'documents.*' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    protected function afterSave(Model $model, Request $request, bool $isUpdate): void
    {
        /** @var Lead $model */
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
