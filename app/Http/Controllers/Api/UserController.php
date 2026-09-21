<?php

namespace App\Http\Controllers\Api;

use App\Core\CRUD\CRUDController;
use App\Core\CRUD\Field;
use App\Core\CRUD\InputMaker;
use App\Models\JobTitle;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends CRUDController
{
    protected string $model = User::class;
    protected array $searchable = ['name', 'email', 'phone', 'jobTitle.name'];
    protected array $with = ['jobTitle', 'roles', 'permissions'];
    protected string $defaultSortBy = 'id';
    protected string $defaultSortOrder = 'desc';

    protected function inputMaker(): InputMaker
    {
        return InputMaker::make()
            ->title('فريق العمل والمستخدمين')
            ->singularTitle('عضو فريق')
            ->model(User::class)
            ->fields([
                Field::text('name', 'الاسم الثلاثي')->required()->col(6),
                Field::email('email', 'البريد الإلكتروني')->required()->rules('email')->col(6),
                Field::tel('phone', 'رقم الهاتف / الجوال')->col(6),
                Field::relation('job_title_id', 'المسمى الوظيفي (Job Title)', 'jobTitle', 'name', 'id')->required()->col(6),
                Field::select('status', 'حالة الحساب', [
                    ['value' => 'active', 'label' => 'نشط ومفعل'],
                    ['value' => 'inactive', 'label' => 'معطل مؤقتاً'],
                ])->default('active')->required()->col(6),
                Field::date('joining_date', 'تاريخ الانضمام')->col(6),
                Field::password('password', 'كلمة المرور')->placeholder('8 أحرف على الأقل (افتراضي 12345678)')->col(6),
            ]);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission('users.view');

        $query = $this->query();

        // 1. Search Query
        if ($search = $request->input('search')) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('jobTitle', function ($jq) use ($search) {
                        $jq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // 2. Filter by Job Title
        if ($request->filled('job_title_id')) {
            $query->where('job_title_id', $request->job_title_id);
        }

        // 3. Filter by Role
        if ($request->filled('role')) {
            $query->role($request->role);
        }

        // 4. Filter by Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 5. Sorting
        $sortBy = $request->input('sort_by', $this->defaultSortBy);
        $sortOrder = $request->input('sort_order', $this->defaultSortOrder);
        $query->orderBy($sortBy, $sortOrder);

        $perPage = (int) $request->input('per_page', 15);
        $paginated = $query->paginate($perPage);

        // Stats summary for header counters
        $allUsers = User::with('roles')->get();
        $stats = [
            'total' => $allUsers->count(),
            'active' => $allUsers->where('status', 'active')->count(),
            'inactive' => $allUsers->where('status', 'inactive')->count(),
            'owners' => $allUsers->filter(fn($u) => $u->hasRole('Owner'))->count(),
        ];

        // Format items with role names
        $items = collect($paginated->items())->map(function (User $user) {
            $arr = $user->toArray();
            $arr['roles_list'] = $user->getRoleNames();
            $arr['avatar_url'] = $user->avatar_url;
            return $arr;
        });

        // Get all roles and job titles for frontend filters
        $roles = Role::all(['id', 'name', 'display_name']);
        $jobTitles = JobTitle::where('is_active', true)->get(['id', 'name', 'code']);

        return response()->json([
            'success' => true,
            'data' => $items,
            'stats' => $stats,
            'roles' => $roles,
            'job_titles' => $jobTitles,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
            'schema' => $this->inputMaker()->toSchema(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission('users.create');
        return parent::store($request);
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        $this->authorizePermission('users.update');
        return parent::update($request, $id);
    }

    public function destroy(int|string|Request $ids): JsonResponse
    {
        $this->authorizePermission('users.delete');
        return parent::destroy($ids);
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $this->authorizePermission(['users.activate', 'users.deactivate']);

        $user = User::findOrFail($id);

        // Prevent deactivating sole Owner
        if ($user->hasRole('Owner') && $user->status === 'active' && User::role('Owner')->where('status', 'active')->count() <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تعطيل المالك الوحيد للمنظومة.',
            ], 422);
        }

        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        $user->update(['status' => $newStatus]);

        activity('users')
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log("تم " . ($newStatus === 'active' ? 'تفعيل' : 'تعطيل') . " حساب المستخدم {$user->name}");

        return response()->json([
            'success' => true,
            'message' => "تم " . ($newStatus === 'active' ? 'تفعيل' : 'تعطيل') . " الحساب بنجاح",
            'data' => $user->fresh($this->with),
        ]);
    }

    protected function beforeSave(Model $model, Request $request, bool $isUpdate): void
    {
        /** @var User $model */
        if ($request->filled('password')) {
            $model->password = Hash::make($request->password);
        } elseif (!$isUpdate && !$model->password) {
            $model->password = Hash::make('12345678');
        }

        if ($request->has('email')) {
            $model->email = strtolower(trim($request->email));
        }
    }

    protected function afterSave(Model $model, Request $request, bool $isUpdate): void
    {
        /** @var User $model */

        // Sync Roles if provided (Supports Multiple Roles)
        if ($request->has('roles')) {
            $roles = $request->input('roles');
            $roles = is_array($roles) ? $roles : explode(',', (string)$roles);
            $model->syncRoles($roles);

            activity('roles')
                ->performedOn($model)
                ->causedBy(auth()->user())
                ->withProperties(['assigned_roles' => $roles])
                ->log("تم تحديث أدوار المستخدم {$model->name}");
        } elseif (!$isUpdate && !$model->roles()->exists()) {
            // Default role if none given
            $model->assignRole('Project Team');
        }

        // Handle Avatar Media Upload if provided
        if ($request->hasFile('avatar')) {
            $model->clearMediaCollection('avatar');
            $model->addMediaFromRequest('avatar')->toMediaCollection('avatar');
        }
    }

    protected function customValidationRules(bool $isUpdate = false, mixed $currentId = null): array
    {
        $uniqueEmail = 'unique:users,email';
        if ($isUpdate && $currentId) {
            $uniqueEmail .= ",{$currentId}";
        }

        return [
            'email' => ['required', 'email', $uniqueEmail],
            'roles' => ['nullable'],
            'avatar' => ['nullable', 'image', 'max:5120'], // max 5MB
        ];
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
            abort(403, 'ليس لديك الصلاحية الكافية لتنفيذ هذا الإجراء (' . implode(', ', $perms) . ').');
        }
    }
}
