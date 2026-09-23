<?php

namespace Tests\Feature;

use App\Core\Tenancy\TenantDatabaseManager;
use App\Models\Central\Tenant;
use App\Models\User;
use App\Services\Tenant\TenantProvisioningService;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate', [
            '--database' => 'central',
            '--path' => 'database/migrations/central',
            '--force' => true,
        ]);
    }

    public function test_two_tenants_have_completely_isolated_databases(): void
    {
        $provisioningService = app(TenantProvisioningService::class);

        $slugA = 'alpha-' . time();
        $slugB = 'beta-' . time();

        // 1. Provision Tenant A
        $resA = $provisioningService->provision([
            'name' => 'Company Alpha',
            'slug' => $slugA,
            'company_code' => 'ALPHA-' . uniqid(),
            'domain' => $slugA . '.localhost',
        ], [
            'name' => 'Alpha Owner',
            'email' => "owner@{$slugA}.test",
            'password' => 'secret123',
        ]);

        // 2. Provision Tenant B
        $resB = $provisioningService->provision([
            'name' => 'Company Beta',
            'slug' => $slugB,
            'company_code' => 'BETA-' . uniqid(),
            'domain' => $slugB . '.localhost',
        ], [
            'name' => 'Beta Owner',
            'email' => "owner@{$slugB}.test",
            'password' => 'secret123',
        ]);

        /** @var Tenant $tenantA */
        $tenantA = $resA['tenant'];
        /** @var Tenant $tenantB */
        $tenantB = $resB['tenant'];

        // 3. Switch to Tenant A and create a specific user
        TenantDatabaseManager::switchToTenant($tenantA);
        $userInA = User::create([
            'name' => 'Engineer in Alpha',
            'email' => 'engineer@alpha.test',
            'password' => bcrypt('12345678'),
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('users', ['email' => 'engineer@alpha.test']);

        // 4. Switch to Tenant B and assert user does NOT exist in Tenant B
        TenantDatabaseManager::switchToTenant($tenantB);
        $this->assertDatabaseMissing('users', ['email' => 'engineer@alpha.test']);
        $this->assertDatabaseHas('users', ['email' => "owner@{$slugB}.test"]);
        $this->assertDatabaseMissing('users', ['email' => "owner@{$slugA}.test"]);

        // 5. Test API tenant identification header isolation
        $loginResponseA = $this->withHeaders([
            'X-Tenant-Slug' => $slugA,
        ])->postJson('/api/auth/login', [
            'email' => 'engineer@alpha.test',
            'password' => '12345678',
        ]);

        $loginResponseA->assertStatus(200);
        $loginResponseA->assertJsonPath('data.user.email', 'engineer@alpha.test');

        // Trying to login with Alpha credentials under Beta tenant must fail (User does not exist in Beta)
        $loginResponseB = $this->withHeaders([
            'X-Tenant-Slug' => $slugB,
        ])->postJson('/api/auth/login', [
            'email' => 'engineer@alpha.test',
            'password' => '12345678',
        ]);

        $loginResponseB->assertStatus(422);

        // Clean up
        TenantDatabaseManager::dropDatabase($tenantA);
        TenantDatabaseManager::dropDatabase($tenantB);
        $resA['domain']->delete();
        $resB['domain']->delete();
        $tenantA->delete();
        $tenantB->delete();
    }
}
