<?php

namespace Tests\Feature;

use App\Core\Tenancy\TenantDatabaseManager;
use App\Models\Central\Tenant;
use App\Models\User;
use App\Services\Tenant\TenantProvisioningService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityLogAndMediaIsolationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate', [
            '--database' => 'central',
            '--path' => 'database/migrations/central',
            '--force' => true,
        ]);
    }

    public function test_activity_log_and_media_are_isolated_per_tenant(): void
    {
        $service = app(TenantProvisioningService::class);

        $slug1 = 'act_iso_1_' . time();
        $slug2 = 'act_iso_2_' . time();

        $res1 = $service->provision([
            'name' => 'Tenant One',
            'slug' => $slug1,
            'domain' => $slug1 . '.localhost',
        ], [
            'name' => 'Owner 1',
            'email' => "owner@{$slug1}.test",
            'password' => 'secret123',
        ]);

        $res2 = $service->provision([
            'name' => 'Tenant Two',
            'slug' => $slug2,
            'domain' => $slug2 . '.localhost',
        ], [
            'name' => 'Owner 2',
            'email' => "owner@{$slug2}.test",
            'password' => 'secret123',
        ]);

        /** @var Tenant $tenant1 */
        $tenant1 = $res1['tenant'];
        /** @var Tenant $tenant2 */
        $tenant2 = $res2['tenant'];

        // 1. Perform action in Tenant 1
        TenantDatabaseManager::switchToTenant($tenant1);
        $user1 = $res1['owner'];

        activity('audit')
            ->performedOn($user1)
            ->causedBy($user1)
            ->log('Alpha confidential operation executed');

        // Assert log is present in Tenant 1 database
        $this->assertDatabaseHas('activity_log', [
            'description' => 'Alpha confidential operation executed',
        ]);

        // 2. Switch to Tenant 2
        TenantDatabaseManager::switchToTenant($tenant2);

        // Assert log does NOT exist in Tenant 2 database
        $this->assertDatabaseMissing('activity_log', [
            'description' => 'Alpha confidential operation executed',
        ]);

        // 3. Test Media Attachment in Tenant 1
        TenantDatabaseManager::switchToTenant($tenant1);
        Storage::fake('public');

        $fakeFile = UploadedFile::fake()->image('site_blueprint.jpg', 600, 600);
        $user1->addMedia($fakeFile)->toMediaCollection('avatar');

        $this->assertDatabaseHas('media', [
            'collection_name' => 'avatar',
            'file_name' => 'site_blueprint.jpg',
        ]);

        // Switch to Tenant 2 and assert media table has 0 records of Tenant 1
        TenantDatabaseManager::switchToTenant($tenant2);
        $this->assertDatabaseMissing('media', [
            'file_name' => 'site_blueprint.jpg',
        ]);

        // Clean up
        TenantDatabaseManager::dropDatabase($tenant1);
        TenantDatabaseManager::dropDatabase($tenant2);
        $res1['domain']->delete();
        $res2['domain']->delete();
        $tenant1->delete();
        $tenant2->delete();
    }
}
