<?php

namespace Tests\Feature;

use App\Core\Tenancy\TenantDatabaseManager;
use App\Models\Central\Tenant;
use App\Models\Customer;
use App\Models\Measurement;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\Tenant\TenantProvisioningService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class MeasurementDxfImportTest extends TestCase
{
    protected TenantProvisioningService $provisioningService;
    protected Tenant $tenant;
    protected User $owner;
    protected string $slug;
    protected string $token;
    protected string $dxfFixturePath;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate', [
            '--database' => 'central',
            '--path' => 'database/migrations/central',
            '--force' => true,
        ]);

        $this->provisioningService = app(TenantProvisioningService::class);
        $this->slug = str_replace('.', '', uniqid('dxf-test-', true)) . rand(100, 999);

        $res = $this->provisioningService->provision([
            'name' => 'DXF CAD Engineering Corp',
            'slug' => $this->slug,
            'company_code' => 'DXF-' . uniqid(),
            'domain' => $this->slug . '.localhost',
        ], [
            'name' => 'CAD Engineer',
            'email' => "cad@{$this->slug}.test",
            'password' => 'CadPass123!',
        ]);

        $this->tenant = $res['tenant'];
        $this->owner = $res['owner'];

        TenantDatabaseManager::switchToTenant($this->tenant);
        $this->token = $this->owner->createToken('token_cad')->plainTextToken;

        $this->dxfFixturePath = base_path('tests/Fixtures/sample_floor_plan.dxf');
    }

    protected function tearDown(): void
    {
        try {
            if (isset($this->tenant)) {
                TenantDatabaseManager::purgeTenantConnection($this->tenant);
            }
        } catch (\Throwable $e) {
            // ignore
        }

        parent::tearDown();
    }

    /**
     * TEST 1: parse-dxf returns correct layers and counts.
     */
    public function test_parse_dxf_returns_correct_layers_and_counts(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenant);

        $file = new UploadedFile(
            $this->dxfFixturePath,
            'sample_floor_plan.dxf',
            'application/dxf',
            null,
            true
        );

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'X-Tenant-Slug' => $this->slug,
            'Accept' => 'application/json',
        ])->postJson('/api/measurements/parse-dxf', [
            'file' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $layersFound = $response->json('data.layers_found');
        $this->assertContains('ROOMS', $layersFound);
        $this->assertContains('ROOM_LABELS', $layersFound);
        $this->assertContains('WALLS', $layersFound);

        // Polyline count on ROOMS layer = 3
        $this->assertEquals(3, $response->json('data.polyline_count_per_layer.ROOMS'));
    }

    /**
     * TEST 2 & 3 & 4: import-from-dxf calculates exact Shoelace areas and maps correct labels.
     * - RECEPTION = 20.0 m²
     * - BEDROOM 1 = 14.0 m²
     * - KITCHEN (L-SHAPE) = 11.1 m² (proving Shoelace formula for irregular polygon)
     */
    public function test_import_from_dxf_calculates_exact_shoelace_areas_and_matches_labels(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenant);

        $customer = Customer::create([
            'name' => 'CAD Client Villa',
            'customer_type' => 'individual',
            'phone' => '01011113333',
            'status' => 'active',
        ]);

        $opportunity = Opportunity::create([
            'customer_id' => $customer->id,
            'title' => 'Villa Floor Plan Finishing',
            'stage' => 'Proposal',
        ]);

        $file = new UploadedFile(
            $this->dxfFixturePath,
            'sample_floor_plan.dxf',
            'application/dxf',
            null,
            true
        );

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'X-Tenant-Slug' => $this->slug,
            'Accept' => 'application/json',
        ])->postJson('/api/measurements/import-from-dxf', [
            'file' => $file,
            'rooms_layer' => 'ROOMS',
            'labels_layer' => 'ROOM_LABELS',
            'opportunity_id' => $opportunity->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'Draft')
            ->assertJsonPath('data.opportunity_id', $opportunity->id);

        $items = $response->json('data.items');
        $this->assertCount(3, $items);

        $reception = collect($items)->firstWhere('room_name', 'RECEPTION');
        $this->assertNotNull($reception, 'RECEPTION room must be detected and mapped');
        $this->assertEquals(20.00, (float) $reception['net_quantity']);

        $bedroom = collect($items)->firstWhere('room_name', 'BEDROOM 1');
        $this->assertNotNull($bedroom, 'BEDROOM 1 room must be detected and mapped');
        $this->assertEquals(14.00, (float) $bedroom['net_quantity']);

        $kitchen = collect($items)->firstWhere('room_name', 'KITCHEN (L-SHAPE)');
        $this->assertNotNull($kitchen, 'KITCHEN (L-SHAPE) room must be detected and mapped');
        // Irregular 5/6-sided polygon Shoelace calculation MUST equal 11.10 m²
        $this->assertEquals(11.10, (float) $kitchen['net_quantity']);

        // Total Area = 20.0 + 14.0 + 11.1 = 45.10 m²
        $this->assertEquals(45.10, (float) $response->json('data.total_area'));
    }

    /**
     * TEST 5: Rejects file with .dxf extension that is not a valid DXF file.
     */
    public function test_import_rejects_corrupted_dxf_file(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenant);

        $customer = Customer::create([
            'name' => 'Corrupt File Customer',
            'customer_type' => 'individual',
            'phone' => '01044445555',
            'status' => 'active',
        ]);

        $opportunity = Opportunity::create([
            'customer_id' => $customer->id,
            'title' => 'Corrupt CAD Opp',
            'stage' => 'New',
        ]);

        $fakeDxf = UploadedFile::fake()->createWithContent('fake_drawing.dxf', 'This is plain text with no DXF structure.');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'X-Tenant-Slug' => $this->slug,
            'Accept' => 'application/json',
        ])->postJson('/api/measurements/import-from-dxf', [
            'file' => $fakeDxf,
            'rooms_layer' => 'ROOMS',
            'labels_layer' => 'ROOM_LABELS',
            'opportunity_id' => $opportunity->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /**
     * TEST 6: Rejects .dwg or .pdf files explicitly with unsupported format message.
     */
    public function test_import_rejects_dwg_and_pdf_files_with_clear_message(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenant);

        $customer = Customer::create([
            'name' => 'DWG Test Customer',
            'customer_type' => 'individual',
            'phone' => '01066667777',
            'status' => 'active',
        ]);

        $opportunity = Opportunity::create([
            'customer_id' => $customer->id,
            'title' => 'DWG CAD Opp',
            'stage' => 'New',
        ]);

        // Attempting DWG
        $dwgFile = UploadedFile::fake()->create('blueprint.dwg', 1024, 'application/acad');
        $responseDwg = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'X-Tenant-Slug' => $this->slug,
            'Accept' => 'application/json',
        ])->postJson('/api/measurements/import-from-dxf', [
            'file' => $dwgFile,
            'rooms_layer' => 'ROOMS',
            'opportunity_id' => $opportunity->id,
        ]);

        $responseDwg->assertStatus(422)
            ->assertJsonValidationErrors(['file']);

        // Attempting PDF
        $pdfFile = UploadedFile::fake()->create('blueprint.pdf', 1024, 'application/pdf');
        $responsePdf = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'X-Tenant-Slug' => $this->slug,
            'Accept' => 'application/json',
        ])->postJson('/api/measurements/import-from-dxf', [
            'file' => $pdfFile,
            'rooms_layer' => 'ROOMS',
            'opportunity_id' => $opportunity->id,
        ]);

        $responsePdf->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /**
     * TEST 7: Rejects request if opportunity_id is missing.
     */
    public function test_import_from_dxf_requires_explicit_opportunity_id(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenant);

        $file = new UploadedFile(
            $this->dxfFixturePath,
            'sample_floor_plan.dxf',
            'application/dxf',
            null,
            true
        );

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'X-Tenant-Slug' => $this->slug,
            'Accept' => 'application/json',
        ])->postJson('/api/measurements/import-from-dxf', [
            'file' => $file,
            'rooms_layer' => 'ROOMS',
            'labels_layer' => 'ROOM_LABELS',
            // Missing opportunity_id
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['opportunity_id']);
    }

    /**
     * TEST 8: Resulting DXF measurement is strictly in Draft status and not auto-approved.
     */
    public function test_resulting_dxf_measurement_is_strictly_draft(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenant);

        $customer = Customer::create([
            'name' => 'Draft Status Verification Client',
            'customer_type' => 'individual',
            'phone' => '01088889999',
            'status' => 'active',
        ]);

        $opportunity = Opportunity::create([
            'customer_id' => $customer->id,
            'title' => 'Draft Status Opp',
            'stage' => 'Qualified',
        ]);

        $file = new UploadedFile(
            $this->dxfFixturePath,
            'sample_floor_plan.dxf',
            'application/dxf',
            null,
            true
        );

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'X-Tenant-Slug' => $this->slug,
            'Accept' => 'application/json',
        ])->postJson('/api/measurements/import-from-dxf', [
            'file' => $file,
            'rooms_layer' => 'ROOMS',
            'labels_layer' => 'ROOM_LABELS',
            'opportunity_id' => $opportunity->id,
        ]);

        $response->assertStatus(200);
        $measurementId = $response->json('data.id');

        $measurement = Measurement::findOrFail($measurementId);
        $this->assertEquals('Draft', $measurement->status);
        $this->assertNull($measurement->approved_at);
        $this->assertNull($measurement->approved_by);
    }
}
