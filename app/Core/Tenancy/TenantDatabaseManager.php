<?php

namespace App\Core\Tenancy;

use App\Models\Central\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TenantDatabaseManager
{
    /**
     * Get standardized database name for a tenant (e.g. sarh_tenant_1, sarh_tenant_2).
     */
    public static function getDatabaseName(Tenant|int|string $tenant): string
    {
        if ($tenant instanceof Tenant) {
            return $tenant->database_name ?: ('sarh_tenant_' . $tenant->id);
        }

        if (is_numeric($tenant)) {
            return 'sarh_tenant_' . $tenant;
        }

        $str = (string)$tenant;
        if (str_starts_with($str, 'sarh_tenant_')) {
            return $str;
        }

        return 'sarh_tenant_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $str);
    }

    /**
     * Create physical MySQL database for a tenant if it does not exist.
     */
    public static function createDatabase(Tenant|string $tenant, bool $fresh = false): void
    {
        $dbName = is_string($tenant) ? $tenant : self::getDatabaseName($tenant);
        $dbName = preg_replace('/[^a-zA-Z0-9_]/', '', $dbName);

        if ($fresh) {
            DB::connection('central')->statement("DROP DATABASE IF EXISTS `{$dbName}`;");
        }
        DB::connection('central')->statement("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    }

    /**
     * Drop a tenant's database (used on rollbacks / deletions).
     */
    public static function dropDatabase(Tenant|string $tenant): void
    {
        $dbName = is_string($tenant) ? $tenant : self::getDatabaseName($tenant);
        $dbName = preg_replace('/[^a-zA-Z0-9_]/', '', $dbName);

        DB::connection('central')->statement("DROP DATABASE IF EXISTS `{$dbName}`;");
    }

    /**
     * Check if a tenant database exists.
     */
    public static function databaseExists(Tenant|string $tenant): bool
    {
        $dbName = is_string($tenant) ? $tenant : self::getDatabaseName($tenant);
        $dbName = preg_replace('/[^a-zA-Z0-9_]/', '', $dbName);

        $result = DB::connection('central')->select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?", [$dbName]);
        return !empty($result);
    }

    /**
     * Dynamically switch default database connection to the given tenant.
     */
    public static function switchToTenant(Tenant $tenant): void
    {
        $dbName = self::getDatabaseName($tenant);

        // Configure tenant connection
        Config::set('database.connections.tenant.database', $dbName);

        // Purge and reconnect
        DB::purge('tenant');
        DB::reconnect('tenant');

        // Set as default connection
        DB::setDefaultConnection('tenant');

        // Set in memory context
        TenantContext::setTenant($tenant);

        // Reset permission cache for current tenant database
        try {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
            // PermissionRegistrar may not be registered yet in early bootstraps
        }
    }

    /**
     * Switch back to central database connection.
     */
    public static function switchToCentral(): void
    {
        DB::setDefaultConnection('central');
        TenantContext::clear();
    }

    /**
     * Run migrations strictly on tenant's database.
     */
    public static function migrateTenant(Tenant $tenant): void
    {
        self::createDatabase($tenant);
        self::switchToTenant($tenant);

        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);
    }

    /**
     * Fresh migration on tenant's database.
     */
    public static function freshTenant(Tenant $tenant): void
    {
        self::createDatabase($tenant);
        self::switchToTenant($tenant);

        Artisan::call('migrate:fresh', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);
    }
}
