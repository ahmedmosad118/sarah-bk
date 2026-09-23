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

        $query = Activity::with(['causer', 'subject'])->latest('id');

        // 1. Filter by log name / module
        if ($request->filled('log_name') && $request->log_name !== 'all') {
            $module = $request->log_name;
            $query->where(function ($q) use ($module) {
                if ($module === 'auth') {
                    $q->where('log_name', 'auth')->orWhereIn('event', ['login', 'logout']);
                } elseif ($module === 'customers') {
                    $q->whereIn('log_name', ['customers', 'customer'])
                      ->orWhere('subject_type', \App\Models\Customer::class);
                } elseif ($module === 'leads') {
                    $q->whereIn('log_name', ['leads', 'lead', 'commercial'])
                      ->orWhere('subject_type', \App\Models\Lead::class);
                } elseif ($module === 'opportunities') {
                    $q->whereIn('log_name', ['opportunities', 'opportunity', 'commercial'])
                      ->orWhere('subject_type', \App\Models\Opportunity::class);
                } elseif ($module === 'users') {
                    $q->whereIn('log_name', ['users', 'user'])
                      ->orWhere('subject_type', \App\Models\User::class);
                } elseif ($module === 'roles') {
                    $q->whereIn('log_name', ['roles', 'role', 'permissions']);
                } elseif ($module === 'job_titles') {
                    $q->whereIn('log_name', ['job_titles', 'job_title'])
                      ->orWhere('subject_type', \App\Models\JobTitle::class);
                } elseif ($module === 'settings') {
                    $q->whereIn('log_name', ['settings', 'setting'])
                      ->orWhere('subject_type', \App\Models\Setting::class);
                } else {
                    $q->where('log_name', $module);
                }
            });
        }

        // 2. Filter by event (created, updated, deleted, login, logout, etc.)
        if ($request->filled('event') && $request->event !== 'all') {
            $evt = $request->event;
            $query->where(function ($q) use ($evt) {
                if ($evt === 'login') {
                    $q->where('event', 'login')
                      ->orWhere(function ($sub) {
                          $sub->where('log_name', 'auth')
                              ->where('description', 'like', '%تسجيل الدخول%');
                      });
                } elseif ($evt === 'logout') {
                    $q->where('event', 'logout')
                      ->orWhere(function ($sub) {
                          $sub->where('log_name', 'auth')
                              ->where('description', 'like', '%تسجيل الخروج%');
                      });
                } else {
                    $q->where('event', $evt);
                }
            });
        }

        // 3. Filter by causer / user
        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->causer_id)->where('causer_type', \App\Models\User::class);
        }

        // 4. Search in description
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhereHasMorph('causer', [\App\Models\User::class], function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = (int) $request->input('per_page', 20);
        $logs = $query->paginate($perPage);

        // Compute high-level overview stats
        $stats = [
            'total' => Activity::count(),
            'today' => Activity::whereDate('created_at', now()->toDateString())->count(),
            'auth' => Activity::where(function ($q) {
                $q->where('log_name', 'auth')->orWhereIn('event', ['login', 'logout']);
            })->count(),
            'mutations' => Activity::whereIn('event', ['created', 'updated', 'deleted'])->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
            'stats' => $stats,
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
