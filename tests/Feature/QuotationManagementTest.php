<?php

namespace Tests\Feature;

use App\Core\Tenancy\TenantDatabaseManager;
use App\Models\Boq;
use App\Models\Central\Tenant;
use App\Models\Customer;
use App\Models\Estimation;
use App\Models\EstimationItem;
use App\Models\Measurement;
use App\Models\Opportunity;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Scope;
use App\Models\SiteVisit;
use App\Models\User;
use App\Services\Tenant\TenantProvisioningService;
use Illuminate\Support\Facades\Artisan;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class QuotationManagementTest extends TestCase
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

        $this->slugA = str_replace('.', '', uniqid('qt-a-', true)) . rand(100, 999);
        $this->slugB = str_replace('.', '', uniqid('qt-b-', true)) . rand(100, 999);

        // Provision Tenant A
        $resA = $this->provisioningService->provision([
            'name' => 'Alpha Quotation Corp',
            'slug' => $this->slugA,
            'company_code' => 'QTALPH-' . uniqid(),
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
            'name' => 'Beta Quotation Corp',
            'slug' => $this->slugB,
            'company_code' => 'QTBETA-' . uniqid(),
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

    protected function headersA(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ];
    }

    protected function headersB(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->tokenB,
            'X-Tenant-Slug' => $this->slugB,
            'Accept' => 'application/json',
        ];
    }

    /**
     * Helper to create an approved Estimation chain in Tenant A.
     */
    protected function makeApprovedEstimation(string $suffix = '', float $item1Cost = 100.00, float $item2Cost = 200.00): array
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'name' => 'Client ' . $suffix,
            'customer_type' => 'company',
            'phone' => '010' . rand(10000000, 99999999),
            'status' => 'active',
        ]);

        $opportunity = Opportunity::create([
            'customer_id' => $customer->id,
            'title' => 'Project ' . $suffix,
            'stage' => 'Proposal',
        ]);

        $siteVisit = SiteVisit::create([
            'customer_id' => $customer->id,
            'opportunity_id' => $opportunity->id,
            'status' => 'Completed',
            'scheduled_date' => '2026-09-20',
            'visit_date' => '2026-09-20',
            'general_assessment' => 'معاينة ميدانية توثيقية',
            'created_by' => $this->ownerA->id,
        ]);

        $measurement = Measurement::create([
            'opportunity_id' => $opportunity->id,
            'measurement_number' => 'M-' . $suffix . '-001',
            'version' => 1,
            'status' => 'Approved',
            'total_area' => 120.00,
        ]);

        $mItem1 = $measurement->items()->create([
            'room_name' => 'Reception',
            'item_name' => 'Porcelain Floor',
            'unit' => 'm2',
            'measurement_type' => 'area',
            'count' => 1,
            'length' => 10,
            'width' => 8,
            'gross_quantity' => 10.00,
            'net_quantity' => 10.00,
        ]);

        $mItem2 = $measurement->items()->create([
            'room_name' => 'Office',
            'item_name' => 'HDF Floor',
            'unit' => 'm2',
            'measurement_type' => 'area',
            'count' => 1,
            'length' => 8,
            'width' => 5,
            'gross_quantity' => 5.00,
            'net_quantity' => 5.00,
        ]);

        $scope = Scope::create([
            'opportunity_id' => $opportunity->id,
            'measurement_id' => $measurement->id,
            'scope_number' => 'SC-' . $suffix . '-001',
            'version' => 1,
            'status' => 'Approved',
            'title' => 'Scope ' . $suffix,
            'approved_by' => $this->ownerA->id,
            'approved_at' => now(),
        ]);

        $sItem1 = $scope->items()->create([
            'trade_category' => 'أعمال الأرضيات',
            'item_name' => 'توريد وتركيب بورسلين',
            'specification' => 'بورسلين إسباني',
        ]);
        $sItem1->measurementItems()->attach($mItem1->id);

        $sItem2 = $scope->items()->create([
            'trade_category' => 'أعمال الأرضيات',
            'item_name' => 'توريد وتركيب HDF',
            'specification' => 'باركيه ألماني',
        ]);
        $sItem2->measurementItems()->attach($mItem2->id);

        $boq = Boq::create([
            'opportunity_id' => $opportunity->id,
            'scope_id' => $scope->id,
            'boq_number' => 'BOQ-' . $suffix . '-001',
            'version' => 1,
            'status' => 'Approved',
            'title' => 'BOQ ' . $suffix,
            'approved_by' => $this->ownerA->id,
            'approved_at' => now(),
        ]);

        $boqItem1 = $boq->items()->create([
            'scope_item_id' => $sItem1->id,
            'item_code' => '1.1',
            'description' => 'توريد وتركيب بورسلين',
            'unit' => 'm2',
            'quantity' => 10.00,
            'sort_order' => 1,
        ]);

        $boqItem2 = $boq->items()->create([
            'scope_item_id' => $sItem2->id,
            'item_code' => '1.2',
            'description' => 'توريد وتركيب HDF',
            'unit' => 'm2',
            'quantity' => 5.00,
            'sort_order' => 2,
        ]);

        // Create Estimation
        $estimation = Estimation::create([
            'opportunity_id' => $opportunity->id,
            'boq_id' => $boq->id,
            'estimation_number' => 'EST-' . $suffix . '-001',
            'version' => 1,
            'status' => 'Approved',
            'approved_by' => $this->ownerA->id,
            'approved_at' => now(),
            'direct_cost' => ($item1Cost * 10.00) + ($item2Cost * 5.00),
            'overhead_rate' => 0.00,
            'overhead_amount' => 0.00,
            'total_cost' => ($item1Cost * 10.00) + ($item2Cost * 5.00),
        ]);

        $estItem1 = $estimation->items()->create([
            'boq_item_id' => $boqItem1->id,
            'item_code' => '1.1',
            'description' => 'توريد وتركيب بورسلين',
            'unit' => 'm2',
            'quantity' => 10.00,
            'sort_order' => 1,
            'material_cost' => $item1Cost,
            'labor_cost' => 0.00,
            'equipment_cost' => 0.00,
            'subcontractor_cost' => 0.00,
            'other_cost' => 0.00,
            'unit_cost' => $item1Cost,
            'total_cost' => $item1Cost * 10.00,
        ]);

        $estItem1->costs()->create([
            'cost_type' => 'material',
            'description' => 'مادة البورسلين',
            'cost_rate' => $item1Cost,
        ]);

        $estItem2 = $estimation->items()->create([
            'boq_item_id' => $boqItem2->id,
            'item_code' => '1.2',
            'description' => 'توريد وتركيب HDF',
            'unit' => 'm2',
            'quantity' => 5.00,
            'sort_order' => 2,
            'material_cost' => $item2Cost,
            'labor_cost' => 0.00,
            'equipment_cost' => 0.00,
            'subcontractor_cost' => 0.00,
            'other_cost' => 0.00,
            'unit_cost' => $item2Cost,
            'total_cost' => $item2Cost * 5.00,
        ]);

        $estItem2->costs()->create([
            'cost_type' => 'material',
            'description' => 'خشب HDF',
            'cost_rate' => $item2Cost,
        ]);

        return compact(
            'customer', 'opportunity', 'measurement', 'scope', 'boq',
            'boqItem1', 'boqItem2', 'estimation', 'estItem1', 'estItem2'
        );
    }

    /**
     * Helper to assert that no confidential cost keys exist anywhere in the array.
     */
    protected function assertZeroCostLeakage(array $data): void
    {
        $forbiddenKeys = [
            'direct_cost',
            'material_cost',
            'labor_cost',
            'equipment_cost',
            'subcontractor_cost',
            'other_cost',
            'unit_cost',
            'overhead_rate',
            'overhead_amount',
            'total_cost',
            'costs', // Estimation cost breakdowns
        ];

        $queue = [$data];
        while (!empty($queue)) {
            $current = array_shift($queue);
            if (is_array($current)) {
                foreach ($current as $key => $val) {
                    $this->assertFalse(
                        in_array($key, $forbiddenKeys, true),
                        "Confidential cost key '{$key}' leaked in Quotation response!"
                    );
                    if (is_array($val)) {
                        $queue[] = $val;
                    }
                }
            }
        }
    }

    /**
     * Scenario 1: Approved Estimation -> create Quotation -> auto selling prices -> approve internally
     * -> mark sent -> record accepted. End-to-end full lifecycle.
     */
    public function test_scenario_1_end_to_end_quotation_lifecycle(): void
    {
        $chain = $this->makeApprovedEstimation('S1', 100.00, 200.00);

        // 1. Create Quotation with 20% markup, 0% discount, 15% tax
        $payload = [
            'opportunity_id' => $chain['opportunity']->id,
            'estimation_id' => $chain['estimation']->id,
            'markup_rate' => 20.00,
            'tax_rate' => 15.00,
            'valid_until' => now()->addDays(30)->toDateString(),
            'notes' => 'Internal pricing notes - confidential',
            'payment_terms_text' => '50% دفعة مقدمة عند التوقيع، 50% عند التسليم',
        ];

        $createRes = $this->postJson('/api/quotations', $payload, $this->headersA());
        $createRes->assertStatus(200);
        $createRes->assertJsonPath('success', true);
        $createRes->assertJsonPath('data.status', 'Draft');
        $createRes->assertJsonPath('data.client_status', 'Not Sent');
        $createRes->assertJsonPath('data.version', 1);

        $quotationId = $createRes->json('data.id');

        // Check Items: Item 1: cost 100 * 1.20 = 120, line total = 120 * 10 = 1200
        // Item 2: cost 200 * 1.20 = 240, line total = 240 * 5 = 1200
        // Subtotal = 2400. Tax 15% = 360. Grand total = 2760.
        $this->assertEquals(2400.00, (float) $createRes->json('data.subtotal'));
        $this->assertEquals(360.00, (float) $createRes->json('data.tax_amount'));
        $this->assertEquals(2760.00, (float) $createRes->json('data.grand_total'));

        // 2. Submit Review
        $reviewRes = $this->postJson("/api/quotations/{$quotationId}/submit-review", [], $this->headersA());
        $reviewRes->assertStatus(200);
        $reviewRes->assertJsonPath('data.status', 'Under Review');

        // 3. Approve Internally
        $approveRes = $this->postJson("/api/quotations/{$quotationId}/approve", [], $this->headersA());
        $approveRes->assertStatus(200);
        $approveRes->assertJsonPath('data.status', 'Approved');
        $this->assertNotNull($approveRes->json('data.approved_at'));
        $this->assertEquals($this->ownerA->id, $approveRes->json('data.approved_by'));

        // 4. Mark Sent to Client
        $sentRes = $this->postJson("/api/quotations/{$quotationId}/mark-sent", [], $this->headersA());
        $sentRes->assertStatus(200);
        $sentRes->assertJsonPath('data.client_status', 'Sent');
        $this->assertNotNull($sentRes->json('data.sent_at'));
        $this->assertEquals($this->ownerA->id, $sentRes->json('data.sent_by'));

        // 5. Record Client Response (Accepted)
        $acceptRes = $this->postJson("/api/quotations/{$quotationId}/record-response", [
            'client_status' => 'Accepted',
            'client_response_notes' => 'Customer approved the quotation in meeting',
        ], $this->headersA());
        $acceptRes->assertStatus(200);
        $acceptRes->assertJsonPath('data.client_status', 'Accepted');
        $acceptRes->assertJsonPath('data.client_response_notes', 'Customer approved the quotation in meeting');

        // Verify Activity Log in Tenant A
        TenantDatabaseManager::switchToTenant($this->tenantA);
        $activities = Activity::where('subject_type', Quotation::class)
            ->where('subject_id', $quotationId)
            ->get();
        $this->assertTrue($activities->contains('event', 'created'));
        $this->assertTrue($activities->contains('event', 'approved'));
        $this->assertTrue($activities->contains('event', 'sent'));
        $this->assertTrue($activities->contains('event', 'client_response'));
    }

    /**
     * Scenario 2: Attempt to create Quotation on Draft Estimation -> rejected with 422.
     */
    public function test_scenario_2_reject_quotation_creation_on_draft_estimation(): void
    {
        $chain = $this->makeApprovedEstimation('S2');

        TenantDatabaseManager::switchToTenant($this->tenantA);
        $chain['estimation']->update(['status' => 'Draft']);

        $payload = [
            'opportunity_id' => $chain['opportunity']->id,
            'estimation_id' => $chain['estimation']->id,
        ];

        $res = $this->postJson('/api/quotations', $payload, $this->headersA());
        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['estimation_id']);
    }

    /**
     * Scenario 3: Zero Leakage Test — verify absolutely zero internal cost fields are returned.
     */
    public function test_scenario_3_zero_leakage_of_internal_cost_data(): void
    {
        $chain = $this->makeApprovedEstimation('S3', 150.00, 300.00);

        // 1. Check POST /quotations response
        $createRes = $this->postJson('/api/quotations', [
            'opportunity_id' => $chain['opportunity']->id,
            'estimation_id' => $chain['estimation']->id,
            'markup_rate' => 25.00,
        ], $this->headersA());
        $createRes->assertStatus(200);
        $this->assertZeroCostLeakage($createRes->json());

        $quotationId = $createRes->json('data.id');

        // 2. Check GET /quotations index response
        $indexRes = $this->getJson('/api/quotations', $this->headersA());
        $indexRes->assertStatus(200);
        $this->assertZeroCostLeakage($indexRes->json());

        // 3. Check GET /quotations/{id} show response
        $showRes = $this->getJson("/api/quotations/{$quotationId}", $this->headersA());
        $showRes->assertStatus(200);
        $this->assertZeroCostLeakage($showRes->json());

        // 4. Verify in Database that quotation_items table doesn't even store any cost columns
        TenantDatabaseManager::switchToTenant($this->tenantA);
        $item = QuotationItem::where('quotation_id', $quotationId)->first();
        $this->assertNotNull($item);
        $rawAttributes = $item->getAttributes();
        $this->assertArrayNotHasKey('unit_cost', $rawAttributes);
        $this->assertArrayNotHasKey('material_cost', $rawAttributes);
        $this->assertArrayNotHasKey('total_cost', $rawAttributes);
        $this->assertArrayNotHasKey('direct_cost', $rawAttributes);
    }

    /**
     * Scenario 4: Cross-estimation integrity — reject quotation item linked to an estimation item
     * belonging to a different estimation.
     */
    public function test_scenario_4_reject_cross_estimation_item_tampering(): void
    {
        $chain1 = $this->makeApprovedEstimation('S4A');
        $chain2 = $this->makeApprovedEstimation('S4B');

        $createRes = $this->postJson('/api/quotations', [
            'opportunity_id' => $chain1['opportunity']->id,
            'estimation_id' => $chain1['estimation']->id,
        ], $this->headersA());
        $quotationId = $createRes->json('data.id');

        // Attempt to update quotation by injecting an item from chain2's estimation
        $updatePayload = [
            'items' => [
                [
                    'estimation_item_id' => $chain2['estItem1']->id, // Belongs to Estimation B
                    'markup_rate' => 15.00,
                ],
            ],
        ];

        $updateRes = $this->putJson("/api/quotations/{$quotationId}", $updatePayload, $this->headersA());
        $updateRes->assertStatus(422);
        $updateRes->assertJsonValidationErrors(['items.0.estimation_item_id']);
    }

    /**
     * Scenario 5: Approved quotation is immutable — direct edit or deletion fails (422) and requires revision.
     */
    public function test_scenario_5_approved_quotation_is_immutable_and_requires_revision(): void
    {
        $chain = $this->makeApprovedEstimation('S5');

        $createRes = $this->postJson('/api/quotations', [
            'opportunity_id' => $chain['opportunity']->id,
            'estimation_id' => $chain['estimation']->id,
        ], $this->headersA());
        $quotationId = $createRes->json('data.id');

        // Approve quotation
        $this->postJson("/api/quotations/{$quotationId}/approve", [], $this->headersA());

        // 1. Direct update must fail
        $editRes = $this->putJson("/api/quotations/{$quotationId}", [
            'notes' => 'Attempted Illegal Modification',
        ], $this->headersA());
        $editRes->assertStatus(422);

        // 2. Direct deletion must fail
        $delRes = $this->deleteJson("/api/quotations/{$quotationId}", [], $this->headersA());
        $delRes->assertStatus(422);

        // 3. Creating revision branches new Draft V2
        $revRes = $this->postJson("/api/quotations/{$quotationId}/create-revision", [], $this->headersA());
        $revRes->assertStatus(200);
        $revRes->assertJsonPath('data.version', 2);
        $revRes->assertJsonPath('data.status', 'Draft');
        $revRes->assertJsonPath('data.client_status', 'Not Sent');
        $revRes->assertJsonPath('data.estimation_id', $chain['estimation']->id);

        $newRevId = $revRes->json('data.id');
        $this->assertNotEquals($quotationId, $newRevId);

        // Newly created revision can be edited
        $editRevRes = $this->putJson("/api/quotations/{$newRevId}", [
            'markup_rate' => 30.00,
        ], $this->headersA());
        $editRevRes->assertStatus(200);
        $this->assertEquals(30.00, (float) $editRevRes->json('data.markup_rate'));
    }

    /**
     * Scenario 6: Attempt to mark Sent when internal status is Draft -> rejected with 422.
     */
    public function test_scenario_6_cannot_send_unapproved_quotation_to_client(): void
    {
        $chain = $this->makeApprovedEstimation('S6');

        $createRes = $this->postJson('/api/quotations', [
            'opportunity_id' => $chain['opportunity']->id,
            'estimation_id' => $chain['estimation']->id,
        ], $this->headersA());
        $quotationId = $createRes->json('data.id');

        // Quotation is still Draft internally
        $sentRes = $this->postJson("/api/quotations/{$quotationId}/mark-sent", [], $this->headersA());
        $sentRes->assertStatus(422);

        // Submit for review -> still Under Review, not Approved yet
        $this->postJson("/api/quotations/{$quotationId}/submit-review", [], $this->headersA());
        $sentRes2 = $this->postJson("/api/quotations/{$quotationId}/mark-sent", [], $this->headersA());
        $sentRes2->assertStatus(422);
    }

    /**
     * Scenario 7: Submitting status: Approved or client_status: Accepted directly in POST /quotations is ignored.
     */
    public function test_scenario_7_cannot_force_statuses_in_store(): void
    {
        $chain = $this->makeApprovedEstimation('S7');

        $payload = [
            'opportunity_id' => $chain['opportunity']->id,
            'estimation_id' => $chain['estimation']->id,
            'status' => 'Approved',
            'client_status' => 'Accepted',
        ];

        $res = $this->postJson('/api/quotations', $payload, $this->headersA());
        $res->assertStatus(200);
        // Must strictly initialize to Draft and Not Sent
        $res->assertJsonPath('data.status', 'Draft');
        $res->assertJsonPath('data.client_status', 'Not Sent');
    }

    /**
     * Scenario 8: Exact mathematical calculation verification.
     * Item cost: 100, markup 20% -> price 120.
     * Qty = 10 -> line total = 1200.
     * Discount = 5% -> discount value = 60.00, taxable = 1140.00.
     * Tax rate = 15% -> tax amount = 171.00.
     * Grand total = 1311.00.
     */
    public function test_scenario_8_exact_mathematical_precision(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'name' => 'Math Client',
            'customer_type' => 'individual',
            'phone' => '01011112222',
            'status' => 'active',
        ]);

        $opportunity = Opportunity::create([
            'customer_id' => $customer->id,
            'title' => 'Math Precision Opportunity',
        ]);

        $measurement = Measurement::create([
            'opportunity_id' => $opportunity->id,
            'measurement_number' => 'M-MATH-01',
            'version' => 1,
            'status' => 'Approved',
        ]);

        $mItem = $measurement->items()->create([
            'room_name' => 'Room',
            'item_name' => 'Single Item',
            'unit' => 'm2',
            'measurement_type' => 'area',
            'gross_quantity' => 10.00,
            'net_quantity' => 10.00,
        ]);

        $scope = Scope::create([
            'opportunity_id' => $opportunity->id,
            'measurement_id' => $measurement->id,
            'scope_number' => 'SC-MATH-01',
            'version' => 1,
            'status' => 'Approved',
        ]);

        $sItem = $scope->items()->create([
            'trade_category' => 'الأرضيات',
            'item_name' => 'Single Item',
        ]);
        $sItem->measurementItems()->attach($mItem->id);

        $boq = Boq::create([
            'opportunity_id' => $opportunity->id,
            'scope_id' => $scope->id,
            'boq_number' => 'BOQ-MATH-01',
            'version' => 1,
            'status' => 'Approved',
        ]);

        $boqItem = $boq->items()->create([
            'scope_item_id' => $sItem->id,
            'item_code' => 'MATH.1',
            'description' => 'بند الحساب الدقيق',
            'unit' => 'm2',
            'quantity' => 10.00,
            'sort_order' => 1,
        ]);

        $estimation = Estimation::create([
            'opportunity_id' => $opportunity->id,
            'boq_id' => $boq->id,
            'estimation_number' => 'EST-MATH-01',
            'version' => 1,
            'status' => 'Approved',
            'direct_cost' => 1000.00,
            'total_cost' => 1000.00,
        ]);

        $estItem = $estimation->items()->create([
            'boq_item_id' => $boqItem->id,
            'item_code' => 'MATH.1',
            'description' => 'بند الحساب الدقيق',
            'unit' => 'm2',
            'quantity' => 10.00,
            'sort_order' => 1,
            'material_cost' => 100.00,
            'unit_cost' => 100.00,
            'total_cost' => 1000.00,
        ]);

        // Create Quotation with:
        // markup: 20%, discount_type: 'percentage', discount_value: 5%, tax_rate: 15%
        $payload = [
            'opportunity_id' => $opportunity->id,
            'estimation_id' => $estimation->id,
            'markup_rate' => 20.00,
            'discount_type' => 'percentage',
            'discount_value' => 5.00,
            'tax_rate' => 15.00,
        ];

        $res = $this->postJson('/api/quotations', $payload, $this->headersA());
        $res->assertStatus(200);

        $data = $res->json('data');

        // 1. Line selling price
        $this->assertEquals(120.00, (float) $data['items'][0]['unit_selling_price']);
        $this->assertEquals(1200.00, (float) $data['items'][0]['line_total']);

        // 2. Subtotal
        $this->assertEquals(1200.00, (float) $data['subtotal']);

        // 3. Discount Amount (5% of 1200 = 60.00)
        $this->assertEquals(60.00, (float) $data['discount_amount']);

        // 4. Taxable Amount (1200 - 60 = 1140.00)
        $this->assertEquals(1140.00, (float) $data['taxable_amount']);

        // 5. Tax Amount (15% of 1140 = 171.00)
        $this->assertEquals(171.00, (float) $data['tax_amount']);

        // 6. Grand Total (1140 + 171 = 1311.00)
        $this->assertEquals(1311.00, (float) $data['grand_total']);
    }

    /**
     * Scenario 9: Tenant Isolation — Tenant B cannot access Tenant A quotations or data.
     */
    public function test_scenario_9_tenant_isolation_prevents_unauthorized_access(): void
    {
        $chain = $this->makeApprovedEstimation('S9');

        $createRes = $this->postJson('/api/quotations', [
            'opportunity_id' => $chain['opportunity']->id,
            'estimation_id' => $chain['estimation']->id,
        ], $this->headersA());
        $quotationId = $createRes->json('data.id');

        // Tenant B attempts to read Tenant A's quotation -> 404
        $viewRes = $this->getJson("/api/quotations/{$quotationId}", $this->headersB());
        $viewRes->assertStatus(404);

        // Tenant B attempts to update Tenant A's quotation -> 404
        $editRes = $this->putJson("/api/quotations/{$quotationId}", [
            'notes' => 'Injected by Tenant B',
        ], $this->headersB());
        $editRes->assertStatus(404);

        // Tenant B attempts to delete Tenant A's quotation -> 404
        $deleteRes = $this->deleteJson("/api/quotations/{$quotationId}", [], $this->headersB());
        $deleteRes->assertStatus(404);
    }

    /**
     * Scenario 10: Historical Freezing — Estimation v1 has a quotation. Then Estimation v2
     * is created and approved. Old Quotation remains strictly tied to v1.
     */
    public function test_scenario_10_historical_freezing_across_estimation_revisions(): void
    {
        $chain = $this->makeApprovedEstimation('S10', 100.00, 200.00);

        // Create Quotation on Estimation v1
        $qtRes = $this->postJson('/api/quotations', [
            'opportunity_id' => $chain['opportunity']->id,
            'estimation_id' => $chain['estimation']->id,
            'markup_rate' => 20.00,
        ], $this->headersA());
        $quotationId = $qtRes->json('data.id');

        // Now branch new Estimation revision (v2)
        $estRevRes = $this->postJson("/api/estimations/{$chain['estimation']->id}/create-revision", [], $this->headersA());
        $estV2Id = $estRevRes->json('data.id');

        // Update items in estimation v2 with higher costs
        $this->putJson("/api/estimations/{$estV2Id}", [
            'items' => [
                [
                    'boq_item_id' => $chain['boqItem1']->id,
                    'costs' => [['cost_type' => 'material', 'cost_rate' => 500.00]],
                ],
            ],
        ], $this->headersA());

        // Approve estimation v2
        $this->postJson("/api/estimations/{$estV2Id}/approve", [], $this->headersA());

        // Verify that the existing Quotation is still strictly tied to Estimation v1
        TenantDatabaseManager::switchToTenant($this->tenantA);
        $quotation = Quotation::with('items')->find($quotationId);
        $this->assertEquals($chain['estimation']->id, $quotation->estimation_id);
        $this->assertNotEquals($estV2Id, $quotation->estimation_id);

        // Subtotal must remain based on v1 costs (2400.00), untouched by v2
        $this->assertEquals(2400.00, (float) $quotation->subtotal);
    }

    /**
     * Test 11: Quick creation from estimation endpoint `POST /quotations/from-estimation/{estimationId}`.
     */
    public function test_quick_creation_from_estimation_endpoint(): void
    {
        $chain = $this->makeApprovedEstimation('S11');

        $res = $this->postJson("/api/quotations/from-estimation/{$chain['estimation']->id}", [
            'markup_rate' => 15.00,
            'discount_type' => 'fixed',
            'discount_value' => 100.00,
            'notes' => 'Quick quotation creation',
        ], $this->headersA());

        $res->assertStatus(200);
        $res->assertJsonPath('success', true);
        $res->assertJsonPath('data.estimation_id', $chain['estimation']->id);
        $res->assertJsonPath('data.opportunity_id', $chain['opportunity']->id);
        $this->assertEquals(15.00, (float) $res->json('data.markup_rate'));
        $this->assertEquals(100.00, (float) $res->json('data.discount_amount'));
    }

    /**
     * Test 12: Opportunity traceability chain includes quotations and accurate next_action.
     */
    public function test_opportunity_chain_traceability_includes_quotations(): void
    {
        $chain = $this->makeApprovedEstimation('S12');

        // Check Opportunity chain before Quotation -> next_action should recommend creating quotation
        $chainRes1 = $this->getJson("/api/opportunities/{$chain['opportunity']->id}/chain", $this->headersA());
        $chainRes1->assertStatus(200);
        $this->assertEquals('quotation', $chainRes1->json('data.next_action.stage'));
        $this->assertCount(0, $chainRes1->json('data.quotations'));

        // Create Quotation
        $qtRes = $this->postJson('/api/quotations', [
            'opportunity_id' => $chain['opportunity']->id,
            'estimation_id' => $chain['estimation']->id,
        ], $this->headersA());
        $quotationId = $qtRes->json('data.id');

        // Check Opportunity chain after Draft Quotation -> next_action should recommend submitting/reviewing quotation
        $chainRes2 = $this->getJson("/api/opportunities/{$chain['opportunity']->id}/chain", $this->headersA());
        $chainRes2->assertStatus(200);
        $this->assertCount(1, $chainRes2->json('data.quotations'));
        $this->assertEquals('quotation', $chainRes2->json('data.next_action.stage'));
        $this->assertEquals('submit_quotation_for_review', $chainRes2->json('data.next_action.key'));

        // Approve quotation
        $this->postJson("/api/quotations/{$quotationId}/approve", [], $this->headersA());

        // Check Opportunity chain after Approved Quotation -> next_action should recommend sending quotation
        $chainRes3 = $this->getJson("/api/opportunities/{$chain['opportunity']->id}/chain", $this->headersA());
        $chainRes3->assertStatus(200);
        $this->assertEquals('quotation', $chainRes3->json('data.next_action.stage'));
        $this->assertEquals('send_quotation', $chainRes3->json('data.next_action.key'));

        // Mark sent
        $this->postJson("/api/quotations/{$quotationId}/mark-sent", [], $this->headersA());

        // Check Opportunity chain after Sent Quotation -> next_action should recommend recording client response
        $chainRes4 = $this->getJson("/api/opportunities/{$chain['opportunity']->id}/chain", $this->headersA());
        $chainRes4->assertStatus(200);
        $this->assertEquals('quotation', $chainRes4->json('data.next_action.stage'));
        $this->assertEquals('record_client_response', $chainRes4->json('data.next_action.key'));

        // Record accepted
        $this->postJson("/api/quotations/{$quotationId}/record-response", ['client_status' => 'Accepted'], $this->headersA());

        // Check Opportunity chain after Accepted Quotation -> next_action should be contract
        $chainRes5 = $this->getJson("/api/opportunities/{$chain['opportunity']->id}/chain", $this->headersA());
        $chainRes5->assertStatus(200);
        $this->assertEquals('contract', $chainRes5->json('data.next_action.stage'));
        $this->assertEquals('ready_for_contract', $chainRes5->json('data.next_action.key'));
    }
}
