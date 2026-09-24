<?php

namespace Tests\Feature;

use App\Core\Tenancy\TenantDatabaseManager;
use App\Models\Central\Tenant;
use App\Models\Customer;
use App\Models\Measurement;
use App\Models\MeasurementItem;
use App\Models\Opportunity;
use App\Models\Scope;
use App\Models\ScopeItem;
use App\Models\User;
use App\Services\Tenant\TenantProvisioningService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ScopeManagementTest extends TestCase
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

        $this->slugA = str_replace('.', '', uniqid('scope-a-', true)) . rand(100, 999);
        $this->slugB = str_replace('.', '', uniqid('scope-b-', true)) . rand(100, 999);

        // Provision Tenant A
        $resA = $this->provisioningService->provision([
            'name' => 'Alpha Engineering Solutions',
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
            'name' => 'Beta Engineering Solutions',
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
     * SCENARIO 1: Opportunity with Approved Measurement -> Create Scope -> Add items & link measurement items -> Approve.
     */
    public function test_can_create_scope_from_approved_measurement_and_approve(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'name' => 'Commercial Tower Client',
            'customer_type' => 'company',
            'phone' => '01011112222',
            'status' => 'active',
        ]);

        $opportunity = Opportunity::create([
            'customer_id' => $customer->id,
            'title' => 'Commercial Mall Fit-Out Scope',
            'stage' => 'Proposal',
            'estimated_value' => 750000.00,
        ]);

        $measurement = Measurement::create([
            'opportunity_id' => $opportunity->id,
            'measurement_number' => 'M-OPP1-0001',
            'version' => 1,
            'status' => 'Approved', // Approved technical baseline
            'total_area' => 150.00,
            'total_linear' => 30.00,
        ]);

        $mItem1 = $measurement->items()->create([
            'room_name' => 'Retail Hall',
            'item_name' => 'Floor Porcelain Tiles',
            'unit' => 'm2',
            'measurement_type' => 'area',
            'count' => 1,
            'length' => 10,
            'width' => 15,
            'gross_quantity' => 150.00,
            'net_quantity' => 150.00,
        ]);

        $mItem2 = $measurement->items()->create([
            'room_name' => 'Retail Hall',
            'item_name' => 'Skirting Cornice',
            'unit' => 'lm',
            'measurement_type' => 'linear',
            'count' => 1,
            'length' => 30,
            'gross_quantity' => 30.00,
            'net_quantity' => 30.00,
        ]);

        // Create Scope via API
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson('/api/scopes', [
            'opportunity_id' => $opportunity->id,
            'measurement_id' => $measurement->id,
            'title' => 'نطاق الأعمال والمواصفات الفنية للفرع التجاري',
            'general_inclusions' => 'يشمل التوريد والتركيب والتشوين والمصنعيات ونقل المخلفات.',
            'general_exclusions' => 'يستثنى رسوم الكهرباء والمياه وأعمال التكييف المركزي.',
            'items' => [
                [
                    'trade_category' => 'أعمال الأرضيات والتكسيات',
                    'item_name' => 'توريد وتركيب أرضيات بورسلين 60×120 سم فرز أول',
                    'specification' => 'بورسلين ليزر إسباني سمك 9 مم، تثبيت بمونة أسمنتية وإضافات سيروبوند، سقية إيبوكسي.',
                    'inclusions' => 'شامل توريد البورسلين والمواد اللاصقة وسقية الإيبوكسي.',
                    'exclusions' => 'يستثنى تعديل الميول الخرسانية القائمة.',
                    'measurement_item_ids' => [$mItem1->id],
                ],
                [
                    'trade_category' => 'أعمال الأرضيات والتكسيات',
                    'item_name' => 'توريد وتركيب وزرات رخامية بارتفاع 10 سم',
                    'specification' => 'رخام كرارة تركي مع جلي وتلميع.',
                    'measurement_item_ids' => [$mItem2->id],
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.opportunity_id', $opportunity->id)
            ->assertJsonPath('data.measurement_id', $measurement->id)
            ->assertJsonPath('data.status', 'Draft');

        $scopeId = $response->json('data.id');
        $this->assertCount(2, $response->json('data.items'));

        // Submit for review
        $reviewRes = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson("/api/scopes/{$scopeId}/submit-review");

        $reviewRes->assertStatus(200)
            ->assertJsonPath('data.status', 'Under Review');

        // Approve Scope
        $approveRes = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson("/api/scopes/{$scopeId}/approve");

        $approveRes->assertStatus(200)
            ->assertJsonPath('data.status', 'Approved');

        $this->assertDatabaseHas('scopes', [
            'id' => $scopeId,
            'status' => 'Approved',
        ]);

        // Verify Opportunity approvedScope relationship
        $opp = Opportunity::with('approvedScope')->find($opportunity->id);
        $this->assertNotNull($opp->approvedScope);
        $this->assertEquals($scopeId, $opp->approvedScope->id);
    }

    /**
     * SCENARIO 2: Attempt to create Scope on non-approved (Draft) Measurement -> MUST return 422.
     */
    public function test_cannot_create_scope_on_draft_measurement(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'name' => 'Draft Measurement Client',
            'customer_type' => 'individual',
            'phone' => '01033334444',
            'status' => 'active',
        ]);

        $opportunity = Opportunity::create([
            'customer_id' => $customer->id,
            'title' => 'Pending Measurement Opp',
            'stage' => 'Proposal',
        ]);

        $draftMeasurement = Measurement::create([
            'opportunity_id' => $opportunity->id,
            'measurement_number' => 'M-DRAFT-001',
            'version' => 1,
            'status' => 'Draft', // NOT APPROVED!
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson('/api/scopes', [
            'opportunity_id' => $opportunity->id,
            'measurement_id' => $draftMeasurement->id,
            'items' => [
                [
                    'trade_category' => 'أعمال الدهانات',
                    'item_name' => 'دهان حوائط',
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['measurement_id']);
    }

    /**
     * SCENARIO 3: Attempt to link MeasurementItem from a different measurement -> MUST return 422.
     */
    public function test_cannot_link_measurement_item_from_another_measurement(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'name' => 'Integrity Check Client',
            'customer_type' => 'individual',
            'phone' => '01055556666',
            'status' => 'active',
        ]);

        $opportunity = Opportunity::create([
            'customer_id' => $customer->id,
            'title' => 'Integrity Project',
            'stage' => 'Proposal',
        ]);

        // Measurement A (Approved)
        $measA = Measurement::create([
            'opportunity_id' => $opportunity->id,
            'measurement_number' => 'M-A-001',
            'status' => 'Approved',
        ]);

        // Measurement B (Belongs to different opp or separate stream)
        $measB = Measurement::create([
            'opportunity_id' => $opportunity->id,
            'measurement_number' => 'M-B-001',
            'status' => 'Approved',
        ]);

        $itemB = $measB->items()->create([
            'room_name' => 'Foreign Room',
            'item_name' => 'Foreign Item',
            'unit' => 'm2',
            'net_quantity' => 50,
        ]);

        // Attempting to create Scope for Meas A while referencing Item from Meas B -> 422
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson('/api/scopes', [
            'opportunity_id' => $opportunity->id,
            'measurement_id' => $measA->id,
            'items' => [
                [
                    'trade_category' => 'أعمال الدهانات',
                    'item_name' => 'دهانات ديكورية',
                    'measurement_item_ids' => [$itemB->id], // INVALID: Item belongs to Meas B!
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.measurement_item_ids']);
    }

    /**
     * SCENARIO 4: Approved Scope cannot be casually modified or deleted -> MUST return 422.
     */
    public function test_approved_scope_cannot_be_modified_or_deleted(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'name' => 'Immutable Scope Client',
            'customer_type' => 'company',
            'phone' => '01077778888',
            'status' => 'active',
        ]);

        $opportunity = Opportunity::create([
            'customer_id' => $customer->id,
            'title' => 'Locked Scope Project',
            'stage' => 'Won',
        ]);

        $measurement = Measurement::create([
            'opportunity_id' => $opportunity->id,
            'measurement_number' => 'M-LOCK-001',
            'status' => 'Approved',
        ]);

        $scope = Scope::create([
            'opportunity_id' => $opportunity->id,
            'measurement_id' => $measurement->id,
            'scope_number' => 'SC-LOCK-001',
            'status' => 'Approved',
        ]);

        // Attempting to modify approved scope -> MUST return 422
        $updateRes = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->putJson("/api/scopes/{$scope->id}", [
            'title' => 'Unauthorized Scope Modification',
        ]);

        $updateRes->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        // Attempting to delete approved scope -> MUST return 422
        $deleteRes = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->deleteJson("/api/scopes/{$scope->id}");

        $deleteRes->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /**
     * SCENARIO 5: Scope Revision Lifecycle: V1 Approved -> V2 Created Draft -> V2 Approved -> V1 Superseded.
     */
    public function test_scope_revision_lifecycle_and_historical_preservation(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'name' => 'Revision Test Client',
            'customer_type' => 'company',
            'phone' => '01099990000',
            'status' => 'active',
        ]);

        $opportunity = Opportunity::create([
            'customer_id' => $customer->id,
            'title' => 'Multi-Revision Building Scope',
            'stage' => 'Proposal',
        ]);

        $measurement = Measurement::create([
            'opportunity_id' => $opportunity->id,
            'measurement_number' => 'M-BLD-001',
            'status' => 'Approved',
        ]);

        $v1 = Scope::create([
            'opportunity_id' => $opportunity->id,
            'measurement_id' => $measurement->id,
            'scope_number' => 'SC-BLD-001',
            'version' => 1,
            'status' => 'Approved',
            'title' => 'Scope V1 Original Specs',
        ]);

        $v1Item = $v1->items()->create([
            'trade_category' => 'أعمال الدهانات',
            'item_name' => 'دهانات بلاستيك وجهين',
            'specification' => 'مواصفة أصلية 1',
        ]);

        // Create Revision
        $revRes = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson("/api/scopes/{$v1->id}/create-revision");

        $revRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.version', 2)
            ->assertJsonPath('data.status', 'Draft')
            ->assertJsonPath('data.scope_number', 'SC-BLD-001-V2');

        $v2Id = $revRes->json('data.id');

        // Version 1 is still Approved while V2 is Draft
        $this->assertEquals('Approved', $v1->fresh()->status);

        // Update V2 with updated specification
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->putJson("/api/scopes/{$v2Id}", [
            'title' => 'Scope V2 Revised Specs',
            'items' => [
                [
                    'trade_category' => 'أعمال الدهانات',
                    'item_name' => 'دهانات بلاستيك 3 أوجه مع سيلر مقاوم للرطوبة',
                    'specification' => 'مواصفة معدلة 2',
                ],
            ],
        ]);

        // Approve V2
        $approveV2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson("/api/scopes/{$v2Id}/approve");

        $approveV2->assertStatus(200)
            ->assertJsonPath('data.status', 'Approved');

        // Assert: V1 is now Superseded, V2 is Approved
        $this->assertEquals('Superseded', $v1->fresh()->status);
        $this->assertEquals('Approved', Scope::find($v2Id)->status);

        // Assert: V1 historical specification is untouched
        $this->assertEquals('مواصفة أصلية 1', $v1Item->fresh()->specification);
    }

    /**
     * SCENARIO 6: Multi-Tenant Isolation & IDOR Protection across Tenants.
     */
    public function test_scope_tenant_isolation_and_idor_protection(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantB);

        $customerB = Customer::create([
            'name' => 'Beta Scope Customer',
            'customer_type' => 'company',
            'phone' => '01122224444',
            'status' => 'active',
        ]);

        $opportunityB = Opportunity::create([
            'customer_id' => $customerB->id,
            'title' => 'Beta Scope Opportunity',
            'stage' => 'Proposal',
        ]);

        $measurementB = Measurement::create([
            'opportunity_id' => $opportunityB->id,
            'measurement_number' => 'M-BETA-001',
            'status' => 'Approved',
        ]);

        $scopeB = Scope::create([
            'opportunity_id' => $opportunityB->id,
            'measurement_id' => $measurementB->id,
            'scope_number' => 'SC-BETA-001',
            'status' => 'Draft',
        ]);

        // Tenant A attempts to view Tenant B's scope -> 404
        $viewRes = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->getJson("/api/scopes/{$scopeB->id}");

        $viewRes->assertStatus(404);

        // Tenant A attempts to update Tenant B's scope -> 404
        $updateRes = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->putJson("/api/scopes/{$scopeB->id}", [
            'title' => 'Malicious Cross-Tenant Attack',
        ]);

        $updateRes->assertStatus(404);

        // Tenant A attempts to approve Tenant B's scope -> 404
        $approveRes = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson("/api/scopes/{$scopeB->id}/approve");

        $approveRes->assertStatus(404);
    }

    /**
     * SCENARIO 7: Pure Technical Scope — Absolute Zero Pricing / Margin Columns.
     */
    public function test_scope_tables_have_zero_pricing_or_cost_columns(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $forbiddenColumns = [
            'price', 'unit_price', 'total_price', 'cost', 'unit_cost', 'total_cost',
            'margin', 'markup', 'profit', 'tax', 'discount', 'selling_price'
        ];

        foreach ($forbiddenColumns as $col) {
            $this->assertFalse(
                Schema::hasColumn('scopes', $col),
                "scopes table must NOT contain commercial pricing column '{$col}'"
            );
            $this->assertFalse(
                Schema::hasColumn('scope_items', $col),
                "scope_items table must NOT contain commercial pricing column '{$col}'"
            );
            $this->assertFalse(
                Schema::hasColumn('scope_item_measurement_item', $col),
                "scope_item_measurement_item table must NOT contain commercial pricing column '{$col}'"
            );
        }
    }

    /**
     * SCENARIO 8: Status Bypass Protection — Passing 'status' => 'Approved' in store() is completely ignored and forced to Draft.
     */
    public function test_store_scope_ignores_status_field_and_forces_draft(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'name' => 'Bypass Test Customer',
            'customer_type' => 'individual',
            'phone' => '01012345678',
            'status' => 'active',
        ]);

        $opportunity = Opportunity::create([
            'customer_id' => $customer->id,
            'title' => 'Bypass Test Opp',
            'stage' => 'Proposal',
        ]);

        $measurement = Measurement::create([
            'opportunity_id' => $opportunity->id,
            'measurement_number' => 'M-BYP-001',
            'status' => 'Approved',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson('/api/scopes', [
            'opportunity_id' => $opportunity->id,
            'measurement_id' => $measurement->id,
            'title' => 'Malicious Status Injection Scope',
            'status' => 'Approved', // Injection attempt!
            'version' => 99, // Injection attempt!
            'approved_by' => 1,
            'approved_at' => now()->toDateTimeString(),
            'items' => [
                [
                    'trade_category' => 'أعمال عامة',
                    'item_name' => 'بند تجريبي',
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'Draft')
            ->assertJsonPath('data.version', 1);

        $scopeId = $response->json('data.id');
        $storedScope = Scope::find($scopeId);

        $this->assertEquals('Draft', $storedScope->status);
        $this->assertEquals(1, $storedScope->version);
        $this->assertNull($storedScope->approved_by);
        $this->assertNull($storedScope->approved_at);
    }

    /**
     * SCENARIO 9: Cannot have two approved scopes via direct status injection.
     */
    public function test_cannot_have_two_approved_scopes_via_direct_status_injection(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'name' => 'Dual Approve Customer',
            'customer_type' => 'individual',
            'phone' => '01087654321',
            'status' => 'active',
        ]);

        $opportunity = Opportunity::create([
            'customer_id' => $customer->id,
            'title' => 'Dual Approve Opp',
            'stage' => 'Proposal',
        ]);

        $measurement = Measurement::create([
            'opportunity_id' => $opportunity->id,
            'measurement_number' => 'M-DUAL-001',
            'status' => 'Approved',
        ]);

        // Legitimate approved Scope 1
        $scope1 = Scope::create([
            'opportunity_id' => $opportunity->id,
            'measurement_id' => $measurement->id,
            'scope_number' => 'SC-LEGIT-001',
            'status' => 'Approved',
            'approved_by' => $this->ownerA->id,
            'approved_at' => now(),
        ]);

        // Attacker attempts to post a second scope with status: Approved
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson('/api/scopes', [
            'opportunity_id' => $opportunity->id,
            'measurement_id' => $measurement->id,
            'title' => 'Illegitimate Second Approved Scope',
            'status' => 'Approved',
            'items' => [
                [
                    'trade_category' => 'أعمال عامة',
                    'item_name' => 'بند تجريبي 2',
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'Draft');

        // Only scope 1 remains Approved
        $approvedScopes = Scope::where('opportunity_id', $opportunity->id)->where('status', 'Approved')->get();
        $this->assertCount(1, $approvedScopes);
        $this->assertEquals($scope1->id, $approvedScopes->first()->id);
    }
}
