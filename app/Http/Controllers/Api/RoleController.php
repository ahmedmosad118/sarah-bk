<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * List all roles with permissions and user count.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission('roles.view');

        $roles = Role::with('permissions')
            ->withCount('users')
            ->orderBy('id', 'asc')
            ->get();

        $permissionsByModule = Permission::all()->groupBy('module');

        return response()->json([
            'success' => true,
            'data' => $roles,
            'modules' => $permissionsByModule,
            'total_roles' => $roles->count(),
            'total_permissions' => Permission::count(),
        ]);
    }

    /**
     * Show single role details with permissions.
     */
    public function show(int $id): JsonResponse
    {
        $this->authorizePermission('roles.view');

        $role = Role::with('permissions')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $role,
        ]);
    }

    /**
     * Store new custom role.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission('roles.create');

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:roles,name',
            'display_name' => 'nullable|string|max:150',
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        DB::beginTransaction();
        try {
            $role = Role::create([
                'name' => trim($validated['name']),
                'guard_name' => 'web',
                'display_name' => $validated['display_name'] ?? $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_default' => false,
            ]);

            if (!empty($validated['permissions'])) {
                $role->syncPermissions($validated['permissions']);
            }

            activity('roles')
                ->performedOn($role)
                ->causedBy(auth()->user())
                ->log("تم إنشاء دور مخصص جديد ({$role->name})");

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الدور بنجاح',
                'data' => $role->load('permissions'),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ الدور: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update role and synced permissions.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorizePermission('roles.update');

        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name' => "required|string|max:100|unique:roles,name,{$id}",
            'display_name' => 'nullable|string|max:150',
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        // Prevent changing system name of Owner
        if ($role->name === 'Owner' && $validated['name'] !== 'Owner') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تغيير الاسم البرمجي لدور المالك (Owner).',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $role->update([
                'name' => trim($validated['name']),
                'display_name' => $validated['display_name'] ?? $role->display_name,
                'description' => $validated['description'] ?? $role->description,
            ]);

            if (isset($validated['permissions'])) {
                // Owner always maintains all permissions
                if ($role->name === 'Owner') {
                    $role->syncPermissions(Permission::all());
                } else {
                    $role->syncPermissions($validated['permissions']);
                }
            }

            activity('roles')
                ->performedOn($role)
                ->causedBy(auth()->user())
                ->log("تم تحديث صلاحيات وبيانات الدور ({$role->name})");

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الدور والصلاحيات بنجاح',
                'data' => $role->load('permissions'),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الدور: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete custom role.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->authorizePermission('roles.delete');

        $role = Role::findOrFail($id);

        if ($role->is_default || in_array($role->name, ['Owner', 'Super Admin', 'Administrator', 'Viewer'])) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف الأدوار الأساسية للنظام (System Roles).',
            ], 422);
        }

        if ($role->users()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف الدور لوجود مستخدمين مرتبطين به. قم بنقل المستخدمين أولاً.',
            ], 422);
        }

        activity('roles')
            ->performedOn($role)
            ->causedBy(auth()->user())
            ->log("تم حذف الدور ({$role->name})");

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الدور بنجاح',
        ]);
    }

    /**
     * Get permission catalog for matrix builder.
     */
    public function permissions(): JsonResponse
    {
        $this->authorizePermission('permissions.view');

        $modules = Permission::all()->groupBy('module')->map(function ($perms, $mod) {
            return [
                'module' => $mod,
                'permissions' => $perms->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'display_name' => $p->display_name ?: $p->name,
                ]),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $modules,
        ]);
    }

    private function authorizePermission(string $permission): void
    {
        $user = auth()->user();
        if (!$user) {
            return;
        }

        if ($user->hasRole('Owner') || $user->hasRole('Super Admin')) {
            return;
        }

        if (!$user->hasPermissionTo($permission)) {
            abort(403, "ليس لديك الصلاحية المطلوبة ({$permission}).");
        }
    }
}
