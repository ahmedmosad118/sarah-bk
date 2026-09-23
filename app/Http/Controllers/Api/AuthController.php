<?php

namespace App\Http\Controllers\Api;

use App\Core\Tenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Authenticate user inside tenant database.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', strtolower(trim($validated['email'])))->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if (!$user->isActive()) {
            return response()->json([
                'success' => false,
                'message' => __('auth.account_inactive'),
                'error_code' => 'USER_INACTIVE',
            ], 403);
        }

        $user->update(['last_login_at' => now()]);

        // Create Sanctum Token
        $token = $user->createToken('tenant_auth_token')->plainTextToken;

        // Log Activity
        activity('auth')
            ->performedOn($user)
            ->causedBy($user)
            ->log(__('activity.login'));

        $tenant = TenantContext::getTenant();

        return response()->json([
            'success' => true,
            'message' => __('auth.login_success'),
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar_url' => $user->avatar_url,
                    'job_title' => $user->jobTitle?->name,
                    'job_title_id' => $user->job_title_id,
                    'status' => $user->status,
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                ],
                'tenant' => $tenant ? [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'company_code' => $tenant->company_code,
                    'plan' => $tenant->plan,
                ] : null,
            ],
        ]);
    }

    /**
     * Get profile of authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => __('auth.unauthorized'),
            ], 401);
        }

        $user->load('jobTitle');
        $tenant = TenantContext::getTenant();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar_url' => $user->avatar_url,
                    'job_title' => $user->jobTitle?->name,
                    'job_title_id' => $user->job_title_id,
                    'status' => $user->status,
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                ],
                'tenant' => $tenant ? [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'company_code' => $tenant->company_code,
                    'plan' => $tenant->plan,
                ] : null,
            ],
        ]);
    }

    /**
     * Logout and revoke active token.
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user) {
            activity('auth')
                ->performedOn($user)
                ->causedBy($user)
                ->log(__('activity.logout'));

            $user->currentAccessToken()?->delete();
        }

        return response()->json([
            'success' => true,
            'message' => __('auth.logout_success'),
        ]);
    }

    /**
     * Update password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        /** @var User $user */
        $user = $request->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => [__('auth.current_password_invalid')],
            ]);
        }

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        activity('auth')
            ->performedOn($user)
            ->causedBy($user)
            ->log(__('activity.password_changed'));

        return response()->json([
            'success' => true,
            'message' => __('auth.password_changed_success'),
        ]);
    }

    /**
     * Update authenticated user profile details.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => "required|email|max:150|unique:users,email,{$user->id}",
            'phone' => 'nullable|string|max:30',
        ]);

        $user->update([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'phone' => $validated['phone'] ?? null,
        ]);

        activity('auth')
            ->performedOn($user)
            ->causedBy($user)
            ->log(__('activity.profile_updated'));

        return response()->json([
            'success' => true,
            'message' => __('auth.profile_updated_success'),
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar_url' => $user->avatar_url,
                    'job_title' => $user->jobTitle?->name,
                    'job_title_id' => $user->job_title_id,
                    'status' => $user->status,
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                ],
            ],
        ]);
    }

    /**
     * Upload user avatar via Spatie Media Library.
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        /** @var User $user */
        $user = $request->user();

        try {
            $user->clearMediaCollection('avatar');
            $media = $user->addMediaFromRequest('avatar')
                ->toMediaCollection('avatar');

            $user->avatar = $media->getUrl();
            $user->save();
            $user->refresh();

            activity('auth')
                ->performedOn($user)
                ->causedBy($user)
                ->log(__('activity.avatar_updated'));

            return response()->json([
                'success' => true,
                'message' => __('auth.avatar_updated_success'),
                'avatar_url' => $user->avatar_url,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar_url' => $user->avatar_url,
                    'job_title' => $user->jobTitle?->name,
                    'job_title_id' => $user->job_title_id,
                    'status' => $user->status,
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => __('auth.avatar_upload_failed', ['error' => $e->getMessage()]),
            ], 422);
        }
    }
}
