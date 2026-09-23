<?php

namespace App\Http\Controllers\Api;

use App\Core\CRUD\CRUDController;
use App\Core\CRUD\Field;
use App\Core\CRUD\InputMaker;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\SiteVisit;
use App\Models\SiteVisitRoom;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SiteVisitController extends CRUDController
{
    protected string $model = SiteVisit::class;
    protected array $searchable = ['general_assessment', 'internal_notes'];
    protected array $with = ['customer', 'opportunity', 'lead', 'assignedUser', 'creator', 'rooms', 'media'];
    protected string $defaultSortBy = 'id';
    protected string $defaultSortOrder = 'desc';

    protected function inputMaker(): InputMaker
    {
        return InputMaker::make()
            ->title('siteVisits.title')
            ->singularTitle('siteVisits.singular')
            ->model(SiteVisit::class)
            ->fields([
                Field::select('customer_id', 'siteVisits.customer', [])
                    ->required()
                    ->col(6),

                Field::select('opportunity_id', 'siteVisits.opportunity', [])
                    ->col(6),

                Field::select('lead_id', 'siteVisits.lead', [])
                    ->col(6),

                Field::select('status', 'siteVisits.status', [
                    ['value' => 'Scheduled', 'label' => 'siteVisits.statusScheduled'],
                    ['value' => 'Completed', 'label' => 'siteVisits.statusCompleted'],
                    ['value' => 'Cancelled', 'label' => 'siteVisits.statusCancelled'],
                    ['value' => 'Rescheduled', 'label' => 'siteVisits.statusRescheduled'],
                ])->default('Scheduled')->col(6),

                Field::date('scheduled_date', 'siteVisits.scheduledDate')
                    ->col(6),

                Field::text('scheduled_time', 'siteVisits.scheduledTime')
                    ->col(6),

                Field::date('visit_date', 'siteVisits.visitDate')
                    ->col(6),

                Field::select('assigned_to', 'siteVisits.assignedTo', [])
                    ->col(6),

                Field::textarea('general_assessment', 'siteVisits.generalAssessment')
                    ->col(12),

                Field::textarea('internal_notes', 'siteVisits.internalNotes')
                    ->col(12),

                Field::media('site_photos', 'siteVisits.sitePhotos')
                    ->multiple()
                    ->col(12),
            ]);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission('site_visits.view');

        $query = $this->query();

        // 1. Full-text search across assessment, notes, customer info, opportunity, lead, and rooms
        if ($search = $request->input('search')) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('general_assessment', 'like', "%{$search}%")
                    ->orWhere('internal_notes', 'like', "%{$search}%")
                    ->orWhereHas('customer', function (Builder $cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('opportunity', function (Builder $oq) use ($search) {
                        $oq->where('title', 'like', "%{$search}%");
                    })
                    ->orWhereHas('lead', function (Builder $lq) use ($search) {
                        $lq->where('title', 'like', "%{$search}%");
                    })
                    ->orWhereHas('rooms', function (Builder $rq) use ($search) {
                        $rq->where('room_name', 'like', "%{$search}%")
                            ->orWhere('notes', 'like', "%{$search}%");
                    });
            });
        }

        // 2. Filter by Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 3. Filter by Customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // 4. Filter by Opportunity
        if ($request->filled('opportunity_id')) {
            $query->where('opportunity_id', $request->opportunity_id);
        }

        // 5. Filter by Lead
        if ($request->filled('lead_id')) {
            $query->where('lead_id', $request->lead_id);
        }

        // 6. Filter by Assigned User (Engineer)
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        // 7. Filter by Date range or specific date
        if ($request->filled('scheduled_date')) {
            $query->whereDate('scheduled_date', $request->scheduled_date);
        }

        if ($request->filled('visit_date')) {
            $query->whereDate('visit_date', $request->visit_date);
        }

        // 8. Sorting (Safe SQL Injection Whitelisting)
        $rawSortBy = (string) $request->input('sort_by', $this->defaultSortBy);
        $allowedSorts = ['id', 'status', 'scheduled_date', 'scheduled_time', 'visit_date', 'created_at'];
        $sortBy = in_array($rawSortBy, $allowedSorts, true) ? $rawSortBy : $this->defaultSortBy;

        $rawSortOrder = strtolower((string) $request->input('sort_order', $this->defaultSortOrder));
        $sortOrder = in_array($rawSortOrder, ['asc', 'desc'], true) ? $rawSortOrder : 'desc';

        $query->orderBy($sortBy, $sortOrder);

        // 9. Pagination (DOS Protection: Capped 1 - 100)
        $rawPerPage = (int) $request->input('per_page', 15);
        $perPage = min(max($rawPerPage, 1), 100);
        $paginated = $query->paginate($perPage);

        // High Performance Stats Summary via direct SQL aggregations
        $today = Carbon::today()->toDateString();
        $stats = [
            'total' => SiteVisit::count(),
            'scheduled' => SiteVisit::where('status', 'Scheduled')->count(),
            'completed' => SiteVisit::where('status', 'Completed')->count(),
            'cancelled' => SiteVisit::where('status', 'Cancelled')->count(),
            'rescheduled' => SiteVisit::where('status', 'Rescheduled')->count(),
            'scheduled_today' => SiteVisit::where('status', 'Scheduled')->whereDate('scheduled_date', $today)->count(),
            'overdue' => SiteVisit::whereIn('status', ['Scheduled', 'Rescheduled'])->whereDate('scheduled_date', '<', $today)->count(),
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
        $this->authorizePermission('site_visits.view');
        return parent::show($id);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission('site_visits.create');
        return parent::store($request);
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission('site_visits.update');
        return parent::update($request, $id);
    }

    public function destroy(int|string|Request $ids): JsonResponse
    {
        $this->authorizePermission('site_visits.delete');
        return parent::destroy($ids);
    }

    /**
     * Create a Site Visit directly originating from an Opportunity.
     */
    public function createFromOpportunity(Request $request, int|string $opportunityId): JsonResponse
    {
        $this->authorizePermission('site_visits.create');

        $opportunity = Opportunity::findOrFail($opportunityId);

        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:Scheduled,Completed,Cancelled,Rescheduled'],
            'scheduled_date' => ['nullable', 'date'],
            'scheduled_time' => ['nullable', 'string', 'max:20'],
            'visit_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'general_assessment' => ['nullable', 'string', 'max:5000'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
            'rooms' => ['nullable', 'array'],
            'rooms.*.room_name' => ['required', 'string', 'max:150'],
            'rooms.*.estimated_area' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'rooms.*.notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $siteVisit = DB::transaction(function () use ($opportunity, $validated) {
            $visit = SiteVisit::create([
                'customer_id' => $opportunity->customer_id,
                'opportunity_id' => $opportunity->id,
                'lead_id' => $opportunity->lead_id,
                'status' => $validated['status'] ?? 'Scheduled',
                'scheduled_date' => $validated['scheduled_date'] ?? null,
                'scheduled_time' => $validated['scheduled_time'] ?? null,
                'visit_date' => $validated['visit_date'] ?? null,
                'assigned_to' => array_key_exists('assigned_to', $validated) ? $validated['assigned_to'] : $opportunity->assigned_to,
                'general_assessment' => $validated['general_assessment'] ?? null,
                'internal_notes' => $validated['internal_notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            if (!empty($validated['rooms'])) {
                foreach ($validated['rooms'] as $roomData) {
                    $visit->rooms()->create([
                        'room_name' => $roomData['room_name'],
                        'estimated_area' => $roomData['estimated_area'] ?? null,
                        'notes' => $roomData['notes'] ?? null,
                    ]);
                }
            }

            activity('commercial')
                ->event('created')
                ->performedOn($visit)
                ->causedBy(auth()->user())
                ->log(__('activity.site_visit_created_from_opportunity', [
                    'id' => $visit->id,
                    'opportunity' => $opportunity->title,
                ]));

            return $visit;
        });

        return response()->json([
            'success' => true,
            'message' => __('messages.site_visit_created_success'),
            'data' => $siteVisit->fresh($this->with),
        ], 201);
    }

    /**
     * Assign engineer/user to Site Visit.
     */
    public function assign(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission(['site_visits.assign', 'site_visits.update']);

        $validated = $request->validate([
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
        ]);

        $siteVisit = SiteVisit::findOrFail($id);
        $siteVisit->assigned_to = $validated['assigned_to'];
        $siteVisit->save();

        $assignedUser = User::find($validated['assigned_to']);

        activity('commercial')
            ->event('updated')
            ->performedOn($siteVisit)
            ->causedBy(auth()->user())
            ->log(__('activity.site_visit_assigned', [
                'id' => $siteVisit->id,
                'user' => $assignedUser?->name,
            ]));

        return response()->json([
            'success' => true,
            'message' => __('messages.site_visit_assigned_success'),
            'data' => $siteVisit->fresh($this->with),
        ]);
    }

    /**
     * Schedule or reschedule Site Visit date and time.
     */
    public function schedule(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission(['site_visits.update', 'site_visits.create']);

        $validated = $request->validate([
            'scheduled_date' => ['required', 'date'],
            'scheduled_time' => ['nullable', 'string', 'max:20'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $siteVisit = SiteVisit::findOrFail($id);
        $isRescheduled = ($siteVisit->scheduled_date && $siteVisit->scheduled_date->toDateString() !== $validated['scheduled_date']);

        $siteVisit->scheduled_date = $validated['scheduled_date'];
        if (array_key_exists('scheduled_time', $validated)) {
            $siteVisit->scheduled_time = $validated['scheduled_time'];
        }
        if (array_key_exists('assigned_to', $validated) && $validated['assigned_to'] !== null) {
            $siteVisit->assigned_to = $validated['assigned_to'];
        }
        if (isset($validated['internal_notes'])) {
            $siteVisit->internal_notes = $validated['internal_notes'];
        }

        $siteVisit->status = $isRescheduled ? 'Rescheduled' : ($siteVisit->status === 'Cancelled' ? 'Scheduled' : $siteVisit->status);
        $siteVisit->save();

        activity('commercial')
            ->event('updated')
            ->performedOn($siteVisit)
            ->causedBy(auth()->user())
            ->log(__('activity.site_visit_scheduled', [
                'id' => $siteVisit->id,
                'date' => $siteVisit->scheduled_date?->toDateString(),
            ]));

        return response()->json([
            'success' => true,
            'message' => __('messages.site_visit_scheduled_success'),
            'data' => $siteVisit->fresh($this->with),
        ]);
    }

    /**
     * Complete Site Visit with mandatory visit_date and general_assessment.
     */
    public function complete(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission(['site_visits.complete', 'site_visits.update']);

        $validated = $request->validate([
            'visit_date' => ['required', 'date'],
            'general_assessment' => ['required', 'string', 'max:5000'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
            'rooms' => ['nullable', 'array'],
            'rooms.*.room_name' => ['required', 'string', 'max:150'],
            'rooms.*.estimated_area' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'rooms.*.notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $siteVisit = DB::transaction(function () use ($id, $validated) {
            $visit = SiteVisit::findOrFail($id);
            $visit->visit_date = $validated['visit_date'];
            $visit->general_assessment = $validated['general_assessment'];
            if (isset($validated['internal_notes'])) {
                $visit->internal_notes = $validated['internal_notes'];
            }
            $visit->status = 'Completed';
            $visit->save();

            // Sync rooms if provided
            if (array_key_exists('rooms', $validated)) {
                $visit->rooms()->delete();
                if (!empty($validated['rooms'])) {
                    foreach ($validated['rooms'] as $roomData) {
                        $visit->rooms()->create([
                            'room_name' => $roomData['room_name'],
                            'estimated_area' => $roomData['estimated_area'] ?? null,
                            'notes' => $roomData['notes'] ?? null,
                        ]);
                    }
                }
            }

            activity('commercial')
                ->event('updated')
                ->performedOn($visit)
                ->causedBy(auth()->user())
                ->log(__('activity.site_visit_completed', ['id' => $visit->id]));

            return $visit;
        });

        return response()->json([
            'success' => true,
            'message' => __('messages.site_visit_completed_success'),
            'data' => $siteVisit->fresh($this->with),
        ]);
    }

    /**
     * Cancel Site Visit with optional notes.
     */
    public function cancel(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission(['site_visits.cancel', 'site_visits.update']);

        $validated = $request->validate([
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $siteVisit = SiteVisit::findOrFail($id);
        $siteVisit->status = 'Cancelled';
        if (isset($validated['internal_notes'])) {
            $siteVisit->internal_notes = $validated['internal_notes'];
        }
        $siteVisit->save();

        activity('commercial')
            ->event('updated')
            ->performedOn($siteVisit)
            ->causedBy(auth()->user())
            ->log(__('activity.site_visit_cancelled', ['id' => $siteVisit->id]));

        return response()->json([
            'success' => true,
            'message' => __('messages.site_visit_cancelled_success'),
            'data' => $siteVisit->fresh($this->with),
        ]);
    }

    /**
     * Upload site photos to media collection.
     */
    public function uploadPhotos(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission('site_visits.update');

        $request->validate([
            'photos' => ['required'],
            'photos.*' => ['file', 'mimes:jpg,jpeg,png,webp,heic', 'max:10240'],
        ]);

        $siteVisit = SiteVisit::findOrFail($id);

        $files = is_array($request->file('photos')) ? $request->file('photos') : [$request->file('photos')];
        $uploaded = [];

        foreach ($files as $file) {
            $ext = strtolower($file->getClientOriginalExtension());
            if (in_array($ext, ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phar', 'pht', 'exe', 'sh', 'bat', 'cmd'], true)) {
                continue;
            }
            $media = $siteVisit->addMedia($file)->toMediaCollection('site_photos');
            $uploaded[] = $media;
        }

        activity('commercial')
            ->event('updated')
            ->performedOn($siteVisit)
            ->causedBy(auth()->user())
            ->log(__('activity.site_visit_photos_uploaded', ['id' => $siteVisit->id, 'count' => count($uploaded)]));

        return response()->json([
            'success' => true,
            'message' => __('messages.site_visit_photos_uploaded_success'),
            'data' => $siteVisit->fresh($this->with),
        ]);
    }

    protected function beforeSave(Model $model, Request $request, bool $isUpdate): void
    {
        /** @var SiteVisit $model */
        if (!$isUpdate) {
            if (empty($model->created_by)) {
                $model->created_by = auth()->id();
            }
            if (empty($model->status)) {
                $model->status = 'Scheduled';
            }
        }

        // Auto default visit_date to today if marked completed without explicit date
        if ($model->status === 'Completed' && empty($model->visit_date)) {
            $model->visit_date = Carbon::today()->toDateString();
        }
    }

    protected function customValidationRules(bool $isUpdate = false, mixed $currentId = null): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'opportunity_id' => ['nullable', 'integer', 'exists:opportunities,id'],
            'lead_id' => ['nullable', 'integer', 'exists:leads,id'],
            'status' => ['nullable', 'string', 'in:Requested,Scheduled,Completed,Cancelled,Rescheduled'],
            'scheduled_date' => ['nullable', 'date'],
            'scheduled_time' => ['nullable', 'string', 'max:20'],
            'visit_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'general_assessment' => ['nullable', 'string', 'max:5000'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
            'rooms' => ['nullable', 'array'],
            'rooms.*.room_name' => ['required', 'string', 'max:150'],
            'rooms.*.estimated_area' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'rooms.*.notes' => ['nullable', 'string', 'max:2000'],
            'site_photos' => ['nullable'],
            'site_photos.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,heic', 'max:10240'],
            'documents' => ['nullable'],
            'documents.*' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    protected function afterSave(Model $model, Request $request, bool $isUpdate): void
    {
        /** @var SiteVisit $model */
        // Sync rooms if provided
        if ($request->has('rooms')) {
            $rooms = $request->input('rooms', []);
            if (is_array($rooms)) {
                $model->rooms()->delete();
                foreach ($rooms as $room) {
                    if (!empty($room['room_name'])) {
                        $model->rooms()->create([
                            'room_name' => $room['room_name'],
                            'estimated_area' => $room['estimated_area'] ?? null,
                            'notes' => $room['notes'] ?? null,
                        ]);
                    }
                }
            }
        }

        // Upload site photos if provided
        if ($request->hasFile('site_photos')) {
            $photos = is_array($request->file('site_photos')) ? $request->file('site_photos') : [$request->file('site_photos')];
            foreach ($photos as $photo) {
                $ext = strtolower($photo->getClientOriginalExtension());
                if (in_array($ext, ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phar', 'pht', 'exe', 'sh', 'bat', 'cmd'], true)) {
                    continue;
                }
                $model->addMedia($photo)->toMediaCollection('site_photos');
            }
        }

        // Upload documents if provided
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
