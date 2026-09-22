<?php

namespace App\Console\Commands;

use App\Core\Tenancy\TenantDatabaseManager;
use App\Models\Central\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TenantsMigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:migrate
                            {--tenant= : Specific tenant slug or ID to migrate}
                            {--fresh : Drop all tables and re-run all migrations}
                            {--seed : Seed default data after migrations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run migrations across all tenant databases (or a specific tenant)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 SARH ERP — Starting Tenant Database Migrations...');

        // Ensure central DB connection
        TenantDatabaseManager::switchToCentral();

        $tenantOption = $this->option('tenant');
        $query = Tenant::query();

        if ($tenantOption) {
            $query->where('slug', $tenantOption)->orWhere('id', $tenantOption);
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->warn('⚠️ No matching tenants found to migrate.');
            return self::SUCCESS;
        }

        $this->info("📦 Found {$tenants->count()} tenant(s) to process.\n");

        $migratedCount = 0;
        $failedCount = 0;

        foreach ($tenants as $tenant) {
            $this->line("<fg=cyan>--------------------------------------------------</>");
            $this->line("<fg=yellow>🏢 Tenant:</> <fg=white;options=bold>{$tenant->name}</> (<fg=green>{$tenant->slug}</>) [DB: <fg=magenta>{$tenant->database_name}</>]");

            try {
                // Ensure physical DB exists
                TenantDatabaseManager::createDatabase($tenant);

                // Run migration
                TenantDatabaseManager::migrateTenant($tenant);

                if ($this->option('seed')) {
                    $this->line("  🌱 Seeding tenant default data...");
                    (new \Database\Seeders\Tenant\DefaultSettingsSeeder())->run();
                    (new \Database\Seeders\Tenant\DefaultJobTitlesSeeder())->run();
                    (new \Database\Seeders\Tenant\DefaultPermissionsSeeder())->run();
                    (new \Database\Seeders\Tenant\DefaultRolesSeeder())->run();
                    (new \Database\Seeders\Tenant\RolePermissionMappingSeeder())->run();
                }

                $this->info("  ✅ Migrations executed successfully.");
                $migratedCount++;
            } catch (\Throwable $e) {
                $this->error("  ❌ Failed migrating tenant [{$tenant->slug}]: " . $e->getMessage());
                $failedCount++;
            }
        }

        // Return to central
        TenantDatabaseManager::switchToCentral();

        $this->line("<fg=cyan>==================================================</>");
        if ($failedCount === 0) {
            $this->info("🎉 All {$migratedCount} tenant database(s) migrated successfully with 0 errors.");
            return self::SUCCESS;
        } else {
            $this->warn("⚠️ Completed with errors: {$migratedCount} succeeded, {$failedCount} failed.");
            return self::FAILURE;
        }
    }
}
