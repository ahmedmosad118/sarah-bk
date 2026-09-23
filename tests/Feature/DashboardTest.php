<?php

namespace Tests\Feature;

use App\Core\Tenancy\TenantDatabaseManager;
use App\Models\Central\Tenant;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\SiteVisit;
use App\Models\User;
use App\Services\Tenant\TenantProvisioningService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    protected TenantProvisioningService $provisioningService;
    protected Tenant $tenant;
    protected User $owner;
    protected string $slug;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate', [
            '--database' => 'central',
            '--path' => 'database/migrations/central',
            '--force' => true,
        ]);

        $this->provisioningService = app(TenantProvisioningService::class);
        $this->slug = 'dash-test-' . time() . '-' . rand(10, 99);

        $res = $this->provisioningService->provision([
            'name' => 'Dashboard Commercial Enterprise',
            'slug' => $this->slug,
            'company_code' => 'DASH-' . uniqid(),
            'domain' => $this->slug . '.localhost',
        ], [
            'name' => 'Dashboard Owner',
            'email' => "owner@{$this->slug}.test",
            'password' => 'DashPass123!',
        ]);

        $this->tenant = $res['tenant'];
        $this->owner = $res['owner'];

        TenantDatabaseManager::switchToTenant($this->tenant);
        $this->token = $this->owner->createToken('dash_token')->plainTextToken;
    }

    protected function tearDown(): void
    {
        try {
            TenantDatabaseManager::dropDatabase($this->tenant);
            $this->tenant->domains()->delete();
            $this->tenant->delete();
        } catch (\Throwable $e) {
            // Ignore teardown errors
        }

        parent::tearDown();
    }

    public function test_dashboard_summary_returns_commercial_kpis_and_lists(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenant);

        // Seed data
        $custIndividual = Customer::create([
            'customer_type' => 'individual',
            'name' => 'Eng. Omar Khaled',
            'phone' => '01011112222',
            'created_by' => $this->owner->id,
        ]);

        $custCompany = Customer::create([
            'customer_type' => 'company',
            'name' => 'Al-Amal Towers Co.',
            'company_name' => 'Al-Amal Group',
            'phone' => '01122223333',
            'created_by' => $this->owner->id,
        ]);

        $lead = Lead::create([
            'customer_id' => $custIndividual->id,
            'title' => 'Villa Interior Finishing',
            'status' => 'New',
            'created_by' => $this->owner->id,
        ]);

        $opportunity = Opportunity::create([
            'customer_id' => $custCompany->id,
            'lead_id' => $lead->id,
            'title' => 'Administrative Building Fitout',
            'stage' => 'Proposal',
            'estimated_value' => 750000.00,
            'created_by' => $this->owner->id,
        ]);

        $today = Carbon::today()->toDateString();
        $visit = SiteVisit::create([
            'customer_id' => $custIndividual->id,
            'opportunity_id' => $opportunity->id,
            'status' => 'Scheduled',
            'scheduled_date' => $today,
            'scheduled_time' => '14:00',
            'created_by' => $this->owner->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ])->getJson('/api/dashboard/summary');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('stats.customers.total', 2)
            ->assertJsonPath('stats.customers.individual', 1)
            ->assertJsonPath('stats.customers.company', 1)
            ->assertJsonPath('stats.leads.total', 1)
            ->assertJsonPath('stats.leads.new', 1)
            ->assertJsonPath('stats.opportunities.total', 1)
            ->assertJsonPath('stats.opportunities.active', 1)
            ->assertJsonPath('stats.site_visits.total', 1)
            ->assertJsonPath('stats.site_visits.scheduled_today', 1);

        $this->assertNotEmpty($response->json('upcoming_visits'));
        $this->assertNotEmpty($response->json('active_opportunities'));
        $this->assertNotEmpty($response->json('recent_leads'));
    }
}
