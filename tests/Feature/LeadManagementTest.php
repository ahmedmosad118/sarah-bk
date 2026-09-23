<?php

namespace Tests\Feature;

use App\Core\Tenancy\TenantDatabaseManager;
use App\Models\Central\Tenant;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use App\Services\Tenant\TenantProvisioningService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeadManagementTest extends TestCase
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

        $this->slugA = 'lead-alpha-' . time() . '-' . rand(10, 99);
        $this->slugB = 'lead-beta-' . time() . '-' . rand(10, 99);

        // Provision Tenant A
        $resA = $this->provisioningService->provision([
            'name' => 'Alpha Builders Co',
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

        // Provision Tenant B
        $resB = $this->provisioningService->provision([
            'name' => 'Beta Developers Co',
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
     * 1. Test Lead CRUD Lifecycle.
     */
    public function test_lead_crud_lifecycle(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'customer_type' => 'individual',
            'name' => 'المهندس طارق منصور',
            'phone' => '01011223344',
            'status' => 'active',
        ]);

        // 1. Create Lead
        $resCreate = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson('/api/leads', [
            'customer_id' => $customer->id,
            'title' => 'تشطيب فيلا التجمع الخامس',
            'description' => 'تشطيب الترا لوكس مساحة 600 متر',
            'source' => 'Website',
            'status' => 'New',
            'estimated_value' => 750000.00,
            'expected_start_date' => '2026-11-01',
            'notes' => 'العميل يرغب في بدء المعاينة الأسبوع القادم',
        ]);

        $resCreate->assertStatus(201);
        $leadId = $resCreate->json('data.id');
        $this->assertNotNull($leadId);
        $this->assertEquals('تشطيب فيلا التجمع الخامس', $resCreate->json('data.title'));
        $this->assertEquals('750000.00', $resCreate->json('data.estimated_value'));

        // 2. Read Lead
        $resShow = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->getJson("/api/leads/{$leadId}");

        $resShow->assertStatus(200);
        $resShow->assertJsonPath('data.title', 'تشطيب فيلا التجمع الخامس');
        $resShow->assertJsonPath('data.customer.name', 'المهندس طارق منصور');

        // 3. Update Lead
        $resUpdate = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->putJson("/api/leads/{$leadId}", [
            'customer_id' => $customer->id,
            'title' => 'تشطيب فيلا التجمع الخامس - معدل',
            'source' => 'WhatsApp',
            'status' => 'Contacted',
            'estimated_value' => 850000.00,
            'expected_start_date' => '2026-11-15',
            'notes' => 'تم التواصل مع العميل وتحديد موعد',
        ]);

        $resUpdate->assertStatus(200);
        $this->assertEquals('تشطيب فيلا التجمع الخامس - معدل', $resUpdate->json('data.title'));
        $this->assertEquals('Contacted', $resUpdate->json('data.status'));

        // 4. List Leads with Stats
        $resList = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->getJson('/api/leads');

        $resList->assertStatus(200);
        $resList->assertJsonStructure([
            'success',
            'data',
            'meta',
            'stats' => ['total', 'new', 'contacted', 'qualified', 'converted', 'lost', 'total_estimated_value'],
            'schema',
        ]);
        $this->assertEquals(1, $resList->json('stats.total'));
        $this->assertEquals(1, $resList->json('stats.contacted'));

        // 5. Delete Lead
        $resDelete = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->deleteJson("/api/leads/{$leadId}");

        $resDelete->assertStatus(200);
        $this->assertDatabaseMissing('leads', ['id' => $leadId]);
    }

    /**
     * 2. Test Customer & User Eloquent Relationships.
     */
    public function test_lead_relationships(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'customer_type' => 'company',
            'name' => 'شركة إعمار مصر',
            'company_name' => 'إعمار',
            'status' => 'active',
        ]);

        $salesRep = User::create([
            'name' => 'مسؤول المبيعات خالد',
            'email' => 'khaled@alpha.test',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);

        $lead = Lead::create([
            'customer_id' => $customer->id,
            'title' => 'تشطيب فرع شركة إعمار',
            'source' => 'Google',
            'status' => 'New',
            'estimated_value' => 1200000.00,
            'assigned_to' => $salesRep->id,
            'created_by' => $this->ownerA->id,
        ]);

        // Assert Customer hasMany Leads
        $this->assertCount(1, $customer->leads);
        $this->assertEquals($lead->id, $customer->leads->first()->id);

        // Assert Lead belongsTo Customer
        $this->assertEquals('شركة إعمار مصر', $lead->customer->name);

        // Assert Lead belongsTo Assigned User & Creator
        $this->assertEquals($salesRep->id, $lead->assignedUser->id);
        $this->assertEquals($this->ownerA->id, $lead->creator->id);

        // Assert User hasMany relationships
        $this->assertCount(1, $salesRep->assignedLeads);
        $this->assertCount(1, $this->ownerA->createdLeads);
    }

    /**
     * 3. Test Lead Validation Rules.
     */
    public function test_lead_validation_enforces_correct_data(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        // 1. Missing required core fields (customer_id, title)
        $res = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson('/api/leads', []);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['customer_id', 'title']);

        // Quick capture without source/status succeeds with defaults
        $cust = Customer::create(['name' => 'عميل التقاط سريع', 'customer_type' => 'individual', 'status' => 'active']);
        $resFast = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson('/api/leads', [
            'customer_id' => $cust->id,
            'title' => 'استفسار سريع',
        ]);
        $resFast->assertStatus(201);
        $this->assertEquals('New', $resFast->json('data.status'));
        $this->assertEquals('Other', $resFast->json('data.source'));

        // 2. Invalid status (not in enum)
        $resStatus = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson('/api/leads', [
            'customer_id' => 1,
            'title' => 'مشروع غير صالح',
            'source' => 'Website',
            'status' => 'INVALID_STATUS_XYZ',
        ]);

        $resStatus->assertStatus(422);
        $resStatus->assertJsonValidationErrors(['status']);

        // 3. Invalid source
        $resSource = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson('/api/leads', [
            'customer_id' => 1,
            'title' => 'مشروع غير صالح',
            'source' => 'DARK_WEB_SOURCE',
            'status' => 'New',
        ]);

        $resSource->assertStatus(422);
        $resSource->assertJsonValidationErrors(['source']);

        // 4. Negative value
        $resVal = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson('/api/leads', [
            'customer_id' => 1,
            'title' => 'مشروع غير صالح',
            'source' => 'Website',
            'status' => 'New',
            'estimated_value' => -500,
        ]);

        $resVal->assertStatus(422);
        $resVal->assertJsonValidationErrors(['estimated_value']);
    }

    /**
     * 4. Test Lead RBAC Permissions.
     */
    public function test_lead_permissions_are_strictly_enforced(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'customer_type' => 'individual',
            'name' => 'عميل الصلاحيات',
            'status' => 'active',
        ]);

        $lead = Lead::create([
            'customer_id' => $customer->id,
            'title' => 'مشروع فحص الصلاحيات',
            'source' => 'Phone',
            'status' => 'New',
            'created_by' => $this->ownerA->id,
        ]);

        // Create limited user with NO lead permissions
        $restrictedUser = User::create([
            'name' => 'مستخدم مقيد',
            'email' => 'restricted@alpha.test',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);
        $restrictedToken = $restrictedUser->createToken('restricted_token')->plainTextToken;

        // View -> 403
        $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $restrictedToken,
        ])->getJson('/api/leads')->assertStatus(403);

        // Create -> 403
        $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $restrictedToken,
        ])->postJson('/api/leads', [
            'customer_id' => $customer->id,
            'title' => 'غير مصرح',
            'source' => 'Website',
            'status' => 'New',
        ])->assertStatus(403);

        // Update -> 403
        $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $restrictedToken,
        ])->putJson("/api/leads/{$lead->id}", [
            'customer_id' => $customer->id,
            'title' => 'تعديل غير مصرح',
            'source' => 'Website',
            'status' => 'New',
        ])->assertStatus(403);

        // Delete -> 403
        $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $restrictedToken,
        ])->deleteJson("/api/leads/{$lead->id}")->assertStatus(403);

        // Convert -> 403
        $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $restrictedToken,
        ])->postJson("/api/leads/{$lead->id}/convert")->assertStatus(403);

        // Assign -> 403
        $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $restrictedToken,
        ])->postJson("/api/leads/{$lead->id}/assign", ['assigned_to' => $restrictedUser->id])->assertStatus(403);

        // Grant permissions and verify success
        TenantDatabaseManager::switchToTenant($this->tenantA);
        $restrictedUser->givePermissionTo(['leads.view', 'leads.create']);
        $restrictedUser->unsetRelation('roles');
        $restrictedUser->unsetRelation('permissions');
        $this->app['auth']->forgetGuards();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $resSuccess = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $restrictedToken,
        ])->getJson('/api/leads');

        $resSuccess->assertStatus(200);
    }

    /**
     * 5. Test Tenant Isolation for Leads.
     */
    public function test_tenant_leads_are_completely_isolated(): void
    {
        // Tenant A creates Lead A
        TenantDatabaseManager::switchToTenant($this->tenantA);
        $custA = Customer::create(['name' => 'Customer Alpha', 'customer_type' => 'individual', 'status' => 'active']);
        $leadA = Lead::create([
            'customer_id' => $custA->id,
            'title' => 'Alpha Secret Project Lead',
            'source' => 'Referral',
            'status' => 'New',
            'created_by' => $this->ownerA->id,
        ]);

        // Tenant B creates Lead B
        TenantDatabaseManager::switchToTenant($this->tenantB);
        $custB = Customer::create(['name' => 'Customer Beta', 'customer_type' => 'company', 'status' => 'active']);
        $leadB = Lead::create([
            'customer_id' => $custB->id,
            'title' => 'Beta Confidential Commercial Lead',
            'source' => 'Website',
            'status' => 'Qualified',
            'created_by' => $this->ownerB->id,
        ]);

        // Tenant A lists leads -> Only sees Lead A
        $resA = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->getJson('/api/leads');

        $resA->assertStatus(200);
        $this->assertCount(1, $resA->json('data'));
        $this->assertEquals('Alpha Secret Project Lead', $resA->json('data.0.title'));

        // Tenant B lists leads -> Only sees Lead B
        $resB = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugB,
            'Authorization' => 'Bearer ' . $this->tokenB,
        ])->getJson('/api/leads');

        $resB->assertStatus(200);
        $this->assertCount(1, $resB->json('data'));
        $this->assertEquals('Beta Confidential Commercial Lead', $resB->json('data.0.title'));
    }

    /**
     * 6. Test IDOR & Cross-Tenant Manipulation Prevention.
     */
    public function test_idor_cross_tenant_lead_manipulation_is_blocked(): void
    {
        // Create Lead in Tenant B
        TenantDatabaseManager::switchToTenant($this->tenantB);
        $custB = Customer::create(['name' => 'Cust Beta IDOR', 'customer_type' => 'individual', 'status' => 'active']);
        $leadB = Lead::create([
            'customer_id' => $custB->id,
            'title' => 'Target Lead in Beta',
            'source' => 'Website',
            'status' => 'New',
            'created_by' => $this->ownerB->id,
        ]);

        // Tenant A tries to access Lead B ID via Tenant A API context -> 404 (Not in Tenant A DB)
        $resGet = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->getJson("/api/leads/{$leadB->id}");

        $resGet->assertStatus(404);

        // Tenant A tries to update Lead B ID -> 404
        $resPut = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->putJson("/api/leads/{$leadB->id}", [
            'customer_id' => 1,
            'title' => 'Hacked Lead Title',
            'source' => 'Website',
            'status' => 'New',
        ]);

        $resPut->assertStatus(404);

        // Tenant A tries to delete Lead B ID via Tenant A context
        $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->deleteJson("/api/leads/{$leadB->id}");

        // Assert Tenant B lead remained 100% untouched and intact in Tenant B's database
        TenantDatabaseManager::switchToTenant($this->tenantB);
        $this->assertDatabaseHas('leads', [
            'id' => $leadB->id,
            'title' => 'Target Lead in Beta',
        ]);
    }

    /**
     * 7. Test Cross-Tenant Customer and User Assignment Prevention.
     */
    public function test_cannot_assign_cross_tenant_customer_or_user(): void
    {
        // Create Customer & User in Tenant B
        TenantDatabaseManager::switchToTenant($this->tenantB);
        $custB = Customer::create(['name' => 'Beta Customer Entity', 'customer_type' => 'company', 'status' => 'active']);
        $userB = User::create([
            'name' => 'Beta Sales Rep',
            'email' => 'beta.rep@beta.test',
            'password' => bcrypt('pass123'),
            'status' => 'active',
        ]);

        // Tenant A tries to create a Lead referencing Tenant B's Customer ID
        TenantDatabaseManager::switchToTenant($this->tenantA);
        $resCust = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson('/api/leads', [
            'customer_id' => $custB->id, // Exists in Tenant B DB, but NOT in Tenant A DB
            'title' => 'Cross Tenant Customer Exploitation',
            'source' => 'Website',
            'status' => 'New',
        ]);

        $resCust->assertStatus(422);
        $resCust->assertJsonValidationErrors(['customer_id']);

        // Create valid customer in Tenant A, but try assigning Tenant B's User ID
        $custA = Customer::create(['name' => 'Alpha Customer', 'customer_type' => 'individual', 'status' => 'active']);

        $resUser = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson('/api/leads', [
            'customer_id' => $custA->id,
            'title' => 'Cross Tenant User Assignment Exploitation',
            'source' => 'Website',
            'status' => 'New',
            'assigned_to' => $userB->id, // User from Tenant B
        ]);

        $resUser->assertStatus(422);
        $resUser->assertJsonValidationErrors(['assigned_to']);
    }

    /**
     * 8. Test Lead Convert and Assign Endpoints with Activity Logs.
     */
    public function test_lead_convert_and_assign_with_activity_log(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create(['name' => 'العميل للتنشيط', 'customer_type' => 'individual', 'status' => 'active']);
        $salesRep = User::create([
            'name' => 'المهندس ياسر',
            'email' => 'yasser@alpha.test',
            'password' => bcrypt('secret123'),
            'status' => 'active',
        ]);

        $lead = Lead::create([
            'customer_id' => $customer->id,
            'title' => 'مشروع تحويل وإسناد',
            'source' => 'Referral',
            'status' => 'New',
            'created_by' => $this->ownerA->id,
        ]);

        // 1. Assign Lead
        $resAssign = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson("/api/leads/{$lead->id}/assign", [
            'assigned_to' => $salesRep->id,
        ]);

        $resAssign->assertStatus(200);
        $this->assertEquals($salesRep->id, $lead->fresh()->assigned_to);

        // 2. Convert Lead
        $resConvert = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson("/api/leads/{$lead->id}/convert");

        $resConvert->assertStatus(200);
        $this->assertEquals('Converted', $lead->fresh()->status);

        // 3. Progressive Qualification & Enrichment
        $resQualify = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson("/api/leads/{$lead->id}/qualify", [
            'status' => 'Qualified',
            'estimated_value' => 650000.00,
            'expected_start_date' => '2026-12-01',
            'notes' => 'تم الاتفاق المبدئي على نطاق التشطيب والميزانية',
        ]);

        $resQualify->assertStatus(200);
        $this->assertEquals('Qualified', $lead->fresh()->status);
        $this->assertEquals('650000.00', $lead->fresh()->estimated_value);
        $this->assertEquals('2026-12-01', $lead->fresh()->expected_start_date->format('Y-m-d'));

        // Verify activity logs created in Tenant A database
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'commercial',
            'subject_id' => $lead->id,
            'subject_type' => Lead::class,
        ]);
    }

    /**
     * 9. Test Quick Customer Creation Flow.
     */
    public function test_quick_customer_creation_flow_from_lead(): void
    {
        // 1. Create customer through standard endpoint
        $resCust = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson('/api/customers', [
            'customer_type' => 'company',
            'name' => 'المهندس شريف سالم',
            'company_name' => 'مجموعة سالم للإنشاءات',
            'phone' => '01099887766',
            'status' => 'active',
        ]);

        $resCust->assertStatus(201);
        $customerId = $resCust->json('data.id');

        // 2. Immediately create lead using newly created customer
        $resLead = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson('/api/leads', [
            'customer_id' => $customerId,
            'title' => 'مشروع المقر الإداري لمجموعة سالم',
            'source' => 'Walk-in',
            'status' => 'New',
            'estimated_value' => 2500000.00,
        ]);

        $resLead->assertStatus(201);
        $this->assertEquals($customerId, $resLead->json('data.customer_id'));
        $this->assertEquals('مجموعة سالم للإنشاءات', $resLead->json('data.customer.company_name'));
    }

    /**
     * 10. Test Lead Media Upload & Tenant Scoping.
     */
    public function test_lead_documents_media_upload_and_tenant_scoping(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);
        Storage::fake('public');

        $customer = Customer::create(['name' => 'Media Customer', 'customer_type' => 'individual', 'status' => 'active']);

        $blueprintPdf = UploadedFile::fake()->create('project_tender_specs.pdf', 1024, 'application/pdf');

        $res = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson('/api/leads', [
            'customer_id' => $customer->id,
            'title' => 'مشروع مع مرفقات ومواصفات',
            'source' => 'Website',
            'status' => 'New',
            'documents' => [$blueprintPdf],
        ]);

        $res->assertStatus(201);
        $leadId = $res->json('data.id');

        $lead = Lead::find($leadId);
        $this->assertCount(1, $lead->getMedia('documents'));
        $this->assertEquals('project_tender_specs.pdf', $lead->getFirstMedia('documents')->file_name);
        $this->assertEquals($this->slugA, $lead->getFirstMedia('documents')->getCustomProperty('tenant_slug'));
    }
}
