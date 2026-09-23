<?php

namespace Tests\Feature;

use App\Core\Tenancy\TenantDatabaseManager;
use App\Models\Central\Tenant;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\Tenant\TenantProvisioningService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OpportunityManagementTest extends TestCase
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

        $this->slugA = 'opp-alpha-' . time() . '-' . rand(10, 99);
        $this->slugB = 'opp-beta-' . time() . '-' . rand(10, 99);

        // Provision Tenant A
        $resA = $this->provisioningService->provision([
            'name' => 'Alpha Contracting Enterprise',
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
            'name' => 'Beta Engineering Enterprise',
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
     * 1. Test Opportunity CRUD Lifecycle.
     */
    public function test_opportunity_crud_lifecycle(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'customer_type' => 'individual',
            'name' => 'المهندس كريم حلمي',
            'phone' => '01012345678',
            'status' => 'active',
        ]);

        // 1. Create Opportunity
        $resCreate = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson('/api/opportunities', [
            'customer_id' => $customer->id,
            'title' => 'تشطيب فيلا بالم هيلز',
            'description' => 'تشطيب فاخر مع أعمال كهروميكانيكية متكاملة',
            'stage' => 'New',
            'estimated_value' => 1500000.00,
            'expected_start_date' => '2026-11-01',
            'expected_close_date' => '2026-11-20',
            'notes' => 'المشروع في مرحلة تقديم العرض الفني والمالي',
        ]);

        $resCreate->assertStatus(201);
        $oppId = $resCreate->json('data.id');
        $this->assertNotNull($oppId);
        $this->assertEquals('تشطيب فيلا بالم هيلز', $resCreate->json('data.title'));
        $this->assertEquals('1500000.00', $resCreate->json('data.estimated_value'));
        $this->assertEquals('New', $resCreate->json('data.stage'));

        // 2. Read Opportunity
        $resShow = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->getJson("/api/opportunities/{$oppId}");

        $resShow->assertStatus(200);
        $resShow->assertJsonPath('data.title', 'تشطيب فيلا بالم هيلز');
        $resShow->assertJsonPath('data.customer.name', 'المهندس كريم حلمي');

        // 3. Update Opportunity
        $resUpdate = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->putJson("/api/opportunities/{$oppId}", [
            'customer_id' => $customer->id,
            'title' => 'تشطيب فيلا بالم هيلز - التجمع',
            'stage' => 'Proposal',
            'estimated_value' => 1750000.00,
            'expected_start_date' => '2026-11-15',
            'expected_close_date' => '2026-12-01',
            'notes' => 'تم إرسال عرض السعر المبدئي وجاري المتابعة',
        ]);

        $resUpdate->assertStatus(200);
        $this->assertEquals('تشطيب فيلا بالم هيلز - التجمع', $resUpdate->json('data.title'));
        $this->assertEquals('Proposal', $resUpdate->json('data.stage'));
        $this->assertEquals('1750000.00', $resUpdate->json('data.estimated_value'));

        // 4. List Opportunities with Stats
        $resList = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->getJson('/api/opportunities');

        $resList->assertStatus(200);
        $resList->assertJsonStructure([
            'success',
            'data',
            'meta',
            'stats' => ['total', 'new', 'qualified', 'proposal', 'negotiation', 'won', 'lost', 'total_estimated_value', 'won_estimated_value'],
            'schema',
        ]);
        $this->assertEquals(1, $resList->json('stats.total'));
        $this->assertEquals(1, $resList->json('stats.proposal'));

        // 5. Delete Opportunity
        $resDelete = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->deleteJson("/api/opportunities/{$oppId}");

        $resDelete->assertStatus(200);
        $this->assertDatabaseMissing('opportunities', ['id' => $oppId]);
    }

    /**
     * 2. Test Customer, Lead & User Eloquent Relationships.
     */
    public function test_opportunity_relationships(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'customer_type' => 'company',
            'name' => 'شركة أوراسكوم للإنشاءات',
            'company_name' => 'أوراسكوم',
            'status' => 'active',
        ]);

        $salesRep = User::create([
            'name' => 'مهندس المبيعات سمير',
            'email' => 'samir@alpha.test',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);

        $lead = Lead::create([
            'customer_id' => $customer->id,
            'title' => 'استفسار تشطيب مبنى أوراسكوم',
            'source' => 'Website',
            'status' => 'Qualified',
            'created_by' => $this->ownerA->id,
        ]);

        $opportunity = Opportunity::create([
            'customer_id' => $customer->id,
            'lead_id' => $lead->id,
            'title' => 'مشروع تشطيب المقر الإداري لأوراسكوم',
            'stage' => 'Qualified',
            'estimated_value' => 3500000.00,
            'assigned_to' => $salesRep->id,
            'created_by' => $this->ownerA->id,
        ]);

        // Customer hasMany Opportunities
        $this->assertCount(1, $customer->opportunities);
        $this->assertEquals($opportunity->id, $customer->opportunities->first()->id);

        // Lead hasMany Opportunities & hasOne opportunity
        $this->assertCount(1, $lead->opportunities);
        $this->assertEquals($opportunity->id, $lead->opportunity->id);

        // Opportunity belongsTo Customer & Lead
        $this->assertEquals('شركة أوراسكوم للإنشاءات', $opportunity->customer->name);
        $this->assertEquals($lead->id, $opportunity->lead->id);

        // Opportunity belongsTo Assigned User & Creator
        $this->assertEquals($salesRep->id, $opportunity->assignedUser->id);
        $this->assertEquals($this->ownerA->id, $opportunity->creator->id);

        // User relationships
        $this->assertCount(1, $salesRep->assignedOpportunities);
        $this->assertCount(1, $this->ownerA->createdOpportunities);
    }

    /**
     * 3. Test Opportunity Validation Rules.
     */
    public function test_opportunity_validation_enforces_correct_data(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        // 1. Missing required fields (customer_id, title)
        $res = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson('/api/opportunities', []);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['customer_id', 'title']);

        // 2. Invalid stage
        $resStage = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson('/api/opportunities', [
            'customer_id' => 1,
            'title' => 'فرصة غير صالحة',
            'stage' => 'INVALID_STAGE_XYZ',
        ]);

        $resStage->assertStatus(422);
        $resStage->assertJsonValidationErrors(['stage']);

        // 3. Negative estimated value
        $resVal = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson('/api/opportunities', [
            'customer_id' => 1,
            'title' => 'فرصة غير صالحة',
            'stage' => 'New',
            'estimated_value' => -1000,
        ]);

        $resVal->assertStatus(422);
        $resVal->assertJsonValidationErrors(['estimated_value']);

        // 4. Invalid date range (expected_close_date before expected_start_date)
        $resDate = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson('/api/opportunities', [
            'customer_id' => 1,
            'title' => 'فرصة غير صالحة',
            'stage' => 'New',
            'expected_start_date' => '2026-12-01',
            'expected_close_date' => '2026-11-01',
        ]);

        $resDate->assertStatus(422);
        $resDate->assertJsonValidationErrors(['expected_close_date']);
    }

    /**
     * 4. Test Opportunity RBAC Permissions.
     */
    public function test_opportunity_permissions_are_strictly_enforced(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'customer_type' => 'individual',
            'name' => 'عميل الصلاحيات',
            'status' => 'active',
        ]);

        $opportunity = Opportunity::create([
            'customer_id' => $customer->id,
            'title' => 'فرصة فحص الصلاحيات',
            'stage' => 'New',
            'created_by' => $this->ownerA->id,
        ]);

        // Create limited user with NO opportunity permissions
        $restrictedUser = User::create([
            'name' => 'مستخدم بدون صلاحيات',
            'email' => 'restricted.opp@alpha.test',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);
        $restrictedToken = $restrictedUser->createToken('restricted_token')->plainTextToken;

        // View -> 403
        $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $restrictedToken,
        ])->getJson('/api/opportunities')->assertStatus(403);

        // Create -> 403
        $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $restrictedToken,
        ])->postJson('/api/opportunities', [
            'customer_id' => $customer->id,
            'title' => 'فرصة غير مصرح بها',
        ])->assertStatus(403);

        // Update -> 403
        $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $restrictedToken,
        ])->putJson("/api/opportunities/{$opportunity->id}", [
            'customer_id' => $customer->id,
            'title' => 'تعديل غير مصرح',
        ])->assertStatus(403);

        // Delete -> 403
        $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $restrictedToken,
        ])->deleteJson("/api/opportunities/{$opportunity->id}")->assertStatus(403);

        // Stage Change -> 403
        $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $restrictedToken,
        ])->postJson("/api/opportunities/{$opportunity->id}/stage", ['stage' => 'Qualified'])->assertStatus(403);

        // Assign -> 403
        $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $restrictedToken,
        ])->postJson("/api/opportunities/{$opportunity->id}/assign", ['assigned_to' => $restrictedUser->id])->assertStatus(403);

        // Grant permissions and verify success
        TenantDatabaseManager::switchToTenant($this->tenantA);
        $restrictedUser->givePermissionTo(['opportunities.view', 'opportunities.create']);
        $restrictedUser->unsetRelation('roles');
        $restrictedUser->unsetRelation('permissions');
        $this->app['auth']->forgetGuards();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $resSuccess = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $restrictedToken,
        ])->getJson('/api/opportunities');

        $resSuccess->assertStatus(200);
    }

    /**
     * 5. Test Controlled Lead to Opportunity Conversion Flow with Duplicate Prevention.
     */
    public function test_controlled_lead_to_opportunity_conversion(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'customer_type' => 'individual',
            'name' => 'الدكتور هشام فؤاد',
            'phone' => '01233445566',
            'status' => 'active',
        ]);

        $lead = Lead::create([
            'customer_id' => $customer->id,
            'title' => 'تشطيب عيادة طبية بالمهندسين',
            'description' => 'تشطيب كامل مساحة 250 متر مع تصميم حديث',
            'source' => 'WhatsApp',
            'status' => 'Qualified',
            'estimated_value' => 800000.00,
            'expected_start_date' => '2026-11-01',
            'created_by' => $this->ownerA->id,
            'notes' => 'العميل جاهز لبدء مرحلة التعاقد وإعداد الرسومات',
        ]);

        // 1. Convert Lead to Opportunity
        $resConvert = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson("/api/leads/{$lead->id}/convert-to-opportunity", [
            'title' => 'تشطيب وتجهيز عيادة الدكتور هشام - المهندسين',
            'stage' => 'Proposal',
            'estimated_value' => 850000.00,
            'expected_start_date' => '2026-11-01',
            'expected_close_date' => '2026-11-15',
            'notes' => 'تم الاتفاق المبدئي ونقل البيانات بنجاح',
        ]);

        $resConvert->assertStatus(201);
        $oppId = $resConvert->json('data.id');
        $this->assertNotNull($oppId);
        $this->assertEquals('تشطيب وتجهيز عيادة الدكتور هشام - المهندسين', $resConvert->json('data.title'));
        $this->assertEquals($customer->id, $resConvert->json('data.customer_id'));
        $this->assertEquals($lead->id, $resConvert->json('data.lead_id'));
        $this->assertEquals('Proposal', $resConvert->json('data.stage'));
        $this->assertEquals('850000.00', $resConvert->json('data.estimated_value'));

        // 2. Verify Lead status is now Converted
        $lead->refresh();
        $this->assertEquals('Converted', $lead->status);

        // 3. Verify duplicate conversion prevention (re-converting the same lead must return 422)
        $resDup = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson("/api/leads/{$lead->id}/convert-to-opportunity", [
            'title' => 'محاولة تحويل مكررة',
        ]);

        $resDup->assertStatus(422);
        $resDup->assertJsonValidationErrors(['lead_id']);

        // 4. Verify alternative endpoint `POST /api/opportunities/convert-from-lead/{leadId}` also blocks duplicate
        $resDup2 = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson("/api/opportunities/convert-from-lead/{$lead->id}", [
            'title' => 'محاولة ثانية مكررة',
        ]);

        $resDup2->assertStatus(422);
        $resDup2->assertJsonValidationErrors(['lead_id']);
    }

    /**
     * 6. Test Tenant Isolation for Opportunities.
     */
    public function test_tenant_opportunities_are_completely_isolated(): void
    {
        // Tenant A creates Opportunity A
        TenantDatabaseManager::switchToTenant($this->tenantA);
        $custA = Customer::create(['name' => 'Customer Alpha', 'customer_type' => 'individual', 'status' => 'active']);
        $oppA = Opportunity::create([
            'customer_id' => $custA->id,
            'title' => 'Alpha Confidential Opportunity',
            'stage' => 'Proposal',
            'estimated_value' => 2000000.00,
            'created_by' => $this->ownerA->id,
        ]);

        // Tenant B creates Opportunity B
        TenantDatabaseManager::switchToTenant($this->tenantB);
        $custB = Customer::create(['name' => 'Customer Beta', 'customer_type' => 'company', 'status' => 'active']);
        $oppB = Opportunity::create([
            'customer_id' => $custB->id,
            'title' => 'Beta Secret Opportunity',
            'stage' => 'Won',
            'estimated_value' => 5000000.00,
            'created_by' => $this->ownerB->id,
        ]);

        // Tenant A lists -> Only sees Opportunity A
        $resA = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->getJson('/api/opportunities');

        $resA->assertStatus(200);
        $this->assertCount(1, $resA->json('data'));
        $this->assertEquals('Alpha Confidential Opportunity', $resA->json('data.0.title'));

        // Tenant B lists -> Only sees Opportunity B
        $resB = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugB,
            'Authorization' => 'Bearer ' . $this->tokenB,
        ])->getJson('/api/opportunities');

        $resB->assertStatus(200);
        $this->assertCount(1, $resB->json('data'));
        $this->assertEquals('Beta Secret Opportunity', $resB->json('data.0.title'));
    }

    /**
     * 7. Test IDOR & Cross-Tenant Opportunity Manipulation Prevention.
     */
    public function test_idor_cross_tenant_opportunity_manipulation_is_blocked(): void
    {
        // Create Opportunity in Tenant B
        TenantDatabaseManager::switchToTenant($this->tenantB);
        $custB = Customer::create(['name' => 'Beta Cust IDOR', 'customer_type' => 'individual', 'status' => 'active']);
        $oppB = Opportunity::create([
            'customer_id' => $custB->id,
            'title' => 'Target Opportunity in Beta',
            'stage' => 'New',
            'created_by' => $this->ownerB->id,
        ]);

        // Tenant A tries to access Opportunity B ID via Tenant A context -> 404
        $resGet = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->getJson("/api/opportunities/{$oppB->id}");

        $resGet->assertStatus(404);

        // Tenant A tries to update Opportunity B ID -> 404
        $resPut = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->putJson("/api/opportunities/{$oppB->id}", [
            'customer_id' => 1,
            'title' => 'Hacked Opportunity Title',
            'stage' => 'Won',
        ]);

        $resPut->assertStatus(404);

        // Tenant A tries to delete Opportunity B ID
        $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->deleteJson("/api/opportunities/{$oppB->id}");

        // Assert Tenant B opportunity remains 100% untouched
        TenantDatabaseManager::switchToTenant($this->tenantB);
        $this->assertDatabaseHas('opportunities', [
            'id' => $oppB->id,
            'title' => 'Target Opportunity in Beta',
        ]);
    }

    /**
     * 8. Test Cross-Tenant Customer, Lead and User Assignment Prevention.
     */
    public function test_cannot_assign_cross_tenant_customer_lead_or_user(): void
    {
        // Create Customer, Lead & User in Tenant B
        TenantDatabaseManager::switchToTenant($this->tenantB);
        $custB = Customer::create(['name' => 'Beta Cust Entity', 'customer_type' => 'company', 'status' => 'active']);
        $leadB = Lead::create([
            'customer_id' => $custB->id,
            'title' => 'Beta Lead Entity',
            'status' => 'Qualified',
            'created_by' => $this->ownerB->id,
        ]);
        $userB = User::create([
            'name' => 'Beta Rep',
            'email' => 'beta.rep@beta.test',
            'password' => bcrypt('pass123'),
            'status' => 'active',
        ]);

        // Tenant A tries to create Opportunity referencing Tenant B Customer ID
        TenantDatabaseManager::switchToTenant($this->tenantA);
        $resCust = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson('/api/opportunities', [
            'customer_id' => $custB->id,
            'title' => 'Cross Tenant Customer Exploitation',
            'stage' => 'New',
        ]);

        $resCust->assertStatus(422);
        $resCust->assertJsonValidationErrors(['customer_id']);

        // Create valid customer in Tenant A, but try referencing Tenant B's Lead ID
        $custA = Customer::create(['name' => 'Alpha Customer', 'customer_type' => 'individual', 'status' => 'active']);

        $resLead = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson('/api/opportunities', [
            'customer_id' => $custA->id,
            'lead_id' => $leadB->id,
            'title' => 'Cross Tenant Lead Exploitation',
            'stage' => 'New',
        ]);

        $resLead->assertStatus(422);
        $resLead->assertJsonValidationErrors(['lead_id']);

        // Try assigning Tenant B User
        $resUser = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson('/api/opportunities', [
            'customer_id' => $custA->id,
            'title' => 'Cross Tenant User Assignment Exploitation',
            'stage' => 'New',
            'assigned_to' => $userB->id,
        ]);

        $resUser->assertStatus(422);
        $resUser->assertJsonValidationErrors(['assigned_to']);

        // Tenant A attempts to convert Tenant B's Lead ID
        $resConvertCross = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson("/api/leads/{$leadB->id}/convert-to-opportunity", [
            'title' => 'Cross Tenant Lead Conversion Exploitation',
        ]);

        $resConvertCross->assertStatus(404);
    }

    /**
     * 9. Test Opportunity Stage Progression and Assignment with Activity Log.
     */
    public function test_opportunity_stage_and_assign_with_activity_log(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create(['name' => 'العميل للمرحلة', 'customer_type' => 'individual', 'status' => 'active']);
        $salesRep = User::create([
            'name' => 'المهندس رامي زكريا',
            'email' => 'rami@alpha.test',
            'password' => bcrypt('secret123'),
            'status' => 'active',
        ]);

        $opportunity = Opportunity::create([
            'customer_id' => $customer->id,
            'title' => 'مشروع فيلا زايد ريزيدنس',
            'stage' => 'New',
            'estimated_value' => 1200000.00,
            'created_by' => $this->ownerA->id,
        ]);

        // 1. Assign Opportunity
        $resAssign = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson("/api/opportunities/{$opportunity->id}/assign", [
            'assigned_to' => $salesRep->id,
        ]);

        $resAssign->assertStatus(200);
        $this->assertEquals($salesRep->id, $opportunity->fresh()->assigned_to);

        // 2. Advance Stage to Negotiation
        $resStage = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson("/api/opportunities/{$opportunity->id}/stage", [
            'stage' => 'Negotiation',
            'notes' => 'المفاوضات جارية على جدول الدفعات ومواعيد التوريد',
        ]);

        $resStage->assertStatus(200);
        $this->assertEquals('Negotiation', $opportunity->fresh()->stage);

        // 3. Mark as Won
        $resWon = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson("/api/opportunities/{$opportunity->id}/stage", [
            'stage' => 'Won',
        ]);

        $resWon->assertStatus(200);
        $this->assertEquals('Won', $opportunity->fresh()->stage);

        // Assert activity logs in Tenant A database
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'commercial',
            'subject_id' => $opportunity->id,
            'subject_type' => Opportunity::class,
        ]);
    }

    /**
     * 10. Test Opportunity Documents Media Upload.
     */
    public function test_opportunity_documents_media_upload(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);
        Storage::fake('public');

        $customer = Customer::create(['name' => 'Media Opp Customer', 'customer_type' => 'company', 'status' => 'active']);

        $proposalPdf = UploadedFile::fake()->create('commercial_proposal_v1.pdf', 1024, 'application/pdf');

        $res = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => 'Bearer ' . $this->tokenA,
        ])->postJson('/api/opportunities', [
            'customer_id' => $customer->id,
            'title' => 'فرصة مع عرض فني ومالي مرفق',
            'stage' => 'Proposal',
            'documents' => [$proposalPdf],
        ]);

        $res->assertStatus(201);
        $oppId = $res->json('data.id');

        $opportunity = Opportunity::find($oppId);
        $this->assertCount(1, $opportunity->getMedia('documents'));
        $this->assertEquals('commercial_proposal_v1.pdf', $opportunity->getFirstMedia('documents')->file_name);
        $this->assertEquals($this->slugA, $opportunity->getFirstMedia('documents')->getCustomProperty('tenant_slug'));
    }
}
