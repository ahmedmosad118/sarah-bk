<?php

namespace Tests\Feature;

use App\Core\Tenancy\TenantContext;
use App\Core\Tenancy\TenantDatabaseManager;
use App\Models\Central\Tenant;
use App\Models\Customer;
use App\Models\JobTitle;
use App\Models\User;
use App\Services\Tenant\TenantProvisioningService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
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

        Artisan::call('migrate', [
            '--database' => 'central',
            '--path' => 'database/migrations/central',
            '--force' => true,
        ]);

        $this->provisioningService = app(TenantProvisioningService::class);

        $this->slugA = 'cust-alpha-' . time() . '-' . rand(10, 99);
        $this->slugB = 'cust-beta-' . time() . '-' . rand(10, 99);

        // Provision Tenant A
        $resA = $this->provisioningService->provision([
            'name' => 'Alpha Contracting Co',
            'slug' => $this->slugA,
            'company_code' => 'ALPH-' . uniqid(),
            'domain' => $this->slugA . '.localhost',
        ], [
            'name' => 'Alpha Owner',
            'email' => "owner@{$this->slugA}.test",
            'password' => 'AlphaPass123!',
        ]);

        $this->tenantA = $resA['tenant'];
        $this->ownerA = $resA['owner'];

        // Provision Tenant B
        $resB = $this->provisioningService->provision([
            'name' => 'Beta Engineering Co',
            'slug' => $this->slugB,
            'company_code' => 'BETA-' . uniqid(),
            'domain' => $this->slugB . '.localhost',
        ], [
            'name' => 'Beta Owner',
            'email' => "owner@{$this->slugB}.test",
            'password' => 'BetaPass456!',
        ]);

        $this->tenantB = $resB['tenant'];
        $this->ownerB = $resB['owner'];

        // Issue tokens
        TenantDatabaseManager::switchToTenant($this->tenantA);
        $this->tokenA = $this->ownerA->createToken('token_a')->plainTextToken;

        TenantDatabaseManager::switchToTenant($this->tenantB);
        $this->tokenB = $this->ownerB->createToken('token_b')->plainTextToken;
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
            // Ignore teardown errors
        }

        parent::tearDown();
    }

    /**
     * Test 1: Customer CRUD - Create Individual & Company, List, Show, Update, Delete
     */
    public function test_customer_crud_lifecycle(): void
    {
        // 1. Create Individual Customer
        $createRes1 = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson('/api/customers', [
            'customer_type' => 'individual',
            'name' => 'Tariq Mansoor',
            'phone' => '0551234567',
            'whatsapp' => '0551234567',
            'email' => 'tariq@client.test',
            'address' => 'Riyadh, Al-Nakheel District',
            'notes' => 'Villa decoration and interior fit-out inquiry',
            'status' => 'active',
        ]);

        $createRes1->assertStatus(201);
        $createRes1->assertJsonPath('data.name', 'Tariq Mansoor');
        $createRes1->assertJsonPath('data.customer_type', 'individual');
        $customerId = $createRes1->json('data.id');

        // 2. Create Company Customer
        $createRes2 = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson('/api/customers', [
            'customer_type' => 'company',
            'name' => 'Eng. Fahad Al-Otaibi',
            'company_name' => 'Al-Falak Real Estate Investment',
            'phone' => '0114567890',
            'whatsapp' => '0509876543',
            'email' => 'contact@alfalak.test',
            'address' => 'King Fahd Road, Tower 4, Floor 12',
            'status' => 'active',
        ]);

        $createRes2->assertStatus(201);
        $createRes2->assertJsonPath('data.company_name', 'Al-Falak Real Estate Investment');
        $createRes2->assertJsonPath('data.customer_type', 'company');

        // 3. List Customers
        $listRes = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->getJson('/api/customers');

        $listRes->assertStatus(200);
        $listRes->assertJsonPath('meta.total', 2);
        $listRes->assertJsonPath('stats.individuals', 1);
        $listRes->assertJsonPath('stats.companies', 1);

        // 4. Show Customer Details
        $showRes = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->getJson("/api/customers/{$customerId}");

        $showRes->assertStatus(200);
        $showRes->assertJsonPath('data.name', 'Tariq Mansoor');

        // 5. Update Customer
        $updateRes = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->putJson("/api/customers/{$customerId}", [
            'customer_type' => 'individual',
            'name' => 'Tariq Mansoor Al-Harbi',
            'phone' => '0559998888',
            'status' => 'inactive',
        ]);

        $updateRes->assertStatus(200);
        $updateRes->assertJsonPath('data.name', 'Tariq Mansoor Al-Harbi');
        $updateRes->assertJsonPath('data.status', 'inactive');

        // 6. Delete Customer
        $deleteRes = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->deleteJson("/api/customers/{$customerId}");

        $deleteRes->assertStatus(200);

        TenantDatabaseManager::switchToTenant($this->tenantA);
        $this->assertNull(Customer::find($customerId));
    }

    /**
     * Test 2: Server-side Validation Rules
     */
    public function test_customer_validation_enforces_correct_data(): void
    {
        // 1. Invalid customer_type
        $res1 = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson('/api/customers', [
            'customer_type' => 'invalid_type',
            'name' => 'Valid Name',
            'status' => 'active',
        ]);
        $res1->assertStatus(422);
        $res1->assertJsonValidationErrors(['customer_type']);

        // 2. Missing name
        $res2 = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson('/api/customers', [
            'customer_type' => 'individual',
            'name' => '',
            'status' => 'active',
        ]);
        $res2->assertStatus(422);
        $res2->assertJsonValidationErrors(['name']);

        // 3. Invalid email format
        $res3 = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson('/api/customers', [
            'customer_type' => 'individual',
            'name' => 'Valid Name',
            'email' => 'not-a-valid-email',
            'status' => 'active',
        ]);
        $res3->assertStatus(422);
        $res3->assertJsonValidationErrors(['email']);

        // 4. Invalid status
        $res4 = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson('/api/customers', [
            'customer_type' => 'individual',
            'name' => 'Valid Name',
            'status' => 'unknown_status',
        ]);
        $res4->assertStatus(422);
        $res4->assertJsonValidationErrors(['status']);
    }

    /**
     * Test 3: Authorization (RBAC Permissions)
     */
    public function test_customer_permissions_are_strictly_enforced(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $unauthorizedUser = User::create([
            'name' => 'Restricted Staff',
            'email' => 'restricted@alpha.test',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);
        $unauthorizedToken = $unauthorizedUser->createToken('unauth_token')->plainTextToken;

        // 1. Unauthorized View -> 403
        $viewRes = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $unauthorizedToken,
        ])->getJson('/api/customers');
        $viewRes->assertStatus(403);

        // 2. Unauthorized Create -> 403
        $createRes = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $unauthorizedToken,
        ])->postJson('/api/customers', [
            'customer_type' => 'individual',
            'name' => 'Hacked Client',
            'status' => 'active',
        ]);
        $createRes->assertStatus(403);

        // 3. Grant 'customers.view' only
        TenantDatabaseManager::switchToTenant($this->tenantA);
        $viewRole = Role::create([
            'name' => 'Customer Viewer',
            'guard_name' => 'web',
        ]);
        $viewRole->givePermissionTo('customers.view');
        $unauthorizedUser->assignRole($viewRole);
        $unauthorizedUser->unsetRelation('roles');
        $unauthorizedUser->unsetRelation('permissions');
        $this->app['auth']->forgetGuards();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        // Now view succeeds
        $viewPass = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $unauthorizedToken,
        ])->getJson('/api/customers');
        $viewPass->assertStatus(200);

        // But create is still Forbidden (403)
        $createStillForbidden = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $unauthorizedToken,
        ])->postJson('/api/customers', [
            'customer_type' => 'individual',
            'name' => 'Hacked Client',
            'status' => 'active',
        ]);
        $createStillForbidden->assertStatus(403);
    }

    /**
     * Test 4: Multi-Role Aggregation
     */
    public function test_multi_role_aggregation_for_customers(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $roleViewer = Role::create(['name' => 'Role Cust Viewer', 'guard_name' => 'web']);
        $roleViewer->givePermissionTo('customers.view');

        $roleCreator = Role::create(['name' => 'Role Cust Creator', 'guard_name' => 'web']);
        $roleCreator->givePermissionTo('customers.create');

        $multiUser = User::create([
            'name' => 'Multi Role Officer',
            'email' => 'multi@alpha.test',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);
        $multiUser->assignRole($roleViewer, $roleCreator);

        $token = $multiUser->createToken('multi_token')->plainTextToken;

        // Can list
        $resList = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/customers');
        $resList->assertStatus(200);

        // Can create
        $resCreate = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/customers', [
            'customer_type' => 'individual',
            'name' => 'Multi Role Created Client',
            'status' => 'active',
        ]);
        $resCreate->assertStatus(201);
    }

    /**
     * Test 5: Multi-Tenant Customer Isolation
     */
    public function test_tenant_customers_are_completely_isolated_across_databases(): void
    {
        // Create Customer in Tenant A
        TenantDatabaseManager::switchToTenant($this->tenantA);
        $custA = Customer::create([
            'customer_type' => 'individual',
            'name' => 'Alpha Top Secret Client',
            'phone' => '0501111111',
            'status' => 'active',
        ]);

        // Create Customer in Tenant B
        TenantDatabaseManager::switchToTenant($this->tenantB);
        $custB = Customer::create([
            'customer_type' => 'company',
            'name' => 'Beta Representative',
            'company_name' => 'Beta Global Contracting',
            'phone' => '0502222222',
            'status' => 'active',
        ]);

        // 1. Query Tenant A via API -> Must contain custA, MUST NOT contain custB
        $resA = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->getJson('/api/customers');

        $resA->assertStatus(200);
        $namesA = collect($resA->json('data'))->pluck('name');
        $this->assertTrue($namesA->contains('Alpha Top Secret Client'));
        $this->assertFalse($namesA->contains('Beta Representative'));

        // 2. Query Tenant B via API -> Must contain custB, MUST NOT contain custA
        $resB = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugB,
            'Authorization' => 'Bearer ' . $this->tokenB,
        ])->getJson('/api/customers');

        $resB->assertStatus(200);
        $namesB = collect($resB->json('data'))->pluck('name');
        $this->assertTrue($namesB->contains('Beta Representative'));
        $this->assertFalse($namesB->contains('Alpha Top Secret Client'));
    }

    /**
     * Test 6: IDOR Protection with Colliding Numeric IDs
     */
    public function test_idor_cross_tenant_customer_manipulation_is_blocked(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);
        $custA = Customer::create([
            'customer_type' => 'individual',
            'name' => 'Alpha Target Client',
            'status' => 'active',
        ]);
        $custAId = $custA->id;

        TenantDatabaseManager::switchToTenant($this->tenantB);
        $custB = Customer::create([
            'customer_type' => 'company',
            'name' => 'Beta Protected Enterprise',
            'company_name' => 'Untouchable Ltd',
            'status' => 'active',
        ]);
        $custBId = $custB->id;

        // Verify ID collision across independent databases
        $this->assertEquals($custAId, $custBId);

        // Tenant A updates its Customer ID 1
        $updateRes = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->putJson("/api/customers/{$custAId}", [
            'customer_type' => 'individual',
            'name' => 'Alpha Target Client Renamed',
            'status' => 'active',
        ]);
        $updateRes->assertStatus(200);

        // Assert Tenant A customer was updated
        TenantDatabaseManager::switchToTenant($this->tenantA);
        $this->assertEquals('Alpha Target Client Renamed', $custA->fresh()->name);

        // Assert Tenant B customer with the EXACT SAME ID remained 100% untouched
        TenantDatabaseManager::switchToTenant($this->tenantB);
        $this->assertEquals('Beta Protected Enterprise', $custB->fresh()->name);
        $this->assertEquals('Untouchable Ltd', $custB->fresh()->company_name);
    }

    /**
     * Test 7: Search and Filter Isolation
     */
    public function test_customer_search_and_filters_never_leak_across_tenants(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);
        Customer::create([
            'customer_type' => 'company',
            'name' => 'Zaid Contractor',
            'company_name' => 'UniqueSearchCompanyXYZ',
            'phone' => '0599999999',
            'status' => 'active',
        ]);

        // Search for 'UniqueSearchCompanyXYZ' from Tenant B context
        $searchRes = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugB,
            'Authorization' => 'Bearer ' . $this->tokenB,
        ])->getJson('/api/customers?search=UniqueSearchCompanyXYZ');

        $searchRes->assertStatus(200);
        $this->assertCount(0, $searchRes->json('data'));
    }

    /**
     * Test 8: Activity Log Generation & Tenant Scoping
     */
    public function test_customer_mutations_generate_activity_logs(): void
    {
        // 1. Create Customer via API
        $res = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson('/api/customers', [
            'customer_type' => 'individual',
            'name' => 'Salim Al-Ghamdi',
            'status' => 'active',
        ]);
        $res->assertStatus(201);
        $id = $res->json('data.id');

        TenantDatabaseManager::switchToTenant($this->tenantA);
        $activity = Activity::where('subject_type', Customer::class)
            ->where('subject_id', $id)
            ->first();

        $this->assertNotNull($activity);
        $this->assertStringContainsString('Salim Al-Ghamdi', $activity->description);

        // 2. Activity in Tenant A must NOT exist in Tenant B
        TenantDatabaseManager::switchToTenant($this->tenantB);
        $activityInB = Activity::where('subject_type', Customer::class)
            ->where('description', 'like', '%Salim Al-Ghamdi%')
            ->first();

        $this->assertNull($activityInB);
    }

    /**
     * Test 9: Media / Document Attachment Scoping
     */
    public function test_customer_documents_media_upload_and_tenant_directory_isolation(): void
    {
        Storage::fake('public');

        TenantDatabaseManager::switchToTenant($this->tenantA);
        $customerA = Customer::create([
            'customer_type' => 'company',
            'name' => 'Corporate Client',
            'company_name' => 'Mega Projects Co',
            'status' => 'active',
        ]);

        $file = UploadedFile::fake()->create('contract_spec.pdf', 1024, 'application/pdf');
        $media = $customerA->addMedia($file)->toMediaCollection('documents');

        $path = $media->getPath();
        $this->assertStringContainsString("tenants/{$this->slugA}", $path);
    }

    /**
     * Test 10: Duplicate Phone and Email Allowance
     */
    public function test_duplicate_phone_and_email_are_permitted_for_different_customers(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $sharedPhone = '0505555555';
        $sharedEmail = 'shared.family.office@sarh.test';

        $c1 = Customer::create([
            'customer_type' => 'individual',
            'name' => 'Brother A',
            'phone' => $sharedPhone,
            'email' => $sharedEmail,
            'status' => 'active',
        ]);

        $c2 = Customer::create([
            'customer_type' => 'individual',
            'name' => 'Brother B',
            'phone' => $sharedPhone,
            'email' => $sharedEmail,
            'status' => 'active',
        ]);

        $this->assertNotNull($c1->id);
        $this->assertNotNull($c2->id);
        $this->assertNotEquals($c1->id, $c2->id);
    }

    /**
     * Test 11: Customer is Pure CRM Data Entity (No Access, No Tokens, No Spatie Roles/Permissions)
     *
     * Verifies that the Customer model is strictly CRM data. It does NOT extend Authenticatable,
     * does NOT use HasRoles or HasApiTokens, and has no password or credentials in the database.
     */
    public function test_customer_is_pure_crm_data_model_without_roles_permissions_or_tokens(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'customer_type' => 'individual',
            'name' => 'CRM Client Entity',
            'phone' => '0501234999',
            'email' => 'crm.client@test.local',
            'status' => 'active',
        ]);

        // 1. Customer is an Eloquent Model, NOT an Authenticatable User
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Model::class, $customer);
        $this->assertNotInstanceOf(\Illuminate\Contracts\Auth\Authenticatable::class, $customer);
        $this->assertNotInstanceOf(\Illuminate\Foundation\Auth\User::class, $customer);

        // 2. Customer table schema has NO password or secret auth credentials
        $tableColumns = \Illuminate\Support\Facades\Schema::getColumnListing('customers');
        $this->assertNotContains('password', $tableColumns);
        $this->assertNotContains('remember_token', $tableColumns);
        $this->assertNotContains('email_verified_at', $tableColumns);
        $this->assertNotContains('two_factor_secret', $tableColumns);

        // 3. Customer does NOT have Spatie HasRoles trait methods
        $this->assertFalse(method_exists($customer, 'assignRole'));
        $this->assertFalse(method_exists($customer, 'givePermissionTo'));
        $this->assertFalse(method_exists($customer, 'hasPermissionTo'));
        $this->assertFalse(method_exists($customer, 'hasRole'));

        // 4. Customer does NOT have Sanctum HasApiTokens trait methods
        $this->assertFalse(method_exists($customer, 'createToken'));
        $this->assertFalse(method_exists($customer, 'tokens'));
    }

    /**
     * Test 12: Customer Cannot Authenticate or Login via System Auth Endpoints
     *
     * Verifies that attempting to use a Customer's email/phone to log in to the tenant API fails.
     * System authentication is restricted exclusively to staff/admin User records.
     */
    public function test_customer_cannot_authenticate_or_login_to_system(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'customer_type' => 'individual',
            'name' => 'External Customer',
            'phone' => '0590001122',
            'email' => 'external.customer@company.com',
            'status' => 'active',
        ]);

        // Attempt login via /api/auth/login using customer email
        $loginRes = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
        ])->postJson('/api/auth/login', [
            'tenant' => $this->slugA,
            'email' => 'external.customer@company.com',
            'password' => 'AlphaPass123!',
        ]);

        // Login fails because Customers cannot authenticate
        $loginRes->assertStatus(422);

        // Verify unauthenticated requests to customer management endpoints are blocked (401)
        $unauthRes = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
        ])->getJson('/api/customers');

        $unauthRes->assertStatus(401);
    }
}
