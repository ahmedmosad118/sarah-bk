<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Check if authenticated user has any of the given permissions, or is Owner / Super Admin.
     */
    protected function authorizePermission(string|array $permission): void
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
