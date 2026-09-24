<?php

namespace Tests\Feature;

use App\Core\Tenancy\TenantDatabaseManager;
use App\Models\Central\Tenant;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Measurement;
use App\Models\MeasurementItem;
use App\Models\Opportunity;
use App\Models\SiteVisit;
use App\Models\SiteVisitRoom;
use App\Models\User;
use App\Services\Tenant\TenantProvisioningService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MeasurementManagementTest extends TestCase
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

        $this->slugA = str_replace('.', '', uniqid('meas-a-', true)) . rand(100, 999);
        $this->slugB = str_replace('.', '', uniqid('meas-b-', true)) . rand(100, 999);

        // Provision Tenant A
        $resA = $this->provisioningService->provision([
            'name' => 'Alpha Quantity Surveying Corp',
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
            'name' => 'Beta Quantity Surveying Corp',
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
            if (isset($this->tenantA)) {
                TenantDatabaseManager::purgeTenantConnection($this->tenantA);
            }
            if (isset($this->tenantB)) {
                TenantDatabaseManager::purgeTenantConnection($this->tenantB);
            }
        } catch (\Throwable $e) {
            // ignore
        }

        parent::tearDown();
    }

    /**
     * SCENARIO 1: Opportunity without Site Visit -> Create Measurement -> Add items -> Approve.
     */
    public function test_can_create_measurement_for_opportunity_without_site_visit_and_approve(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'name' => 'Architectural Client',
            'customer_type' => 'individual',
            'phone' => '01011112222',
            'status' => 'active',
        ]);

        $opportunity = Opportunity::create([
            'customer_id' => $customer->id,
            'title' => 'Commercial Mall Finishing (From CAD)',
            'stage' => 'Qualified',
            'estimated_value' => 500000.00,
        ]);

        $engineer = User::create([
            'name' => 'Eng. Senior QS',
            'email' => 'qs@alpha.test',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        // Create measurement without site visit
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson('/api/measurements', [
            'opportunity_id' => $opportunity->id,
            'site_visit_id' => null,
            'measured_by' => $engineer->id,
            'measured_at' => '2026-11-10',
            'notes' => 'Takeoffs extracted from AutoCAD revision 4.',
            'items' => [
                [
                    'room_name' => 'Zone A - Retail Hall',
                    'item_name' => 'Gypsum Board Ceiling',
                    'unit' => 'm2',
                    'count' => 2,
                    'length' => 10,
                    'width' => 8,
                    'deductions' => 10,
                    'notes' => 'Deduction for skylight opening.',
                ],
            ],
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.opportunity_id', $opportunity->id)
            ->assertJsonPath('data.status', 'Draft');

        $measurementId = $response->json('data.id');

        // Verify Gross (2 * 10 * 8 = 160) and Net (160 - 10 = 150)
        $this->assertEquals(150.00, (float) $response->json('data.total_area'));

        // Approve measurement
        $approveResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson("/api/measurements/{$measurementId}/approve");

        $approveResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'Approved');

        $this->assertDatabaseHas('measurements', [
            'id' => $measurementId,
            'status' => 'Approved',
            'total_area' => 150.00,
        ]);
    }

    /**
     * SCENARIO 2: Opportunity with completed Site Visit -> Import rooms -> Modify measurements -> Approve.
     */
    public function test_can_create_measurement_from_site_visit_import_and_approve(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'name' => 'Villa Owner Client',
            'customer_type' => 'individual',
            'phone' => '01033334444',
            'status' => 'active',
        ]);

        $opportunity = Opportunity::create([
            'customer_id' => $customer->id,
            'title' => 'Villa Full Renovation',
            'stage' => 'Proposal',
        ]);

        $siteVisit = SiteVisit::create([
            'customer_id' => $customer->id,
            'opportunity_id' => $opportunity->id,
            'status' => 'Completed',
            'visit_date' => '2026-11-01',
            'general_assessment' => 'Site ready for fit-out.',
        ]);

        $siteVisit->rooms()->create([
            'room_name' => 'Grand Salon',
            'estimated_area' => 45.00,
            'notes' => 'High ceiling.',
        ]);

        $siteVisit->rooms()->create([
            'room_name' => 'Master Bedroom Suite',
            'estimated_area' => 28.00,
        ]);

        // Import from site visit
        $importResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson("/api/measurements/import-from-site-visit/{$siteVisit->id}", [
            'opportunity_id' => $opportunity->id,
        ]);

        $importResponse->assertSuccessful()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'Draft')
            ->assertJsonPath('data.site_visit_id', $siteVisit->id);

        $measurementId = $importResponse->json('data.id');
        $this->assertCount(2, $importResponse->json('data.items'));

        // Update items with exact dimensions
        $updateResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->putJson("/api/measurements/{$measurementId}", [
            'items' => [
                [
                    'room_name' => 'Grand Salon',
                    'item_name' => 'Wall Emulsion Paint',
                    'unit' => 'm2',
                    'count' => 1,
                    'length' => 9.00,
                    'width' => 5.00,
                    'deductions' => 3.00, // 45 - 3 = 42
                ],
                [
                    'room_name' => 'Master Bedroom Suite',
                    'item_name' => 'Parquet Flooring',
                    'unit' => 'm2',
                    'count' => 1,
                    'length' => 7.00,
                    'width' => 4.00,
                    'deductions' => 0.00, // 28
                ],
            ],
        ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        // Total Area = 42 + 28 = 70.00
        $this->assertEquals(70.00, (float) $updateResponse->json('data.total_area'));

        // Approve
        $approveResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson("/api/measurements/{$measurementId}/approve");

        $approveResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'Approved');
    }

    /**
     * SCENARIO 3: Site Visit contains rough estimated_area -> Verify it does NOT automatically become approved quantity.
     */
    public function test_site_visit_rough_area_does_not_become_approved_quantity_automatically(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'name' => 'Test Client Rough Area',
            'customer_type' => 'individual',
            'phone' => '01055556666',
            'status' => 'active',
        ]);

        $opportunity = Opportunity::create([
            'customer_id' => $customer->id,
            'title' => 'Rough Area Verification',
            'stage' => 'New',
        ]);

        $siteVisit = SiteVisit::create([
            'customer_id' => $customer->id,
            'opportunity_id' => $opportunity->id,
            'status' => 'Completed',
        ]);

        $siteVisit->rooms()->create([
            'room_name' => 'Reception Area',
            'estimated_area' => 88.50, // Rough field guess
        ]);

        // Import
        $importResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson("/api/measurements/import-from-site-visit/{$siteVisit->id}", [
            'opportunity_id' => $opportunity->id,
        ]);

        $importResponse->assertSuccessful();
        $measurementId = $importResponse->json('data.id');

        // At import, net_quantity is 0.00 (not 88.50) because dimensions were not measured yet
        $this->assertEquals(0.00, (float) $importResponse->json('data.total_area'));
        $this->assertEquals(0.00, (float) $importResponse->json('data.items.0.net_quantity'));

        // Historical SiteVisitRoom still has its original rough estimate
        $this->assertEquals(88.50, (float) SiteVisitRoom::where('site_visit_id', $siteVisit->id)->first()->estimated_area);
    }

    /**
     * SCENARIO 4: Approved Measurement cannot be casually edited.
     */
    public function test_approved_measurement_cannot_be_modified(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'name' => 'Immutable Measurement Customer',
            'customer_type' => 'company',
            'phone' => '01077778888',
            'status' => 'active',
        ]);

        $opportunity = Opportunity::create([
            'customer_id' => $customer->id,
            'title' => 'Immutable Project',
            'stage' => 'Won',
        ]);

        $measurement = Measurement::create([
            'opportunity_id' => $opportunity->id,
            'measurement_number' => 'M-LOCK-001',
            'version' => 1,
            'status' => 'Approved',
            'total_area' => 100.00,
        ]);

        $measurement->items()->create([
            'room_name' => 'Room 1',
            'item_name' => 'Approved Item',
            'unit' => 'm2',
            'count' => 1,
            'length' => 10,
            'width' => 10,
            'gross_quantity' => 100.00,
            'net_quantity' => 100.00,
        ]);

        // Attempting to update approved measurement -> MUST return 422
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->putJson("/api/measurements/{$measurement->id}", [
            'notes' => 'Attempting unauthorized modification.',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /**
     * SCENARIO 5: Approved Measurement -> Create Revision -> Approve Revision -> Version 1 becomes Superseded.
     */
    public function test_can_create_revision_from_approved_measurement_and_preserve_history(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'name' => 'Revision Client',
            'customer_type' => 'company',
            'phone' => '01099990000',
            'status' => 'active',
        ]);

        $opportunity = Opportunity::create([
            'customer_id' => $customer->id,
            'title' => 'Multi-Revision Building',
            'stage' => 'Negotiation',
        ]);

        // Version 1 (Approved)
        $v1 = Measurement::create([
            'opportunity_id' => $opportunity->id,
            'measurement_number' => 'M-BLD-001',
            'version' => 1,
            'status' => 'Approved',
            'total_area' => 50.00,
            'approved_at' => Carbon::yesterday(),
        ]);

        $v1->items()->create([
            'room_name' => 'Unit 101',
            'item_name' => 'Plaster Works',
            'unit' => 'm2',
            'count' => 1,
            'length' => 10,
            'width' => 5,
            'gross_quantity' => 50.00,
            'net_quantity' => 50.00,
        ]);

        // Create Revision via API
        $revResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson("/api/measurements/{$v1->id}/create-revision");

        $revResponse->assertSuccessful()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.version', 2)
            ->assertJsonPath('data.status', 'Draft')
            ->assertJsonPath('data.measurement_number', 'M-BLD-001-V2');

        $v2Id = $revResponse->json('data.id');

        // Version 1 is still Approved while Version 2 is Draft
        $this->assertEquals('Approved', $v1->fresh()->status);

        // Update Version 2 with new quantity
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->putJson("/api/measurements/{$v2Id}", [
            'items' => [
                [
                    'room_name' => 'Unit 101',
                    'item_name' => 'Plaster Works',
                    'unit' => 'm2',
                    'count' => 1,
                    'length' => 12,
                    'width' => 5,
                    'deductions' => 0, // 60.00
                ],
            ],
        ]);

        // Approve Version 2
        $approveV2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson("/api/measurements/{$v2Id}/approve");

        $approveV2->assertStatus(200)
            ->assertJsonPath('data.status', 'Approved');

        // Version 1 is now Superseded, Version 2 is Approved
        $this->assertEquals('Superseded', $v1->fresh()->status);
        $this->assertEquals('Approved', Measurement::find($v2Id)->status);
    }

    /**
     * SCENARIOS 6 & 7: Tenant Isolation & IDOR Protection across Tenants.
     */
    public function test_tenant_isolation_and_cross_tenant_access_protection(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantB);

        $customerB = Customer::create([
            'name' => 'Beta Tenant Customer',
            'customer_type' => 'company',
            'phone' => '01122223333',
            'status' => 'active',
        ]);

        $opportunityB = Opportunity::create([
            'customer_id' => $customerB->id,
            'title' => 'Beta Opportunity',
            'stage' => 'Qualified',
        ]);

        $measurementB = Measurement::create([
            'opportunity_id' => $opportunityB->id,
            'measurement_number' => 'M-BETA-999',
            'status' => 'Draft',
        ]);

        $siteVisitB = SiteVisit::create([
            'customer_id' => $customerB->id,
            'opportunity_id' => $opportunityB->id,
            'status' => 'Completed',
        ]);

        // Tenant A attempts to view Tenant B's measurement -> 404
        $viewResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->getJson("/api/measurements/{$measurementB->id}");

        $viewResponse->assertStatus(404);

        // Tenant A attempts to import Tenant B's site visit -> 404
        $importResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson("/api/measurements/import-from-site-visit/{$siteVisitB->id}");

        $importResponse->assertStatus(404);

        // Tenant A attempts to approve Tenant B's measurement -> 404
        $approveResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson("/api/measurements/{$measurementB->id}/approve");

        $approveResponse->assertStatus(404);
    }

    /**
     * SCENARIOS 8, 9, 10, 11: Exact Mathematical Formulas for AREA, VOLUME, LINEAR, and COUNT.
     */
    public function test_mathematical_calculation_formulas(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'name' => 'Math Formulas Client',
            'customer_type' => 'individual',
            'phone' => '01088887777',
            'status' => 'active',
        ]);

        $opportunity = Opportunity::create([
            'customer_id' => $customer->id,
            'title' => 'Math Engine Validation',
            'stage' => 'Proposal',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson('/api/measurements', [
            'opportunity_id' => $opportunity->id,
            'items' => [
                // Scenario 8: AREA -> 2 * 5 * 4 - 2 = 38 m2
                [
                    'room_name' => 'Room Area',
                    'item_name' => 'Area Item',
                    'unit' => 'm2',
                    'count' => 2,
                    'length' => 5,
                    'width' => 4,
                    'deductions' => 2,
                ],
                // Scenario 9: VOLUME -> 1 * 5 * 4 * 3 - 2 = 58 m3
                [
                    'room_name' => 'Room Volume',
                    'item_name' => 'Volume Item',
                    'unit' => 'm3',
                    'count' => 1,
                    'length' => 5,
                    'width' => 4,
                    'height' => 3,
                    'deductions' => 2,
                ],
                // Scenario 10: LINEAR -> 3 * 4 - 1 = 11 lm
                [
                    'room_name' => 'Room Linear',
                    'item_name' => 'Linear Item',
                    'unit' => 'lm',
                    'count' => 3,
                    'length' => 4,
                    'deductions' => 1,
                ],
                // Scenario 11: COUNT -> 10 - 2 = 8 pcs
                [
                    'room_name' => 'Room Count',
                    'item_name' => 'Count Item',
                    'unit' => 'pcs',
                    'count' => 10,
                    'deductions' => 2,
                ],
            ],
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('success', true);

        $items = $response->json('data.items');

        // Item 0 (Area): Net = 38
        $this->assertEquals('m2', $items[0]['unit']);
        $this->assertEquals(40.00, (float) $items[0]['gross_quantity']);
        $this->assertEquals(38.00, (float) $items[0]['net_quantity']);

        // Item 1 (Volume): Net = 58
        $this->assertEquals('m3', $items[1]['unit']);
        $this->assertEquals(60.00, (float) $items[1]['gross_quantity']);
        $this->assertEquals(58.00, (float) $items[1]['net_quantity']);

        // Item 2 (Linear): Net = 11
        $this->assertEquals('lm', $items[2]['unit']);
        $this->assertEquals(12.00, (float) $items[2]['gross_quantity']);
        $this->assertEquals(11.00, (float) $items[2]['net_quantity']);

        // Item 3 (Count): Net = 8
        $this->assertEquals('pcs', $items[3]['unit']);
        $this->assertEquals(10.00, (float) $items[3]['gross_quantity']);
        $this->assertEquals(8.00, (float) $items[3]['net_quantity']);

        // Summary Aggregates
        $this->assertEquals(38.00, (float) $response->json('data.total_area'));
        $this->assertEquals(58.00, (float) $response->json('data.total_volume'));
        $this->assertEquals(11.00, (float) $response->json('data.total_linear'));
        $this->assertEquals(8.00, (float) $response->json('data.total_count'));
    }

    /**
     * Test Cannot Approve Empty Measurement or Delete Approved Measurement.
     */
    public function test_cannot_approve_empty_measurement_or_delete_approved(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'name' => 'Edge Cases Client',
            'customer_type' => 'individual',
            'phone' => '01011119999',
            'status' => 'active',
        ]);

        $opportunity = Opportunity::create([
            'customer_id' => $customer->id,
            'title' => 'Edge Cases Project',
            'stage' => 'Proposal',
        ]);

        $emptyMeasurement = Measurement::create([
            'opportunity_id' => $opportunity->id,
            'measurement_number' => 'M-EMPTY-001',
            'status' => 'Draft',
        ]);

        // Attempting to approve empty measurement -> 422
        $approveResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson("/api/measurements/{$emptyMeasurement->id}/approve");

        $approveResponse->assertStatus(422)
            ->assertJsonValidationErrors(['items']);

        // Draft can be deleted
        $deleteDraft = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->deleteJson("/api/measurements/{$emptyMeasurement->id}");

        $deleteDraft->assertStatus(200);

        // Create approved measurement and attempt deleting
        $approvedM = Measurement::create([
            'opportunity_id' => $opportunity->id,
            'measurement_number' => 'M-APP-001',
            'status' => 'Approved',
        ]);

        $deleteApproved = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->deleteJson("/api/measurements/{$approvedM->id}");

        $deleteApproved->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /**
     * TEST 1: Import requires explicit opportunity_id (returns 422 if missing).
     */
    public function test_import_requires_explicit_opportunity_id(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'name' => 'Import Validation Client',
            'customer_type' => 'individual',
            'phone' => '01012345678',
            'status' => 'active',
        ]);

        $siteVisit = SiteVisit::create([
            'customer_id' => $customer->id,
            'status' => 'Completed',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson("/api/measurements/import-from-site-visit/{$siteVisit->id}", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['opportunity_id']);
    }

    /**
     * TEST 2: Import rejects opportunity customer mismatch (SiteVisit for Customer A, Opportunity for Customer B).
     */
    public function test_import_rejects_opportunity_customer_mismatch(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customerA = Customer::create([
            'name' => 'Customer Alpha',
            'customer_type' => 'individual',
            'phone' => '01011112222',
            'status' => 'active',
        ]);

        $customerB = Customer::create([
            'name' => 'Customer Beta',
            'customer_type' => 'company',
            'phone' => '01033334444',
            'status' => 'active',
        ]);

        $siteVisitA = SiteVisit::create([
            'customer_id' => $customerA->id,
            'status' => 'Completed',
        ]);

        $opportunityB = Opportunity::create([
            'customer_id' => $customerB->id,
            'title' => 'Customer B Opportunity',
            'stage' => 'Qualified',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson("/api/measurements/import-from-site-visit/{$siteVisitA->id}", [
            'opportunity_id' => $opportunityB->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['opportunity_id']);
    }

    /**
     * TEST 3: Import does NOT mutate historical site visit opportunity_id.
     */
    public function test_import_does_not_mutate_site_visit_opportunity_id(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'name' => 'Customer Direct Visit',
            'customer_type' => 'individual',
            'phone' => '01055557777',
            'status' => 'active',
        ]);

        $siteVisit = SiteVisit::create([
            'customer_id' => $customer->id,
            'opportunity_id' => null, // Direct site visit
            'status' => 'Completed',
        ]);

        $opportunity = Opportunity::create([
            'customer_id' => $customer->id,
            'title' => 'Explicit Opp Link',
            'stage' => 'Qualified',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson("/api/measurements/import-from-site-visit/{$siteVisit->id}", [
            'opportunity_id' => $opportunity->id,
        ]);

        $response->assertSuccessful();

        // Historical site visit record MUST remain untouched (opportunity_id stays null)
        $this->assertNull($siteVisit->fresh()->opportunity_id);

        // Created measurement references both independently
        $this->assertEquals($opportunity->id, $response->json('data.opportunity_id'));
        $this->assertEquals($siteVisit->id, $response->json('data.site_visit_id'));
    }

    /**
     * TEST 4: Customer having multiple open opportunities -> measurement links to explicitly provided opportunity.
     */
    public function test_import_with_customer_having_multiple_open_opportunities(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'name' => 'Multi-Opp Client',
            'customer_type' => 'company',
            'phone' => '01088889999',
            'status' => 'active',
        ]);

        $oppOld = Opportunity::create([
            'customer_id' => $customer->id,
            'title' => 'Project Alpha 1',
            'stage' => 'Qualified',
            'created_at' => Carbon::now()->subDays(5),
        ]);

        $oppNew = Opportunity::create([
            'customer_id' => $customer->id,
            'title' => 'Project Alpha 2',
            'stage' => 'Proposal',
            'created_at' => Carbon::now(),
        ]);

        $siteVisit = SiteVisit::create([
            'customer_id' => $customer->id,
            'opportunity_id' => null,
            'status' => 'Completed',
        ]);

        // Explicitly importing into older Opportunity 1
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson("/api/measurements/import-from-site-visit/{$siteVisit->id}", [
            'opportunity_id' => $oppOld->id,
        ]);

        $response->assertSuccessful();
        $this->assertEquals($oppOld->id, $response->json('data.opportunity_id'));
    }

    /**
     * TEST 5: Contradictory Unit <-> Measurement Type is strictly rejected server-side.
     */
    public function test_unit_and_measurement_type_mismatch_is_rejected(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'name' => 'Unit Mismatch Customer',
            'customer_type' => 'individual',
            'phone' => '01011113333',
            'status' => 'active',
        ]);

        $opportunity = Opportunity::create([
            'customer_id' => $customer->id,
            'title' => 'Unit Validation Opp',
            'stage' => 'Qualified',
        ]);

        // Attempting to send m2 with volume -> MUST return 422
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson('/api/measurements', [
            'opportunity_id' => $opportunity->id,
            'items' => [
                [
                    'room_name' => 'Room 1',
                    'item_name' => 'Contradictory Item',
                    'unit' => 'm2',
                    'measurement_type' => 'volume', // Invalid combination!
                    'count' => 1,
                    'length' => 5,
                    'width' => 4,
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.unit']);

        // Attempting to send pcs with linear -> MUST return 422
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson('/api/measurements', [
            'opportunity_id' => $opportunity->id,
            'items' => [
                [
                    'room_name' => 'Room 2',
                    'item_name' => 'Contradictory Count Item',
                    'unit' => 'pcs',
                    'measurement_type' => 'linear', // Invalid combination!
                    'count' => 5,
                ],
            ],
        ]);

        $response2->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.unit']);
    }

    /**
     * TEST 6: Store Measurement rejects site visit belonging to a different customer.
     */
    public function test_store_measurement_rejects_mismatched_site_visit_customer(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customerA = Customer::create([
            'name' => 'Customer Alpha Context',
            'customer_type' => 'individual',
            'phone' => '01044445555',
            'status' => 'active',
        ]);

        $customerB = Customer::create([
            'name' => 'Customer Beta Context',
            'customer_type' => 'company',
            'phone' => '01066667777',
            'status' => 'active',
        ]);

        $opportunityA = Opportunity::create([
            'customer_id' => $customerA->id,
            'title' => 'Opp for Customer A',
            'stage' => 'Qualified',
        ]);

        $siteVisitB = SiteVisit::create([
            'customer_id' => $customerB->id,
            'status' => 'Completed',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson('/api/measurements', [
            'opportunity_id' => $opportunityA->id,
            'site_visit_id' => $siteVisitB->id, // Mismatched customer!
            'items' => [
                [
                    'room_name' => 'Hall',
                    'item_name' => 'Flooring',
                    'unit' => 'm2',
                    'count' => 1,
                    'length' => 5,
                    'width' => 4,
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['site_visit_id']);
    }

    /**
     * TEST 7: Opportunity approvedMeasurement relation accurately resolves approved revision and enables Scope traceability.
     */
    public function test_opportunity_approved_measurement_helper_relation(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'name' => 'Scope Traceability Customer',
            'customer_type' => 'company',
            'phone' => '01088889990',
            'status' => 'active',
        ]);

        $opportunity = Opportunity::create([
            'customer_id' => $customer->id,
            'title' => 'Future Scope Commercial Hub',
            'stage' => 'Qualified',
        ]);

        // V1 (Superseded)
        $m1 = Measurement::create([
            'opportunity_id' => $opportunity->id,
            'measurement_number' => 'M-TRACE-001',
            'version' => 1,
            'status' => 'Superseded',
            'total_area' => 100.00,
        ]);

        // V2 (Approved)
        $m2 = Measurement::create([
            'opportunity_id' => $opportunity->id,
            'measurement_number' => 'M-TRACE-001-V2',
            'version' => 2,
            'status' => 'Approved',
            'total_area' => 120.00,
        ]);

        // V3 (Draft)
        $m3 = Measurement::create([
            'opportunity_id' => $opportunity->id,
            'measurement_number' => 'M-TRACE-001-V3',
            'version' => 3,
            'status' => 'Draft',
            'total_area' => 130.00,
        ]);

        $opp = Opportunity::with('approvedMeasurement', 'latestMeasurement')->find($opportunity->id);

        $this->assertNotNull($opp->approvedMeasurement);
        $this->assertEquals($m2->id, $opp->approvedMeasurement->id);
        $this->assertEquals('Approved', $opp->approvedMeasurement->status);
        $this->assertEquals(120.00, (float) $opp->approvedMeasurement->total_area);

        $this->assertNotNull($opp->latestMeasurement);
        $this->assertEquals($m3->id, $opp->latestMeasurement->id);
        $this->assertEquals(3, $opp->latestMeasurement->version);
    }

    /**
     * SCENARIO 21: Status Bypass Protection — Passing 'status' => 'Approved' in store() is completely ignored and forced to Draft.
     */
    public function test_store_measurement_ignores_status_field_and_forces_draft(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'name' => 'Measurement Bypass Customer',
            'customer_type' => 'individual',
            'phone' => '01011223344',
            'status' => 'active',
        ]);

        $opportunity = Opportunity::create([
            'customer_id' => $customer->id,
            'title' => 'Measurement Bypass Opp',
            'stage' => 'Proposal',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson('/api/measurements', [
            'opportunity_id' => $opportunity->id,
            'status' => 'Approved', // Injection attempt!
            'version' => 10, // Injection attempt!
            'approved_by' => 1,
            'approved_at' => now()->toDateTimeString(),
            'notes' => 'Test injection notes',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'Draft')
            ->assertJsonPath('data.version', 1);

        $measId = $response->json('data.id');
        $storedMeas = Measurement::find($measId);

        $this->assertEquals('Draft', $storedMeas->status);
        $this->assertEquals(1, $storedMeas->version);
        $this->assertNull($storedMeas->approved_by);
        $this->assertNull($storedMeas->approved_at);
    }
}
