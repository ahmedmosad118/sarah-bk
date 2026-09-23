<?php

namespace Tests\Feature;

use App\Core\Tenancy\TenantDatabaseManager;
use App\Models\Central\Tenant;
use App\Models\JobTitle;
use App\Models\User;
use App\Services\Tenant\TenantProvisioningService;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserAndRoleManagementTest extends TestCase
{
    protected Tenant $tenant;
    protected User $owner;
    protected string $slug;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate', [
            '--database' => 'central',
            '--path' => 'database/migrations/central',
            '--force' => true,
        ]);

        $this->slug = 'roles-test-' . time();
        $res = app(TenantProvisioningService::class)->provision([
            'name' => 'Roles Test Company',
            'slug' => $this->slug,
            'company_code' => 'ROLES-' . uniqid(),
            'domain' => $this->slug . '.localhost',
        ], [
            'name' => 'Owner Account',
            'email' => "owner_{$this->slug}@sarh.test",
            'password' => 'secret123',
        ]);

        $this->tenant = $res['tenant'];
        $this->owner = $res['owner'];
    }

    protected function tearDown(): void
    {
        TenantDatabaseManager::dropDatabase($this->tenant);
        $this->tenant->domains()->delete();
        $this->tenant->delete();

        parent::tearDown();
    }

    public function test_can_create_user_with_job_title_and_multiple_roles(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenant);

        $siteEngineerTitle = JobTitle::where('name', 'Site Engineer')->first();
        $this->assertNotNull($siteEngineerTitle);

        // 1. Create user with Job Title = Site Engineer
        $user = User::create([
            'name' => 'م. كريم محمود',
            'email' => 'karim@sarh.test',
            'password' => bcrypt('password123'),
            'job_title_id' => $siteEngineerTitle->id,
            'status' => 'active',
        ]);

        $this->assertEquals($siteEngineerTitle->id, $user->job_title_id);
        $this->assertEquals('Site Engineer', $user->jobTitle->name);

        // 2. Assign Multiple Roles: Site Engineer + Procurement
        $user->assignRole(['Site Engineer', 'Procurement']);

        $this->assertTrue($user->hasRole('Site Engineer'));
        $this->assertTrue($user->hasRole('Procurement'));

        // 3. Verify user inherits permissions from BOTH roles
        // From Site Engineer:
        $this->assertTrue($user->hasPermissionTo('site_visits.create'));
        $this->assertTrue($user->hasPermissionTo('measurements.create'));

        // From Procurement:
        $this->assertTrue($user->hasPermissionTo('procurement.request'));
        $this->assertTrue($user->hasPermissionTo('suppliers.view'));

        // 4. Critical Rule Test: User does NOT have unauthorized permissions outside their roles (e.g. Invoices Approval)
        $this->assertFalse($user->hasPermissionTo('invoices.approve'));
        $this->assertFalse($user->hasPermissionTo('users.delete'));
    }

    public function test_job_title_does_not_control_authorization(): void
    {
        TenantDatabaseManager::switchToTenant($this->tenant);

        // A user with Job Title = "General Manager", but assigned role = "Viewer" (Read-Only)
        $gmTitle = JobTitle::where('name', 'General Manager')->first();
        $this->assertNotNull($gmTitle);

        $user = User::create([
            'name' => 'أحمد المراقب',
            'email' => 'viewer.gm@sarh.test',
            'password' => bcrypt('password123'),
            'job_title_id' => $gmTitle->id,
            'status' => 'active',
        ]);

        $user->assignRole('Viewer');

        // Can view dashboard and projects (granted by Viewer role)
        $this->assertTrue($user->hasPermissionTo('dashboard.view'));
        $this->assertTrue($user->hasPermissionTo('projects.view'));

        // CANNOT create projects or approve contracts even though his Job Title is "General Manager"
        $this->assertFalse($user->hasPermissionTo('projects.create'));
        $this->assertFalse($user->hasPermissionTo('contracts.approve'));
    }

    public function test_owner_can_list_roles_via_api(): void
    {
        $response = $this->actingAs($this->owner, 'sanctum')
            ->withHeaders(['X-Tenant-Slug' => $this->slug])
            ->getJson('/api/roles');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
            'modules',
            'total_roles',
            'total_permissions',
        ]);
    }

    public function test_user_can_update_profile(): void
    {
        $response = $this->actingAs($this->owner, 'sanctum')
            ->withHeaders(['X-Tenant-Slug' => $this->slug])
            ->putJson('/api/auth/profile', [
                'name' => 'المهندس أحمد المعدل',
                'email' => $this->owner->email,
                'phone' => '01099887766',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'user' => [
                    'name' => 'المهندس أحمد المعدل',
                    'phone' => '01099887766',
                ],
            ],
        ]);

        $this->assertEquals('المهندس أحمد المعدل', $this->owner->fresh()->name);
        $this->assertEquals('01099887766', $this->owner->fresh()->phone);
    }

    public function test_user_can_upload_avatar(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $avatarFile = \Illuminate\Http\UploadedFile::fake()->image('my-avatar.png', 200, 200);

        $response = $this->actingAs($this->owner, 'sanctum')
            ->withHeaders(['X-Tenant-Slug' => $this->slug])
            ->postJson('/api/auth/profile/avatar', [
                'avatar' => $avatarFile,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertNotNull($response->json('avatar_url'));
        $this->assertNotNull($this->owner->fresh()->avatar);
    }

    public function test_user_can_change_password(): void
    {
        $response = $this->actingAs($this->owner, 'sanctum')
            ->withHeaders(['X-Tenant-Slug' => $this->slug])
            ->postJson('/api/auth/change-password', [
                'current_password' => 'secret123',
                'new_password' => 'new-secret-password-123',
                'new_password_confirmation' => 'new-secret-password-123',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('new-secret-password-123', $this->owner->fresh()->password));
    }
}
