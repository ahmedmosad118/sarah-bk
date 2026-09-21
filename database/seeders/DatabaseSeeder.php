<?php

namespace Database\Seeders;

use App\Models\Central\Tenant;
use App\Services\Tenant\TenantProvisioningService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with demo tenant.
     */
    public function run(): void
    {
        if (!Tenant::where('slug', 'demo')->exists()) {
            app(TenantProvisioningService::class)->provision([
                'name' => 'شركة الصرح للمقاولات والتشطيبات',
                'slug' => 'demo',
                'company_code' => 'SARH-DEMO',
                'domain' => 'demo.localhost',
                'plan' => 'enterprise',
            ], [
                'name' => 'المهندس أحمد علي (مالك المنشأة)',
                'email' => 'owner@sarh.app',
                'phone' => '01000000000',
                'password' => 'password123',
            ]);
        }
    }
}
