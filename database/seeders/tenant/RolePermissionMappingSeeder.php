<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionMappingSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $allPermissions = Permission::all();

        // 1. Owner -> All Tenant Permissions
        $ownerRole = Role::where('name', 'Owner')->first();
        if ($ownerRole) {
            $ownerRole->syncPermissions($allPermissions);
        }

        // Helper to sync by name array
        $assign = function (string $roleName, array $permissionNames) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $existing = Permission::whereIn('name', $permissionNames)->get();
                $role->syncPermissions($existing);
            }
        };

        // 2. Super Admin
        $assign('Super Admin', [
            'dashboard.view',
            'users.view', 'users.create', 'users.update', 'users.delete', 'users.activate', 'users.deactivate',
            'roles.view', 'roles.create', 'roles.update', 'roles.delete',
            'permissions.view',
            'settings.view', 'settings.update',
            'company.view', 'company.update', 'company.settings',
            'reports.view', 'reports.export', 'reports.print',
            'activity_log.view', 'activity_log.export',
            'projects.view', 'customers.view', 'leads.view', 'opportunities.view', 'finance.view',
            'media.view', 'media.upload', 'media.update', 'media.delete', 'media.download'
        ]);

        // 3. Administrator
        $assign('Administrator', [
            'dashboard.view',
            'users.view', 'users.create', 'users.update',
            'roles.view', 'roles.create', 'roles.update',
            'settings.view', 'settings.update',
            'company.view',
            'reports.view',
            'activity_log.view',
            'media.view', 'media.upload', 'media.download'
        ]);

        // 4. General Manager
        $assign('General Manager', [
            'dashboard.view',
            'company.view',
            'projects.view', 'projects.approve', 'projects.export',
            'planning.view', 'wbs.view',
            'tasks.view', 'tasks.approve',
            'measurements.view', 'measurements.approve',
            'quotations.view', 'quotations.approve',
            'contracts.view', 'contracts.approve',
            'boq.view', 'boq.approve',
            'estimations.view', 'estimations.approve',
            'procurement.view', 'procurement.approve',
            'finance.view', 'invoices.view', 'invoices.approve', 'project_cost.view', 'project_cost.export',
            'change_requests.view', 'change_requests.approve',
            'reports.view', 'reports.export', 'reports.print',
            'activity_log.view'
        ]);

        // 5. Project Manager
        $assign('Project Manager', [
            'dashboard.view',
            'projects.view', 'projects.create', 'projects.update', 'projects.assign',
            'planning.view', 'planning.create', 'planning.update',
            'wbs.view', 'wbs.create', 'wbs.update',
            'tasks.view', 'tasks.create', 'tasks.update', 'tasks.assign', 'tasks.complete', 'tasks.approve',
            'site_visits.view', 'site_visits.create', 'site_visits.update', 'site_visits.assign', 'site_visits.complete', 'site_visits.cancel',
            'measurements.view', 'measurements.create', 'measurements.update', 'measurements.delete', 'measurements.approve',
            'scope.view', 'scope.create', 'scope.update',
            'boq.view',
            'change_requests.view', 'change_requests.create', 'change_requests.update',
            'reports.view', 'reports.export',
            'media.view', 'media.upload', 'media.download'
        ]);

        // 6. Project Team
        $assign('Project Team', [
            'dashboard.view',
            'projects.view',
            'planning.view', 'wbs.view',
            'tasks.view', 'tasks.update', 'tasks.complete',
            'measurements.view',
            'media.view', 'media.upload', 'media.download'
        ]);

        // 7. Site Engineer
        $assign('Site Engineer', [
            'dashboard.view',
            'projects.view',
            'tasks.view', 'tasks.update', 'tasks.complete',
            'site_visits.view', 'site_visits.create', 'site_visits.update', 'site_visits.complete', 'site_visits.cancel',
            'measurements.view', 'measurements.create', 'measurements.update',
            'media.view', 'media.upload', 'media.download'
        ]);

        // 8. Site Supervisor
        $assign('Site Supervisor', [
            'projects.view',
            'tasks.view', 'tasks.update', 'tasks.complete',
            'site_visits.view', 'site_visits.create',
            'media.view', 'media.upload'
        ]);

        // 9. Procurement
        $assign('Procurement', [
            'materials.view', 'materials.create', 'materials.update',
            'suppliers.view', 'suppliers.create', 'suppliers.update',
            'procurement.view', 'procurement.create', 'procurement.update', 'procurement.request', 'procurement.export',
            'media.view', 'media.upload', 'media.download'
        ]);

        // 10. Warehouse
        $assign('Warehouse', [
            'materials.view',
            'inventory.view', 'inventory.receive', 'inventory.issue', 'inventory.transfer', 'inventory.adjust',
            'media.view', 'media.upload'
        ]);

        // 11. Accountant
        $assign('Accountant', [
            'finance.view',
            'payments.view', 'payments.create', 'payments.update',
            'expenses.view', 'expenses.create', 'expenses.update',
            'invoices.view', 'invoices.create', 'invoices.update',
            'project_cost.view',
            'reports.view', 'reports.export'
        ]);

        // 12. Sales
        $assign('Sales', [
            'customers.view', 'customers.create', 'customers.update',
            'leads.view', 'leads.create', 'leads.update', 'leads.assign', 'leads.convert',
            'opportunities.view', 'opportunities.create', 'opportunities.update', 'opportunities.assign', 'opportunities.convert',
            'site_visits.view', 'site_visits.create', 'site_visits.cancel',
            'quotations.view', 'quotations.create', 'quotations.update', 'quotations.send', 'quotations.export'
        ]);

        // 13. Estimator
        $assign('Estimator', [
            'site_visits.view',
            'measurements.view',
            'scope.view', 'scope.create', 'scope.update',
            'boq.view', 'boq.create', 'boq.update', 'boq.import', 'boq.export',
            'estimations.view', 'estimations.create', 'estimations.update', 'estimations.export',
            'materials.view'
        ]);

        // 14. Finance
        $assign('Finance', [
            'finance.view', 'finance.create', 'finance.update',
            'payments.view', 'payments.create', 'payments.update',
            'expenses.view', 'expenses.create', 'expenses.update',
            'invoices.view', 'invoices.create', 'invoices.update', 'invoices.approve',
            'project_cost.view', 'project_cost.create', 'project_cost.update',
            'reports.view', 'reports.export'
        ]);

        // 15. HR
        $assign('HR', [
            'dashboard.view',
            'users.view', 'users.create', 'users.update',
            'reports.view'
        ]);

        // 16. Quality
        $assign('Quality', [
            'dashboard.view',
            'projects.view',
            'tasks.view', 'tasks.approve',
            'media.view', 'media.upload', 'media.download'
        ]);

        // 17. HSE
        $assign('HSE', [
            'dashboard.view',
            'projects.view',
            'tasks.view',
            'media.view', 'media.upload',
            'reports.view'
        ]);

        // 18. Document Controller
        $assign('Document Controller', [
            'projects.view',
            'contracts.view',
            'media.view', 'media.upload', 'media.update', 'media.delete', 'media.download'
        ]);

        // 19. Viewer (Read-Only)
        $assign('Viewer', [
            'dashboard.view',
            'reports.view',
            'projects.view',
            'customers.view',
            'leads.view',
            'opportunities.view',
            'materials.view',
            'tasks.view'
        ]);
    }
}
