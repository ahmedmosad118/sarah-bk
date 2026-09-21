<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of the activity logs for the current tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission('activity_log.view');

        $query = Activity::with('causer')->latest('id');

        // 1. Filter by log name / module
        if ($request->filled('log_name')) {
            $query->where('log_name', $request->log_name);
        }

        // 2. Filter by event (created, updated, deleted, login, logout, etc.)
        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        // 3. Filter by causer / user
        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->causer_id)->where('causer_type', \App\Models\User::class);
        }

        // 4. Search in description
        if ($search = $request->input('search')) {
            $query->where('description', 'like', "%{$search}%");
        }

        $perPage = (int) $request->input('per_page', 20);
        $logs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
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
