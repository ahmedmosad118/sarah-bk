<?php

namespace App\Http\Controllers\Api;

use App\Core\CRUD\CRUDController;
use App\Core\CRUD\Field;
use App\Core\CRUD\InputMaker;
use App\Models\Measurement;
use App\Models\MeasurementItem;
use App\Models\Opportunity;
use App\Models\SiteVisit;
use App\Models\User;
use App\Services\DxfParserService;
use App\Services\MeasurementCalculationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MeasurementController extends CRUDController
{
    protected string $model = Measurement::class;
    protected array $searchable = ['measurement_number', 'notes'];
    protected array $with = ['opportunity.customer', 'siteVisit', 'measuredUser', 'reviewedUser', 'approvedUser', 'creator', 'items', 'media'];
    protected string $defaultSortBy = 'id';
    protected string $defaultSortOrder = 'desc';

    protected MeasurementCalculationService $calculationService;
    protected DxfParserService $dxfParserService;

    public function __construct(
        MeasurementCalculationService $calculationService,
        DxfParserService $dxfParserService
    ) {
        $this->calculationService = $calculationService;
        $this->dxfParserService = $dxfParserService;
    }

    protected function inputMaker(): InputMaker
    {
        return InputMaker::make()
            ->title('measurements.title')
            ->singularTitle('measurements.singular')
            ->model(Measurement::class)
            ->fields([
                Field::select('opportunity_id', 'measurements.opportunity', [])
                    ->required()
                    ->col(6),

                Field::select('site_visit_id', 'measurements.siteVisit', [])
                    ->col(6),

                Field::text('measurement_number', 'measurements.measurementNumber')
                    ->col(6),

                Field::number('version', 'measurements.version')
                    ->default(1)
                    ->col(6),

                Field::select('status', 'measurements.status', [
                    ['value' => 'Draft', 'label' => 'measurements.statusDraft'],
                    ['value' => 'Under Review', 'label' => 'measurements.statusUnderReview'],
                    ['value' => 'Approved', 'label' => 'measurements.statusApproved'],
                    ['value' => 'Superseded', 'label' => 'measurements.statusSuperseded'],
                ])->default('Draft')->col(6),

                Field::select('measured_by', 'measurements.measuredBy', [])
                    ->col(6),

                Field::date('measured_at', 'measurements.measuredAt')
                    ->col(6),

                Field::textarea('notes', 'measurements.notes')
                    ->col(12),
            ]);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission('measurements.view');

        $query = $this->query();

        // 1. Search Query
        if ($search = $request->input('search')) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('measurement_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('opportunity', function (Builder $oq) use ($search) {
                        $oq->where('title', 'like', "%{$search}%")
                            ->orWhereHas('customer', function (Builder $cq) use ($search) {
                                $cq->where('name', 'like', "%{$search}%")
                                    ->orWhere('company_name', 'like', "%{$search}%");
                            });
                    })
                    ->orWhereHas('items', function (Builder $iq) use ($search) {
                        $iq->where('room_name', 'like', "%{$search}%")
                            ->orWhere('item_name', 'like', "%{$search}%")
                            ->orWhere('notes', 'like', "%{$search}%");
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

        if ($request->filled('site_visit_id')) {
            $query->where('site_visit_id', $request->site_visit_id);
        }

        if ($request->filled('measured_by')) {
            $query->where('measured_by', $request->measured_by);
        }

        if ($request->filled('measured_at')) {
            $query->whereDate('measured_at', $request->measured_at);
        }

        // 3. Sorting
        $rawSortBy = (string) $request->input('sort_by', $this->defaultSortBy);
        $allowedSorts = ['id', 'measurement_number', 'version', 'status', 'measured_at', 'total_area', 'created_at'];
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
            'total' => Measurement::count(),
            'draft' => Measurement::where('status', 'Draft')->count(),
            'under_review' => Measurement::where('status', 'Under Review')->count(),
            'approved' => Measurement::where('status', 'Approved')->count(),
            'superseded' => Measurement::where('status', 'Superseded')->count(),
            'total_measured_area' => (float) Measurement::where('status', 'Approved')->sum('total_area'),
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
        $this->authorizePermission('measurements.view');
        return parent::show($id);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission('measurements.create');

        $validated = $request->validate([
            'opportunity_id' => ['required', 'integer', 'exists:opportunities,id'],
            'site_visit_id' => ['nullable', 'integer', 'exists:site_visits,id'],
            'measurement_number' => ['nullable', 'string', 'max:50'],
            'version' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', 'in:Draft,Under Review,Approved,Superseded'],
            'measured_by' => ['nullable', 'integer', 'exists:users,id'],
            'measured_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['nullable', 'array'],
            'items.*.room_name' => ['required', 'string', 'max:150'],
            'items.*.item_name' => ['required', 'string', 'max:250'],
            'items.*.unit' => ['required', 'string', 'in:m2,m3,lm,pcs,area,volume,linear,count'],
            'items.*.measurement_type' => ['nullable', 'string', 'in:area,volume,linear,count,m2,m3,lm,pcs'],
            'items.*.count' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'items.*.length' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'items.*.width' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'items.*.height' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'items.*.deductions' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'items.*.notes' => ['nullable', 'string', 'max:2000'],
            'items.*.sort_order' => ['nullable', 'integer'],
        ]);

        $measurement = DB::transaction(function () use ($validated, $request) {
            $number = $validated['measurement_number'] ?? $this->generateMeasurementNumber($validated['opportunity_id']);
            $itemsData = $validated['items'] ?? [];

            $calculatedItems = [];
            foreach ($itemsData as $idx => $item) {
                $item['sort_order'] = $item['sort_order'] ?? $idx;
                $calculatedItems[] = $this->calculationService->calculateItem($item);
            }

            $summary = $this->calculationService->calculateSummary($calculatedItems);

            $measurement = Measurement::create([
                'opportunity_id' => $validated['opportunity_id'],
                'site_visit_id' => $validated['site_visit_id'] ?? null,
                'measurement_number' => $number,
                'version' => $validated['version'] ?? 1,
                'status' => $validated['status'] ?? 'Draft',
                'measured_by' => $validated['measured_by'] ?? auth()->id(),
                'measured_at' => $validated['measured_at'] ?? Carbon::today()->toDateString(),
                'total_area' => $summary['total_area'],
                'total_volume' => $summary['total_volume'],
                'total_linear' => $summary['total_linear'],
                'total_count' => $summary['total_count'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($calculatedItems as $calcItem) {
                $measurement->items()->create($calcItem);
            }

            activity('commercial')
                ->event('created')
                ->performedOn($measurement)
                ->causedBy(auth()->user())
                ->log(__('activity.measurement_created', ['number' => $measurement->measurement_number]));

            return $measurement;
        });

        return response()->json([
            'success' => true,
            'message' => __('messages.measurement_created_success'),
            'data' => $measurement->fresh($this->with),
        ], 201);
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission('measurements.update');

        $measurement = Measurement::findOrFail($id);

        if (!$measurement->canBeEdited()) {
            throw ValidationException::withMessages([
                'status' => [__('messages.approved_measurement_cannot_be_edited')],
            ]);
        }

        $validated = $request->validate([
            'site_visit_id' => ['nullable', 'integer', 'exists:site_visits,id'],
            'measurement_number' => ['nullable', 'string', 'max:50'],
            'measured_by' => ['nullable', 'integer', 'exists:users,id'],
            'measured_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['nullable', 'array'],
            'items.*.room_name' => ['required', 'string', 'max:150'],
            'items.*.item_name' => ['required', 'string', 'max:250'],
            'items.*.unit' => ['required', 'string', 'in:m2,m3,lm,pcs,area,volume,linear,count'],
            'items.*.measurement_type' => ['nullable', 'string', 'in:area,volume,linear,count,m2,m3,lm,pcs'],
            'items.*.count' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'items.*.length' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'items.*.width' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'items.*.height' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'items.*.deductions' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'items.*.notes' => ['nullable', 'string', 'max:2000'],
            'items.*.sort_order' => ['nullable', 'integer'],
        ]);

        $measurement = DB::transaction(function () use ($measurement, $validated, $request) {
            if (isset($validated['site_visit_id'])) {
                $measurement->site_visit_id = $validated['site_visit_id'];
            }
            if (!empty($validated['measurement_number'])) {
                $measurement->measurement_number = $validated['measurement_number'];
            }
            if (isset($validated['measured_by'])) {
                $measurement->measured_by = $validated['measured_by'];
            }
            if (isset($validated['measured_at'])) {
                $measurement->measured_at = $validated['measured_at'];
            }
            if (isset($validated['notes'])) {
                $measurement->notes = $validated['notes'];
            }

            if (array_key_exists('items', $validated)) {
                $itemsData = $validated['items'] ?? [];
                $calculatedItems = [];
                foreach ($itemsData as $idx => $item) {
                    $item['sort_order'] = $item['sort_order'] ?? $idx;
                    $calculatedItems[] = $this->calculationService->calculateItem($item);
                }

                $summary = $this->calculationService->calculateSummary($calculatedItems);

                $measurement->total_area = $summary['total_area'];
                $measurement->total_volume = $summary['total_volume'];
                $measurement->total_linear = $summary['total_linear'];
                $measurement->total_count = $summary['total_count'];

                $measurement->items()->delete();
                foreach ($calculatedItems as $calcItem) {
                    $measurement->items()->create($calcItem);
                }
            }

            $measurement->save();

            activity('commercial')
                ->event('updated')
                ->performedOn($measurement)
                ->causedBy(auth()->user())
                ->log(__('activity.measurement_updated', ['number' => $measurement->measurement_number]));

            return $measurement;
        });

        return response()->json([
            'success' => true,
            'message' => __('messages.measurement_updated_success'),
            'data' => $measurement->fresh($this->with),
        ]);
    }

    public function destroy(int|string|Request $ids): JsonResponse
    {
        $this->authorizePermission('measurements.delete');

        $idList = is_array($ids) ? $ids : (is_numeric($ids) ? [(int) $ids] : explode(',', (string) $ids));

        $measurements = Measurement::whereIn('id', $idList)->get();

        foreach ($measurements as $m) {
            if ($m->isApproved()) {
                throw ValidationException::withMessages([
                    'status' => [__('messages.approved_measurement_cannot_be_deleted')],
                ]);
            }
        }

        foreach ($measurements as $m) {
            $number = $m->measurement_number;
            $m->delete();

            activity('commercial')
                ->event('deleted')
                ->performedOn($m)
                ->causedBy(auth()->user())
                ->log(__('activity.measurement_deleted', ['number' => $number]));
        }

        return response()->json([
            'success' => true,
            'message' => __('messages.measurement_deleted_success'),
        ]);
    }

    /**
     * Create a Measurement directly originating from an Opportunity.
     */
    public function createFromOpportunity(Request $request, int|string $opportunityId): JsonResponse
    {
        $this->authorizePermission('measurements.create');

        $opportunity = Opportunity::findOrFail($opportunityId);

        $validated = $request->validate([
            'site_visit_id' => ['nullable', 'integer', 'exists:site_visits,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['nullable', 'array'],
            'items.*.room_name' => ['required', 'string', 'max:150'],
            'items.*.item_name' => ['required', 'string', 'max:250'],
            'items.*.unit' => ['required', 'string', 'in:m2,m3,lm,pcs,area,volume,linear,count'],
            'items.*.count' => ['nullable', 'numeric', 'min:0'],
            'items.*.length' => ['nullable', 'numeric', 'min:0'],
            'items.*.width' => ['nullable', 'numeric', 'min:0'],
            'items.*.height' => ['nullable', 'numeric', 'min:0'],
            'items.*.deductions' => ['nullable', 'numeric', 'min:0'],
        ]);

        $request->merge(['opportunity_id' => $opportunity->id]);
        return $this->store($request);
    }

    /**
     * Import room spaces from a completed Site Visit as draft starter measurement rows.
     */
    public function importFromSiteVisit(Request $request, int|string $siteVisitId): JsonResponse
    {
        $this->authorizePermission('measurements.create');

        $siteVisit = SiteVisit::with('rooms', 'opportunity', 'customer')->findOrFail($siteVisitId);

        $validated = $request->validate([
            'opportunity_id' => ['required', 'integer', 'exists:opportunities,id'],
        ]);

        $opportunity = Opportunity::findOrFail($validated['opportunity_id']);

        // Sanity check: opportunity must belong to same customer as the site visit
        if ($siteVisit->customer_id && $opportunity->customer_id !== $siteVisit->customer_id) {
            throw ValidationException::withMessages([
                'opportunity_id' => [__('messages.opportunity_customer_mismatch')],
            ]);
        }

        // No implicit mutation of the SiteVisit record whatsoever.
        // If it isn't linked to this Opportunity yet, that stays true —
        // Measurement just references both independently.

        $measurement = DB::transaction(function () use ($siteVisit, $opportunity) {
            $number = $this->generateMeasurementNumber($opportunity->id);

            $measurement = Measurement::create([
                'opportunity_id' => $opportunity->id,
                'site_visit_id' => $siteVisit->id,
                'measurement_number' => $number,
                'version' => 1,
                'status' => 'Draft',
                'measured_by' => auth()->id(),
                'measured_at' => Carbon::today()->toDateString(),
                'notes' => "تم استيراد الفراغات والغرف من المعاينة رقم #{$siteVisit->id}.",
                'created_by' => auth()->id(),
            ]);

            // Copy rooms as starting items with estimated area if available
            $sortOrder = 0;
            $itemsData = [];
            if ($siteVisit->rooms->isNotEmpty()) {
                foreach ($siteVisit->rooms as $room) {
                    $estimatedArea = (float) ($room->estimated_area ?? 0.0);
                    $calc = $this->calculationService->calculateItem([
                        'room_name' => $room->room_name,
                        'item_name' => 'أعمال تشطيبات عامة',
                        'unit' => 'm2',
                        'measurement_type' => 'area',
                        'count' => 1.00,
                        'length' => null,
                        'width' => null,
                        'height' => null,
                        'deductions' => 0.00,
                        'net_quantity' => 0.00,
                        'notes' => $room->notes ? "ملاحظات المعاينة: {$room->notes}" : ($estimatedArea > 0 ? "المساحة التقريبية بالمعاينة: {$estimatedArea} م²" : null),
                        'sort_order' => $sortOrder++,
                    ]);
                    $itemsData[] = $calc;
                    $measurement->items()->create($calc);
                }
            }

            if (!empty($itemsData)) {
                $summary = $this->calculationService->calculateSummary($itemsData);
                $measurement->update([
                    'total_area' => $summary['total_area'],
                    'total_volume' => $summary['total_volume'],
                    'total_linear' => $summary['total_linear'],
                    'total_count' => $summary['total_count'],
                ]);
            }

            activity('commercial')
                ->event('created')
                ->performedOn($measurement)
                ->causedBy(auth()->user())
                ->log("تم استيراد غرف المعاينة #{$siteVisit->id} إلى المقايسة #{$measurement->measurement_number}.");

            return $measurement;
        });

        return response()->json([
            'success' => true,
            'message' => __('messages.site_visit_rooms_imported_success'),
            'data' => $measurement->fresh($this->with),
        ], 201);
    }

    /**
     * Parse DXF file to return detected layers and polyline counts for user selection.
     */
    public function parseDxf(Request $request): JsonResponse
    {
        $this->authorizePermission('measurements.create');

        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'rooms_layer' => ['nullable', 'string', 'max:100'],
            'labels_layer' => ['nullable', 'string', 'max:100'],
        ]);

        $summary = $this->dxfParserService->getLayerSummary($request->file('file'));

        $rooms = [];
        if ($request->filled('rooms_layer')) {
            try {
                $rooms = $this->dxfParserService->parseRooms(
                    $request->file('file'),
                    $request->input('rooms_layer'),
                    $request->input('labels_layer')
                );
            } catch (\Exception $e) {
                $rooms = [];
            }
        }

        return response()->json([
            'success' => true,
            'data' => array_merge($summary, [
                'rooms_preview' => $rooms,
            ]),
        ]);
    }

    /**
     * Import room dimensions and geometry from DXF file into a new Draft measurement.
     */
    public function importFromDxf(Request $request): JsonResponse
    {
        $this->authorizePermission('measurements.create');

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'rooms_layer' => ['required', 'string', 'max:100'],
            'labels_layer' => ['nullable', 'string', 'max:100'],
            'opportunity_id' => ['required', 'integer', 'exists:opportunities,id'],
        ]);

        $opportunity = Opportunity::findOrFail($validated['opportunity_id']);

        $parsedRooms = $this->dxfParserService->parseRooms(
            $request->file('file'),
            $validated['rooms_layer'],
            $validated['labels_layer'] ?? null
        );

        if (empty($parsedRooms)) {
            throw ValidationException::withMessages([
                'rooms_layer' => [__('messages.dxf_no_rooms_found')],
            ]);
        }

        $measurement = DB::transaction(function () use ($opportunity, $parsedRooms, $request) {
            $number = $this->generateMeasurementNumber($opportunity->id);

            $measurement = Measurement::create([
                'opportunity_id' => $opportunity->id,
                'site_visit_id' => null,
                'measurement_number' => $number,
                'version' => 1,
                'status' => 'Draft', // Strict Rule: DXF import is always Draft until reviewed by an engineer
                'measured_by' => auth()->id(),
                'measured_at' => Carbon::today()->toDateString(),
                'notes' => 'تم استيراد أبعاد ومساحات الغرف من ملف أوتوكاد DXF (' . ($request->file('file')->getClientOriginalName()) . ').',
                'created_by' => auth()->id(),
            ]);

            $calculatedItems = [];
            foreach ($parsedRooms as $idx => $room) {
                $calc = $this->calculationService->calculateItem([
                    'room_name' => $room['room_name'],
                    'item_name' => 'أعمال تشطيبات عامة (مستورد من DXF)',
                    'unit' => 'm2',
                    'measurement_type' => 'area',
                    'count' => 1.00,
                    'length' => $room['length'] ?? null,
                    'width' => $room['width'] ?? null,
                    'height' => null,
                    'deductions' => 0.00,
                    'net_quantity' => empty($room['length']) ? $room['area'] : null,
                    'notes' => $room['notes'] ?? "مستورد من DXF: مساحة هندسية = {$room['area']} م² (محيط: {$room['perimeter']} م.ط).",
                    'sort_order' => $idx,
                ]);

                $calculatedItems[] = $calc;
                $measurement->items()->create($calc);
            }

            if (!empty($calculatedItems)) {
                $summary = $this->calculationService->calculateSummary($calculatedItems);
                $measurement->update([
                    'total_area' => $summary['total_area'],
                    'total_volume' => $summary['total_volume'],
                    'total_linear' => $summary['total_linear'],
                    'total_count' => $summary['total_count'],
                ]);
            }

            activity('commercial')
                ->event('created')
                ->performedOn($measurement)
                ->causedBy(auth()->user())
                ->log("تم استيراد مقايسة من ملف DXF برقم #{$measurement->measurement_number}.");

            return $measurement;
        });

        return response()->json([
            'success' => true,
            'message' => __('messages.dxf_imported_success'),
            'data' => $measurement->fresh($this->with),
        ], 201);
    }

    /**
     * Submit measurement for engineering review.
     */
    public function submitReview(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission('measurements.update');

        $measurement = Measurement::findOrFail($id);

        if (!$measurement->isDraft()) {
            throw ValidationException::withMessages([
                'status' => [__('messages.only_draft_can_be_submitted_for_review')],
            ]);
        }

        $measurement->status = 'Under Review';
        $measurement->reviewed_by = auth()->id();
        $measurement->reviewed_at = Carbon::now();
        $measurement->save();

        activity('commercial')
            ->event('updated')
            ->performedOn($measurement)
            ->causedBy(auth()->user())
            ->log(__('activity.measurement_submitted_for_review', ['number' => $measurement->measurement_number]));

        return response()->json([
            'success' => true,
            'message' => __('messages.measurement_submitted_review_success'),
            'data' => $measurement->fresh($this->with),
        ]);
    }

    /**
     * Approve measurement as authoritative technical truth.
     */
    public function approve(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission('measurements.approve');

        $measurement = Measurement::with('items')->findOrFail($id);

        if (!$measurement->canBeApproved()) {
            throw ValidationException::withMessages([
                'status' => [__('messages.measurement_cannot_be_approved_in_current_state')],
            ]);
        }

        if ($measurement->items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => [__('messages.cannot_approve_empty_measurement')],
            ]);
        }

        // Validate items integrity
        foreach ($measurement->items as $item) {
            if (empty($item->room_name) || empty($item->item_name)) {
                throw ValidationException::withMessages([
                    'items' => [__('messages.measurement_items_missing_required_info')],
                ]);
            }
        }

        $measurement = DB::transaction(function () use ($measurement) {
            // Supersede any previously approved measurement for this opportunity
            Measurement::where('opportunity_id', $measurement->opportunity_id)
                ->where('id', '!=', $measurement->id)
                ->where('status', 'Approved')
                ->update(['status' => 'Superseded']);

            $measurement->status = 'Approved';
            $measurement->approved_by = auth()->id();
            $measurement->approved_at = Carbon::now();
            $measurement->save();

            activity('commercial')
                ->event('updated')
                ->performedOn($measurement)
                ->causedBy(auth()->user())
                ->log(__('activity.measurement_approved', ['number' => $measurement->measurement_number]));

            return $measurement;
        });

        return response()->json([
            'success' => true,
            'message' => __('messages.measurement_approved_success'),
            'data' => $measurement->fresh($this->with),
        ]);
    }

    /**
     * Create a new draft revision/version from an approved measurement.
     */
    public function createRevision(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission('measurements.create');

        $parent = Measurement::with('items')->findOrFail($id);

        $revision = DB::transaction(function () use ($parent) {
            $newVersion = $parent->version + 1;
            $baseNumber = preg_replace('/-V\d+$/i', '', $parent->measurement_number);
            $newNumber = "{$baseNumber}-V{$newVersion}";

            $newMeasurement = Measurement::create([
                'opportunity_id' => $parent->opportunity_id,
                'site_visit_id' => $parent->site_visit_id,
                'measurement_number' => $newNumber,
                'version' => $newVersion,
                'status' => 'Draft',
                'measured_by' => auth()->id(),
                'measured_at' => Carbon::today()->toDateString(),
                'total_area' => $parent->total_area,
                'total_volume' => $parent->total_volume,
                'total_linear' => $parent->total_linear,
                'total_count' => $parent->total_count,
                'notes' => "إصدار ومراجعة رقم ({$newVersion}) مستخرجة من المقايسة #{$parent->measurement_number}.",
                'created_by' => auth()->id(),
            ]);

            foreach ($parent->items as $item) {
                $newMeasurement->items()->create([
                    'room_name' => $item->room_name,
                    'item_name' => $item->item_name,
                    'unit' => $item->unit,
                    'measurement_type' => $item->measurement_type,
                    'count' => $item->count,
                    'length' => $item->length,
                    'width' => $item->width,
                    'height' => $item->height,
                    'deductions' => $item->deductions,
                    'gross_quantity' => $item->gross_quantity,
                    'net_quantity' => $item->net_quantity,
                    'notes' => $item->notes,
                    'sort_order' => $item->sort_order,
                ]);
            }

            activity('commercial')
                ->event('created')
                ->performedOn($newMeasurement)
                ->causedBy(auth()->user())
                ->log("تم إنشاء مراجعة مقايسة جديدة رقم #{$newMeasurement->measurement_number} (الإصدار {$newVersion}).");

            return $newMeasurement;
        });

        return response()->json([
            'success' => true,
            'message' => __('messages.measurement_revision_created_success'),
            'data' => $revision->fresh($this->with),
        ], 201);
    }

    /**
     * Generate sequential measurement code.
     */
    protected function generateMeasurementNumber(int $opportunityId): string
    {
        $count = Measurement::where('opportunity_id', $opportunityId)->count();
        $next = $count + 1;
        $padded = str_pad((string) $next, 4, '0', STR_PAD_LEFT);
        return "M-OPP{$opportunityId}-{$padded}";
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
