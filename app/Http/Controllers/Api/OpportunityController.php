<?php

namespace App\Http\Controllers\Api;

use App\Core\CRUD\CRUDController;
use App\Core\CRUD\Field;
use App\Core\CRUD\InputMaker;
use App\Models\Boq;
use App\Models\Customer;
use App\Models\Estimation;
use App\Models\Lead;
use App\Models\Measurement;
use App\Models\Opportunity;
use App\Models\Quotation;
use App\Models\Scope;
use App\Models\SiteVisit;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;
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
    protected array $with = [
        'customer',
        'lead',
        'assignedUser',
        'creator',
        'siteVisits.assignedUser',
        'siteVisits.rooms',
        'measurements.measuredUser',
    ];
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
            $model->stage = 'New';
        }
    }

    protected function customValidationRules(bool $isUpdate = false, mixed $currentId = null): array
    {
        $rules = [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'lead_id' => ['nullable', 'integer', 'exists:leads,id'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'loss_reason' => ['nullable', 'string', 'max:100'],
            'competitor_name' => ['nullable', 'string', 'max:200'],
            'loss_notes' => ['nullable', 'string', 'max:2000'],
            'estimated_value' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'expected_start_date' => ['nullable', 'date'],
            'expected_close_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'stage' => ['nullable', 'string', 'in:New,Qualified,Proposal,Negotiation,Won,Lost'],
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

    /**
     * Commercial & Technical Chain Traceability for a specific Opportunity.
     * Full vertical visibility: Customer -> Lead -> Opportunity -> Site Visits -> Measurements -> Scopes.
     */
    public function chain(int|string $id): JsonResponse
    {
        $this->authorizePermission('opportunities.view');

        $opportunity = Opportunity::with([
            'customer',
            'lead.assignedUser',
            'assignedUser',
            'creator',
            'siteVisits' => function ($q) {
                $q->with(['assignedUser', 'creator', 'rooms'])->orderBy('scheduled_date', 'desc')->orderBy('id', 'desc');
            },
            'measurements' => function ($q) {
                $q->with(['measuredUser', 'approvedUser', 'items'])->orderBy('version', 'desc')->orderBy('id', 'desc');
            },
            'approvedMeasurement.items',
            'scopes' => function ($q) {
                $q->with([
                    'preparedUser',
                    'reviewedUser',
                    'approvedUser',
                    'measurement',
                    'items.measurementItems',
                ])->orderBy('version', 'desc')->orderBy('id', 'desc');
            },
            'approvedScope.items.measurementItems',
            'boqs' => function ($q) {
                $q->with([
                    'preparedUser',
                    'approvedUser',
                    'scope.measurement',
                    'items.scopeItem',
                ])->orderBy('version', 'desc')->orderBy('id', 'desc');
            },
            'estimations' => function ($q) {
                $q->with([
                    'preparedUser',
                    'approvedUser',
                    'boq',
                    'items.costs',
                ])->orderBy('version', 'desc')->orderBy('id', 'desc');
            },
            'quotations' => function ($q) {
                $q->with([
                    'preparedUser',
                    'approvedUser',
                    'sentUser',
                    'items',
                ])->orderBy('version', 'desc')->orderBy('id', 'desc');
            },
        ])->findOrFail($id);

        // 1. Gather all entity IDs for the unified activity history
        $oppIds = [$opportunity->id];
        $customerIds = array_filter([$opportunity->customer_id]);
        $leadIds = array_filter([$opportunity->lead_id]);
        $visitIds = $opportunity->siteVisits->pluck('id')->all();
        $measurementIds = $opportunity->measurements->pluck('id')->all();
        $scopeIds = $opportunity->scopes->pluck('id')->all();
        $boqIds = $opportunity->boqs->pluck('id')->all();
        $estimationIds = $opportunity->estimations->pluck('id')->all();
        $quotationIds = $opportunity->quotations->pluck('id')->all();

        $timeline = Activity::with('causer')
            ->where(function ($q) use ($oppIds, $customerIds, $leadIds, $visitIds, $measurementIds, $scopeIds, $boqIds, $estimationIds, $quotationIds) {
                $q->where(function ($sub) use ($oppIds) {
                    $sub->where('subject_type', Opportunity::class)->whereIn('subject_id', $oppIds);
                });
                if (!empty($customerIds)) {
                    $q->orWhere(function ($sub) use ($customerIds) {
                        $sub->where('subject_type', Customer::class)->whereIn('subject_id', $customerIds);
                    });
                }
                if (!empty($leadIds)) {
                    $q->orWhere(function ($sub) use ($leadIds) {
                        $sub->where('subject_type', Lead::class)->whereIn('subject_id', $leadIds);
                    });
                }
                if (!empty($visitIds)) {
                    $q->orWhere(function ($sub) use ($visitIds) {
                        $sub->where('subject_type', SiteVisit::class)->whereIn('subject_id', $visitIds);
                    });
                }
                if (!empty($measurementIds)) {
                    $q->orWhere(function ($sub) use ($measurementIds) {
                        $sub->where('subject_type', Measurement::class)->whereIn('subject_id', $measurementIds);
                    });
                }
                if (!empty($scopeIds)) {
                    $q->orWhere(function ($sub) use ($scopeIds) {
                        $sub->where('subject_type', Scope::class)->whereIn('subject_id', $scopeIds);
                    });
                }
                if (!empty($boqIds)) {
                    $q->orWhere(function ($sub) use ($boqIds) {
                        $sub->where('subject_type', Boq::class)->whereIn('subject_id', $boqIds);
                    });
                }
                if (!empty($estimationIds)) {
                    $q->orWhere(function ($sub) use ($estimationIds) {
                        $sub->where('subject_type', Estimation::class)->whereIn('subject_id', $estimationIds);
                    });
                }
                if (!empty($quotationIds)) {
                    $q->orWhere(function ($sub) use ($quotationIds) {
                        $sub->where('subject_type', Quotation::class)->whereIn('subject_id', $quotationIds);
                    });
                }
            })
            ->latest('id')
            ->limit(30)
            ->get()
            ->map(function ($act) {
                return [
                    'id' => $act->id,
                    'description' => $act->description,
                    'event' => $act->event,
                    'subject_type' => class_basename($act->subject_type),
                    'subject_id' => $act->subject_id,
                    'causer_name' => $act->causer?->name ?? 'النظام',
                    'created_at' => $act->created_at?->toIso8601String(),
                ];
            });

        // 2. Compute Next Logical Action & Chain Status
        $approvedMeasurement = $opportunity->approvedMeasurement;
        $approvedScope = $opportunity->approvedScope;
        // Resolved from the already eager-loaded collection (no extra queries).
        $approvedBoq = $opportunity->boqs->firstWhere('status', 'Approved');
        $latestBoq = $opportunity->boqs->sortBy([['version', 'desc'], ['id', 'desc']])->first();
        $hasVisits = $opportunity->siteVisits->isNotEmpty();
        $hasMeasurements = $opportunity->measurements->isNotEmpty();
        $hasScopes = $opportunity->scopes->isNotEmpty();

        // Stale BOQ detection: an approved BOQ exists, but it references an older/superseded scope revision
        $isBoqStale = (bool) ($approvedBoq && $approvedScope && (int) $approvedBoq->scope_id !== (int) $approvedScope->id);
        $isBoqCurrent = (bool) ($approvedBoq && $approvedScope && (int) $approvedBoq->scope_id === (int) $approvedScope->id);

        // BOQs associated with the currently approved scope
        $boqsForCurrentScope = $approvedScope
            ? $opportunity->boqs->where('scope_id', $approvedScope->id)
            : collect();
        $latestBoqForCurrentScope = $boqsForCurrentScope->sortBy([['version', 'desc'], ['id', 'desc']])->first();
        $approvedBoqForCurrentScope = $boqsForCurrentScope->firstWhere('status', 'Approved');

        // Resolved estimations from eager-loaded collection
        $approvedEstimation = $opportunity->estimations->firstWhere('status', 'Approved');
        $latestEstimation = $opportunity->estimations->sortBy([['version', 'desc'], ['id', 'desc']])->first();

        // Stale Estimation detection: an approved estimation exists, but it references an older/superseded BOQ revision
        $isEstimationStale = (bool) ($approvedEstimation && $approvedBoqForCurrentScope && (int) $approvedEstimation->boq_id !== (int) $approvedBoqForCurrentScope->id);
        $isEstimationCurrent = (bool) ($approvedEstimation && $approvedBoqForCurrentScope && (int) $approvedEstimation->boq_id === (int) $approvedBoqForCurrentScope->id);

        // Estimations associated with the currently approved BOQ
        $estimationsForCurrentBoq = $approvedBoqForCurrentScope
            ? $opportunity->estimations->where('boq_id', $approvedBoqForCurrentScope->id)
            : collect();
        $latestEstimationForCurrentBoq = $estimationsForCurrentBoq->sortBy([['version', 'desc'], ['id', 'desc']])->first();
        $approvedEstimationForCurrentBoq = $estimationsForCurrentBoq->firstWhere('status', 'Approved');

        // Resolved quotations from eager-loaded collection
        $approvedQuotation = $opportunity->quotations->firstWhere('status', 'Approved');
        $latestQuotation = $opportunity->quotations->sortBy([['version', 'desc'], ['id', 'desc']])->first();

        // Quotations associated with the currently approved Estimation
        $quotationsForCurrentEst = $approvedEstimationForCurrentBoq
            ? $opportunity->quotations->where('estimation_id', $approvedEstimationForCurrentBoq->id)
            : collect();
        $latestQuotationForCurrentEst = $quotationsForCurrentEst->sortBy([['version', 'desc'], ['id', 'desc']])->first();
        $approvedQuotationForCurrentEst = $quotationsForCurrentEst->firstWhere('status', 'Approved');

        // Stale Quotation detection: an approved quotation exists, but it references an older/superseded estimation revision
        $isQuotationStale = (bool) ($approvedQuotation && $approvedEstimationForCurrentBoq && (int) $approvedQuotation->estimation_id !== (int) $approvedEstimationForCurrentBoq->id);
        $isQuotationCurrent = (bool) ($approvedQuotation && $approvedEstimationForCurrentBoq && (int) $approvedQuotation->estimation_id === (int) $approvedEstimationForCurrentBoq->id);

        $nextAction = null;
        if (!$hasVisits) {
            $nextAction = [
                'stage' => 'site_visit',
                'key' => 'schedule_site_visit',
                'title' => 'جدولة أول معاينة ميدانية للموقع',
                'description' => 'لم يتم تسجيل أي معاينة ميدانية بعد لهذه الفرصة. الخطوة المنطقية التالية هي تحديد موعد لمعاينة الموقع وفحص الواقع على الطبيعة.',
                'action_label' => '+ جدولة موعد معاينة',
                'action_type' => 'route',
                'action_target' => '/site-visits',
                'permission' => 'site_visits.create',
            ];
        } elseif (!$hasMeasurements) {
            $nextAction = [
                'stage' => 'measurement',
                'key' => 'create_measurement',
                'title' => 'إعداد المقايسة والحصر الهندسي',
                'description' => 'تمت المعاينة الميدانية بنجاح. الخطوة التالية هي تحويل المشاهدات والأبعاد إلى حصر هندسي ومقايسة فنية دقيقة.',
                'action_label' => '+ إنشاء مقايسة هندسية',
                'action_type' => 'route',
                'action_target' => '/measurements',
                'permission' => 'measurements.create',
            ];
        } elseif (!$approvedMeasurement) {
            $underReviewM = $opportunity->measurements->firstWhere('status', 'Under Review');
            if ($underReviewM) {
                $nextAction = [
                    'stage' => 'measurement',
                    'key' => 'approve_measurement',
                    'title' => 'استكمال واعتماد المراجعة الفنية للمقايسة',
                    'description' => 'توجد مقايسة قيد المراجعة الفنية. يجب اعتمادها رسمياً لتكون المصدر الموثوق الوحيد للكميات قبل الانتقال لتحديد نطاق الأعمال.',
                    'action_label' => 'مراجعة واعتماد المقايسة',
                    'action_type' => 'route',
                    'action_target' => '/measurements',
                    'permission' => 'measurements.approve',
                ];
            } else {
                $nextAction = [
                    'stage' => 'measurement',
                    'key' => 'submit_measurement_for_review',
                    'title' => 'تقديم المقايسة للمراجعة الفنية',
                    'description' => 'المقايسة الحالية مسودة (Draft). الخطوة التالية هي مراجعة الأبعاد وتدقيق الخصومات وتقديمها للمراجعة الفنية.',
                    'action_label' => 'استعراض وتقديم المقايسة',
                    'action_type' => 'route',
                    'action_target' => '/measurements',
                    'permission' => 'measurements.edit',
                ];
            }
        } elseif (!$hasScopes) {
            $nextAction = [
                'stage' => 'scope',
                'key' => 'create_scope',
                'title' => 'إعداد وثيقة نطاق الأعمال (Scope of Work)',
                'description' => 'تم اعتماد المقايسة الهندسية بنجاح! الخطوة التالية هي توصيف حزم الأعمال وطريقة التنفيذ والاشتمالات والاستثناءات بناءً على الكميات المعتمدة.',
                'action_label' => '+ إعداد نطاق الأعمال',
                'action_type' => 'route',
                'action_target' => '/scopes',
                'permission' => 'scopes.create',
            ];
        } elseif (!$approvedScope) {
            $underReviewS = $opportunity->scopes->firstWhere('status', 'Under Review');
            if ($underReviewS) {
                $nextAction = [
                    'stage' => 'scope',
                    'key' => 'approve_scope',
                    'title' => 'مراجعة واعتماد وثيقة نطاق الأعمال',
                    'description' => 'وثيقة نطاق الأعمال قيد التدقيق الهندسي. يجب اعتمادها رسمياً لغلق التوصيفات الفنية للعملية.',
                    'action_label' => 'مراجعة واعتماد النطاق',
                    'action_type' => 'route',
                    'action_target' => '/scopes',
                    'permission' => 'scopes.approve',
                ];
            } else {
                $nextAction = [
                    'stage' => 'scope',
                    'key' => 'submit_scope_for_review',
                    'title' => 'استكمال وتقديم نطاق الأعمال للمراجعة',
                    'description' => 'وثيقة نطاق الأعمال ما زالت في حالة مسودة (Draft). يجب ربط البنود وتقديمها للاعتماد الفني.',
                    'action_label' => 'استكمال وتقديم النطاق',
                    'action_type' => 'route',
                    'action_target' => '/scopes',
                    'permission' => 'scopes.edit',
                ];
            }
        } elseif (!$latestBoqForCurrentScope) {
            $nextAction = [
                'stage' => 'boq',
                'key' => 'create_boq',
                'title' => $isBoqStale
                    ? 'إعداد جدول الكميات لنطاق الأعمال الجديد المعتمد'
                    : 'إعداد جدول الكميات (BOQ)',
                'description' => $isBoqStale
                    ? "تم اعتماد إصدار جديد من نطاق الأعمال (V{$approvedScope->version}). جدول الكميات المعتمد الحالي (#{$approvedBoq->boq_number}) مرتبط بإصدار سابق، ويجب توليد جدول كميات جديد ليعكس التوصيف والكميات المحدثة."
                    : 'تم اعتماد نطاق الأعمال بنجاح! الخطوة التالية هي توليد جدول الكميات الرسمي القابل للتسعير من بنود النطاق المعتمد وكمياته.',
                'action_label' => '+ إنشاء جدول الكميات',
                'action_type' => 'route',
                'action_target' => '/boqs',
                'permission' => 'boqs.create',
            ];
        } elseif ($latestBoqForCurrentScope->status === 'Draft') {
            $nextAction = [
                'stage' => 'boq',
                'key' => 'submit_boq_for_review',
                'title' => 'استكمال وتقديم جدول الكميات للمراجعة',
                'description' => 'جدول الكميات الحالي مسودة (Draft). الخطوة التالية هي مراجعة البنود والكميات المولدة وتقديمه للمراجعة الفنية.',
                'action_label' => 'استعراض وتقديم جدول الكميات',
                'action_type' => 'route',
                'action_target' => '/boqs',
                'permission' => 'boqs.update',
            ];
        } elseif ($latestBoqForCurrentScope->status === 'Under Review') {
            $nextAction = [
                'stage' => 'boq',
                'key' => 'approve_boq',
                'title' => 'مراجعة واعتماد جدول الكميات',
                'description' => 'جدول الكميات قيد التدقيق الهندسي. يجب اعتماده رسمياً ليصبح وثيقة الكميات القابلة للتسعير في مرحلة Estimation.',
                'action_label' => 'مراجعة واعتماد جدول الكميات',
                'action_type' => 'route',
                'action_target' => '/boqs',
                'permission' => 'boqs.approve',
            ];
        } elseif (!$latestEstimationForCurrentBoq) {
            $nextAction = [
                'stage' => 'estimation',
                'key' => $isEstimationStale ? 'create_estimation' : 'ready_for_estimation',
                'title' => $isEstimationStale
                    ? 'إعداد دراسة تسعير وتكلفة لجدول الكميات الجديد المعتمد'
                    : 'إعداد دراسة التسعير والتكلفة (Estimation)',
                'description' => $isEstimationStale
                    ? "تم اعتماد إصدار جديد من جدول الكميات (#{$approvedBoqForCurrentScope->boq_number}). دراسة التسعير المعتمدة الحالية (#{$approvedEstimation->estimation_number}) مرتبطة بإصدار سابق، ويجب إعداد دراسة تسعير جديدة للكميات المحدثة."
                    : 'تم اعتماد جدول الكميات بنجاح وكمياته مجمدة! الخطوة التالية هي إعداد دراسة التكاليف المباشرة والعمومية (Estimation) لتحديد التكلفة الفعلية للأعمال.',
                'action_label' => '+ إعداد دراسة التسعير',
                'action_type' => 'route',
                'action_target' => '/estimations',
                'permission' => 'estimations.create',
            ];
        } elseif ($latestEstimationForCurrentBoq->status === 'Draft') {
            $nextAction = [
                'stage' => 'estimation',
                'key' => 'submit_estimation_for_review',
                'title' => 'استكمال وتقديم دراسة التسعير للمراجعة',
                'description' => 'دراسة التسعير الحالية مسودة (Draft). الخطوة التالية هي استكمال تقدير أسعار التكلفة والمصاريف العمومية وتقديمها للمراجعة والاعتماد.',
                'action_label' => 'استعراض وتقديم دراسة التسعير',
                'action_type' => 'route',
                'action_target' => '/estimations',
                'permission' => 'estimations.update',
            ];
        } elseif ($latestEstimationForCurrentBoq->status === 'Under Review') {
            $nextAction = [
                'stage' => 'estimation',
                'key' => 'approve_estimation',
                'title' => 'مراجعة واعتماد دراسة التسعير والتكلفة',
                'description' => 'دراسة التسعير قيد التدقيق الإداري والمالي. يجب اعتمادها رسمياً لتكون الأساس المالي المعتمد لعرض السعر (Quotation).',
                'action_label' => 'مراجعة واعتماد دراسة التسعير',
                'action_type' => 'route',
                'action_target' => '/estimations',
                'permission' => 'estimations.approve',
            ];
        } elseif (!$latestQuotationForCurrentEst) {
            $nextAction = [
                'stage' => 'quotation',
                'key' => $isQuotationStale ? 'create_quotation' : 'ready_for_quotation',
                'title' => $isQuotationStale
                    ? 'إعداد عرض سعر جديد لدراسة التكلفة المحدثة المعتمدة'
                    : 'إعداد عرض السعر للعميل (Quotation)',
                'description' => $isQuotationStale
                    ? "تم اعتماد إصدار جديد من دراسة التكلفة (#{$approvedEstimationForCurrentBoq->estimation_number}). عرض السعر المعتمد الحالي (#{$approvedQuotation->quotation_number}) مبني على دراسة سابقة، ويجب إعداد عرض سعر محدث."
                    : 'اكتملت دراسة التكاليف والكميات بنجاح وتم اعتماد التسعير الداخلي! الخطوة التالية هي تحديد هامش الربح والخصومات وإعداد عرض السعر الرسمي للعميل.',
                'action_label' => '+ إنشاء عرض سعر',
                'action_type' => 'route',
                'action_target' => '/quotations',
                'permission' => 'quotations.create',
            ];
        } elseif ($latestQuotationForCurrentEst->status === 'Draft') {
            $nextAction = [
                'stage' => 'quotation',
                'key' => 'submit_quotation_for_review',
                'title' => 'استكمال وتقديم عرض السعر للمراجعة الداخلية',
                'description' => 'عرض السعر الحالي مسودة (Draft). الخطوة التالية هي مراجعة نسب الهامش والخصومات والشروط التجارية وتقديمه للمراجعة الداخلية.',
                'action_label' => 'استعراض وتقديم عرض السعر',
                'action_type' => 'route',
                'action_target' => '/quotations',
                'permission' => 'quotations.update',
            ];
        } elseif ($latestQuotationForCurrentEst->status === 'Under Review') {
            $nextAction = [
                'stage' => 'quotation',
                'key' => 'approve_quotation',
                'title' => 'مراجعة واعتماد عرض السعر داخلياً',
                'description' => 'عرض السعر قيد التدقيق الإداري والتجاري. يجب اعتماده داخلياً قبل الإرسال الرسمي للعميل.',
                'action_label' => 'مراجعة واعتماد عرض السعر',
                'action_type' => 'route',
                'action_target' => '/quotations',
                'permission' => 'quotations.approve',
            ];
        } elseif ($latestQuotationForCurrentEst->client_status === 'Not Sent') {
            $nextAction = [
                'stage' => 'quotation',
                'key' => 'send_quotation',
                'title' => 'إرسال عرض السعر المعتمد للعميل',
                'description' => 'تم اعتماد عرض السعر داخلياً بنجاح! الخطوة التالية هي تصدير العرض وإرساله رسمياً للعميل ومتابعة استلامه.',
                'action_label' => 'إرسال العرض للعميل',
                'action_type' => 'route',
                'action_target' => '/quotations',
                'permission' => 'quotations.send',
            ];
        } elseif ($latestQuotationForCurrentEst->client_status === 'Sent') {
            $nextAction = [
                'stage' => 'quotation',
                'key' => 'record_client_response',
                'title' => 'متابعة وتسجيل رد العميل على عرض السعر',
                'description' => 'تم إرسال العرض للعميل وهو قيد الدراسة من طرفه. الخطوة التالية هي تسجيل رد العميل الرسمي (قبول أو رفض) مع ملاحظاته.',
                'action_label' => 'تسجيل رد العميل',
                'action_type' => 'route',
                'action_target' => '/quotations',
                'permission' => 'quotations.update',
            ];
        } elseif ($latestQuotationForCurrentEst->client_status === 'Accepted') {
            $nextAction = [
                'stage' => 'contract',
                'key' => 'ready_for_contract',
                'title' => 'تمت موافقة العميل! جاهز لإعداد العقد (Ready for Contract)',
                'description' => 'وافق العميل رسمياً على عرض السعر! العملية التجارية اكتملت بنجاح والخطوة التالية هي صياغة وتوقيع العقد الرسمي (Contract).',
                'action_label' => null,
                'action_type' => 'info',
                'action_target' => null,
                'permission' => null,
            ];
        } else {
            // Rejected or Expired
            $nextAction = [
                'stage' => 'quotation',
                'key' => 'quotation_rejected_or_expired',
                'title' => 'عرض السعر بحاجة لمراجعة أو تجديد',
                'description' => 'حالة عرض السعر لدى العميل هي ' . $latestQuotationForCurrentEst->client_status . '. يمكنك إصدار نسخة مراجعة جديدة أو التفاوض على شروط جديدة.',
                'action_label' => 'استعراض عروض الأسعار',
                'action_type' => 'route',
                'action_target' => '/quotations',
                'permission' => 'quotations.view',
            ];
        }

        // 3. Traceability Matrix between Scopes and Measurement Items
        $traceabilityMatrix = $opportunity->scopes->map(function ($scope) use ($approvedMeasurement) {
            $isReferencingApproved = $approvedMeasurement && $scope->measurement_id === $approvedMeasurement->id;
            return [
                'scope_id' => $scope->id,
                'scope_number' => $scope->scope_number,
                'version' => $scope->version,
                'status' => $scope->status,
                'referenced_measurement' => [
                    'id' => $scope->measurement_id,
                    'measurement_number' => $scope->measurement?->measurement_number,
                    'version' => $scope->measurement?->version,
                    'status' => $scope->measurement?->status,
                    'is_approved_current' => $isReferencingApproved,
                    'is_historical_superseded' => $scope->measurement?->status === 'Superseded',
                ],
                'items' => $scope->items->map(function ($scopeItem) {
                    return [
                        'id' => $scopeItem->id,
                        'trade_category' => $scopeItem->trade_category,
                        'item_name' => $scopeItem->item_name,
                        'specification' => $scopeItem->specification,
                        'inclusions' => $scopeItem->inclusions,
                        'exclusions' => $scopeItem->exclusions,
                        'linked_measurements' => $scopeItem->measurementItems->map(function ($mItem) {
                            return [
                                'id' => $mItem->id,
                                'room_name' => $mItem->room_name,
                                'item_name' => $mItem->item_name,
                                'net_quantity' => (float)$mItem->net_quantity,
                                'unit' => $mItem->unit,
                                'measurement_type' => $mItem->measurement_type,
                            ];
                        }),
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'opportunity' => $opportunity,
                'customer' => $opportunity->customer,
                'lead' => $opportunity->lead,
                'site_visits' => $opportunity->siteVisits,
                'measurements' => $opportunity->measurements,
                'approved_measurement' => $approvedMeasurement,
                'scopes' => $opportunity->scopes,
                'approved_scope' => $approvedScope,
                'boqs' => $opportunity->boqs,
                'approved_boq' => $approvedBoq,
                'estimations' => $opportunity->estimations,
                'approved_estimation' => $approvedEstimationForCurrentBoq ?? $approvedEstimation,
                'quotations' => $opportunity->quotations,
                'approved_quotation' => $approvedQuotationForCurrentEst ?? $approvedQuotation,
                'traceability_matrix' => $traceabilityMatrix,
                'current_status' => [
                    'commercial' => [
                        'stage' => $opportunity->stage,
                        'is_qualified' => in_array($opportunity->stage, ['Qualified', 'Proposal', 'Negotiation', 'Won']),
                    ],
                    'technical' => [
                        'has_approved' => (bool)$approvedMeasurement,
                        'status' => $approvedMeasurement ? 'Approved' : ($opportunity->measurements->first()?->status ?? 'None'),
                        'version' => $approvedMeasurement ? "V{$approvedMeasurement->version}" : null,
                        'code' => $approvedMeasurement?->measurement_number,
                        'total_area' => $approvedMeasurement ? (float)$approvedMeasurement->total_area : 0,
                        'items_count' => $approvedMeasurement ? $approvedMeasurement->items->count() : 0,
                    ],
                    'scope' => [
                        'has_approved' => (bool)$approvedScope,
                        'status' => $approvedScope ? 'Approved' : ($opportunity->scopes->first()?->status ?? 'None'),
                        'version' => $approvedScope ? "V{$approvedScope->version}" : null,
                        'code' => $approvedScope?->scope_number,
                        'items_count' => $approvedScope ? $approvedScope->items->count() : 0,
                        'referenced_measurement_version' => $approvedScope && $approvedScope->measurement 
                            ? "V{$approvedScope->measurement->version}" 
                            : null,
                    ],
                    'boq' => [
                        'has_approved' => (bool)$approvedBoq,
                        'status' => $approvedBoq ? 'Approved' : ($latestBoq?->status ?? 'None'),
                        'version' => $approvedBoq ? "V{$approvedBoq->version}" : null,
                        'code' => $approvedBoq?->boq_number,
                        'items_count' => $approvedBoq ? $approvedBoq->items->count() : 0,
                        'referenced_scope_version' => $approvedBoq && $approvedBoq->scope
                            ? "V{$approvedBoq->scope->version}"
                            : null,
                        'referenced_measurement_version' => $approvedBoq && $approvedBoq->scope?->measurement
                            ? "V{$approvedBoq->scope->measurement->version}"
                            : null,
                        'is_stale' => $isBoqStale,
                        'is_current' => $isBoqCurrent,
                    ],
                    'estimation' => [
                        'has_approved' => (bool)$approvedEstimationForCurrentBoq,
                        'status' => $approvedEstimationForCurrentBoq ? 'Approved' : ($latestEstimationForCurrentBoq?->status ?? ($latestEstimation?->status ?? 'None')),
                        'version' => $approvedEstimationForCurrentBoq ? "V{$approvedEstimationForCurrentBoq->version}" : ($latestEstimation ? "V{$latestEstimation->version}" : null),
                        'code' => $approvedEstimationForCurrentBoq?->estimation_number ?? $latestEstimation?->estimation_number,
                        'total_cost' => $approvedEstimationForCurrentBoq ? (float)$approvedEstimationForCurrentBoq->total_cost : ($latestEstimation ? (float)$latestEstimation->total_cost : 0),
                        'direct_cost' => $approvedEstimationForCurrentBoq ? (float)$approvedEstimationForCurrentBoq->direct_cost : ($latestEstimation ? (float)$latestEstimation->direct_cost : 0),
                        'overhead_amount' => $approvedEstimationForCurrentBoq ? (float)$approvedEstimationForCurrentBoq->overhead_amount : ($latestEstimation ? (float)$latestEstimation->overhead_amount : 0),
                        'items_count' => $approvedEstimationForCurrentBoq ? $approvedEstimationForCurrentBoq->items->count() : ($latestEstimation ? $latestEstimation->items->count() : 0),
                        'referenced_boq_version' => $approvedEstimationForCurrentBoq && $approvedEstimationForCurrentBoq->boq
                            ? "V{$approvedEstimationForCurrentBoq->boq->version}"
                            : ($latestEstimation && $latestEstimation->boq ? "V{$latestEstimation->boq->version}" : null),
                        'is_stale' => $isEstimationStale,
                        'is_current' => $isEstimationCurrent,
                    ],
                    'quotation' => [
                        'has_approved' => (bool)$approvedQuotationForCurrentEst,
                        'status' => $approvedQuotationForCurrentEst ? 'Approved' : ($latestQuotationForCurrentEst?->status ?? ($latestQuotation?->status ?? 'None')),
                        'client_status' => $approvedQuotationForCurrentEst ? $approvedQuotationForCurrentEst->client_status : ($latestQuotation?->client_status ?? 'Not Sent'),
                        'version' => $approvedQuotationForCurrentEst ? "V{$approvedQuotationForCurrentEst->version}" : ($latestQuotation ? "V{$latestQuotation->version}" : null),
                        'code' => $approvedQuotationForCurrentEst?->quotation_number ?? $latestQuotation?->quotation_number,
                        'grand_total' => $approvedQuotationForCurrentEst ? (float)$approvedQuotationForCurrentEst->grand_total : ($latestQuotation ? (float)$latestQuotation->grand_total : 0),
                        'subtotal' => $approvedQuotationForCurrentEst ? (float)$approvedQuotationForCurrentEst->subtotal : ($latestQuotation ? (float)$latestQuotation->subtotal : 0),
                        'items_count' => $approvedQuotationForCurrentEst ? $approvedQuotationForCurrentEst->items->count() : ($latestQuotation ? $latestQuotation->items->count() : 0),
                        'is_stale' => $isQuotationStale,
                        'is_current' => $isQuotationCurrent,
                    ],
                    'is_complete' => (bool)($approvedMeasurement && $approvedScope),
                ],
                'next_action' => $nextAction,
                'timeline' => $timeline,
            ],
        ]);
    }
}
