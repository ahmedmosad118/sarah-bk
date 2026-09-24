<?php

namespace Tests\Feature;

use App\Core\Tenancy\TenantDatabaseManager;
use App\Models\Central\Tenant;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Measurement;
use App\Models\MeasurementItem;
use App\Models\Opportunity;
use App\Models\Scope;
use App\Models\ScopeItem;
use App\Models\SiteVisit;
use App\Models\SiteVisitRoom;
use App\Models\User;
use App\Services\Tenant\TenantProvisioningService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OpportunityChainTraceabilityTest extends TestCase
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

        $this->slugA = str_replace('.', '', uniqid('chain-a-', true)) . rand(100, 999);
        $this->slugB = str_replace('.', '', uniqid('chain-b-', true)) . rand(100, 999);

        // Provision Tenant A
        $resA = $this->provisioningService->provision([
            'name' => 'Alpha Commercial Chain Corp',
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
            'name' => 'Beta Foreign Corp',
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

        TenantDatabaseManager::switchToTenant($this->tenantA);
        $this->tokenA = $this->ownerA->createToken('token_a')->plainTextToken;

        TenantDatabaseManager::switchToTenant($this->tenantB);
        $this->tokenB = $this->ownerB->createToken('token_b')->plainTextToken;
    }

    protected function tearDown(): void
    {
        try {
            if (isset($this->tenantA)) {
                TenantDatabaseManager::dropDatabase($this->tenantA);
                $this->tenantA->domains()->delete();
                $this->tenantA->delete();
            }
            if (isset($this->tenantB)) {
                TenantDatabaseManager::dropDatabase($this->tenantB);
                $this->tenantB->domains()->delete();
                $this->tenantB->delete();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        parent::tearDown();
    }

    /**
     * Helper to set headers for Tenant A.
     */
    protected function withTenantA(): static
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);
        return $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => "Bearer {$this->tokenA}",
            'Accept' => 'application/json',
        ]);
    }

    /**
     * Helper to set headers for Tenant B.
     */
    protected function withTenantB(): static
    {
        TenantDatabaseManager::switchToTenant($this->tenantB);
        return $this->withHeaders([
            'X-Tenant-Slug' => $this->slugB,
            'Authorization' => "Bearer {$this->tokenB}",
            'Accept' => 'application/json',
        ]);
    }

    /**
     * 1. Test Opportunity chain loads with full hierarchy:
     * Customer -> Lead -> Opportunity -> Multiple Site Visits -> Multiple Measurements -> Scopes.
     */
    public function test_opportunity_chain_loads_with_full_hierarchy(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        // 1. Customer
        $customer = Customer::create([
            'name' => 'شركة النور للتجارة والتوزيع',
            'customer_type' => 'company',
            'company_name' => 'مجموعة النور القابضة',
            'phone' => '01012345678',
            'whatsapp' => '01012345678',
            'email' => 'info@alnoor.test',
            'status' => 'active',
            'created_by' => $this->ownerA->id,
        ]);

        // 2. Lead
        $lead = Lead::create([
            'customer_id' => $customer->id,
            'title' => 'طلب تشطيب الفرع الإداري بالتجمع',
            'description' => 'تشطيب متكامل للمقر الإداري بمساحة 250 م2',
            'status' => 'Converted',
            'source' => 'Website',
            'created_by' => $this->ownerA->id,
        ]);

        // 3. Opportunity
        $opp = Opportunity::create([
            'customer_id' => $customer->id,
            'lead_id' => $lead->id,
            'title' => 'مشروع تشطيب المقر الإداري لشركة النور',
            'description' => 'فرصة تجارية مؤهلة لتشطيب المبنى الإداري بالكامل',
            'stage' => 'Proposal',
            'estimated_value' => 750000.00,
            'expected_start_date' => '2026-10-01',
            'expected_close_date' => '2026-10-15',
            'assigned_to' => $this->ownerA->id,
            'created_by' => $this->ownerA->id,
        ]);

        // 4. Two Site Visits
        $visit1 = SiteVisit::create([
            'customer_id' => $customer->id,
            'opportunity_id' => $opp->id,
            'lead_id' => $lead->id,
            'status' => 'Completed',
            'scheduled_date' => '2026-09-18',
            'visit_date' => '2026-09-18',
            'assigned_to' => $this->ownerA->id,
            'general_assessment' => 'معاينة أولية لفحص الموقع ورفع الأبعاد المعمارية',
            'created_by' => $this->ownerA->id,
        ]);
        SiteVisitRoom::create([
            'site_visit_id' => $visit1->id,
            'room_name' => 'الريسبشن',
            'estimated_area' => 50.00,
        ]);

        $visit2 = SiteVisit::create([
            'customer_id' => $customer->id,
            'opportunity_id' => $opp->id,
            'status' => 'Completed',
            'scheduled_date' => '2026-09-21',
            'visit_date' => '2026-09-21',
            'assigned_to' => $this->ownerA->id,
            'general_assessment' => 'معاينة تكميلية لفحص تمديدات التكييف والسباكة',
            'created_by' => $this->ownerA->id,
        ]);

        // 5. Measurements (V1 Approved, V2 Draft)
        $m1 = Measurement::create([
            'opportunity_id' => $opp->id,
            'site_visit_id' => $visit1->id,
            'measurement_number' => 'M-OPP-001',
            'version' => 1,
            'status' => 'Approved',
            'measured_by' => $this->ownerA->id,
            'approved_by' => $this->ownerA->id,
            'measured_at' => '2026-09-22',
            'approved_at' => Carbon::now(),
            'total_area' => 120.00,
            'created_by' => $this->ownerA->id,
        ]);

        $mItem1 = MeasurementItem::create([
            'measurement_id' => $m1->id,
            'room_name' => 'صالة الاستقبال',
            'item_name' => 'أرضيات بورسلين 60×120',
            'unit' => 'm2',
            'measurement_type' => 'area',
            'count' => 1,
            'length' => 10.00,
            'width' => 8.00,
            'deductions' => 0,
            'gross_quantity' => 80.00,
            'net_quantity' => 80.00,
        ]);

        $mItem2 = MeasurementItem::create([
            'measurement_id' => $m1->id,
            'room_name' => 'مكتب المدير',
            'item_name' => 'أرضيات HDF بلجيكي',
            'unit' => 'm2',
            'measurement_type' => 'area',
            'count' => 1,
            'length' => 8.00,
            'width' => 5.00,
            'deductions' => 0,
            'gross_quantity' => 40.00,
            'net_quantity' => 40.00,
        ]);

        $m2 = Measurement::create([
            'opportunity_id' => $opp->id,
            'measurement_number' => 'M-OPP-001-V2',
            'version' => 2,
            'status' => 'Draft',
            'measured_by' => $this->ownerA->id,
            'total_area' => 125.00,
            'created_by' => $this->ownerA->id,
        ]);

        // 6. Scope based on Measurement V1
        $scope1 = Scope::create([
            'opportunity_id' => $opp->id,
            'measurement_id' => $m1->id,
            'scope_number' => 'SC-OPP-001',
            'version' => 1,
            'status' => 'Approved',
            'title' => 'نطاق أعمال تشطيب وتجهيز المقر',
            'general_inclusions' => 'يشمل توريد الخامات والمصنعيات',
            'general_exclusions' => 'لا يشمل أجهزة التكييف',
            'prepared_by' => $this->ownerA->id,
            'approved_by' => $this->ownerA->id,
            'approved_at' => Carbon::now(),
            'created_by' => $this->ownerA->id,
        ]);

        $sItem1 = $scope1->items()->create([
            'trade_category' => 'أعمال الأرضيات والبورسلين والرخام والجرانيت',
            'item_name' => 'توريد وتركيب أرضيات بورسلين إسباني',
            'specification' => 'تركيب بمادة لصق إيبوكسية وكلبسات تسوية',
            'inclusions' => 'يشمل الوزرة والعراميس',
            'exclusions' => 'لا يشمل الباركيه',
            'sort_order' => 1,
        ]);
        $sItem1->measurementItems()->sync([$mItem1->id]);

        // API Call
        $res = $this->withTenantA()->getJson("/api/opportunities/{$opp->id}/chain");

        $res->assertOk();
        $res->assertJsonPath('success', true);

        // Assert Customer
        $res->assertJsonPath('data.customer.name', 'شركة النور للتجارة والتوزيع');
        $res->assertJsonPath('data.customer.customer_type', 'company');

        // Assert Lead
        $res->assertJsonPath('data.lead.title', 'طلب تشطيب الفرع الإداري بالتجمع');

        // Assert Opportunity
        $res->assertJsonPath('data.opportunity.title', 'مشروع تشطيب المقر الإداري لشركة النور');
        $res->assertJsonPath('data.opportunity.stage', 'Proposal');

        // Assert 2 Site Visits
        $this->assertCount(2, $res->json('data.site_visits'));

        // Assert Measurements & Approved Identification
        $this->assertCount(2, $res->json('data.measurements'));
        $res->assertJsonPath('data.approved_measurement.id', $m1->id);
        $res->assertJsonPath('data.approved_measurement.version', 1);

        // Assert Scopes & Approved Identification
        $this->assertCount(1, $res->json('data.scopes'));
        $res->assertJsonPath('data.approved_scope.id', $scope1->id);

        // Assert Current Status
        $res->assertJsonPath('data.current_status.commercial.stage', 'Proposal');
        $res->assertJsonPath('data.current_status.technical.status', 'Approved');
        $res->assertJsonPath('data.current_status.technical.version', 'V1');
        $res->assertJsonPath('data.current_status.scope.status', 'Approved');
        $res->assertJsonPath('data.current_status.scope.version', 'V1');
        $res->assertJsonPath('data.current_status.is_complete', true);

        // Next Action: Ready for BOQ
        $res->assertJsonPath('data.next_action.key', 'ready_for_boq');
    }

    /**
     * 2. Test Historical Measurement revision is not replaced by latest revision:
     * Scope V1 continues to point strictly to Measurement V1 even when Measurement V2 exists.
     */
    public function test_historical_measurement_revision_is_preserved_for_scope(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create(['name' => 'عميل تجريبي', 'created_by' => $this->ownerA->id]);
        $opp = Opportunity::create([
            'customer_id' => $customer->id,
            'title' => 'فرصة تجريبية للمراجعات',
            'stage' => 'Qualified',
            'created_by' => $this->ownerA->id,
        ]);

        // Measurement V1 Approved
        $mV1 = Measurement::create([
            'opportunity_id' => $opp->id,
            'measurement_number' => 'M-TEST-001-V1',
            'version' => 1,
            'status' => 'Superseded',
            'total_area' => 100.00,
            'created_by' => $this->ownerA->id,
        ]);

        // Measurement V2 Approved
        $mV2 = Measurement::create([
            'opportunity_id' => $opp->id,
            'measurement_number' => 'M-TEST-001-V2',
            'version' => 2,
            'status' => 'Approved',
            'total_area' => 105.00,
            'created_by' => $this->ownerA->id,
        ]);

        // Scope V1 created during V1 timeframe and anchored to V1
        $sV1 = Scope::create([
            'opportunity_id' => $opp->id,
            'measurement_id' => $mV1->id,
            'scope_number' => 'SC-TEST-001-V1',
            'version' => 1,
            'status' => 'Approved',
            'title' => 'نطاق مبني على المقايسة V1',
            'created_by' => $this->ownerA->id,
        ]);

        $res = $this->withTenantA()->getJson("/api/opportunities/{$opp->id}/chain");
        $res->assertOk();

        // Scope V1 MUST reference measurement V1, NOT V2
        $matrix = $res->json('data.traceability_matrix');
        $this->assertNotEmpty($matrix);
        $this->assertEquals($sV1->id, $matrix[0]['scope_id']);
        $this->assertEquals($mV1->id, $matrix[0]['referenced_measurement']['id']);
        $this->assertEquals('M-TEST-001-V1', $matrix[0]['referenced_measurement']['measurement_number']);
        $this->assertEquals(1, $matrix[0]['referenced_measurement']['version']);
        $this->assertTrue($matrix[0]['referenced_measurement']['is_historical_superseded']);
    }

    /**
     * 3. Test Scope Items display Measurement Item relationships without owning quantities:
     * Quantities come from linked MeasurementItem and Scope contains zero pricing.
     */
    public function test_scope_items_display_measurement_item_relationships_without_owning_quantities(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create(['name' => 'عميل اختبار', 'created_by' => $this->ownerA->id]);
        $opp = Opportunity::create(['customer_id' => $customer->id, 'title' => 'فرصة ربط البنود', 'created_by' => $this->ownerA->id]);

        $m = Measurement::create([
            'opportunity_id' => $opp->id,
            'measurement_number' => 'M-LINK-001',
            'version' => 1,
            'status' => 'Approved',
            'total_area' => 48.50,
            'created_by' => $this->ownerA->id,
        ]);

        $mItem = MeasurementItem::create([
            'measurement_id' => $m->id,
            'room_name' => 'غرفة النوم الرئيسية',
            'item_name' => 'سيراميك أرضيات',
            'unit' => 'm2',
            'measurement_type' => 'area',
            'count' => 1,
            'net_quantity' => 48.50,
            'gross_quantity' => 48.50,
        ]);

        $scope = Scope::create([
            'opportunity_id' => $opp->id,
            'measurement_id' => $m->id,
            'scope_number' => 'SC-LINK-001',
            'version' => 1,
            'status' => 'Approved',
            'title' => 'نطاق تشطيب الأرضيات',
            'created_by' => $this->ownerA->id,
        ]);

        $sItem = $scope->items()->create([
            'trade_category' => 'أعمال الأرضيات والبورسلين والرخام والجرانيت',
            'item_name' => 'تركيب سيراميك أرضيات فرز أول',
            'specification' => 'مواصفة كاملة',
            'sort_order' => 1,
        ]);
        $sItem->measurementItems()->sync([$mItem->id]);

        $res = $this->withTenantA()->getJson("/api/opportunities/{$opp->id}/chain");
        $res->assertOk();

        $matrix = $res->json('data.traceability_matrix');
        $itemTrace = $matrix[0]['items'][0];

        $this->assertEquals('تركيب سيراميك أرضيات فرز أول', $itemTrace['item_name']);
        $this->assertCount(1, $itemTrace['linked_measurements']);
        $this->assertEquals('غرفة النوم الرئيسية', $itemTrace['linked_measurements'][0]['room_name']);
        $this->assertEquals(48.50, $itemTrace['linked_measurements'][0]['net_quantity']);
        $this->assertEquals('m2', $itemTrace['linked_measurements'][0]['unit']);
    }

    /**
     * 4. Test missing stages produce correct empty states and progression of next_action.
     */
    public function test_missing_stages_produce_correct_empty_states_and_next_actions(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create(['name' => 'عميل فارغ', 'created_by' => $this->ownerA->id]);
        $opp = Opportunity::create(['customer_id' => $customer->id, 'title' => 'فرصة جديدة فارغة', 'stage' => 'New', 'created_by' => $this->ownerA->id]);

        // State 1: No site visits
        $res1 = $this->withTenantA()->getJson("/api/opportunities/{$opp->id}/chain");
        $res1->assertOk();
        $this->assertEmpty($res1->json('data.site_visits'));
        $this->assertEmpty($res1->json('data.measurements'));
        $this->assertEmpty($res1->json('data.scopes'));
        $this->assertEquals('schedule_site_visit', $res1->json('data.next_action.key'));

        // State 2: Site visit added, no measurements
        $visit = SiteVisit::create([
            'customer_id' => $customer->id,
            'opportunity_id' => $opp->id,
            'status' => 'Completed',
            'scheduled_date' => '2026-09-24',
            'created_by' => $this->ownerA->id,
        ]);

        $res2 = $this->withTenantA()->getJson("/api/opportunities/{$opp->id}/chain");
        $res2->assertOk();
        $this->assertEquals('create_measurement', $res2->json('data.next_action.key'));

        // State 3: Draft measurement added
        $m = Measurement::create([
            'opportunity_id' => $opp->id,
            'measurement_number' => 'M-DRAFT-01',
            'version' => 1,
            'status' => 'Draft',
            'created_by' => $this->ownerA->id,
        ]);

        $res3 = $this->withTenantA()->getJson("/api/opportunities/{$opp->id}/chain");
        $res3->assertOk();
        $this->assertEquals('submit_measurement_for_review', $res3->json('data.next_action.key'));

        // State 4: Measurement Under Review
        $m->update(['status' => 'Under Review']);
        $res4 = $this->withTenantA()->getJson("/api/opportunities/{$opp->id}/chain");
        $res4->assertOk();
        $this->assertEquals('approve_measurement', $res4->json('data.next_action.key'));

        // State 5: Measurement Approved, no scopes
        $m->update(['status' => 'Approved', 'approved_by' => $this->ownerA->id, 'approved_at' => Carbon::now()]);
        $res5 = $this->withTenantA()->getJson("/api/opportunities/{$opp->id}/chain");
        $res5->assertOk();
        $this->assertEquals('create_scope', $res5->json('data.next_action.key'));

        // State 6: Scope Draft
        $s = Scope::create([
            'opportunity_id' => $opp->id,
            'measurement_id' => $m->id,
            'scope_number' => 'SC-DRAFT-01',
            'version' => 1,
            'status' => 'Draft',
            'created_by' => $this->ownerA->id,
        ]);
        $res6 = $this->withTenantA()->getJson("/api/opportunities/{$opp->id}/chain");
        $res6->assertOk();
        $this->assertEquals('submit_scope_for_review', $res6->json('data.next_action.key'));

        // State 7: Scope Under Review
        $s->update(['status' => 'Under Review']);
        $res7 = $this->withTenantA()->getJson("/api/opportunities/{$opp->id}/chain");
        $res7->assertOk();
        $this->assertEquals('approve_scope', $res7->json('data.next_action.key'));

        // State 8: Scope Approved -> Ready for BOQ
        $s->update(['status' => 'Approved', 'approved_by' => $this->ownerA->id, 'approved_at' => Carbon::now()]);
        $res8 = $this->withTenantA()->getJson("/api/opportunities/{$opp->id}/chain");
        $res8->assertOk();
        $this->assertEquals('ready_for_boq', $res8->json('data.next_action.key'));
    }

    /**
     * 5. Test Cross-Tenant Opportunity access is rejected with 404:
     * Tenant B cannot access Tenant A's Opportunity chain.
     */
    public function test_cross_tenant_opportunity_chain_access_is_rejected(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create(['name' => 'عميل سري أ', 'created_by' => $this->ownerA->id]);
        $oppA = Opportunity::create(['customer_id' => $customer->id, 'title' => 'فرصة سرية أ', 'created_by' => $this->ownerA->id]);

        // Attempt to access oppA using Tenant B context & token
        $res = $this->withTenantB()->getJson("/api/opportunities/{$oppA->id}/chain");

        $res->assertNotFound();
    }

    /**
     * 6. Test Unauthorized user cannot access chain endpoint:
     * User without opportunities.view permission receives 403.
     */
    public function test_unauthorized_user_cannot_access_chain_endpoint(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create(['name' => 'عميل مخصص', 'created_by' => $this->ownerA->id]);
        $opp = Opportunity::create(['customer_id' => $customer->id, 'title' => 'فرصة محمية', 'created_by' => $this->ownerA->id]);

        // Create restricted user without opportunity permissions
        $restrictedUser = User::create([
            'name' => 'عامل محدود الصلاحية',
            'email' => 'worker@alpha.test',
            'password' => bcrypt('Pass123!'),
            'status' => 'active',
        ]);

        $restrictedToken = $restrictedUser->createToken('restricted_token')->plainTextToken;

        $res = $this->withHeaders([
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => "Bearer {$restrictedToken}",
            'Accept' => 'application/json',
        ])->getJson("/api/opportunities/{$opp->id}/chain");

        $res->assertForbidden();
    }

    /**
     * 7. Test query optimization: No N+1 query explosion on chain endpoint.
     */
    public function test_no_n_plus_one_query_explosion_on_chain_endpoint(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create(['name' => 'عميل الأداء', 'created_by' => $this->ownerA->id]);
        $opp = Opportunity::create(['customer_id' => $customer->id, 'title' => 'فرصة فحص الأداء', 'created_by' => $this->ownerA->id]);

        // Add 3 visits
        for ($i = 1; $i <= 3; $i++) {
            $v = SiteVisit::create([
                'customer_id' => $customer->id,
                'opportunity_id' => $opp->id,
                'scheduled_date' => "2026-09-0{$i}",
                'created_by' => $this->ownerA->id,
            ]);
            SiteVisitRoom::create(['site_visit_id' => $v->id, 'room_name' => "غرفة {$i}", 'estimated_area' => 20]);
        }

        // Add 2 measurements with 5 items each
        for ($v = 1; $v <= 2; $v++) {
            $m = Measurement::create([
                'opportunity_id' => $opp->id,
                'measurement_number' => "M-PERF-0{$v}",
                'version' => $v,
                'status' => $v === 1 ? 'Approved' : 'Draft',
                'created_by' => $this->ownerA->id,
            ]);
            for ($k = 1; $k <= 5; $k++) {
                MeasurementItem::create([
                    'measurement_id' => $m->id,
                    'room_name' => "غرفة {$k}",
                    'item_name' => "بند قياس {$k}",
                    'unit' => 'm2',
                    'measurement_type' => 'area',
                    'count' => 1,
                    'net_quantity' => 15.0,
                    'gross_quantity' => 15.0,
                ]);
            }
        }

        // Add 2 scopes with 3 items each
        $m1 = Measurement::where('opportunity_id', $opp->id)->where('version', 1)->first();
        for ($sV = 1; $sV <= 2; $sV++) {
            $sc = Scope::create([
                'opportunity_id' => $opp->id,
                'measurement_id' => $m1->id,
                'scope_number' => "SC-PERF-0{$sV}",
                'version' => $sV,
                'status' => $sV === 1 ? 'Approved' : 'Draft',
                'created_by' => $this->ownerA->id,
            ]);
            for ($j = 1; $j <= 3; $j++) {
                $scItem = $sc->items()->create([
                    'trade_category' => 'عام',
                    'item_name' => "حزمة عمل {$j}",
                    'sort_order' => $j,
                ]);
                $scItem->measurementItems()->sync([$m1->items->first()->id]);
            }
        }

        DB::enableQueryLog();

        $res = $this->withTenantA()->getJson("/api/opportunities/{$opp->id}/chain");
        $res->assertOk();

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Ensure query count is bounded and well below explosive thresholds
        $this->assertLessThanOrEqual(15, $queryCount, "Expected at most 15 queries for full chain load, but executed {$queryCount}");
    }
}
