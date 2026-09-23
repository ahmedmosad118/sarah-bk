<?php

namespace Tests\Feature;

use App\Core\Tenancy\TenantDatabaseManager;
use App\Models\Central\Tenant;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\SiteVisit;
use App\Models\SiteVisitRoom;
use App\Models\User;
use App\Services\Tenant\TenantProvisioningService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SiteVisitManagementTest extends TestCase
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

        $this->slugA = 'visit-alpha-' . time() . '-' . rand(10, 99);
        $this->slugB = 'visit-beta-' . time() . '-' . rand(10, 99);

        // Provision Tenant A
        $resA = $this->provisioningService->provision([
            'name' => 'Alpha Site Inspections Enterprise',
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
            'name' => 'Beta Site Inspections Enterprise',
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
     * 1. Test creating an independent site visit directly with customer and rooms.
     */
    public function test_can_create_independent_site_visit(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'name' => 'Eng. Ahmed Tarek',
            'customer_type' => 'individual',
            'phone' => '01011112222',
            'email' => 'ahmed@tarek.test',
            'status' => 'active',
        ]);

        $engineer = User::create([
            'name' => 'Eng. Field Inspector',
            'email' => 'inspector@alpha.test',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson('/api/site-visits', [
            'customer_id' => $customer->id,
            'status' => 'Scheduled',
            'scheduled_date' => '2026-10-15',
            'scheduled_time' => '14:00',
            'assigned_to' => $engineer->id,
            'internal_notes' => 'Direct inspection request for architectural evaluation.',
            'rooms' => [
                [
                    'room_name' => 'Main Reception',
                    'estimated_area' => 48.50,
                    'notes' => 'Open ceiling space with double height.',
                ],
                [
                    'room_name' => 'Master Suite',
                    'estimated_area' => 32.00,
                    'notes' => 'Ensuite bathroom inspection needed.',
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.customer_id', $customer->id)
            ->assertJsonPath('data.status', 'Scheduled');

        $this->assertStringContainsString('14:00', (string) $response->json('data.scheduled_time'));

        $siteVisitId = $response->json('data.id');
        $this->assertDatabaseHas('site_visits', [
            'id' => $siteVisitId,
            'customer_id' => $customer->id,
            'opportunity_id' => null,
            'status' => 'Scheduled',
        ]);

        $this->assertDatabaseHas('site_visit_rooms', [
            'site_visit_id' => $siteVisitId,
            'room_name' => 'Main Reception',
            'estimated_area' => 48.50,
        ]);
    }

    /**
     * 2. Test creating a site visit directly from an existing Opportunity.
     */
    public function test_can_create_site_visit_from_opportunity(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'name' => 'Delta Construction Client',
            'customer_type' => 'company',
            'phone' => '01033334444',
            'status' => 'active',
        ]);

        $lead = Lead::create([
            'customer_id' => $customer->id,
            'title' => 'Administrative HQ Finishing',
            'status' => 'Converted',
        ]);

        $opportunity = Opportunity::create([
            'customer_id' => $customer->id,
            'lead_id' => $lead->id,
            'title' => 'HQ New Cairo Finishing',
            'stage' => 'Qualified',
            'estimated_value' => 850000.00,
            'created_by' => $this->ownerA->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson("/api/site-visits/from-opportunity/{$opportunity->id}", [
            'scheduled_date' => '2026-10-20',
            'scheduled_time' => '11:30',
            'internal_notes' => 'Inspection directly tied to HQ opportunity.',
            'rooms' => [
                ['room_name' => 'Boardroom', 'estimated_area' => 60.00],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.customer_id', $customer->id)
            ->assertJsonPath('data.opportunity_id', $opportunity->id)
            ->assertJsonPath('data.lead_id', $lead->id)
            ->assertJsonPath('data.status', 'Scheduled');

        $visitId = $response->json('data.id');
        $this->assertDatabaseHas('site_visits', [
            'id' => $visitId,
            'opportunity_id' => $opportunity->id,
            'lead_id' => $lead->id,
        ]);
    }

    /**
     * 3. Test scheduling and completing a site visit with mandatory validation.
     */
    public function test_can_schedule_and_complete_site_visit(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'name' => 'Villa Owner Omar',
            'customer_type' => 'individual',
            'phone' => '01055556666',
            'status' => 'active',
        ]);

        $visit = SiteVisit::create([
            'customer_id' => $customer->id,
            'status' => 'Scheduled',
            'scheduled_date' => '2026-10-10',
            'created_by' => $this->ownerA->id,
        ]);

        // 3a. Reschedule visit
        $rescheduleResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson("/api/site-visits/{$visit->id}/schedule", [
            'scheduled_date' => '2026-10-18',
            'scheduled_time' => '16:00',
            'internal_notes' => 'Rescheduled per client request.',
        ]);

        $rescheduleResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'Rescheduled');

        $this->assertDatabaseHas('site_visits', [
            'id' => $visit->id,
            'status' => 'Rescheduled',
            'scheduled_date' => '2026-10-18',
        ]);

        // 3b. Complete visit without required visit_date and general_assessment -> should fail 422
        $invalidCompleteResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson("/api/site-visits/{$visit->id}/complete", []);

        $invalidCompleteResponse->assertStatus(422)
            ->assertJsonValidationErrors(['visit_date', 'general_assessment']);

        // 3c. Complete visit successfully with assessment and rooms
        $completeResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson("/api/site-visits/{$visit->id}/complete", [
            'visit_date' => '2026-10-18',
            'general_assessment' => 'Site is structurally sound. Ready for MEP rough-ins and wall plastering.',
            'internal_notes' => 'Client requested premium Italian porcelain.',
            'rooms' => [
                ['room_name' => 'Reception Area', 'estimated_area' => 55.00, 'notes' => 'Requires leveling screed'],
                ['room_name' => 'Kitchen', 'estimated_area' => 18.50, 'notes' => 'Plumbing pipes need realignment'],
            ],
        ]);

        $completeResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'Completed')
            ->assertJsonPath('data.general_assessment', 'Site is structurally sound. Ready for MEP rough-ins and wall plastering.');

        $this->assertDatabaseHas('site_visits', [
            'id' => $visit->id,
            'status' => 'Completed',
            'visit_date' => '2026-10-18',
        ]);

        $this->assertDatabaseHas('site_visit_rooms', [
            'site_visit_id' => $visit->id,
            'room_name' => 'Kitchen',
            'estimated_area' => 18.50,
        ]);
    }

    /**
     * 4. Test cancelling a site visit.
     */
    public function test_can_cancel_site_visit(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'name' => 'Client To Cancel',
            'customer_type' => 'individual',
            'phone' => '01077778888',
            'status' => 'active',
        ]);

        $visit = SiteVisit::create([
            'customer_id' => $customer->id,
            'status' => 'Scheduled',
            'scheduled_date' => '2026-10-25',
            'created_by' => $this->ownerA->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson("/api/site-visits/{$visit->id}/cancel", [
            'internal_notes' => 'Client postponed inspection indefinitely.',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'Cancelled');

        $this->assertDatabaseHas('site_visits', [
            'id' => $visit->id,
            'status' => 'Cancelled',
        ]);
    }

    /**
     * 5. Test assigning an engineer to a site visit.
     */
    public function test_can_assign_engineer_to_site_visit(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'name' => 'Commercial Tower Management',
            'customer_type' => 'company',
            'phone' => '01099990000',
            'status' => 'active',
        ]);

        $engineer = User::create([
            'name' => 'Eng. Mostafa Civil',
            'email' => 'mostafa@alpha.test',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $visit = SiteVisit::create([
            'customer_id' => $customer->id,
            'status' => 'Scheduled',
            'created_by' => $this->ownerA->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson("/api/site-visits/{$visit->id}/assign", [
            'assigned_to' => $engineer->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.assigned_to', $engineer->id);

        $this->assertDatabaseHas('site_visits', [
            'id' => $visit->id,
            'assigned_to' => $engineer->id,
        ]);
    }

    /**
     * 6. Test uploading site photos to media collection.
     */
    public function test_can_upload_site_photos(): void
    {
        Storage::fake('public');
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $customer = Customer::create([
            'name' => 'Photo Inspection Customer',
            'customer_type' => 'individual',
            'phone' => '01012345678',
            'status' => 'active',
        ]);

        $visit = SiteVisit::create([
            'customer_id' => $customer->id,
            'status' => 'Scheduled',
            'created_by' => $this->ownerA->id,
        ]);

        $file1 = UploadedFile::fake()->image('reception_view.jpg', 800, 600);
        $file2 = UploadedFile::fake()->image('electrical_panel.png', 800, 600);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson("/api/site-visits/{$visit->id}/photos", [
            'photos' => [$file1, $file2],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertCount(2, $visit->fresh()->getMedia('site_photos'));
    }

    /**
     * 7. Test Tenant Isolation & IDOR Protection.
     */
    public function test_tenant_isolation_and_idor_protection(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantB);

        $customerB = Customer::create([
            'name' => 'Beta Tenant Customer',
            'customer_type' => 'company',
            'phone' => '01122223333',
            'status' => 'active',
        ]);

        $visitB = SiteVisit::create([
            'customer_id' => $customerB->id,
            'status' => 'Scheduled',
            'created_by' => $this->ownerB->id,
        ]);

        // Tenant A attempts to access Tenant B's site visit -> should return 404
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->getJson("/api/site-visits/{$visitB->id}");

        $response->assertStatus(404);

        // Tenant A attempts to complete Tenant B's site visit -> should return 404
        $completeResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->tokenA,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson("/api/site-visits/{$visitB->id}/complete", [
            'visit_date' => '2026-10-20',
            'general_assessment' => 'Unauthorized attempt.',
        ]);

        $completeResponse->assertStatus(404);
    }

    /**
     * 8. Test RBAC Permissions enforcement.
     */
    public function test_rbac_permission_enforcement(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenantA);

        $restrictedUser = User::create([
            'name' => 'Restricted Viewer',
            'email' => 'restricted@alpha.test',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $role = Role::create(['name' => 'Limited Site Inspector', 'guard_name' => 'web']);
        $role->givePermissionTo('site_visits.view');
        $restrictedUser->assignRole($role);

        $tokenRestricted = $restrictedUser->createToken('restricted_token')->plainTextToken;

        $customer = Customer::create([
            'name' => 'Permission Test Client',
            'customer_type' => 'individual',
            'phone' => '01088889999',
            'status' => 'active',
        ]);

        // User CAN view list
        $viewResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $tokenRestricted,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->getJson('/api/site-visits');

        $viewResponse->assertStatus(200);

        // User CANNOT create site visit -> should return 403
        $createResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $tokenRestricted,
            'X-Tenant-Slug' => $this->slugA,
            'Accept' => 'application/json',
        ])->postJson('/api/site-visits', [
            'customer_id' => $customer->id,
            'status' => 'Scheduled',
        ]);

        $createResponse->assertStatus(403);
    }
}
