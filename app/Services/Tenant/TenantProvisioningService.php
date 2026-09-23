<?php

namespace App\Services\Tenant;

use App\Core\Tenancy\TenantContext;
use App\Core\Tenancy\TenantDatabaseManager;
use App\Models\Central\Domain;
use App\Models\Central\Tenant;
use App\Models\JobTitle;
use App\Models\User;
use Database\Seeders\Tenant\DefaultJobTitlesSeeder;
use Database\Seeders\Tenant\DefaultPermissionsSeeder;
use Database\Seeders\Tenant\DefaultRolesSeeder;
use Database\Seeders\Tenant\DefaultSettingsSeeder;
use Database\Seeders\Tenant\RolePermissionMappingSeeder;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TenantProvisioningService
{
    /**
     * Provision a new tenant and initialize its dedicated database and default state.
     *
     * @param array $tenantData  [name, slug, company_code, domain, plan, settings]
     * @param array $ownerData   [name, email, phone, password]
     * @return array [tenant, domain, owner]
     * @throws Exception
     */
    public function provision(array $tenantData, array $ownerData): array
    {
        $slug = Str::slug($tenantData['slug'] ?? Str::slug($tenantData['name']));
        $companyCode = strtoupper($tenantData['company_code'] ?? ('SARH-' . strtoupper(Str::random(4))));
        $domainName = strtolower(trim($tenantData['domain'] ?? ($slug . '.localhost')));

        $tenant = null;
        $domain = null;
        $owner = null;
        $dbCreated = false;
        $dbName = null;

        try {
            // 1. Create Tenant Record in Central DB (marked as provisioning)
            DB::connection('central')->beginTransaction();

            $tenant = Tenant::create([
                'name' => $tenantData['name'],
                'slug' => $slug,
                'company_code' => $companyCode,
                'database_name' => $tenantData['database_name'] ?? 'sarh_tenant_pending',
                'status' => 'provisioning',
                'plan' => $tenantData['plan'] ?? 'enterprise',
                'settings' => $tenantData['settings'] ?? [
                    'created_by' => 'system_provisioning',
                ],
            ]);

            // Sequential numbering: sarh_tenant_1, sarh_tenant_2, ...
            $dbName = $tenantData['database_name'] ?? ('sarh_tenant_' . $tenant->id);
            $tenant->database_name = $dbName;
            $tenant->save();

            // 2. Create Domain Record in Central DB
            $domain = Domain::create([
                'tenant_id' => $tenant->id,
                'domain' => $domainName,
                'is_primary' => true,
            ]);

            DB::connection('central')->commit();

            // 3. Create Tenant Physical Database (fresh)
            TenantDatabaseManager::createDatabase($tenant, true);
            $dbCreated = true;

            // 4. Run Tenant Migrations
            TenantDatabaseManager::migrateTenant($tenant);

            // 5. Seed Default Data (Idempotent seeders)
            (new DefaultSettingsSeeder())->run();
            (new DefaultJobTitlesSeeder())->run();
            (new DefaultPermissionsSeeder())->run();
            (new DefaultRolesSeeder())->run();
            (new RolePermissionMappingSeeder())->run();

            // 6. Find 'Owner' Job Title
            $ownerJobTitle = JobTitle::where('name', 'Owner')->first();

            // 7. Create Owner User inside Tenant Database
            $owner = User::create([
                'name' => $ownerData['name'],
                'email' => strtolower(trim($ownerData['email'])),
                'phone' => $ownerData['phone'] ?? null,
                'password' => Hash::make($ownerData['password'] ?? '12345678'),
                'job_title_id' => $ownerJobTitle?->id,
                'status' => 'active',
                'joining_date' => now(),
            ]);

            // 8. Assign 'Owner' Role to Owner User via Spatie Permission
            $owner->assignRole('Owner');

            // 9. Mark Tenant as Active in Central DB
            DB::connection('central')->table('tenants')->where('id', $tenant->id)->update([
                'status' => 'active',
                'updated_at' => now(),
            ]);
            $tenant->refresh();

            // 10. Record Activity Log for provisioning
            activity('tenant')
                ->performedOn($owner)
                ->causedBy($owner)
                ->withProperties([
                    'tenant_slug' => $tenant->slug,
                    'tenant_name' => $tenant->name,
                    'database' => $tenant->database_name,
                    'domain' => $domain->domain,
                ])
                ->log(__('activity.tenant_provisioned', [
                    'tenant' => $tenant->name,
                    'owner' => $owner->name,
                ]));

            return [
                'tenant' => $tenant,
                'domain' => $domain,
                'owner' => $owner,
            ];

        } catch (\Throwable $e) {
            Log::error("Failed to provision tenant [{$slug}]: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            // Rollback Central DB transaction if active
            try {
                if (DB::connection('central')->transactionLevel() > 0) {
                    DB::connection('central')->rollBack();
                }
            } catch (\Throwable $rollbackError) {
                // Ignore
            }

            // Cleanup created database if needed
            if ($dbCreated && $tenant) {
                try {
                    TenantDatabaseManager::dropDatabase($tenant);
                } catch (\Throwable $dropError) {
                    Log::warning("Failed to drop tenant database on rollback [{$dbName}]: " . $dropError->getMessage());
                }
            }

            // Cleanup central records if created outside transaction
            if ($tenant && $tenant->exists) {
                try {
                    Domain::where('tenant_id', $tenant->id)->delete();
                    $tenant->delete();
                } catch (\Throwable $cleanupError) {
                    // Ignore
                }
            }

            // Revert back to central connection
            TenantDatabaseManager::switchToCentral();

            throw new Exception("Tenant provisioning failed: " . $e->getMessage(), 0, $e);
        }
    }
}
