<?php

namespace App\Core\Tenancy;

use App\Models\Central\Tenant;

class TenantContext
{
    protected static ?Tenant $currentTenant = null;

    public static function setTenant(Tenant $tenant): void
    {
        static::$currentTenant = $tenant;
    }

    public static function getTenant(): ?Tenant
    {
        return static::$currentTenant;
    }

    public static function getTenantId(): ?int
    {
        return static::$currentTenant?->id;
    }

    public static function getTenantSlug(): ?string
    {
        return static::$currentTenant?->slug;
    }

    public static function clear(): void
    {
        static::$currentTenant = null;
    }

    public static function hasTenant(): bool
    {
        return static::$currentTenant !== null;
    }
}
