<?php

namespace App\Core\Tenancy;

use App\Models\Central\Domain;
use App\Models\Central\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    /**
     * Handle an incoming request and identify tenant.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Always ensure central connection is default initially
        if (app()->bound('config')) {
            DB::setDefaultConnection('central');
        }

        $tenant = null;
        $host = $request->getHost(); // e.g. company-a.sarh.app, company-a.localhost, company-a.test

        // 1. Direct Domain Match in central domains table
        if ($host) {
            $domainRecord = Domain::where('domain', $host)->with('tenant')->first();
            if ($domainRecord && $domainRecord->tenant) {
                $tenant = $domainRecord->tenant;
            }
        }

        // 2. Subdomain Resolution
        if (!$tenant && $host && !filter_var($host, FILTER_VALIDATE_IP) && $host !== 'localhost' && $host !== '127.0.0.1') {
            $parts = explode('.', $host);
            if (count($parts) >= 2) {
                $subdomain = strtolower($parts[0]);
                // Ignore platform system subdomains
                if (!in_array($subdomain, ['www', 'app', 'admin', 'api', 'central', 'sarh', 'sarah'])) {
                    $tenant = Tenant::where('slug', $subdomain)->first();
                }
            }
        }

        // 3. Custom Headers (X-Tenant-Slug / X-Tenant / X-Tenant-Domain / X-Tenant-Id)
        if (!$tenant && ($request->hasHeader('X-Tenant-Slug') || $request->hasHeader('X-Tenant'))) {
            $slugHeader = $request->header('X-Tenant-Slug') ?: $request->header('X-Tenant');
            $tenant = Tenant::where('slug', $slugHeader)->orWhere('slug', \Illuminate\Support\Str::slug($slugHeader))->first();
        } elseif (!$tenant && $request->hasHeader('X-Tenant-Domain')) {
            $domainRecord = Domain::where('domain', $request->header('X-Tenant-Domain'))->with('tenant')->first();
            if ($domainRecord && $domainRecord->tenant) {
                $tenant = $domainRecord->tenant;
            }
        } elseif (!$tenant && $request->hasHeader('X-Tenant-Id')) {
            $tenant = Tenant::find((int) $request->header('X-Tenant-Id'));
        }

        // 4. Query Parameter Fallback (Allowed in non-production or testing environments)
        if (!$tenant && !app()->isProduction()) {
            if ($request->has('tenant')) {
                $paramSlug = $request->get('tenant');
                $tenant = Tenant::where('slug', $paramSlug)->orWhere('slug', \Illuminate\Support\Str::slug($paramSlug))->first();
            } elseif ($request->has('tenant_id')) {
                $tenant = Tenant::find((int) $request->get('tenant_id'));
            }
        }

        // 5. Seamless Fallback for localhost / local dev / direct demo login
        if (!$tenant && (!app()->isProduction() || $host === 'localhost' || $host === '127.0.0.1' || str_ends_with($host, '.localhost'))) {
            if ($host === 'localhost' || $host === '127.0.0.1' || str_ends_with($host, '.localhost') || $request->is('api/auth/login')) {
                $tenant = Tenant::where('slug', 'demo')->first() ?? Tenant::where('status', 'active')->first();
            }
        }

        if ($tenant) {
            if (!$tenant->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حساب الشركة / النطاق معطل حالياً. يرجى التواصل مع الدعم الفني.',
                    'error_code' => 'TENANT_INACTIVE',
                ], 403);
            }

            // Switch DB connection and configure context
            TenantDatabaseManager::switchToTenant($tenant);
        } else {
            // If request is targeting tenant API routes without identification
            if ($request->is('api/*') && !$request->is('api/central/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'تعذر التعرف على نطاق أو معرّف الشركة (Tenant). يرجى تمرير الدومين أو الهيدر X-Tenant-Slug.',
                    'error_code' => 'TENANT_NOT_IDENTIFIED',
                ], 400);
            }
        }

        $response = $next($request);

        if ($tenant && method_exists($response, 'header')) {
            $response->header('X-Tenant-Identified', $tenant->slug);
        }

        return $response;
    }
}
