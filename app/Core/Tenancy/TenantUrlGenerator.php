<?php

namespace App\Core\Tenancy;

use Spatie\MediaLibrary\Support\UrlGenerator\DefaultUrlGenerator;

class TenantUrlGenerator extends DefaultUrlGenerator
{
    /**
     * Get the public URL for the media item.
     * Respects current request host, subdomains, subfolders (e.g. XAMPP /sarah-bk/public), and S3/cloud storage.
     */
    public function getUrl(): string
    {
        $disk = $this->media->disk ?? config('media-library.disk_name');
        $diskDriver = config("filesystems.disks.{$disk}.driver", 'local');

        if ($disk === 'public' || $diskDriver === 'local') {
            $relativePath = 'storage/' . ltrim($this->getUrlEncodedPathRelativeToRoot(), '/');
            return $this->versionUrl(asset($relativePath));
        }

        return parent::getUrl();
    }
}
