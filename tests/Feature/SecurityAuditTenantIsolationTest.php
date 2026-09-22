<?php

namespace Tests\Feature;

use App\Core\Tenancy\TenantContext;
use App\Core\Tenancy\TenantDatabaseManager;
use App\Models\Central\Domain;
use App\Models\Central\Tenant;
use App\Models\JobTitle;
use App\Models\Setting;
use App\Models\User;
use App\Services\Tenant\TenantProvisioningService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecurityAuditTenantIsolationTest extends TestCase
{
    protected TenantProvisioningService $provisioningService;
    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected User $ownerA;
    protected User $ownerB;
    protected string $slugA;
    protected string $slugB;
    protected string $tokenA;
    protected string $tokenB;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Ensure central database is fresh and migrated
        Artisan::call('migrate', [
            '--database' => 'central',
            '--path' => 'database/migrations/central',
            '--force' => true,
        ]);

        $this->provisioningService = app(TenantProvisioningService::class);

        $this->slugA = 'sec-alpha-' . time() . '-' . rand(10, 99);
        $this->slugB = 'sec-beta-' . time() . '-' . rand(10, 99);

        // 2. Provision Tenant A
        $resA = $this->provisioningService->provision([
            'name' => 'Sec Alpha Construction',
            'slug' => $this->slugA,
            'company_code' => 'ALPH-' . rand(100, 999),
            'domain' => $this->slugA . '.localhost',
        ], [
            'name' => 'Alpha Owner',
            'email' => "owner@{$this->slugA}.test",
            'password' => 'AlphaPass123!',
        ]);

        $this->tenantA = $resA['tenant'];
        $this->ownerA = $resA['owner'];

        // 3. Provision Tenant B
        $resB = $this->provisioningService->provision([
            'name' => 'Sec Beta Fit-Out',
            'slug' => $this->slugB,
            'company_code' => 'BETA-' . rand(100, 999),
            'domain' => $this->slugB . '.localhost',
        ], [
            'name' => 'Beta Owner',
            'email' => "owner@{$this->slugB}.test",
            'password' => 'BetaPass456!',
        ]);

        $this->tenantB = $resB['tenant'];
        $this->ownerB = $resB['owner'];

        // Generate Sanctum tokens
        TenantDatabaseManager::switchToTenant($this->tenantA);
        $this->tokenA = $this->ownerA->createToken('test_token_a')->plainTextToken;

        TenantDatabaseManager::switchToTenant($this->tenantB);
        $this->tokenB = $this->ownerB->createToken('test_token_b')->plainTextToken;
    }

    protected function tearDown(): void
    {
        try {
            TenantDatabaseManager::dropDatabase($this->tenantA);
            TenantDatabaseManager::dropDatabase($this->tenantB);
            $this->tenantA->domains()->delete();
            $this->tenantB->domains()->delete();
            $this->tenantA->delete();
            $this->tenantB->delete();
        } catch (\Throwable $e) {
            // Ignore cleanup errors in teardown
        }

        parent::tearDown();
    }

    /**
     * Test 1: Tenant Resolution & Header/Query Override Attacks
     */
    public function test_domain_takes_strict_precedence_over_header_and_query_overrides(): void
    {
        // Attacker accesses Tenant A domain URL but passes Header 'X-Tenant-Slug: tenant-b'
        $response = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugB,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->getJson("http://{$this->slugA}.localhost/api/auth/me");

        // Must authenticate as Tenant A owner and NOT switch to Tenant B
        $response->assertStatus(200);
        $response->assertJsonPath('data.user.email', $this->ownerA->email);
        $response->assertJsonPath('data.tenant.slug', $this->slugA);
        $this->assertEquals($this->slugA, TenantContext::getTenantSlug());
    }

    /**
     * Test 2: Token Cross-Tenant Hijacking Attack
     * An attacker with a valid Tenant A token attempts to query Tenant B's API using X-Tenant-Slug.
     */
    public function test_tenant_a_token_cannot_access_tenant_b_data(): void
    {
        $response = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugB,
            'Authorization' => 'Bearer ' . $this->tokenA, // Token issued in Tenant A
        ])->getJson('/api/users');

        // Must be rejected because Token A does not exist in Tenant B database
        $response->assertStatus(401);
    }

    /**
     * Test 3: Authentication Isolation with Identical Emails Across Tenants
     */
    public function test_identical_email_across_tenants_authenticates_strictly_against_own_database(): void
    {
        $sharedEmail = 'same.architect@sarh.test';

        // Create user in Tenant A with PasswordA
        TenantDatabaseManager::switchToTenant($this->tenantA);
        User::create([
            'name' => 'Engineer in A',
            'email' => $sharedEmail,
            'password' => bcrypt('PasswordInTenantA!'),
            'status' => 'active',
        ]);

        // Create user in Tenant B with PasswordB
        TenantDatabaseManager::switchToTenant($this->tenantB);
        User::create([
            'name' => 'Engineer in B',
            'email' => $sharedEmail,
            'password' => bcrypt('PasswordInTenantB!'),
            'status' => 'active',
        ]);

        // 1. Attempting login in Tenant A with PasswordB must FAIL
        $loginFailA = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
        ])->postJson('/api/auth/login', [
            'email' => $sharedEmail,
            'password' => 'PasswordInTenantB!',
        ]);
        $loginFailA->assertStatus(422);

        // 2. Login in Tenant A with PasswordA must SUCCEED
        $loginPassA = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
        ])->postJson('/api/auth/login', [
            'email' => $sharedEmail,
            'password' => 'PasswordInTenantA!',
        ]);
        $loginPassA->assertStatus(200);
        $loginPassA->assertJsonPath('data.user.name', 'Engineer in A');
        $loginPassA->assertJsonPath('data.tenant.slug', $this->slugA);

        // 3. Login in Tenant B with PasswordB must SUCCEED as Engineer in B
        $loginPassB = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugB,
        ])->postJson('/api/auth/login', [
            'email' => $sharedEmail,
            'password' => 'PasswordInTenantB!',
        ]);
        $loginPassB->assertStatus(200);
        $loginPassB->assertJsonPath('data.user.name', 'Engineer in B');
        $loginPassB->assertJsonPath('data.tenant.slug', $this->slugB);
    }

    /**
     * Test 4: Inactive User Authentication Rejection
     */
    public function test_inactive_user_cannot_login(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);
        $inactiveUser = User::create([
            'name' => 'Inactive Worker',
            'email' => 'inactive@alpha.test',
            'password' => bcrypt('secret123'),
            'status' => 'inactive',
        ]);

        $response = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
        ])->postJson('/api/auth/login', [
            'email' => 'inactive@alpha.test',
            'password' => 'secret123',
        ]);

        $response->assertStatus(403);
        $response->assertJsonPath('error_code', 'USER_INACTIVE');
    }

    /**
     * Test 5: IDOR and Resource Isolation with Matching Numeric IDs
     */
    public function test_idor_cross_tenant_resource_manipulation_is_impossible(): void
    {
        // 1. Create a user in Tenant A (will have a specific ID, e.g. ID 2)
        TenantDatabaseManager::switchToTenant($this->tenantA);
        $jobTitleA = JobTitle::first();
        $userA = User::create([
            'name' => 'Alpha Private User',
            'email' => 'private@alpha.test',
            'password' => bcrypt('secret123'),
            'job_title_id' => $jobTitleA?->id,
            'status' => 'active',
        ]);
        $userAId = $userA->id;

        // 2. Create a user in Tenant B (will also have ID 2 in its own DB)
        TenantDatabaseManager::switchToTenant($this->tenantB);
        $jobTitleB = JobTitle::first();
        $userB = User::create([
            'name' => 'Beta Sensitive User',
            'email' => 'sensitive@beta.test',
            'password' => bcrypt('secret123'),
            'job_title_id' => $jobTitleB?->id,
            'status' => 'active',
        ]);
        $userBId = $userB->id;

        // Verify ID collision (both have ID 2 in their respective databases)
        $this->assertEquals($userAId, $userBId);

        // 3. User in Tenant A updates user by ID 2 via API
        $updateResponse = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->putJson("/api/users/{$userAId}", [
            'name' => 'Alpha User Renamed',
            'email' => 'private.renamed@alpha.test',
            'status' => 'active',
            'job_title_id' => $userA->job_title_id,
        ]);

        $updateResponse->assertStatus(200);

        // 4. Assert Tenant A was updated
        TenantDatabaseManager::switchToTenant($this->tenantA);
        $this->assertEquals('Alpha User Renamed', $userA->fresh()->name);

        // 5. Assert Tenant B record with the EXACT SAME ID remained 100% UNTOUCHED
        TenantDatabaseManager::switchToTenant($this->tenantB);
        $this->assertEquals('Beta Sensitive User', $userB->fresh()->name);
        $this->assertEquals('sensitive@beta.test', $userB->fresh()->email);
    }

    /**
     * Test 6: Cross-Tenant Users Listing & Count Isolation
     */
    public function test_tenant_a_cannot_list_or_see_tenant_b_users(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantB);
        User::create([
            'name' => 'Beta Secret Employee',
            'email' => 'secret.employee@beta.test',
            'password' => bcrypt('secret123'),
            'status' => 'active',
        ]);

        $response = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->getJson('/api/users');

        $response->assertStatus(200);
        $emails = collect($response->json('data'))->pluck('email');
        $this->assertFalse($emails->contains('secret.employee@beta.test'));
        $this->assertTrue($emails->contains($this->ownerA->email));
    }

    /**
     * Test 7: Roles & Permissions Multi-Tenant Isolation
     */
    public function test_roles_and_permissions_are_completely_isolated(): void
    {
        // 1. Create a custom role in Tenant A
        TenantDatabaseManager::switchToTenant($this->tenantA);
        $customRoleA = Role::create([
            'name' => 'Alpha Custom Inspector',
            'display_name' => 'مفتش جودة ألفا',
            'guard_name' => 'web',
        ]);

        // 2. Switch to Tenant B and assert role does NOT exist
        TenantDatabaseManager::switchToTenant($this->tenantB);
        $this->assertNull(Role::where('name', 'Alpha Custom Inspector')->first());

        // 3. Verify Spatie Permission Cache key isolation
        $this->assertEquals('spatie.permission.cache.tenant.' . $this->tenantB->id, config('permission.cache.key'));

        TenantDatabaseManager::switchToTenant($this->tenantA);
        $this->assertEquals('spatie.permission.cache.tenant.' . $this->tenantA->id, config('permission.cache.key'));
    }

    /**
     * Test 8: Job Title Does Not Directly Grant Authorization (Must use Roles & Permissions)
     */
    public function test_job_title_does_not_override_rbac_authorization(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $directorTitle = JobTitle::where('name', 'General Manager')->first() ?? JobTitle::first();
        $viewerRole = Role::where('name', 'Viewer')->first();

        // Create user with executive Job Title 'General Manager', but low permission Role 'Viewer'
        $user = User::create([
            'name' => 'Executive Viewer',
            'email' => 'exec.viewer@alpha.test',
            'password' => bcrypt('secret123'),
            'job_title_id' => $directorTitle?->id,
            'status' => 'active',
        ]);
        $user->assignRole($viewerRole);

        $token = $user->createToken('viewer_token')->plainTextToken;

        // Attempting admin action (creating a new user) must be FORBIDDEN (403)
        $forbiddenResponse = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/users', [
            'name' => 'Unauthorized User',
            'email' => 'unauthorized@alpha.test',
            'password' => '12345678',
            'status' => 'active',
            'job_title_id' => $directorTitle?->id,
        ]);

        $forbiddenResponse->assertStatus(403);
    }

    /**
     * Test 9: Activity Logs Isolation Across Tenants
     */
    public function test_activity_logs_never_leak_across_tenants(): void
    {
        // 1. Generate activity in Tenant A
        TenantDatabaseManager::switchToTenant($this->tenantA);
        activity('audit')
            ->performedOn($this->ownerA)
            ->causedBy($this->ownerA)
            ->log('Alpha highly confidential board decision');

        // 2. Query activity logs in Tenant B via API
        $responseB = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugB,
            'Authorization' => 'Bearer ' . $this->tokenB,
        ])->getJson('/api/activity-logs');

        $responseB->assertStatus(200);
        $logs = collect($responseB->json('data'))->pluck('description');
        $this->assertFalse($logs->contains('Alpha highly confidential board decision'));

        // 3. Query activity logs in Tenant A via API -> Must exist
        $responseA = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->getJson('/api/activity-logs');

        $responseA->assertStatus(200);
        $logsA = collect($responseA->json('data'))->pluck('description');
        $this->assertTrue($logsA->contains('Alpha highly confidential board decision'));
    }

    /**
     * Test 10: Media Storage Disk Path Isolation
     */
    public function test_media_storage_paths_are_physically_scoped_by_tenant(): void
    {
        Storage::fake('public');

        // 1. Upload avatar in Tenant A
        TenantDatabaseManager::switchToTenant($this->tenantA);
        $fileA = UploadedFile::fake()->image('alpha_avatar.jpg', 300, 300);
        $this->ownerA->clearMediaCollection('avatar');
        $mediaA = $this->ownerA->addMedia($fileA)->toMediaCollection('avatar');

        // 2. Upload avatar in Tenant B
        TenantDatabaseManager::switchToTenant($this->tenantB);
        $fileB = UploadedFile::fake()->image('beta_avatar.jpg', 300, 300);
        $this->ownerB->clearMediaCollection('avatar');
        $mediaB = $this->ownerB->addMedia($fileB)->toMediaCollection('avatar');

        // 3. Verify media paths in storage contain tenant slug
        $pathA = $mediaA->getPath();
        $pathB = $mediaB->getPath();

        $this->assertStringContainsString("tenants/{$this->slugA}", $pathA);
        $this->assertStringContainsString("tenants/{$this->slugB}", $pathB);

        // 4. Verify separate directories
        $this->assertNotEquals($pathA, $pathB);
    }

    /**
     * Test 11: Settings and Configuration Isolation
     */
    public function test_settings_are_strictly_isolated_per_tenant(): void
    {
        // 1. Set distinct VAT setting in Tenant A
        TenantDatabaseManager::switchToTenant($this->tenantA);
        Setting::set('company_vat_rate', '15.00', 'finance');

        // 2. Set distinct VAT setting in Tenant B
        TenantDatabaseManager::switchToTenant($this->tenantB);
        Setting::set('company_vat_rate', '5.00', 'finance');

        // 3. Retrieve via API in Tenant A
        $resA = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->getJson('/api/settings');

        $resA->assertStatus(200);
        $vatA = collect($resA->json('data.finance'))->firstWhere('key', 'company_vat_rate');
        $this->assertEquals('15.00', $vatA['value']);

        // 4. Retrieve via API in Tenant B
        $resB = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugB,
            'Authorization' => 'Bearer ' . $this->tokenB,
        ])->getJson('/api/settings');

        $resB->assertStatus(200);
        $vatB = collect($resB->json('data.finance'))->firstWhere('key', 'company_vat_rate');
        $this->assertEquals('5.00', $vatB['value']);
    }

    /**
     * Test 12: Tenant Provisioning Failure Rollback & Safe Cleanup
     */
    public function test_tenant_provisioning_failure_triggers_safe_rollback(): void
    {
        $failingSlug = 'fail-test-' . time();
        $thrown = false;

        try {
            $this->provisioningService->provision([
                'name' => null, // Violates NOT NULL database constraint
                'slug' => $failingSlug,
                'company_code' => 'FAIL-999',
                'domain' => $failingSlug . '.localhost',
            ], [
                'name' => 'Owner',
                'email' => 'owner@fail.test',
                'password' => '12345678',
            ]);
        } catch (\Throwable $e) {
            $thrown = true;
            $this->assertStringContainsString('Tenant provisioning failed', $e->getMessage());
        }

        $this->assertTrue($thrown, 'Provisioning should have thrown an exception');

        // Assert Central database has no orphaned active records
        TenantDatabaseManager::switchToCentral();
        $this->assertDatabaseMissing('tenants', ['slug' => $failingSlug]);
        $this->assertDatabaseMissing('domains', ['domain' => $failingSlug . '.localhost']);
    }
}
