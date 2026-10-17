<?php

namespace Tests\Feature;

use App\Core\Tenancy\TenantDatabaseManager;
use App\Models\Central\Tenant;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Quotation;
use App\Models\User;
use App\Services\Tenant\TenantProvisioningService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContractManagementTest extends TestCase
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

        $this->slugA = str_replace('.', '', uniqid('ct-a-', true)).rand(100, 999);
        $this->slugB = str_replace('.', '', uniqid('ct-b-', true)).rand(100, 999);

        // Provision Tenant A
        $resA = $this->provisioningService->provision([
            'name' => 'Alpha Contract Corp',
            'slug' => $this->slugA,
            'company_code' => 'CTALPH-'.uniqid(),
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
            'name' => 'Beta Contract Corp',
            'slug' => $this->slugB,
            'company_code' => 'CTBETA-'.uniqid(),
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
     * Helper to create an approved and client-accepted Quotation baseline in Tenant A.
     */
    protected function makeAcceptedQuotation(string $suffix = '', float $grandTotal = 50000.00, string $status = 'Approved', string $clientStatus = 'Accepted'): array
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
            'title' => 'Commercial Construction '.$suffix,
            'stage' => 'Proposal',
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $boqId = DB::table('boqs')->insertGetId([
            'opportunity_id' => $opportunity->id,
            'scope_id' => 1,
            'boq_number' => 'BOQ-MOCK-'.$suffix.'-'.rand(100, 999),
            'version' => 1,
            'status' => 'Approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $estimationId = DB::table('estimations')->insertGetId([
            'opportunity_id' => $opportunity->id,
            'boq_id' => $boqId,
            'estimation_number' => 'EST-MOCK-'.$suffix.'-'.rand(100, 999),
            'version' => 1,
            'status' => 'Approved',
            'direct_cost' => $grandTotal * 0.8,
            'total_cost' => $grandTotal * 0.8,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $quotation = Quotation::create([
            'opportunity_id' => $opportunity->id,
            'estimation_id' => $estimationId,
            'quotation_number' => 'QT-'.$suffix.'-'.rand(100, 999),
            'version' => 1,
            'status' => $status,
            'client_status' => $clientStatus,
            'title' => 'Commercial Offer '.$suffix,
            'subtotal' => round($grandTotal / 1.15, 2),
            'tax_rate' => 15.00,
            'tax_amount' => round($grandTotal - ($grandTotal / 1.15), 2),
            'grand_total' => $grandTotal,
            'payment_terms_text' => 'Standard payment terms',
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        return compact('customer', 'opportunity', 'quotation');
    }

    public function test_cannot_create_contract_without_quotation(): void
    {
        $setup = $this->makeAcceptedQuotation('noquot');

        $response = $this->postJson('/api/contracts', [
            'opportunity_id' => $setup['opportunity']->id,
        ], $this->headersA());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quotation_id']);
    }

    public function test_cannot_create_contract_with_unapproved_quotation(): void
    {
        $setup = $this->makeAcceptedQuotation('unapp', 50000.00, 'Draft', 'Accepted');

        $response = $this->postJson('/api/contracts', [
            'opportunity_id' => $setup['opportunity']->id,
            'quotation_id' => $setup['quotation']->id,
        ], $this->headersA());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quotation_id']);
    }

    public function test_cannot_create_contract_with_unaccepted_quotation(): void
    {
        $setup = $this->makeAcceptedQuotation('unacc', 50000.00, 'Approved', 'Sent');

        $response = $this->postJson('/api/contracts', [
            'opportunity_id' => $setup['opportunity']->id,
            'quotation_id' => $setup['quotation']->id,
        ], $this->headersA());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quotation_id']);
    }

    public function test_cannot_create_contract_with_opportunity_mismatch(): void
    {
        $setup1 = $this->makeAcceptedQuotation('opp1');
        $setup2 = $this->makeAcceptedQuotation('opp2');

        $response = $this->postJson('/api/contracts', [
            'opportunity_id' => $setup2['opportunity']->id, // Opp 2
            'quotation_id' => $setup1['quotation']->id,   // Quotation from Opp 1
        ], $this->headersA());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quotation_id']);
    }

    public function test_financial_inheritance_defaults_to_quotation_grand_total(): void
    {
        $setup = $this->makeAcceptedQuotation('inherit', 75000.00);

        $response = $this->postJson('/api/contracts', [
            'opportunity_id' => $setup['opportunity']->id,
            'quotation_id' => $setup['quotation']->id,
        ], $this->headersA());

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $contractData = $response->json('data');
        $this->assertEquals('75000.00', $contractData['contract_value']);
        $this->assertEquals('Draft', $contractData['status']);
        $this->assertEquals('Not Sent', $contractData['signature_status']);
        $this->assertNull($contractData['value_adjustment_reason']);
    }

    public function test_contract_value_adjustment_requires_valid_reason(): void
    {
        $setup = $this->makeAcceptedQuotation('adj', 100000.00);

        // 1. Differing value without reason fails
        $failRes1 = $this->postJson('/api/contracts', [
            'opportunity_id' => $setup['opportunity']->id,
            'quotation_id' => $setup['quotation']->id,
            'contract_value' => 110000.00,
        ], $this->headersA());

        $failRes1->assertStatus(422)
            ->assertJsonValidationErrors(['value_adjustment_reason']);

        // 2. Whitespace-only reason fails
        $failRes2 = $this->postJson('/api/contracts', [
            'opportunity_id' => $setup['opportunity']->id,
            'quotation_id' => $setup['quotation']->id,
            'contract_value' => 110000.00,
            'value_adjustment_reason' => '    ',
        ], $this->headersA());

        $failRes2->assertStatus(422)
            ->assertJsonValidationErrors(['value_adjustment_reason']);

        // 3. Short reason (< 10 chars) fails
        $failRes3 = $this->postJson('/api/contracts', [
            'opportunity_id' => $setup['opportunity']->id,
            'quotation_id' => $setup['quotation']->id,
            'contract_value' => 110000.00,
            'value_adjustment_reason' => 'Too short',
        ], $this->headersA());

        $failRes3->assertStatus(422)
            ->assertJsonValidationErrors(['value_adjustment_reason']);

        // 4. Valid detailed reason succeeds
        $successRes = $this->postJson('/api/contracts', [
            'opportunity_id' => $setup['opportunity']->id,
            'quotation_id' => $setup['quotation']->id,
            'contract_value' => 110000.00,
            'value_adjustment_reason' => 'Client requested additional premium marble upgrade during final negotiations.',
        ], $this->headersA());

        $successRes->assertStatus(201);
        $this->assertEquals('110000.00', $successRes->json('data.contract_value'));
        $this->assertNotNull($successRes->json('data.value_adjustment_reason'));
    }

    public function test_cannot_mutate_workflow_or_signature_directly(): void
    {
        $setup = $this->makeAcceptedQuotation('guard', 60000.00);

        // Client attempts to pass status: Approved in store
        $storeRes1 = $this->postJson('/api/contracts', [
            'opportunity_id' => $setup['opportunity']->id,
            'quotation_id' => $setup['quotation']->id,
            'status' => 'Approved',
        ], $this->headersA());

        $storeRes1->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        // Client attempts to pass signature_status: Signed in store
        $storeRes2 = $this->postJson('/api/contracts', [
            'opportunity_id' => $setup['opportunity']->id,
            'quotation_id' => $setup['quotation']->id,
            'signature_status' => 'Signed',
        ], $this->headersA());

        $storeRes2->assertStatus(422)
            ->assertJsonValidationErrors(['signature_status']);

        // Create a valid draft contract
        $createRes = $this->postJson('/api/contracts', [
            'opportunity_id' => $setup['opportunity']->id,
            'quotation_id' => $setup['quotation']->id,
        ], $this->headersA());
        $contractId = $createRes->json('data.id');

        // Client attempts to pass status: Approved in update
        $updateRes1 = $this->putJson("/api/contracts/{$contractId}", [
            'status' => 'Approved',
        ], $this->headersA());

        $updateRes1->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        // Client attempts to pass signature_status: Signed in update
        $updateRes2 = $this->putJson("/api/contracts/{$contractId}", [
            'signature_status' => 'Signed',
        ], $this->headersA());

        $updateRes2->assertStatus(422)
            ->assertJsonValidationErrors(['signature_status']);
    }

    public function test_contract_milestones_validation_on_approval(): void
    {
        $setup = $this->makeAcceptedQuotation('milestones', 100000.00);

        // 1. Create contract without milestones
        $createRes = $this->postJson('/api/contracts', [
            'opportunity_id' => $setup['opportunity']->id,
            'quotation_id' => $setup['quotation']->id,
        ], $this->headersA());
        $contractId = $createRes->json('data.id');

        // Approving with 0 milestones fails
        $failApprove0 = $this->postJson("/api/contracts/{$contractId}/approve", [], $this->headersA());
        $failApprove0->assertStatus(422)
            ->assertJsonValidationErrors(['milestones']);

        // 2. Dual method in single milestone fails
        $failDual = $this->putJson("/api/contracts/{$contractId}", [
            'milestones' => [
                ['milestone_label' => 'M1', 'percentage' => 50.00, 'fixed_amount' => 50000.00],
            ],
        ], $this->headersA());
        $failDual->assertStatus(422)
            ->assertJsonValidationErrors(['milestones.0']);

        // 3. Percentage sum not 100.00% fails
        $this->putJson("/api/contracts/{$contractId}", [
            'milestones' => [
                ['milestone_label' => 'Advance', 'percentage' => 30.00],
                ['milestone_label' => 'Progress', 'percentage' => 50.00],
            ], // Total = 80.00%
        ], $this->headersA())->assertStatus(200);

        $failApprovePct = $this->postJson("/api/contracts/{$contractId}/approve", [], $this->headersA());
        $failApprovePct->assertStatus(422)
            ->assertJsonValidationErrors(['milestones']);

        // 4. Fixed amount sum not matching contract value fails
        $this->putJson("/api/contracts/{$contractId}", [
            'milestones' => [
                ['milestone_label' => 'M1', 'fixed_amount' => 40000.00],
                ['milestone_label' => 'M2', 'fixed_amount' => 40000.00],
            ], // Total = 80,000 but contract value = 100,000
        ], $this->headersA())->assertStatus(200);

        $failApproveFix = $this->postJson("/api/contracts/{$contractId}/approve", [], $this->headersA());
        $failApproveFix->assertStatus(422)
            ->assertJsonValidationErrors(['milestones']);

        // 5. Valid percentage milestones (20% + 70% + 10% = 100.00%) approve successfully
        $this->putJson("/api/contracts/{$contractId}", [
            'milestones' => [
                ['milestone_label' => 'Advance Payment', 'percentage' => 20.00],
                ['milestone_label' => 'Execution Handover', 'percentage' => 70.00],
                ['milestone_label' => 'Retention Delivery', 'percentage' => 10.00],
            ],
        ], $this->headersA())->assertStatus(200);

        $approveRes = $this->postJson("/api/contracts/{$contractId}/approve", [], $this->headersA());
        $approveRes->assertStatus(200);
        $this->assertEquals('Approved', $approveRes->json('data.status'));
        $this->assertNotNull($approveRes->json('data.approved_at'));
    }

    public function test_workflow_approval_locks_opportunity_and_supersedes_revisions(): void
    {
        $setup = $this->makeAcceptedQuotation('workflow', 150000.00);

        // 1. Create and approve Contract V1
        $c1Res = $this->postJson('/api/contracts', [
            'opportunity_id' => $setup['opportunity']->id,
            'quotation_id' => $setup['quotation']->id,
            'milestones' => [
                ['milestone_label' => 'Full Payment', 'percentage' => 100.00],
            ],
        ], $this->headersA());
        $c1Id = $c1Res->json('data.id');

        $this->postJson("/api/contracts/{$c1Id}/approve", [], $this->headersA())
            ->assertStatus(200);

        $c1 = Contract::find($c1Id);
        $this->assertEquals('Approved', $c1->status);

        // 2. Create Revision V2 from V1
        $revRes = $this->postJson("/api/contracts/{$c1Id}/create-revision", [], $this->headersA());
        $revRes->assertStatus(201);
        $c2Id = $revRes->json('data.id');
        $c2Version = $revRes->json('data.version');

        $this->assertEquals(2, $c2Version);
        $this->assertEquals('Draft', $revRes->json('data.status'));
        $this->assertEquals($c1Id, $revRes->json('data.parent_contract_id'));

        // 3. Approve Revision V2 -> V1 becomes Superseded
        $appV2Res = $this->postJson("/api/contracts/{$c2Id}/approve", [], $this->headersA());
        $appV2Res->assertStatus(200);

        $c1->refresh();
        $this->assertEquals('Superseded', $c1->status);

        $c2 = Contract::find($c2Id);
        $this->assertEquals('Approved', $c2->status);
    }

    public function test_conflicting_standalone_contract_approval_is_blocked(): void
    {
        $setup = $this->makeAcceptedQuotation('conflict', 80000.00);

        // Contract 1: Approved
        $c1Res = $this->postJson('/api/contracts', [
            'opportunity_id' => $setup['opportunity']->id,
            'quotation_id' => $setup['quotation']->id,
            'milestones' => [['milestone_label' => 'Full', 'percentage' => 100.00]],
        ], $this->headersA());
        $c1Id = $c1Res->json('data.id');
        $this->postJson("/api/contracts/{$c1Id}/approve", [], $this->headersA())->assertStatus(200);

        // Contract 2: Independent draft for the same opportunity (not a revision)
        $c2Res = $this->postJson('/api/contracts', [
            'opportunity_id' => $setup['opportunity']->id,
            'quotation_id' => $setup['quotation']->id,
            'milestones' => [['milestone_label' => 'Full', 'percentage' => 100.00]],
        ], $this->headersA());
        $c2Id = $c2Res->json('data.id');

        // Trying to approve standalone C2 while C1 is active Approved must fail
        $conflictRes = $this->postJson("/api/contracts/{$c2Id}/approve", [], $this->headersA());
        $conflictRes->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_signature_lifecycle_transitions(): void
    {
        $setup = $this->makeAcceptedQuotation('signature', 90000.00);

        $createRes = $this->postJson('/api/contracts', [
            'opportunity_id' => $setup['opportunity']->id,
            'quotation_id' => $setup['quotation']->id,
            'milestones' => [['milestone_label' => 'Full', 'percentage' => 100.00]],
        ], $this->headersA());
        $contractId = $createRes->json('data.id');

        // 1. Cannot send unapproved contract for signature
        $failSend = $this->postJson("/api/contracts/{$contractId}/send-for-signature", [], $this->headersA());
        $failSend->assertStatus(422)
            ->assertJsonValidationErrors(['signature_status']);

        // 2. Approve contract
        $this->postJson("/api/contracts/{$contractId}/approve", [], $this->headersA())->assertStatus(200);

        // 3. Dispatch for signature
        $sendRes = $this->postJson("/api/contracts/{$contractId}/send-for-signature", [], $this->headersA());
        $sendRes->assertStatus(200);
        $this->assertEquals('Sent for Signature', $sendRes->json('data.signature_status'));

        // 4. Mark Signed
        $signRes = $this->postJson("/api/contracts/{$contractId}/mark-signed", [], $this->headersA());
        $signRes->assertStatus(200);
        $this->assertEquals('Signed', $signRes->json('data.signature_status'));
        $this->assertNotNull($signRes->json('data.signed_at'));
        $this->assertEquals($this->ownerA->id, $signRes->json('data.signed_by'));

        // 5. Signed contract cannot be casually voided
        $voidFail = $this->postJson("/api/contracts/{$contractId}/void-signature", [], $this->headersA());
        $voidFail->assertStatus(422)
            ->assertJsonValidationErrors(['signature_status']);
    }

    public function test_cannot_delete_approved_or_signed_contract(): void
    {
        $setup = $this->makeAcceptedQuotation('del', 50000.00);

        $createRes = $this->postJson('/api/contracts', [
            'opportunity_id' => $setup['opportunity']->id,
            'quotation_id' => $setup['quotation']->id,
            'milestones' => [['milestone_label' => 'Full', 'percentage' => 100.00]],
        ], $this->headersA());
        $contractId = $createRes->json('data.id');

        $this->postJson("/api/contracts/{$contractId}/approve", [], $this->headersA())->assertStatus(200);

        // Attempt delete on approved contract
        $delRes = $this->deleteJson("/api/contracts/{$contractId}", [], $this->headersA());
        $delRes->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $this->assertDatabaseHas('contracts', ['id' => $contractId]);
    }

    public function test_duplicate_document_number_checker_detects_contracts(): void
    {
        TenantDatabaseManager::switchToCentral();

        // 1. Run cleanly on valid databases
        $exitClean = Artisan::call('documents:check-duplicate-numbers', ['--tenant' => $this->slugA]);
        $this->assertEquals(0, $exitClean);

        // 2. Inject duplicate contract number in Tenant A
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $setup = $this->makeAcceptedQuotation('dupecheck', 30000.00);
        $c1 = Contract::create([
            'opportunity_id' => $setup['opportunity']->id,
            'quotation_id' => $setup['quotation']->id,
            'contract_number' => 'CT-DUPE-TEST-999',
            'version' => 1,
            'status' => 'Draft',
            'signature_status' => 'Not Sent',
            'contract_value' => 30000.00,
        ]);

        // Temporarily drop unique index in this test tenant database to simulate duplicate document collision
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::statement('ALTER TABLE `contracts` DROP INDEX `contracts_number_unique`');
        DB::table('contracts')->insert([
            'opportunity_id' => $setup['opportunity']->id,
            'quotation_id' => $setup['quotation']->id,
            'contract_number' => 'CT-DUPE-TEST-999',
            'version' => 2,
            'status' => 'Draft',
            'signature_status' => 'Not Sent',
            'contract_value' => 30000.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        TenantDatabaseManager::switchToCentral();

        // 3. Command must now detect duplicates and exit with 1 (FAILURE)
        $exitFail = Artisan::call('documents:check-duplicate-numbers', ['--tenant' => $this->slugA]);
        $output = Artisan::output();

        $this->assertEquals(1, $exitFail);
        $this->assertStringContainsString('CT-DUPE-TEST-999', $output);
        $this->assertStringContainsString('contracts', $output);
    }

    public function test_tenant_isolation_for_contracts(): void
    {
        $setupA = $this->makeAcceptedQuotation('isofail', 40000.00);

        $resA = $this->postJson('/api/contracts', [
            'opportunity_id' => $setupA['opportunity']->id,
            'quotation_id' => $setupA['quotation']->id,
        ], $this->headersA());

        $contractIdA = $resA->json('data.id');

        // Tenant B requests Tenant A's contract -> must return 404
        $resB = $this->getJson("/api/contracts/{$contractIdA}", $this->headersB());
        $resB->assertStatus(404);
    }
}
