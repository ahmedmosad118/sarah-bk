<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
         \Spatie\MediaLibrary\MediaCollections\Models\Media::creating(function ($media) {
             if (\App\Core\Tenancy\TenantContext::getTenantSlug() && !isset($media->custom_properties['tenant_slug'])) {
                 $media->setCustomProperty('tenant_slug', \App\Core\Tenancy\TenantContext::getTenantSlug());
             }
         });
     }
}
