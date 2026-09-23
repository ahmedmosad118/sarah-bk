<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\SiteVisit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    /**
     * Get executive commercial dashboard summary.
     */
    public function summary(Request $request): JsonResponse
    {
        $today = Carbon::today()->toDateString();

        // 1. Core KPIs Aggregations
        $stats = [
            'customers' => [
                'total' => Customer::count(),
                'individual' => Customer::where('customer_type', 'individual')->count(),
                'company' => Customer::where('customer_type', 'company')->count(),
            ],
            'leads' => [
                'total' => Lead::count(),
                'new' => Lead::where('status', 'New')->count(),
                'contacted' => Lead::where('status', 'Contacted')->count(),
                'qualified' => Lead::where('status', 'Qualified')->count(),
                'converted' => Lead::where('status', 'Converted')->count(),
            ],
            'opportunities' => [
                'total' => Opportunity::count(),
                'active' => Opportunity::whereNotIn('stage', ['Won', 'Lost'])->count(),
                'won' => Opportunity::where('stage', 'Won')->count(),
                'total_estimated_value' => (float) Opportunity::whereNotIn('stage', ['Lost'])->sum('estimated_value'),
            ],
            'site_visits' => [
                'total' => SiteVisit::count(),
                'scheduled' => SiteVisit::where('status', 'Scheduled')->count(),
                'completed' => SiteVisit::where('status', 'Completed')->count(),
                'scheduled_today' => SiteVisit::where('status', 'Scheduled')->whereDate('scheduled_date', $today)->count(),
                'overdue' => SiteVisit::whereIn('status', ['Scheduled', 'Rescheduled'])->whereDate('scheduled_date', '<', $today)->count(),
            ],
            'team' => [
                'total_users' => User::count(),
            ],
        ];

        // 2. Today's and Upcoming Site Visits
        $upcomingVisits = SiteVisit::with(['customer', 'opportunity', 'assignedUser'])
            ->whereIn('status', ['Scheduled', 'Requested', 'Rescheduled'])
            ->orderByRaw("CASE WHEN scheduled_date = '{$today}' THEN 0 WHEN scheduled_date > '{$today}' THEN 1 ELSE 2 END")
            ->orderBy('scheduled_date', 'asc')
            ->orderBy('scheduled_time', 'asc')
            ->limit(5)
            ->get();

        // 3. Top Active Opportunities
        $activeOpportunities = Opportunity::with(['customer', 'assignedUser'])
            ->whereNotIn('stage', ['Won', 'Lost'])
            ->orderBy('estimated_value', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // 4. Recent Incoming Leads
        $recentLeads = Lead::with(['customer', 'assignedUser'])
            ->whereIn('status', ['New', 'Contacted'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // 5. Recent Commercial Activity Logs
        $recentActivities = Activity::with(['causer'])
            ->orderBy('id', 'desc')
            ->limit(6)
            ->get();

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'upcoming_visits' => $upcomingVisits,
            'active_opportunities' => $activeOpportunities,
            'recent_leads' => $recentLeads,
            'recent_activities' => $recentActivities,
        ]);
    }
}
