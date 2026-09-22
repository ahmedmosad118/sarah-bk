<?php

namespace App\Core\Tenancy;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class TenantPathGenerator implements PathGenerator
{
    /**
     * Get the path for the given media, scoped by tenant slug or ID.
     */
    public function getPath(Media $media): string
    {
        return $this->getBasePath($media) . '/';
    }

    /**
     * Get the path for conversions of the given media.
     */
    public function getPathForConversions(Media $media): string
    {
        return $this->getBasePath($media) . '/conversions/';
    }

    /**
     * Get the path for responsive images of the given media.
     */
    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getBasePath($media) . '/responsive-images/';
    }

    /**
     * Get the tenant-scoped base path for a media file.
     * Format: tenants/{tenant_slug}/{media_id}
     */
    protected function getBasePath(Media $media): string
    {
        $tenantSlug = $media->getCustomProperty('tenant_slug')
            ?? TenantContext::getTenantSlug()
            ?? 'central';

        return "tenants/{$tenantSlug}/{$media->id}";
    }
}
