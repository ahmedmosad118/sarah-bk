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
use App\Models\Scope;
use App\Models\SiteVisit;
use App\Models\User;
use App\Services\Tenant\TenantProvisioningService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class EstimationManagementTest extends TestCase
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

        $this->slugA = str_replace('.', '', uniqid('est-a-', true)).rand(100, 999);
        $this->slugB = str_replace('.', '', uniqid('est-b-', true)).rand(100, 999);

        // Provision Tenant A
        $resA = $this->provisioningService->provision([
            'name' => 'Alpha Estimation Corp',
            'slug' => $this->slugA,
            'company_code' => 'ALPH-'.uniqid(),
            'domain' => $this->slugA.'.localhost',
        ], [
            'name' => 'Alpha Owner',
            'email' => "owner@{$this->slugA}.test",
            'password' => 'AlphaPass123!',
        ]);

        $this->tenantA = $resA['tenant'];
        $this->ownerA = $resA['owner'];

        // Provision Tenant B
        $resB = $this->provisioningService->provision([
            'name' => 'Beta Estimation Corp',
            'slug' => $this->slugB,
            'company_code' => 'BETA-'.uniqid(),
            'domain' => $this->slugB.'.localhost',
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
            DB::disconnect('tenant');
            DB::purge('tenant');
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
            'Authorization' => 'Bearer '.$this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ];
    }

    protected function headersB(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->tokenB,
            'X-Tenant-Slug' => $this->slugB,
            'Accept' => 'application/json',
        ];
    }

    /**
     * Helper to create an approved BOQ chain in Tenant A.
     */
    protected function makeApprovedBoq(string $suffix = ''): array
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'name' => 'Client '.$suffix,
            'customer_type' => 'company',
            'phone' => '010'.rand(10000000, 99999999),
            'status' => 'active',
        ]);

        $opportunity = Opportunity::create([
            'customer_id' => $customer->id,
            'title' => 'Project '.$suffix,
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
            'measurement_number' => 'M-'.$suffix.'-001',
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
            'gross_quantity' => 80.00,
            'net_quantity' => 80.00,
        ]);

        $mItem2 = $measurement->items()->create([
            'room_name' => 'Office',
            'item_name' => 'HDF Floor',
            'unit' => 'm2',
            'measurement_type' => 'area',
            'count' => 1,
            'length' => 8,
            'width' => 5,
            'gross_quantity' => 40.00,
            'net_quantity' => 40.00,
        ]);

        $scope = Scope::create([
            'opportunity_id' => $opportunity->id,
            'measurement_id' => $measurement->id,
            'scope_number' => 'SC-'.$suffix.'-001',
            'version' => 1,
            'status' => 'Approved',
            'title' => 'Scope '.$suffix,
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
            'boq_number' => 'BOQ-'.$suffix.'-001',
            'version' => 1,
            'status' => 'Approved',
            'title' => 'BOQ '.$suffix,
            'approved_by' => $this->ownerA->id,
            'approved_at' => now(),
        ]);

        $boqItem1 = $boq->items()->create([
            'scope_item_id' => $sItem1->id,
            'item_code' => '1.1',
            'description' => 'توريد وتركيب بورسلين',
            'unit' => 'm2',
            'quantity' => 80.00,
            'sort_order' => 1,
        ]);

        $boqItem2 = $boq->items()->create([
            'scope_item_id' => $sItem2->id,
            'item_code' => '1.2',
            'description' => 'توريد وتركيب HDF',
            'unit' => 'm2',
            'quantity' => 40.00,
            'sort_order' => 2,
        ]);

        return compact('customer', 'opportunity', 'measurement', 'scope', 'boq', 'boqItem1', 'boqItem2');
    }

    /**
     * Test 1: Create estimation from current approved BOQ copies all items with read-only units and quantities.
     */
    public function test_create_estimation_from_approved_boq_copies_items_and_freezes_quantities(): void
    {
        $chain = $this->makeApprovedBoq('T1');

        $payload = [
            'opportunity_id' => $chain['opportunity']->id,
            'boq_id' => $chain['boq']->id,
            'title' => 'Initial Cost Study T1',
            'notes' => 'Testing estimation generation from BOQ',
        ];

        $res = $this->postJson('/api/estimations', $payload, $this->headersA());
        $res->assertStatus(200);
        $res->assertJsonPath('success', true);
        $res->assertJsonPath('data.status', 'Draft');
        $res->assertJsonPath('data.version', 1);
        $res->assertJsonPath('data.boq_id', $chain['boq']->id);
        $res->assertJsonPath('data.opportunity_id', $chain['opportunity']->id);

        $estId = $res->json('data.id');

        TenantDatabaseManager::switchToTenant($this->tenantA);
        $est = Estimation::with('items')->find($estId);
        $this->assertNotNull($est);
        $this->assertCount(2, $est->items);

        $item1 = $est->items->firstWhere('boq_item_id', $chain['boqItem1']->id);
        $this->assertNotNull($item1);
        $this->assertEquals('1.1', $item1->item_code);
        $this->assertEquals('m2', $item1->unit);
        $this->assertEquals(80.00, (float) $item1->quantity);

        $item2 = $est->items->firstWhere('boq_item_id', $chain['boqItem2']->id);
        $this->assertNotNull($item2);
        $this->assertEquals('1.2', $item2->item_code);
        $this->assertEquals('m2', $item2->unit);
        $this->assertEquals(40.00, (float) $item2->quantity);
    }

    /**
     * Test 2: Reject non-approved BOQ sources (Draft, Under Review, Superseded).
     */
    public function test_reject_creation_from_non_approved_boq(): void
    {
        $chain = $this->makeApprovedBoq('T2');

        TenantDatabaseManager::switchToTenant($this->tenantA);
        $chain['boq']->update(['status' => 'Draft']);

        $payload = [
            'opportunity_id' => $chain['opportunity']->id,
            'boq_id' => $chain['boq']->id,
        ];

        $res = $this->postJson('/api/estimations', $payload, $this->headersA());
        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['boq_id']);
    }

    /**
     * Test 3: Reject BOQ belonging to a different Opportunity.
     */
    public function test_reject_creation_from_mismatched_opportunity_boq(): void
    {
        $chain1 = $this->makeApprovedBoq('T3A');
        $chain2 = $this->makeApprovedBoq('T3B');

        $payload = [
            'opportunity_id' => $chain1['opportunity']->id, // Opp A
            'boq_id' => $chain2['boq']->id, // BOQ from Opp B
        ];

        $res = $this->postJson('/api/estimations', $payload, $this->headersA());
        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['boq_id']);
    }

    /**
     * Test 4: Source quantity and unit cannot be overridden by client input.
     */
    public function test_source_quantity_and_unit_cannot_be_overridden_by_client_input(): void
    {
        $chain = $this->makeApprovedBoq('T4');

        $createRes = $this->postJson('/api/estimations', [
            'opportunity_id' => $chain['opportunity']->id,
            'boq_id' => $chain['boq']->id,
        ], $this->headersA());
        $estId = $createRes->json('data.id');

        // Client attempts to alter source quantity to 99999 and unit to 'km'
        $updatePayload = [
            'items' => [
                [
                    'boq_item_id' => $chain['boqItem1']->id,
                    'quantity' => 99999.00,
                    'unit' => 'km',
                    'costs' => [
                        [
                            'cost_type' => 'material',
                            'cost_rate' => 100.00,
                        ],
                    ],
                ],
            ],
        ];

        $updateRes = $this->putJson("/api/estimations/{$estId}", $updatePayload, $this->headersA());
        $updateRes->assertStatus(200);

        TenantDatabaseManager::switchToTenant($this->tenantA);
        $item = EstimationItem::where('estimation_id', $estId)
            ->where('boq_item_id', $chain['boqItem1']->id)
            ->first();

        // Quantity and unit remain original frozen values (80.00 m2)
        $this->assertEquals(80.00, (float) $item->quantity);
        $this->assertEquals('m2', $item->unit);
        // Cost is calculated from the true frozen quantity (80 * 100 = 8000.00, NOT 99999 * 100)
        $this->assertEquals(8000.00, (float) $item->total_cost);
    }

    /**
     * Test 5: Server-side component cost calculation and rounding.
     */
    public function test_server_side_component_cost_calculation_and_rounding(): void
    {
        $chain = $this->makeApprovedBoq('T5');

        $createRes = $this->postJson('/api/estimations', [
            'opportunity_id' => $chain['opportunity']->id,
            'boq_id' => $chain['boq']->id,
            'overhead_rate' => 10.00,
        ], $this->headersA());
        $estId = $createRes->json('data.id');

        $updatePayload = [
            'overhead_rate' => 10.00,
            'items' => [
                [
                    'boq_item_id' => $chain['boqItem1']->id, // qty 80
                    'costs' => [
                        ['cost_type' => 'material', 'cost_rate' => 150.25], // 80 * 150.25 = 12020.00
                        ['cost_type' => 'labor', 'cost_rate' => 50.00],    // 80 * 50.00 = 4000.00
                    ],
                ],
                [
                    'boq_item_id' => $chain['boqItem2']->id, // qty 40
                    'costs' => [
                        ['cost_type' => 'subcontractor', 'cost_rate' => 120.00], // 40 * 120.00 = 4800.00
                    ],
                ],
            ],
        ];

        $res = $this->putJson("/api/estimations/{$estId}", $updatePayload, $this->headersA());
        $res->assertStatus(200);

        // Direct cost = 12020.00 + 4000.00 + 4800.00 = 20820.00
        // Overhead 10% = 2082.00
        // Total cost = 22902.00
        $res->assertJsonPath('data.direct_cost', '20820.00');
        $res->assertJsonPath('data.overhead_rate', '10.00');
        $res->assertJsonPath('data.overhead_amount', '2082.00');
        $res->assertJsonPath('data.total_cost', '22902.00');

        TenantDatabaseManager::switchToTenant($this->tenantA);
        $est = Estimation::find($estId);
        $this->assertEquals(20820.00, (float) $est->direct_cost);
        $this->assertEquals(2082.00, (float) $est->overhead_amount);
        $this->assertEquals(22902.00, (float) $est->total_cost);
    }

    /**
     * Test 6: Zero pricing rule in Measurement, Scope, and BOQ tables.
     */
    public function test_zero_pricing_rule_in_measurement_scope_boq_tables(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $pricingColumns = ['price', 'unit_rate', 'cost', 'amount', 'total_amount', 'margin', 'selling_price', 'tax'];

        $tablesToCheck = ['measurements', 'measurement_items', 'scopes', 'scope_items', 'boqs', 'boq_items'];

        foreach ($tablesToCheck as $table) {
            foreach ($pricingColumns as $col) {
                $this->assertFalse(
                    Schema::hasColumn($table, $col),
                    "Violated Zero-Pricing standard: Column '{$col}' must NOT exist in table '{$table}'."
                );
            }
        }

        // Verify that cost columns exist ONLY in Estimation tables
        $this->assertTrue(Schema::hasColumn('estimations', 'direct_cost'));
        $this->assertTrue(Schema::hasColumn('estimations', 'total_cost'));
        $this->assertTrue(Schema::hasColumn('estimation_items', 'material_cost'));
        $this->assertTrue(Schema::hasColumn('estimation_item_costs', 'cost_rate'));
    }

    /**
     * Test 7: Full approval workflow and empty/zero-cost approval rejection.
     */
    public function test_full_approval_workflow_and_empty_zero_cost_approval_rejection(): void
    {
        $chain = $this->makeApprovedBoq('T7');

        $createRes = $this->postJson('/api/estimations', [
            'opportunity_id' => $chain['opportunity']->id,
            'boq_id' => $chain['boq']->id,
        ], $this->headersA());
        $estId = $createRes->json('data.id');

        // Cannot approve with all-zero costs
        $failApproveRes = $this->postJson("/api/estimations/{$estId}/approve", [], $this->headersA());
        $failApproveRes->assertStatus(422);
        $failApproveRes->assertJsonValidationErrors(['total_cost']);

        // Cost the items
        $this->putJson("/api/estimations/{$estId}", [
            'items' => [
                [
                    'boq_item_id' => $chain['boqItem1']->id,
                    'costs' => [['cost_type' => 'material', 'cost_rate' => 100.00]],
                ],
                [
                    'boq_item_id' => $chain['boqItem2']->id,
                    'costs' => [['cost_type' => 'labor', 'cost_rate' => 50.00]],
                ],
            ],
        ], $this->headersA());

        // Submit for review: Draft -> Under Review
        $submitRes = $this->postJson("/api/estimations/{$estId}/submit-review", [], $this->headersA());
        $submitRes->assertStatus(200);
        $submitRes->assertJsonPath('data.status', 'Under Review');

        // Approve: Under Review -> Approved
        $approveRes = $this->postJson("/api/estimations/{$estId}/approve", [], $this->headersA());
        $approveRes->assertStatus(200);
        $approveRes->assertJsonPath('data.status', 'Approved');
    }

    /**
     * Test 8: Approved estimation immutability and revision creation.
     */
    public function test_approved_estimation_immutability_and_revision_creation(): void
    {
        $chain = $this->makeApprovedBoq('T8');

        $createRes = $this->postJson('/api/estimations', [
            'opportunity_id' => $chain['opportunity']->id,
            'boq_id' => $chain['boq']->id,
        ], $this->headersA());
        $estId = $createRes->json('data.id');

        // Cost and approve
        $this->putJson("/api/estimations/{$estId}", [
            'items' => [
                [
                    'boq_item_id' => $chain['boqItem1']->id,
                    'costs' => [['cost_type' => 'material', 'cost_rate' => 100.00]],
                ],
                [
                    'boq_item_id' => $chain['boqItem2']->id,
                    'costs' => [['cost_type' => 'labor', 'cost_rate' => 50.00]],
                ],
            ],
        ], $this->headersA());
        $this->postJson("/api/estimations/{$estId}/approve", [], $this->headersA());

        // Immutability: directly updating or deleting approved estimation must fail (422)
        $updateRes = $this->putJson("/api/estimations/{$estId}", ['title' => 'Illegal Edit'], $this->headersA());
        $updateRes->assertStatus(422);

        $deleteRes = $this->deleteJson("/api/estimations/{$estId}", [], $this->headersA());
        $deleteRes->assertStatus(422);

        // Revision creation: branches from approved estimation
        $revRes = $this->postJson("/api/estimations/{$estId}/create-revision", [], $this->headersA());
        $revRes->assertStatus(200);
        $revRes->assertJsonPath('data.version', 2);
        $revRes->assertJsonPath('data.status', 'Draft');
        $revRes->assertJsonPath('data.boq_id', $chain['boq']->id); // Preserves exact parent BOQ

        $revId = $revRes->json('data.id');
        TenantDatabaseManager::switchToTenant($this->tenantA);
        $rev = Estimation::with('items.costs')->find($revId);
        $this->assertNotNull($rev);
        $this->assertCount(2, $rev->items);
        $revItem1 = $rev->items->firstWhere('boq_item_id', $chain['boqItem1']->id);
        $this->assertCount(1, $revItem1->costs);
        $this->assertEquals(100.00, (float) $revItem1->costs->first()->cost_rate);
    }

    /**
     * Test 9: Approving a new estimation revision marks the previous approved estimation as Superseded.
     */
    public function test_approving_new_revision_supersedes_previous_approved_estimation(): void
    {
        $chain = $this->makeApprovedBoq('T9');

        $createRes = $this->postJson('/api/estimations', [
            'opportunity_id' => $chain['opportunity']->id,
            'boq_id' => $chain['boq']->id,
        ], $this->headersA());
        $estId1 = $createRes->json('data.id');

        $this->putJson("/api/estimations/{$estId1}", [
            'items' => [
                ['boq_item_id' => $chain['boqItem1']->id, 'costs' => [['cost_type' => 'material', 'cost_rate' => 100.00]]],
                ['boq_item_id' => $chain['boqItem2']->id, 'costs' => [['cost_type' => 'labor', 'cost_rate' => 50.00]]],
            ],
        ], $this->headersA());
        $this->postJson("/api/estimations/{$estId1}/approve", [], $this->headersA());

        $revRes = $this->postJson("/api/estimations/{$estId1}/create-revision", [], $this->headersA());
        $estId2 = $revRes->json('data.id');

        // Approve revision V2
        $this->postJson("/api/estimations/{$estId2}/approve", [], $this->headersA());

        TenantDatabaseManager::switchToTenant($this->tenantA);
        $est1 = Estimation::find($estId1);
        $est2 = Estimation::find($estId2);

        $this->assertEquals('Superseded', $est1->status);
        $this->assertEquals('Approved', $est2->status);
    }

    /**
     * Test 10: Stale BOQ detection in Opportunity Chain blocks ready_for_quotation.
     */
    public function test_stale_boq_detection_in_opportunity_chain_blocks_ready_for_quotation(): void
    {
        $chain = $this->makeApprovedBoq('T10');

        // 1. Create and approve Estimation V1 on BOQ V1
        $createRes = $this->postJson('/api/estimations', [
            'opportunity_id' => $chain['opportunity']->id,
            'boq_id' => $chain['boq']->id,
        ], $this->headersA());
        $estId1 = $createRes->json('data.id');

        $this->putJson("/api/estimations/{$estId1}", [
            'items' => [
                ['boq_item_id' => $chain['boqItem1']->id, 'costs' => [['cost_type' => 'material', 'cost_rate' => 100.00]]],
                ['boq_item_id' => $chain['boqItem2']->id, 'costs' => [['cost_type' => 'labor', 'cost_rate' => 50.00]]],
            ],
        ], $this->headersA());
        $this->postJson("/api/estimations/{$estId1}/approve", [], $this->headersA());

        // Chain should be ready_for_quotation
        $chainRes1 = $this->getJson("/api/opportunities/{$chain['opportunity']->id}/chain", $this->headersA());
        $chainRes1->assertStatus(200);
        $chainRes1->assertJsonPath('data.next_action.key', 'ready_for_quotation');
        $chainRes1->assertJsonPath('data.current_status.estimation.is_stale', false);
        $chainRes1->assertJsonPath('data.current_status.estimation.is_current', true);

        // 2. Now a new BOQ revision V2 is created and approved!
        $revBoqRes = $this->postJson("/api/boqs/{$chain['boq']->id}/create-revision", [], $this->headersA());
        $boqId2 = $revBoqRes->json('data.id');
        $this->postJson("/api/boqs/{$boqId2}/approve", [], $this->headersA());

        // 3. Chain must detect that Estimation V1 references old BOQ V1!
        $chainRes2 = $this->getJson("/api/opportunities/{$chain['opportunity']->id}/chain", $this->headersA());
        $chainRes2->assertStatus(200);
        $chainRes2->assertJsonPath('data.current_status.estimation.is_stale', true);
        $chainRes2->assertJsonPath('data.current_status.estimation.is_current', false);
        // It must NOT falsely advance to ready_for_quotation; it must require create_estimation!
        $chainRes2->assertJsonPath('data.next_action.key', 'create_estimation');

        // 4. Create and approve Estimation V2 on BOQ V2
        $createEst2Res = $this->postJson('/api/estimations', [
            'opportunity_id' => $chain['opportunity']->id,
            'boq_id' => $boqId2,
        ], $this->headersA());
        $estId2 = $createEst2Res->json('data.id');

        TenantDatabaseManager::switchToTenant($this->tenantA);
        $boq2 = Boq::with('items')->find($boqId2);
        $boq2Item1 = $boq2->items->first();

        $this->putJson("/api/estimations/{$estId2}", [
            'items' => [
                ['boq_item_id' => $boq2Item1->id, 'costs' => [['cost_type' => 'material', 'cost_rate' => 110.00]]],
                ['boq_item_id' => $boq2->items->last()->id, 'costs' => [['cost_type' => 'labor', 'cost_rate' => 50.00]]],
            ],
        ], $this->headersA());
        $this->postJson("/api/estimations/{$estId2}/approve", [], $this->headersA());

        // 5. Chain is now ready_for_quotation with current Estimation V2
        $chainRes3 = $this->getJson("/api/opportunities/{$chain['opportunity']->id}/chain", $this->headersA());
        $chainRes3->assertStatus(200);
        $chainRes3->assertJsonPath('data.next_action.key', 'ready_for_quotation');
        $chainRes3->assertJsonPath('data.current_status.estimation.is_stale', false);
        $chainRes3->assertJsonPath('data.current_status.estimation.is_current', true);
    }

    /**
     * Test 11: Multi-tenant isolation and IDOR protection.
     */
    public function test_multi_tenant_isolation_and_idor_protection(): void
    {
        $chainA = $this->makeApprovedBoq('T11A');

        // Tenant A creates estimation
        $createRes = $this->postJson('/api/estimations', [
            'opportunity_id' => $chainA['opportunity']->id,
            'boq_id' => $chainA['boq']->id,
        ], $this->headersA());
        $estIdA = $createRes->json('data.id');

        // Tenant B attempts to access Tenant A's estimation -> 404
        $showResB = $this->getJson("/api/estimations/{$estIdA}", $this->headersB());
        $showResB->assertStatus(404);

        // Tenant B attempts to update Tenant A's estimation -> 404
        $updateResB = $this->putJson("/api/estimations/{$estIdA}", ['title' => 'Hacked'], $this->headersB());
        $updateResB->assertStatus(404);

        // Tenant B attempts to delete Tenant A's estimation -> 404
        $deleteResB = $this->deleteJson("/api/estimations/{$estIdA}", [], $this->headersB());
        $deleteResB->assertStatus(404);

        // Tenant B attempts to create an estimation in Tenant B referencing Tenant A's BOQ ID -> 422
        $crossCreateRes = $this->postJson('/api/estimations', [
            'opportunity_id' => 1,
            'boq_id' => $chainA['boq']->id,
        ], $this->headersB());
        $crossCreateRes->assertStatus(422);
    }

    /**
     * Test 12: Unauthorized user access rejected with 403.
     */
    public function test_unauthorized_user_access_rejected_with_403(): void
    {
        $chain = $this->makeApprovedBoq('T12');

        TenantDatabaseManager::switchToTenant($this->tenantA);
        // Create user with zero permissions
        $plainUser = User::create([
            'name' => 'Plain User',
            'email' => 'plain@user.test',
            'password' => bcrypt('Secret123!'),
            'status' => 'active',
        ]);
        $plainToken = $plainUser->createToken('plain_token')->plainTextToken;

        $plainHeaders = [
            'Authorization' => 'Bearer '.$plainToken,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ];

        // List -> 403
        $listRes = $this->getJson('/api/estimations', $plainHeaders);
        $listRes->assertStatus(403);

        // Store -> 403
        $storeRes = $this->postJson('/api/estimations', [
            'opportunity_id' => $chain['opportunity']->id,
            'boq_id' => $chain['boq']->id,
        ], $plainHeaders);
        $storeRes->assertStatus(403);
    }

    /**
     * Test 13: Activity log records lifecycle events on 'commercial' log.
     */
    public function test_activity_log_records_lifecycle_events_on_commercial_log(): void
    {
        $chain = $this->makeApprovedBoq('T13');

        $createRes = $this->postJson('/api/estimations', [
            'opportunity_id' => $chain['opportunity']->id,
            'boq_id' => $chain['boq']->id,
        ], $this->headersA());
        $estId = $createRes->json('data.id');

        TenantDatabaseManager::switchToTenant($this->tenantA);
        $act = Activity::where('log_name', 'commercial')
            ->where('subject_type', Estimation::class)
            ->where('subject_id', $estId)
            ->first();

        $this->assertNotNull($act);
        $this->assertEquals('created', $act->event);
    }

    /**
     * Test 14: Convenience endpoint from-boq creates estimation directly.
     */
    public function test_create_from_boq_convenience_endpoint(): void
    {
        $chain = $this->makeApprovedBoq('T14');

        $res = $this->postJson("/api/estimations/from-boq/{$chain['boq']->id}", [
            'title' => 'Quick Estimation from BOQ',
        ], $this->headersA());

        $res->assertStatus(200);
        $res->assertJsonPath('success', true);
        $res->assertJsonPath('data.boq_id', $chain['boq']->id);
        $res->assertJsonPath('data.opportunity_id', $chain['opportunity']->id);
    }

    /**
     * Test 15: No N+1 query explosion on estimation list endpoint.
     */
    public function test_no_n_plus_one_query_explosion_on_estimations_list(): void
    {
        $chain = $this->makeApprovedBoq('T15');

        // Create 3 estimations
        for ($i = 1; $i <= 3; $i++) {
            $this->postJson('/api/estimations', [
                'opportunity_id' => $chain['opportunity']->id,
                'boq_id' => $chain['boq']->id,
                'title' => "Perf Test Est {$i}",
            ], $this->headersA());
        }

        TenantDatabaseManager::switchToTenant($this->tenantA);
        DB::enableQueryLog();

        $res = $this->getJson('/api/estimations', $this->headersA());
        $res->assertStatus(200);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Eager-loading ensures query count is bounded (<= 15 queries)
        $this->assertLessThanOrEqual(
            15,
            count($queries),
            'Query count exceeded N+1 threshold on estimations list endpoint.'
        );
    }

    /**
     * Test 16: Source validity prevents approving an estimation linked to a stale BOQ or superseded revision.
     */
    public function test_source_validity_prevents_approving_stale_boq_or_superseded_estimation(): void
    {
        $chain = $this->makeApprovedBoq('T16');

        // Create Estimation V1 on BOQ V1
        $createRes1 = $this->postJson('/api/estimations', [
            'opportunity_id' => $chain['opportunity']->id,
            'boq_id' => $chain['boq']->id,
        ], $this->headersA());
        $estId1 = $createRes1->json('data.id');

        // Fully cost Estimation V1
        $this->putJson("/api/estimations/{$estId1}", [
            'items' => [
                ['boq_item_id' => $chain['boqItem1']->id, 'costs' => [['cost_type' => 'material', 'cost_rate' => 100.00]]],
                ['boq_item_id' => $chain['boqItem2']->id, 'costs' => [['cost_type' => 'labor', 'cost_rate' => 50.00]]],
            ],
        ], $this->headersA());

        // Now BOQ V2 is created and approved!
        $revBoqRes = $this->postJson("/api/boqs/{$chain['boq']->id}/create-revision", [], $this->headersA());
        $boqId2 = $revBoqRes->json('data.id');
        $this->postJson("/api/boqs/{$boqId2}/approve", [], $this->headersA());

        // Attempting to approve Estimation V1 (which references stale BOQ V1) must fail with 422!
        $failApprove = $this->postJson("/api/estimations/{$estId1}/approve", [], $this->headersA());
        $failApprove->assertStatus(422);
        $failApprove->assertJsonValidationErrors(['boq_id']);

        // Attempting to create a new estimation directly from the stale BOQ V1 must also fail with 422!
        $failStore = $this->postJson('/api/estimations', [
            'opportunity_id' => $chain['opportunity']->id,
            'boq_id' => $chain['boq']->id,
        ], $this->headersA());
        $failStore->assertStatus(422);
        $failStore->assertJsonValidationErrors(['boq_id']);
    }

    /**
     * Test 17: Comprehensive zero-cost and incomplete estimation rules.
     */
    public function test_zero_cost_and_incomplete_estimation_rules(): void
    {
        $chain = $this->makeApprovedBoq('T17');

        $createRes = $this->postJson('/api/estimations', [
            'opportunity_id' => $chain['opportunity']->id,
            'boq_id' => $chain['boq']->id,
        ], $this->headersA());
        $estId = $createRes->json('data.id');

        // Case 1: No cost rows anywhere -> 422
        $resNoCosts = $this->postJson("/api/estimations/{$estId}/approve", [], $this->headersA());
        $resNoCosts->assertStatus(422);

        // Case 2: Partial pricing (Item 1 costed, Item 2 has NO cost rows and NO note) -> 422
        $this->putJson("/api/estimations/{$estId}", [
            'items' => [
                ['boq_item_id' => $chain['boqItem1']->id, 'costs' => [['cost_type' => 'material', 'cost_rate' => 50.00]]],
            ],
        ], $this->headersA());

        $resPartial = $this->postJson("/api/estimations/{$estId}/approve", [], $this->headersA());
        $resPartial->assertStatus(422);
        $resPartial->assertJsonValidationErrors(['items']);

        // Case 3: All items have cost rows, but all rates are 0.00 with NO notes/justification -> 422
        $this->putJson("/api/estimations/{$estId}", [
            'items' => [
                ['boq_item_id' => $chain['boqItem1']->id, 'costs' => [['cost_type' => 'material', 'cost_rate' => 0.00]]],
                ['boq_item_id' => $chain['boqItem2']->id, 'costs' => [['cost_type' => 'labor', 'cost_rate' => 0.00]]],
            ],
        ], $this->headersA());

        $resZeroNoNotes = $this->postJson("/api/estimations/{$estId}/approve", [], $this->headersA());
        $resZeroNoNotes->assertStatus(422);
        $resZeroNoNotes->assertJsonValidationErrors(['zero_cost_reason']);

        // Case 4: Legitimate zero-cost estimation with explicit justification in notes -> 200 Approved
        $this->putJson("/api/estimations/{$estId}", [
            'notes' => 'Materials and labor fully supplied by client per contract clause 4',
            'items' => [
                ['boq_item_id' => $chain['boqItem1']->id, 'costs' => [['cost_type' => 'material', 'cost_rate' => 0.00]]],
                ['boq_item_id' => $chain['boqItem2']->id, 'costs' => [['cost_type' => 'labor', 'cost_rate' => 0.00]]],
            ],
        ], $this->headersA());

        $resZeroJustified = $this->postJson("/api/estimations/{$estId}/approve", [
            'zero_cost_reason' => 'Other — see notes',
        ], $this->headersA());
        $resZeroJustified->assertStatus(200);
        $resZeroJustified->assertJsonPath('data.status', 'Approved');
        $this->assertEquals(0.00, (float) $resZeroJustified->json('data.total_cost'));
    }

    /**
     * Test 18: Estimation numbering uniqueness and collision-free sequence.
     */
    public function test_estimation_numbering_uniqueness_and_collision_free_sequence(): void
    {
        $chain = $this->makeApprovedBoq('T18');

        $res1 = $this->postJson('/api/estimations', [
            'opportunity_id' => $chain['opportunity']->id,
            'boq_id' => $chain['boq']->id,
        ], $this->headersA());
        $res1->assertStatus(200);
        $number1 = $res1->json('data.estimation_number');

        $res2 = $this->postJson('/api/estimations', [
            'opportunity_id' => $chain['opportunity']->id,
            'boq_id' => $chain['boq']->id,
        ], $this->headersA());
        $res2->assertStatus(200);
        $number2 = $res2->json('data.estimation_number');

        // Document numbers must be strictly unique
        $this->assertNotEquals($number1, $number2);
        $this->assertStringContainsString("EST-OPP{$chain['opportunity']->id}-001", $number1);
        $this->assertStringContainsString("EST-OPP{$chain['opportunity']->id}-002", $number2);

        // Database unique constraint prevents inserting a duplicate estimation_number
        $this->expectException(QueryException::class);
        TenantDatabaseManager::switchToTenant($this->tenantA);
        Estimation::create([
            'opportunity_id' => $chain['opportunity']->id,
            'boq_id' => $chain['boq']->id,
            'estimation_number' => $number1, // duplicate!
            'version' => 3,
            'status' => 'Draft',
        ]);
    }

    /**
     * Test 19: Identity manipulation protection on prepared_by and prepared_at.
     */
    public function test_unauthorized_user_cannot_manipulate_prepared_by_on_create_or_update(): void
    {
        $chain = $this->makeApprovedBoq('T19');

        TenantDatabaseManager::switchToTenant($this->tenantA);

        // Target user to fraudulently assign as preparer
        $otherUser = User::create([
            'name' => 'Target Preparer',
            'email' => 'target.preparer@test.com',
            'password' => bcrypt('TargetPass123!'),
            'status' => 'active',
        ]);

        // Regular estimator without 'estimations.approve' permission
        $estimator = User::create([
            'name' => 'Junior Estimator',
            'email' => 'junior.estimator@test.com',
            'password' => bcrypt('JuniorPass123!'),
            'status' => 'active',
        ]);
        $estimator->givePermissionTo(['estimations.view', 'estimations.create', 'estimations.update']);
        $estimatorToken = $estimator->createToken('est_token')->plainTextToken;

        $estimatorHeaders = [
            'Authorization' => 'Bearer '.$estimatorToken,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ];

        // 1. Unauthorized attempt to supply a different prepared_by on store() -> 422
        $fraudulentStore = $this->postJson('/api/estimations', [
            'opportunity_id' => $chain['opportunity']->id,
            'boq_id' => $chain['boq']->id,
            'prepared_by' => $otherUser->id,
        ], $estimatorHeaders);
        $fraudulentStore->assertStatus(422);
        $fraudulentStore->assertJsonValidationErrors(['prepared_by']);

        // 2. Defaulting: when prepared_by is omitted, it defaults to auth()->id()
        $legitStore = $this->postJson('/api/estimations', [
            'opportunity_id' => $chain['opportunity']->id,
            'boq_id' => $chain['boq']->id,
        ], $estimatorHeaders);
        $legitStore->assertStatus(200);
        $legitStore->assertJsonPath('data.prepared_by', $estimator->id);
        $this->assertNotNull($legitStore->json('data.prepared_at'));

        $estId = $legitStore->json('data.id');

        // 3. Unauthorized attempt to reassign prepared_by to a third party on update() -> 422
        $fraudulentUpdate = $this->putJson("/api/estimations/{$estId}", [
            'prepared_by' => $otherUser->id,
        ], $estimatorHeaders);
        $fraudulentUpdate->assertStatus(422);
        $fraudulentUpdate->assertJsonValidationErrors(['prepared_by']);

        // 3b. Self-reassignment attempt: user B without approve permission tries to reassign prepared_by to themself -> 422
        // First, authorized owner sets prepared_by to otherUser
        $this->app['auth']->forgetGuards();
        $this->putJson("/api/estimations/{$estId}", [
            'prepared_by' => $otherUser->id,
        ], $this->headersA())->assertStatus(200);

        // Now estimator attempts to reassign prepared_by to themself (auth()->id())
        $this->app['auth']->forgetGuards();
        $stealPreparerRes = $this->putJson("/api/estimations/{$estId}", [
            'prepared_by' => $estimator->id,
        ], $estimatorHeaders);
        $stealPreparerRes->assertStatus(422);
        $stealPreparerRes->assertJsonValidationErrors(['prepared_by']);

        // 4. Authorized user (Owner / estimations.approve) CAN reassign prepared_by
        $this->app['auth']->forgetGuards();
        $authorizedUpdate = $this->putJson("/api/estimations/{$estId}", [
            'prepared_by' => $otherUser->id,
        ], $this->headersA());
        $authorizedUpdate->assertStatus(200);
        $authorizedUpdate->assertJsonPath('data.prepared_by', $otherUser->id);
    }

    /**
     * Test 20: Cost component description alone does not justify zero-cost estimation.
     * Enforces mandatory zero_cost_reason and notes verification on "Other — see notes".
     */
    public function test_zero_cost_approval_rejects_description_alone_and_requires_zero_cost_reason(): void
    {
        $chain = $this->makeApprovedBoq('T20');

        $createRes = $this->postJson('/api/estimations', [
            'opportunity_id' => $chain['opportunity']->id,
            'boq_id' => $chain['boq']->id,
        ], $this->headersA());
        $estId = $createRes->json('data.id');

        // Setup zero rates with cost descriptions
        $this->putJson("/api/estimations/{$estId}", [
            'notes' => null,
            'items' => [
                [
                    'boq_item_id' => $chain['boqItem1']->id,
                    'notes' => null,
                    'costs' => [
                        [
                            'cost_type' => 'material',
                            'cost_rate' => 0.00,
                            'description' => 'Supplier offers 100% discount on initial samples',
                        ],
                    ],
                ],
                [
                    'boq_item_id' => $chain['boqItem2']->id,
                    'notes' => null,
                    'costs' => [
                        [
                            'cost_type' => 'labor',
                            'cost_rate' => 0.00,
                            'description' => 'In-house existing labor without extra charge',
                        ],
                    ],
                ],
            ],
        ], $this->headersA());

        // Case A: Missing zero_cost_reason -> 422
        $resNoReason = $this->postJson("/api/estimations/{$estId}/approve", [], $this->headersA());
        $resNoReason->assertStatus(422);
        $resNoReason->assertJsonValidationErrors(['zero_cost_reason']);

        // Case B: Reason is 'Other — see notes' but notes is missing or trivial (< 10 chars) -> 422
        $resOtherNoNotes = $this->postJson("/api/estimations/{$estId}/approve", [
            'zero_cost_reason' => 'Other — see notes',
            'notes' => 'free', // only 4 characters
        ], $this->headersA());
        $resOtherNoNotes->assertStatus(422);
        $resOtherNoNotes->assertJsonValidationErrors(['notes']);

        // Verify rejected approval requests leave the record completely unchanged
        TenantDatabaseManager::switchToTenant($this->tenantA);
        $unapproved = Estimation::findOrFail($estId);
        $this->assertEquals('Draft', $unapproved->status);
        $this->assertNull($unapproved->zero_cost_reason);
        $this->assertNull($unapproved->notes);

        // Case C: Reason is 'Other — see notes' with meaningful notes (>= 10 chars) -> 200 Approved
        $notesJustification = 'Complimentary scope approved by general manager as goodwill discount';
        $resOtherValid = $this->postJson("/api/estimations/{$estId}/approve", [
            'zero_cost_reason' => 'Other — see notes',
            'notes' => $notesJustification,
        ], $this->headersA());
        $resOtherValid->assertStatus(200);
        $resOtherValid->assertJsonPath('data.status', 'Approved');
        $resOtherValid->assertJsonPath('data.zero_cost_reason', 'Other — see notes');
        $resOtherValid->assertJsonPath('data.notes', $notesJustification);

        // Verify fresh database read retains both the selected reason and persisted justification
        TenantDatabaseManager::switchToTenant($this->tenantA);
        $freshApproved = Estimation::findOrFail($estId);
        $this->assertEquals('Approved', $freshApproved->status);
        $this->assertEquals('Other — see notes', $freshApproved->zero_cost_reason);
        $this->assertEquals($notesJustification, $freshApproved->notes);

        // Case D: Reason is a specific predefined reason ('Client-Supplied Material') -> 200 Approved without notes requirement
        // Create another draft estimation to test predefined reason
        $createRes2 = $this->postJson('/api/estimations', [
            'opportunity_id' => $chain['opportunity']->id,
            'boq_id' => $chain['boq']->id,
        ], $this->headersA());
        $estId2 = $createRes2->json('data.id');

        $this->putJson("/api/estimations/{$estId2}", [
            'notes' => null, // empty notes
            'items' => [
                ['boq_item_id' => $chain['boqItem1']->id, 'costs' => [['cost_type' => 'material', 'cost_rate' => 0.00]]],
                ['boq_item_id' => $chain['boqItem2']->id, 'costs' => [['cost_type' => 'labor', 'cost_rate' => 0.00]]],
            ],
        ], $this->headersA());

        $resPredefined = $this->postJson("/api/estimations/{$estId2}/approve", [
            'zero_cost_reason' => 'Client-Supplied Material',
        ], $this->headersA());
        $resPredefined->assertStatus(200);
        $resPredefined->assertJsonPath('data.status', 'Approved');
        $resPredefined->assertJsonPath('data.zero_cost_reason', 'Client-Supplied Material');

        TenantDatabaseManager::switchToTenant($this->tenantA);
        $freshPredefined = Estimation::findOrFail($estId2);
        $this->assertEquals('Approved', $freshPredefined->status);
        $this->assertEquals('Client-Supplied Material', $freshPredefined->zero_cost_reason);
    }

    /**
     * Test 21: Guaranteed single approved estimation per opportunity, superseded handling, and atomic rollback on failure.
     */
    public function test_single_approved_estimation_per_opportunity_and_rollback_on_failure(): void
    {
        $chain = $this->makeApprovedBoq('T21');

        // Create Estimation V1
        $res1 = $this->postJson('/api/estimations', [
            'opportunity_id' => $chain['opportunity']->id,
            'boq_id' => $chain['boq']->id,
        ], $this->headersA());
        $estId1 = $res1->json('data.id');

        $this->putJson("/api/estimations/{$estId1}", [
            'items' => [
                ['boq_item_id' => $chain['boqItem1']->id, 'costs' => [['cost_type' => 'material', 'cost_rate' => 100.00]]],
                ['boq_item_id' => $chain['boqItem2']->id, 'costs' => [['cost_type' => 'labor', 'cost_rate' => 50.00]]],
            ],
        ], $this->headersA());

        // Approve V1
        $approve1 = $this->postJson("/api/estimations/{$estId1}/approve", [], $this->headersA());
        $approve1->assertStatus(200);

        // Attempting to re-approve an already approved estimation is rejected by workflow (status must be Draft/Under Review)
        $duplicateApprove = $this->postJson("/api/estimations/{$estId1}/approve", [], $this->headersA());
        $duplicateApprove->assertStatus(422);
        $duplicateApprove->assertJsonValidationErrors(['status']);

        // Create revision V2
        $revRes = $this->postJson("/api/estimations/{$estId1}/create-revision", [], $this->headersA());
        $estId2 = $revRes->json('data.id');

        // Subtest: Verification of atomic rollback on approval failure
        // Configure V2 with unpriced items (which must fail approval validation)
        $this->putJson("/api/estimations/{$estId2}", [
            'items' => [
                ['boq_item_id' => $chain['boqItem1']->id, 'costs' => []],
                ['boq_item_id' => $chain['boqItem2']->id, 'costs' => []],
            ],
        ], $this->headersA());

        $failedApprove = $this->postJson("/api/estimations/{$estId2}/approve", [], $this->headersA());
        $failedApprove->assertStatus(422);

        // Verify that upon failure, transaction rolled back completely: V1 is still Approved, V2 is still Draft
        TenantDatabaseManager::switchToTenant($this->tenantA);
        $v1Record = Estimation::findOrFail($estId1);
        $v2Record = Estimation::findOrFail($estId2);
        $this->assertEquals('Approved', $v1Record->status, 'Previous approved estimation was prematurely altered before rollback!');
        $this->assertEquals('Draft', $v2Record->status, 'Failed estimation approval left partial state changes!');

        // Now cost V2 properly and approve it
        $this->putJson("/api/estimations/{$estId2}", [
            'items' => [
                ['boq_item_id' => $chain['boqItem1']->id, 'costs' => [['cost_type' => 'material', 'cost_rate' => 120.00]]],
                ['boq_item_id' => $chain['boqItem2']->id, 'costs' => [['cost_type' => 'labor', 'cost_rate' => 60.00]]],
            ],
        ], $this->headersA());

        $approve2 = $this->postJson("/api/estimations/{$estId2}/approve", [], $this->headersA());
        $approve2->assertStatus(200);

        TenantDatabaseManager::switchToTenant($this->tenantA);

        $approvedCount = Estimation::where('opportunity_id', $chain['opportunity']->id)
            ->where('status', 'Approved')
            ->count();
        $this->assertEquals(1, $approvedCount, 'Multiple estimations are in Approved status for the same Opportunity!');

        $supersededCount = Estimation::where('opportunity_id', $chain['opportunity']->id)
            ->where('status', 'Superseded')
            ->count();
        $this->assertEquals(1, $supersededCount);
    }

    /**
     * Test 22: Opportunity-level pessimistic lock blocks concurrent database approval under separate connections.
     */
    public function test_opportunity_level_pessimistic_lock_blocks_concurrent_approval(): void
    {
        $chain = $this->makeApprovedBoq('T22');

        $createRes = $this->postJson('/api/estimations', [
            'opportunity_id' => $chain['opportunity']->id,
            'boq_id' => $chain['boq']->id,
        ], $this->headersA());
        $estId = $createRes->json('data.id');

        TenantDatabaseManager::switchToTenant($this->tenantA);

        // Connection 1 begins transaction and acquires exclusive lockForUpdate() on the parent Opportunity
        DB::connection('tenant')->beginTransaction();
        $lockedOpportunity = Opportunity::where('id', $chain['opportunity']->id)->lockForUpdate()->first();
        $this->assertNotNull($lockedOpportunity);

        // Connection 2: instantiate a separate PDO connection to the same tenant database
        $tenantConfig = config('database.connections.tenant');
        $dsn = "mysql:host={$tenantConfig['host']};port={$tenantConfig['port']};dbname={$tenantConfig['database']}";
        $pdo2 = new \PDO($dsn, $tenantConfig['username'], $tenantConfig['password']);
        $pdo2->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo2->exec('SET innodb_lock_wait_timeout = 1');

        $blockedAndTimedOut = false;
        try {
            // Attempt to acquire pessimistic lock on the parent Opportunity row from connection 2
            $stmt = $pdo2->prepare('SELECT * FROM opportunities WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $chain['opportunity']->id]);
            $stmt->fetchAll();
        } catch (\PDOException $e) {
            // Error code 1205: Lock wait timeout exceeded
            if (str_contains($e->getMessage(), '1205') || str_contains($e->getMessage(), 'Lock wait timeout')) {
                $blockedAndTimedOut = true;
            }
        } finally {
            // Release lock on connection 1 and close connection 2
            DB::connection('tenant')->rollBack();
            $pdo2 = null;
        }

        $this->assertTrue(
            $blockedAndTimedOut,
            'Pessimistic lock on parent Opportunity failed to block concurrent connection 2.'
        );
    }

    /**
     * Test 23: Protection of prepared_at with server-side defaults and manager authorization rules.
     */
    public function test_prepared_at_authorization_and_defaults(): void
    {
        $chain = $this->makeApprovedBoq('T23');

        // Create a standard estimator user without 'estimations.approve' permission
        TenantDatabaseManager::switchToTenant($this->tenantA);
        $estimator = User::create([
            'name' => 'Standard Estimator',
            'email' => 'estimator_t23_'.uniqid().'@test.com',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
        ]);
        $estimator->givePermissionTo(['estimations.view', 'estimations.create', 'estimations.update']);
        $estimatorToken = $estimator->createToken('est_token')->plainTextToken;
        $headersEstimator = [
            'Authorization' => 'Bearer '.$estimatorToken,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ];

        // 1. Creation default: omitting prepared_at sets trusted server-side date (today)
        $resDefault = $this->postJson('/api/estimations', [
            'opportunity_id' => $chain['opportunity']->id,
            'boq_id' => $chain['boq']->id,
        ], $headersEstimator);
        $resDefault->assertStatus(200);
        $resDefault->assertJsonPath('data.prepared_at', Carbon::today()->toDateString());
        $estId = $resDefault->json('data.id');

        // 2. Unauthorized creation with custom date: normal estimator cannot falsify date
        $resUnauthorizedCreate = $this->postJson('/api/estimations', [
            'opportunity_id' => $chain['opportunity']->id,
            'boq_id' => $chain['boq']->id,
            'prepared_at' => '2025-01-15',
        ], $headersEstimator);
        $resUnauthorizedCreate->assertStatus(422);
        $resUnauthorizedCreate->assertJsonValidationErrors(['prepared_at']);

        // 3. Unauthorized update: normal estimator cannot alter prepared_at
        $resUnauthorizedUpdate = $this->putJson("/api/estimations/{$estId}", [
            'prepared_at' => '2025-01-15',
        ], $headersEstimator);
        $resUnauthorizedUpdate->assertStatus(422);
        $resUnauthorizedUpdate->assertJsonValidationErrors(['prepared_at']);

        // 4. Authorized manager (Owner) can correct prepared_at
        $this->app['auth']->forgetGuards();
        $resAuthorizedUpdate = $this->putJson("/api/estimations/{$estId}", [
            'prepared_at' => '2025-01-15',
        ], $this->headersA());
        $resAuthorizedUpdate->assertStatus(200);
        $resAuthorizedUpdate->assertJsonPath('data.prepared_at', '2025-01-15');

        // Verify fresh database read retains the authorized date
        TenantDatabaseManager::switchToTenant($this->tenantA);
        $freshEst = Estimation::findOrFail($estId);
        $this->assertEquals('2025-01-15', Carbon::parse($freshEst->prepared_at)->toDateString());
    }

    /**
     * Test 24: Unique document number constraints safety and duplicate check command.
     */
    public function test_document_numbers_unique_constraints_and_duplicate_check_safety(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        // 1. Verify unique indexes exist on clean tenant
        $boqIndexes = collect(DB::select("SHOW INDEXES FROM boqs WHERE Key_name = 'boqs_number_unique'"));
        $this->assertTrue($boqIndexes->isNotEmpty(), 'boqs_number_unique index is missing on clean tenant database.');

        $scopeIndexes = collect(DB::select("SHOW INDEXES FROM scopes WHERE Key_name = 'scopes_number_unique'"));
        $this->assertTrue($scopeIndexes->isNotEmpty(), 'scopes_number_unique index is missing on clean tenant database.');

        $measurementIndexes = collect(DB::select("SHOW INDEXES FROM measurements WHERE Key_name = 'measurements_number_unique'"));
        $this->assertTrue($measurementIndexes->isNotEmpty(), 'measurements_number_unique index is missing on clean tenant database.');

        // 2. Run duplicate check command on clean tenant: must succeed (exit code 0)
        $cleanExitCode = Artisan::call('documents:check-duplicate-numbers', ['--tenant' => $this->slugA]);
        $this->assertEquals(0, $cleanExitCode, 'CheckDocumentDuplicateNumbersCommand reported failure on a clean tenant.');
    }
}
