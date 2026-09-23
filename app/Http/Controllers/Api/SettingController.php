<?php

namespace App\Http\Controllers\Api;

use App\Core\Tenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Get all company settings grouped.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission('settings.view');

        $settings = Setting::all()->groupBy('group');
        $tenant = TenantContext::getTenant();

        return response()->json([
            'success' => true,
            'data' => $settings,
            'tenant' => $tenant,
        ]);
    }

    /**
     * Update settings in bulk.
     */
    public function update(Request $request): JsonResponse
    {
        $this->authorizePermission('settings.update');

        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable',
            'settings.*.group' => 'nullable|string',
        ]);

        foreach ($validated['settings'] as $item) {
            Setting::set(
                $item['key'],
                $item['value'] ?? '',
                $item['group'] ?? 'general'
            );
        }

        activity('settings')
            ->event('updated')
            ->causedBy(auth()->user())
            ->log(__('activity.settings_updated'));

        return response()->json([
            'success' => true,
            'message' => __('messages.settings_saved_success'),
            'data' => Setting::all()->groupBy('group'),
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
            abort(403, __('messages.permission_denied', ['permission' => $permission]));
        }
    }
}
