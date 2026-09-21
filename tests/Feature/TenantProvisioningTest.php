<?php

namespace Tests\Feature;

use App\Core\Tenancy\TenantDatabaseManager;
use App\Models\Central\Domain;
use App\Models\Central\Tenant;
use App\Models\JobTitle;
use App\Models\User;
use App\Services\Tenant\TenantProvisioningService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantProvisioningTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure central database is migrated
        Artisan::call('migrate', [
            '--database' => 'central',
            '--path' => 'database/migrations/central',
            '--force' => true,
        ]);
    }

    public function test_can_provision_new_tenant_successfully(): void
    {
        $provisioningService = app(TenantProvisioningService::class);

        $tenantSlug = 'test-co-' . time();
        $tenantData = [
            'name' => 'شركة الاختبار للمقاولات',
            'slug' => $tenantSlug,
            'company_code' => 'TEST-' . rand(100, 999),
            'domain' => $tenantSlug . '.localhost',
            'plan' => 'enterprise',
        ];

        $ownerData = [
            'name' => 'المهندس أحمد علي',
            'email' => "owner_{$tenantSlug}@sarh.test",
            'phone' => '01012345678',
            'password' => 'password123',
        ];

        $result = $provisioningService->provision($tenantData, $ownerData);

        /** @var Tenant $tenant */
        $tenant = $result['tenant'];
        /** @var Domain $domain */
        $domain = $result['domain'];
        /** @var User $owner */
        $owner = $result['owner'];

        // 1. Assert Central Database records
        $this->assertNotNull($tenant);
        $this->assertEquals('active', $tenant->status);
        $this->assertEquals($tenantSlug, $tenant->slug);
        $this->assertNotNull($domain);
        $this->assertEquals($tenant->id, $domain->tenant_id);

        // 2. Assert Tenant Database exists
        $this->assertTrue(TenantDatabaseManager::databaseExists($tenant));

        // 3. Assert Default Job Titles are seeded (All 34 titles)
        $jobTitlesCount = JobTitle::count();
        $this->assertEquals(34, $jobTitlesCount, "Job Titles count must be exactly 34");
        $this->assertDatabaseHas('job_titles', ['name' => 'Owner']);
        $this->assertDatabaseHas('job_titles', ['name' => 'Site Engineer']);
        $this->assertDatabaseHas('job_titles', ['name' => 'Project Manager']);

        // 4. Assert Default Roles are seeded
        $rolesCount = Role::count();
        $this->assertGreaterThanOrEqual(19, $rolesCount, "Roles count must be at least 19");
        $this->assertDatabaseHas('roles', ['name' => 'Owner']);
        $this->assertDatabaseHas('roles', ['name' => 'Site Engineer']);
        $this->assertDatabaseHas('roles', ['name' => 'Viewer']);

        // 5. Assert Default Permissions are seeded across modules
        $this->assertDatabaseHas('permissions', ['name' => 'dashboard.view']);
        $this->assertDatabaseHas('permissions', ['name' => 'users.create']);
        $this->assertDatabaseHas('permissions', ['name' => 'boq.view']);
        $this->assertDatabaseHas('permissions', ['name' => 'media.upload']);

        // 6. Assert Owner User is created inside Tenant Database with Owner Role
        $this->assertNotNull($owner);
        $this->assertEquals($ownerData['email'], $owner->email);
        $this->assertTrue($owner->hasRole('Owner'));
        $this->assertEquals('active', $owner->status);

        // 7. Assert Owner has all permissions
        $allPermissionsCount = Permission::count();
        $ownerPermissionsCount = $owner->getAllPermissions()->count();
        $this->assertEquals($allPermissionsCount, $ownerPermissionsCount, "Owner must possess all permissions");

        // Clean up
        TenantDatabaseManager::dropDatabase($tenant);
        $domain->delete();
        $tenant->delete();
    }
}
