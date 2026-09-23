<?php

namespace App\Providers;

use App\Core\Tenancy\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 1. Spatie Media Tenant Scoping
        Media::creating(function ($media) {
            if (TenantContext::getTenantSlug() && !isset($media->custom_properties['tenant_slug'])) {
                $media->setCustomProperty('tenant_slug', TenantContext::getTenantSlug());
            }
        });

        // 2. Security Rate Limiting (DOS & Brute Force Protection)
        RateLimiter::for('login', function (Request $request) {
            $tenantKey = $request->header('X-Tenant-Slug') ?: $request->input('tenant', 'default');
            $throttleKey = strtolower(trim((string) $request->input('email'))) . '|' . $tenantKey . '|' . $request->ip();
            return Limit::perMinute(5)->by($throttleKey)->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.too_many_attempts'),
                    'error_code' => 'TOO_MANY_ATTEMPTS',
                ], 429);
            });
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });
    }
}
