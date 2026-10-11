<?php

namespace Tests\Feature;

use App\Core\Tenancy\TenantDatabaseManager;
use App\Models\Boq;
use App\Models\Central\Tenant;
use App\Models\Customer;
use App\Models\Measurement;
use App\Models\Opportunity;
use App\Models\Scope;
use App\Models\SiteVisit;
use App\Models\User;
use App\Services\Tenant\TenantProvisioningService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BoqManagementTest extends TestCase
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

        $this->slugA = str_replace('.', '', uniqid('boq-a-', true)) . rand(100, 999);
        $this->slugB = str_replace('.', '', uniqid('boq-b-', true)) . rand(100, 999);

        // Provision Tenant A
        $resA = $this->provisioningService->provision([
            'name' => 'Alpha BOQ Contracting',
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
            'name' => 'Beta BOQ Contracting',
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
     * Build a complete approved commercial chain (measurement + scope) for BOQ tests.
     *
     * @return array{customer, opportunity, measurement, scope}
     */
    protected function makeApprovedScope(string $suffix = ''): array
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'name' => 'BOQ Client ' . $suffix,
            'customer_type' => 'company',
            'phone' => '010' . rand(10000000, 99999999),
            'status' => 'active',
        ]);

        $opportunity = Opportunity::create([
            'customer_id' => $customer->id,
            'title' => 'BOQ Project ' . $suffix,
            'stage' => 'Proposal',
        ]);

        $measurement = Measurement::create([
            'opportunity_id' => $opportunity->id,
            'measurement_number' => 'M-BOQ-' . $suffix . '-001',
            'version' => 1,
            'status' => 'Approved',
            'total_area' => 120.00,
        ]);

        $mItem1 = $measurement->items()->create([
            'room_name' => 'Reception Hall',
            'item_name' => 'Floor Porcelain',
            'unit' => 'm2',
            'measurement_type' => 'area',
            'count' => 1,
            'length' => 10,
            'width' => 8,
            'gross_quantity' => 80.00,
            'net_quantity' => 80.00,
        ]);

        $mItem2 = $measurement->items()->create([
            'room_name' => 'Manager Office',
            'item_name' => 'Floor HDF',
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
            'scope_number' => 'SC-BOQ-' . $suffix . '-001',
            'version' => 1,
            'status' => 'Approved',
            'title' => 'Approved Scope ' . $suffix,
            'approved_by' => $this->ownerA->id,
            'approved_at' => now(),
        ]);

        $sItem1 = $scope->items()->create([
            'trade_category' => 'أعمال الأرضيات والبورسلين والرخام والجرانيت',
            'item_name' => 'توريد وتركيب بورسلين',
            'specification' => 'بورسلين إسباني فرز أول',
            'sort_order' => 0,
        ]);
        $sItem1->measurementItems()->sync([$mItem1->id]);

        $sItem2 = $scope->items()->create([
            'trade_category' => 'أعمال الدهانات والتشطيبات والديكورات الحائطية',
            'item_name' => 'توريد وتركيب HDF',
            'specification' => 'HDF بلجيكي مقاوم',
            'sort_order' => 1,
        ]);
        $sItem2->measurementItems()->sync([$mItem2->id]);

        return compact('customer', 'opportunity', 'measurement', 'scope') + [
            'mItem1' => $mItem1,
            'mItem2' => $mItem2,
            'sItem1' => $sItem1,
            'sItem2' => $sItem2,
        ];
    }

    /**
     * SCENARIO 1: Full lifecycle — approved scope with homogeneous units
     * generates exact BOQ lines, then submit + approve.
     */
    public function test_can_create_boq_from_approved_scope_and_approve(): void
    {
        $chain = $this->makeApprovedScope('S1');
        $opportunity = $chain['opportunity'];
        $scope = $chain['scope'];

        $response = $this->withHeaders($this->headersA())->postJson('/api/boqs', [
            'opportunity_id' => $opportunity->id,
            'scope_id' => $scope->id,
            'title' => 'جدول كميات المشروع الإداري',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.opportunity_id', $opportunity->id)
            ->assertJsonPath('data.scope_id', $scope->id)
            ->assertJsonPath('data.status', 'Draft')
            ->assertJsonPath('data.version', 1);

        $boqId = $response->json('data.id');
        $lines = $response->json('data.items');

        // Two homogeneous scope items -> two BOQ lines with exact quantities
        $this->assertCount(2, $lines);
        $this->assertEquals('1.1', $lines[0]['item_code']);
        $this->assertEquals('2.1', $lines[1]['item_code']);
        $this->assertEquals('m2', $lines[0]['unit']);
        $this->assertEquals(80.00, (float) $lines[0]['quantity']);
        $this->assertEquals(40.00, (float) $lines[1]['quantity']);
        $this->assertStringContainsString('توريد وتركيب بورسلين', $lines[0]['description']);
        $this->assertStringContainsString('بورسلين إسباني فرز أول', $lines[0]['description']);

        // Nothing skipped
        $this->assertEmpty($response->json('skipped_items'));

        // Submit for review
        $reviewRes = $this->withHeaders($this->headersA())->postJson("/api/boqs/{$boqId}/submit-review");
        $reviewRes->assertStatus(200)->assertJsonPath('data.status', 'Under Review');

        // Approve
        $approveRes = $this->withHeaders($this->headersA())->postJson("/api/boqs/{$boqId}/approve");
        $approveRes->assertStatus(200)->assertJsonPath('data.status', 'Approved');

        $this->assertDatabaseHas('boqs', ['id' => $boqId, 'status' => 'Approved']);

        $opp = Opportunity::with('approvedBoq')->find($opportunity->id);
        $this->assertNotNull($opp->approvedBoq);
        $this->assertEquals($boqId, $opp->approvedBoq->id);
    }

    /**
     * SCENARIO 2: BOQ on Draft scope MUST return 422.
     */
    public function test_cannot_create_boq_on_draft_scope(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create(['name' => 'Draft Scope Client', 'phone' => '01011112222', 'status' => 'active']);
        $opportunity = Opportunity::create(['customer_id' => $customer->id, 'title' => 'Draft Scope Opp', 'stage' => 'Proposal']);
        $measurement = Measurement::create([
            'opportunity_id' => $opportunity->id,
            'measurement_number' => 'M-DRAFT-BOQ',
            'status' => 'Approved',
        ]);
        $scope = Scope::create([
            'opportunity_id' => $opportunity->id,
            'measurement_id' => $measurement->id,
            'scope_number' => 'SC-DRAFT-BOQ',
            'status' => 'Draft', // NOT APPROVED
        ]);

        $response = $this->withHeaders($this->headersA())->postJson('/api/boqs', [
            'opportunity_id' => $opportunity->id,
            'scope_id' => $scope->id,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['scope_id']);
    }

    /**
     * SCENARIO 3: Scope from another opportunity MUST return 422.
     */
    public function test_cannot_create_boq_with_scope_from_another_opportunity(): void
    {
        $chainA = $this->makeApprovedScope('S3A');

        TenantDatabaseManager::switchToTenant($this->tenantA);
        $customerB = Customer::create(['name' => 'Other Opp Client', 'phone' => '01033334444', 'status' => 'active']);
        $opportunityB = Opportunity::create(['customer_id' => $customerB->id, 'title' => 'Other Opp', 'stage' => 'Proposal']);

        $response = $this->withHeaders($this->headersA())->postJson('/api/boqs', [
            'opportunity_id' => $opportunityB->id,
            'scope_id' => $chainA['scope']->id, // Belongs to opportunity A
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['scope_id']);
    }

    /**
     * SCENARIO 4: Mixed-unit scope item is skipped selectively;
     * homogeneous lines are still generated.
     */
    public function test_mixed_units_scope_item_is_skipped_selectively(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create(['name' => 'Mixed Units Client', 'phone' => '01055556666', 'status' => 'active']);
        $opportunity = Opportunity::create(['customer_id' => $customer->id, 'title' => 'Mixed Units Opp', 'stage' => 'Proposal']);
        $measurement = Measurement::create([
            'opportunity_id' => $opportunity->id,
            'measurement_number' => 'M-MIX-001',
            'status' => 'Approved',
        ]);

        $areaItem = $measurement->items()->create([
            'room_name' => 'Hall', 'item_name' => 'Floor', 'unit' => 'm2',
            'measurement_type' => 'area', 'count' => 1, 'net_quantity' => 50, 'gross_quantity' => 50,
        ]);
        $linearItem = $measurement->items()->create([
            'room_name' => 'Hall', 'item_name' => 'Skirting', 'unit' => 'lm',
            'measurement_type' => 'linear', 'count' => 1, 'length' => 20, 'net_quantity' => 20, 'gross_quantity' => 20,
        ]);

        $scope = Scope::create([
            'opportunity_id' => $opportunity->id,
            'measurement_id' => $measurement->id,
            'scope_number' => 'SC-MIX-001',
            'status' => 'Approved',
        ]);

        // Homogeneous line
        $okItem = $scope->items()->create([
            'trade_category' => 'أعمال الأرضيات', 'item_name' => 'بند أرضيات سليم', 'sort_order' => 0,
        ]);
        $okItem->measurementItems()->sync([$areaItem->id]);

        // Heterogeneous line (m2 + lm)
        $badItem = $scope->items()->create([
            'trade_category' => 'أعمال مختلطة', 'item_name' => 'بند مختلط الوحدات', 'sort_order' => 1,
        ]);
        $badItem->measurementItems()->sync([$areaItem->id, $linearItem->id]);

        $response = $this->withHeaders($this->headersA())->postJson('/api/boqs', [
            'opportunity_id' => $opportunity->id,
            'scope_id' => $scope->id,
        ]);

        $response->assertStatus(200)->assertJsonPath('success', true);

        // Only the homogeneous line generated — quantities with different units never summed
        $lines = $response->json('data.items');
        $this->assertCount(1, $lines);
        $this->assertEquals('m2', $lines[0]['unit']);
        $this->assertEquals(50.00, (float) $lines[0]['quantity']);

        $skipped = $response->json('skipped_items');
        $this->assertCount(1, $skipped);
        $this->assertEquals('mixed_units', $skipped[0]['reason']);
        $this->assertEquals($badItem->id, $skipped[0]['scope_item_id']);

        // Warning persists after creation: show() re-derives unresolved scope items
        $boqId = $response->json('data.id');
        $showRes = $this->withHeaders($this->headersA())->getJson("/api/boqs/{$boqId}");
        $showRes->assertOk();
        $unresolved = $showRes->json('unresolved_items');
        $this->assertCount(1, $unresolved);
        $this->assertEquals('mixed_units', $unresolved[0]['reason']);
        $this->assertEquals($badItem->id, $unresolved[0]['scope_item_id']);
        $this->assertEqualsCanonicalizing(['m2', 'lm'], $unresolved[0]['units']);
    }

    /**
     * Fully generated BOQ has no unresolved items on show().
     */
    public function test_show_returns_no_unresolved_items_for_fully_generated_boq(): void
    {
        $chain = $this->makeApprovedScope('SU');

        $createRes = $this->withHeaders($this->headersA())->postJson('/api/boqs', [
            'opportunity_id' => $chain['opportunity']->id,
            'scope_id' => $chain['scope']->id,
        ]);
        $createRes->assertStatus(200);

        $showRes = $this->withHeaders($this->headersA())->getJson('/api/boqs/' . $createRes->json('data.id'));
        $showRes->assertOk();
        $this->assertSame([], $showRes->json('unresolved_items'));
    }

    /**
     * Revisions branch only from an Approved BOQ (Draft / Under Review -> 422).
     */
    public function test_cannot_create_revision_from_non_approved_boq(): void
    {
        $chain = $this->makeApprovedScope('SR');

        $createRes = $this->withHeaders($this->headersA())->postJson('/api/boqs', [
            'opportunity_id' => $chain['opportunity']->id,
            'scope_id' => $chain['scope']->id,
        ]);
        $createRes->assertStatus(200);
        $boqId = $createRes->json('data.id');

        // Draft
        $this->withHeaders($this->headersA())->postJson("/api/boqs/{$boqId}/create-revision")
            ->assertStatus(422)->assertJsonValidationErrors(['status']);

        // Under Review
        $this->withHeaders($this->headersA())->postJson("/api/boqs/{$boqId}/submit-review")->assertStatus(200);
        $this->withHeaders($this->headersA())->postJson("/api/boqs/{$boqId}/create-revision")
            ->assertStatus(422)->assertJsonValidationErrors(['status']);

        $this->assertEquals(1, Boq::where('opportunity_id', $chain['opportunity']->id)->count());
    }

    /**
     * Form schema never exposes status / version as editable inputs.
     */
    public function test_boq_schema_does_not_expose_status_or_version_inputs(): void
    {
        $res = $this->withHeaders($this->headersA())->getJson('/api/boqs');
        $res->assertOk();

        $encoded = json_encode($res->json('schema'));
        $this->assertStringNotContainsString('"status"', $encoded);
        $this->assertStringNotContainsString('"version"', $encoded);
    }

    /**
     * SCENARIO 5: Linking a scope item from another scope MUST return 422
     * (cross-scope integrity).
     */
    public function test_cannot_link_scope_item_from_another_scope(): void
    {
        $chain = $this->makeApprovedScope('S5');
        $opportunity = $chain['opportunity'];
        $scope = $chain['scope'];

        // Foreign scope with its own item
        TenantDatabaseManager::switchToTenant($this->tenantA);
        $foreignScope = Scope::create([
            'opportunity_id' => $opportunity->id,
            'measurement_id' => $chain['measurement']->id,
            'scope_number' => 'SC-FOREIGN-001',
            'status' => 'Approved',
        ]);
        $foreignItem = $foreignScope->items()->create([
            'trade_category' => 'أعمال غريبة', 'item_name' => 'بند من نطاق آخر',
        ]);

        // Create BOQ first
        $createRes = $this->withHeaders($this->headersA())->postJson('/api/boqs', [
            'opportunity_id' => $opportunity->id,
            'scope_id' => $scope->id,
        ]);
        $createRes->assertStatus(200);
        $boqId = $createRes->json('data.id');

        // Attempt to replace lines with a foreign scope item
        $updateRes = $this->withHeaders($this->headersA())->putJson("/api/boqs/{$boqId}", [
            'items' => [
                ['scope_item_id' => $foreignItem->id, 'item_code' => '9.9'],
            ],
        ]);

        $updateRes->assertStatus(422)->assertJsonValidationErrors(['items.0.scope_item_id']);
    }

    /**
     * SCENARIO 6: Approved BOQ is immutable (update + delete -> 422).
     */
    public function test_approved_boq_cannot_be_modified_or_deleted(): void
    {
        $chain = $this->makeApprovedScope('S6');
        $scope = $chain['scope'];
        $opportunity = $chain['opportunity'];

        TenantDatabaseManager::switchToTenant($this->tenantA);
        $boq = Boq::create([
            'opportunity_id' => $opportunity->id,
            'scope_id' => $scope->id,
            'boq_number' => 'BOQ-LOCK-001',
            'status' => 'Approved',
        ]);

        $updateRes = $this->withHeaders($this->headersA())->putJson("/api/boqs/{$boq->id}", [
            'title' => 'Unauthorized BOQ Modification',
        ]);
        $updateRes->assertStatus(422)->assertJsonValidationErrors(['status']);

        $deleteRes = $this->withHeaders($this->headersA())->deleteJson("/api/boqs/{$boq->id}");
        $deleteRes->assertStatus(422)->assertJsonValidationErrors(['status']);
    }

    /**
     * SCENARIO 7: status/version injection ignored on store; scope_id
     * replacement ignored on update.
     */
    public function test_store_boq_ignores_status_injection_and_update_ignores_scope_replacement(): void
    {
        $chain = $this->makeApprovedScope('S7');
        $opportunity = $chain['opportunity'];
        $scope = $chain['scope'];

        $response = $this->withHeaders($this->headersA())->postJson('/api/boqs', [
            'opportunity_id' => $opportunity->id,
            'scope_id' => $scope->id,
            'title' => 'Injection Attempt BOQ',
            'status' => 'Approved', // Injection!
            'version' => 99, // Injection!
            'approved_by' => 1,
            'approved_at' => now()->toDateTimeString(),
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'Draft')
            ->assertJsonPath('data.version', 1);

        $boqId = $response->json('data.id');
        $stored = Boq::find($boqId);
        $this->assertEquals('Draft', $stored->status);
        $this->assertEquals(1, $stored->version);
        $this->assertNull($stored->approved_by);
        $this->assertNull($stored->approved_at);

        // Attempt to silently replace the source scope revision
        TenantDatabaseManager::switchToTenant($this->tenantA);
        $otherScope = Scope::create([
            'opportunity_id' => $opportunity->id,
            'measurement_id' => $chain['measurement']->id,
            'scope_number' => 'SC-OTHER-001',
            'status' => 'Approved',
        ]);

        $updateRes = $this->withHeaders($this->headersA())->putJson("/api/boqs/{$boqId}", [
            'scope_id' => $otherScope->id,
            'title' => 'Scope replacement attempt',
        ]);
        $updateRes->assertStatus(200);

        $this->assertEquals($scope->id, Boq::find($boqId)->scope_id);
    }

    /**
     * SCENARIO 8: Revision lifecycle — V1 Approved -> V2 Draft (same frozen
     * source) -> V2 Approved -> V1 Superseded, quantities preserved.
     */
    public function test_boq_revision_lifecycle_and_historical_preservation(): void
    {
        $chain = $this->makeApprovedScope('S8');
        $opportunity = $chain['opportunity'];
        $scope = $chain['scope'];

        $createRes = $this->withHeaders($this->headersA())->postJson('/api/boqs', [
            'opportunity_id' => $opportunity->id,
            'scope_id' => $scope->id,
        ]);
        $createRes->assertStatus(200);
        $v1Id = $createRes->json('data.id');
        $v1Number = $createRes->json('data.boq_number');

        $this->withHeaders($this->headersA())->postJson("/api/boqs/{$v1Id}/submit-review")->assertStatus(200);
        $this->withHeaders($this->headersA())->postJson("/api/boqs/{$v1Id}/approve")->assertStatus(200);

        $revRes = $this->withHeaders($this->headersA())->postJson("/api/boqs/{$v1Id}/create-revision");
        $revRes->assertStatus(200)
            ->assertJsonPath('data.version', 2)
            ->assertJsonPath('data.status', 'Draft')
            ->assertJsonPath('data.boq_number', $v1Number . '-V2')
            ->assertJsonPath('data.scope_id', $scope->id);

        $v2Id = $revRes->json('data.id');

        // V1 still Approved while V2 is Draft; quantities identical
        $this->assertEquals('Approved', Boq::find($v1Id)->status);
        $this->assertEquals(
            Boq::find($v1Id)->items()->sum('quantity'),
            Boq::find($v2Id)->items()->sum('quantity')
        );

        $this->withHeaders($this->headersA())->postJson("/api/boqs/{$v2Id}/approve")->assertStatus(200);

        $this->assertEquals('Superseded', Boq::find($v1Id)->status);
        $this->assertEquals('Approved', Boq::find($v2Id)->status);
    }

    /**
     * SCENARIO 9: BOQ keeps its original scope revision after a newer
     * scope revision is approved (historical integrity).
     */
    public function test_boq_preserves_original_scope_revision_after_new_scope_approval(): void
    {
        $chain = $this->makeApprovedScope('S9');
        $opportunity = $chain['opportunity'];
        $scopeV1 = $chain['scope'];

        // BOQ built on Scope V1
        $createRes = $this->withHeaders($this->headersA())->postJson('/api/boqs', [
            'opportunity_id' => $opportunity->id,
            'scope_id' => $scopeV1->id,
        ]);
        $createRes->assertStatus(200);
        $boqId = $createRes->json('data.id');
        $this->withHeaders($this->headersA())->postJson("/api/boqs/{$boqId}/submit-review")->assertStatus(200);
        $this->withHeaders($this->headersA())->postJson("/api/boqs/{$boqId}/approve")->assertStatus(200);

        // New scope revision V2 approved afterwards
        $revRes = $this->withHeaders($this->headersA())->postJson("/api/scopes/{$scopeV1->id}/create-revision");
        $revRes->assertStatus(200);
        $scopeV2Id = $revRes->json('data.id');
        $this->withHeaders($this->headersA())->postJson("/api/scopes/{$scopeV2Id}/approve")->assertStatus(200);

        // BOQ still frozen on Scope V1
        $this->assertEquals($scopeV1->id, Boq::find($boqId)->scope_id);

        // Chain exposes both: latest scope V2 + BOQ anchored to V1
        $chainRes = $this->withHeaders($this->headersA())->getJson("/api/opportunities/{$opportunity->id}/chain");
        $chainRes->assertOk();
        $chainRes->assertJsonPath('data.approved_scope.version', 2);
        $chainRes->assertJsonPath('data.approved_boq.id', $boqId);
        $this->assertEquals($scopeV1->id, $chainRes->json('data.approved_boq.scope_id'));
        $this->assertEquals($scopeV1->id, $chainRes->json('data.approved_boq.scope.id'));
        $this->assertEquals('M-BOQ-S9-001', $chainRes->json('data.approved_boq.scope.measurement.measurement_number'));
    }

    /**
     * SCENARIO 10: Tenant isolation + IDOR — cross-tenant BOQ access is 404,
     * cross-tenant source scope reference is rejected.
     */
    public function test_boq_tenant_isolation_and_idor_protection(): void
    {
        $chain = $this->makeApprovedScope('S10');

        // Tenant B builds its own BOQ
        TenantDatabaseManager::switchToTenant($this->tenantB);
        $customerB = Customer::create(['name' => 'Beta BOQ Client', 'phone' => '01122224444', 'status' => 'active']);
        $oppB = Opportunity::create(['customer_id' => $customerB->id, 'title' => 'Beta BOQ Opp', 'stage' => 'Proposal']);
        $measB = Measurement::create([
            'opportunity_id' => $oppB->id, 'measurement_number' => 'M-BETA-BOQ', 'status' => 'Approved',
        ]);
        $scopeB = Scope::create([
            'opportunity_id' => $oppB->id, 'measurement_id' => $measB->id,
            'scope_number' => 'SC-BETA-BOQ', 'status' => 'Approved',
        ]);
        $boqB = Boq::create([
            'opportunity_id' => $oppB->id, 'scope_id' => $scopeB->id, 'boq_number' => 'BOQ-BETA-001',
        ]);

        // Tenant A reads/updates/approves Tenant B's BOQ -> 404
        $this->withHeaders($this->headersA())->getJson("/api/boqs/{$boqB->id}")->assertStatus(404);
        $this->withHeaders($this->headersA())->putJson("/api/boqs/{$boqB->id}", ['title' => 'Attack'])->assertStatus(404);
        $this->withHeaders($this->headersA())->postJson("/api/boqs/{$boqB->id}/approve")->assertStatus(404);

        // Tenant B requesting Tenant A's numeric opportunity ID must NEVER see
        // Tenant A's data: either 404, or (on ID collision across databases)
        // strictly Tenant B's own opportunity.
        $chainRes = $this->withHeaders($this->headersB())->getJson("/api/opportunities/{$chain['opportunity']->id}/chain");
        if ($chainRes->getStatusCode() === 200) {
            $this->assertEquals($oppB->id, $chainRes->json('data.opportunity.id'));
            $this->assertEquals('Beta BOQ Opp', $chainRes->json('data.opportunity.title'));
            foreach ($chainRes->json('data.boqs') as $visibleBoq) {
                $this->assertEquals($oppB->id, $visibleBoq['opportunity_id']);
            }
        } else {
            $chainRes->assertStatus(404);
        }

        // Guaranteed-missing ID is always 404
        $this->withHeaders($this->headersB())->getJson('/api/opportunities/999999/chain')->assertStatus(404);

        // Tenant A attempts to build a BOQ referencing Tenant B's scope ID.
        // Numeric IDs may collide across tenant databases; the implementation
        // must always resolve strictly inside Tenant A's own database.
        $crossRes = $this->withHeaders($this->headersA())->postJson('/api/boqs', [
            'opportunity_id' => $chain['opportunity']->id,
            'scope_id' => $scopeB->id,
        ]);
        if ($crossRes->getStatusCode() === 200) {
            // ID collision: resolved to Tenant A's OWN scope — no leakage occurred.
            $this->assertEquals($chain['scope']->scope_number, $crossRes->json('data.scope.scope_number'));
            $this->assertEquals($chain['opportunity']->id, $crossRes->json('data.opportunity_id'));
        } else {
            $crossRes->assertStatus(422);
        }

        // A scope ID absent from Tenant A's database is always rejected.
        $missingRes = $this->withHeaders($this->headersA())->postJson('/api/boqs', [
            'opportunity_id' => $chain['opportunity']->id,
            'scope_id' => 999999,
        ]);
        $missingRes->assertStatus(422);
    }

    /**
     * SCENARIO 11: Zero pricing — no cost/price/margin columns anywhere.
     */
    public function test_boq_tables_have_zero_pricing_or_cost_columns(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $forbiddenColumns = [
            'price', 'unit_price', 'unit_rate', 'total_price', 'amount', 'total',
            'cost', 'unit_cost', 'total_cost',
            'margin', 'markup', 'profit', 'tax', 'discount', 'selling_price',
        ];

        foreach ($forbiddenColumns as $col) {
            $this->assertFalse(
                Schema::hasColumn('boqs', $col),
                "boqs table must NOT contain commercial pricing column '{$col}'"
            );
            $this->assertFalse(
                Schema::hasColumn('boq_items', $col),
                "boq_items table must NOT contain commercial pricing column '{$col}'"
            );
        }
    }

    /**
     * SCENARIO 12: Compatible units aggregate deterministically —
     * one scope item over two m2 measurement items sums net quantities.
     */
    public function test_quantity_aggregation_sums_compatible_units_with_deductions(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create(['name' => 'Aggregation Client', 'phone' => '01077778888', 'status' => 'active']);
        $opportunity = Opportunity::create(['customer_id' => $customer->id, 'title' => 'Aggregation Opp', 'stage' => 'Proposal']);
        $measurement = Measurement::create([
            'opportunity_id' => $opportunity->id, 'measurement_number' => 'M-AGG-001', 'status' => 'Approved',
        ]);

        // Gross 100 - deductions 15 = net 85 ; plus 40 => 125 total
        $itemA = $measurement->items()->create([
            'room_name' => 'Hall', 'item_name' => 'Paint Walls', 'unit' => 'm2',
            'measurement_type' => 'area', 'count' => 1, 'length' => 10, 'width' => 10,
            'deductions' => 15.00, 'gross_quantity' => 100.00, 'net_quantity' => 85.00,
        ]);
        $itemB = $measurement->items()->create([
            'room_name' => 'Bedroom', 'item_name' => 'Paint Walls', 'unit' => 'm2',
            'measurement_type' => 'area', 'count' => 1, 'length' => 8, 'width' => 5,
            'deductions' => 0, 'gross_quantity' => 40.00, 'net_quantity' => 40.00,
        ]);

        $scope = Scope::create([
            'opportunity_id' => $opportunity->id, 'measurement_id' => $measurement->id,
            'scope_number' => 'SC-AGG-001', 'status' => 'Approved',
        ]);
        $sItem = $scope->items()->create([
            'trade_category' => 'أعمال الدهانات', 'item_name' => 'دهان حوائط بلاستيك',
        ]);
        $sItem->measurementItems()->sync([$itemA->id, $itemB->id]);

        $response = $this->withHeaders($this->headersA())->postJson('/api/boqs', [
            'opportunity_id' => $opportunity->id,
            'scope_id' => $scope->id,
        ]);

        $response->assertStatus(200);
        $lines = $response->json('data.items');
        $this->assertCount(1, $lines);
        $this->assertEquals('m2', $lines[0]['unit']);
        // Net quantities (post-deduction) summed, never gross, never invented
        $this->assertEquals(125.00, (float) $lines[0]['quantity']);
        $this->assertEquals($sItem->id, $lines[0]['scope_item_id']);
    }

    /**
     * SCENARIO 13: BOQ drives the opportunity chain next actions through
     * to Estimation readiness.
     */
    public function test_boq_drives_chain_next_actions_to_estimation_readiness(): void
    {
        $chain = $this->makeApprovedScope('S13');
        $opportunity = $chain['opportunity'];
        $scope = $chain['scope'];

        // The chain requires at least one site visit before quantity stages
        TenantDatabaseManager::switchToTenant($this->tenantA);
        SiteVisit::create([
            'customer_id' => $chain['customer']->id,
            'opportunity_id' => $opportunity->id,
            'status' => 'Completed',
            'scheduled_date' => '2026-09-20',
            'visit_date' => '2026-09-20',
            'general_assessment' => 'معاينة ميدانية توثيقية',
            'created_by' => $this->ownerA->id,
        ]);

        $res0 = $this->withHeaders($this->headersA())->getJson("/api/opportunities/{$opportunity->id}/chain");
        $res0->assertOk()->assertJsonPath('data.next_action.key', 'create_boq');

        $createRes = $this->withHeaders($this->headersA())->postJson('/api/boqs', [
            'opportunity_id' => $opportunity->id,
            'scope_id' => $scope->id,
        ]);
        $createRes->assertStatus(200);
        $boqId = $createRes->json('data.id');

        $res1 = $this->withHeaders($this->headersA())->getJson("/api/opportunities/{$opportunity->id}/chain");
        $res1->assertOk()->assertJsonPath('data.next_action.key', 'submit_boq_for_review');

        $this->withHeaders($this->headersA())->postJson("/api/boqs/{$boqId}/submit-review")->assertStatus(200);

        $res2 = $this->withHeaders($this->headersA())->getJson("/api/opportunities/{$opportunity->id}/chain");
        $res2->assertOk()->assertJsonPath('data.next_action.key', 'approve_boq');

        $this->withHeaders($this->headersA())->postJson("/api/boqs/{$boqId}/approve")->assertStatus(200);

        $res3 = $this->withHeaders($this->headersA())->getJson("/api/opportunities/{$opportunity->id}/chain");
        $res3->assertOk()
            ->assertJsonPath('data.next_action.key', 'ready_for_estimation')
            ->assertJsonPath('data.next_action.stage', 'estimation')
            ->assertJsonPath('data.approved_boq.id', $boqId)
            ->assertJsonPath('data.current_status.boq.has_approved', true)
            ->assertJsonPath('data.current_status.boq.code', $res3->json('data.approved_boq.boq_number'));
    }

    /**
     * SCENARIO 14: Unauthorized user without BOQ permissions receives 403.
     */
    public function test_unauthorized_user_cannot_access_boq_endpoints(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $restrictedUser = User::create([
            'name' => 'BOQ Restricted Worker',
            'email' => 'boq-worker@alpha.test',
            'password' => bcrypt('Pass123!'),
            'status' => 'active',
        ]);
        $restrictedToken = $restrictedUser->createToken('restricted_token')->plainTextToken;

        $headers = [
            'X-Tenant-Slug' => $this->slugA,
            'Authorization' => "Bearer {$restrictedToken}",
            'Accept' => 'application/json',
        ];

        $this->withHeaders($headers)->getJson('/api/boqs')->assertForbidden();
    }
}
