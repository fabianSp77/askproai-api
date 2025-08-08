<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        // Create reseller-specific roles
        $resellerRoles = [
            'reseller_owner' => 'Reseller Owner - Full access to own company and all client companies',
            'reseller_admin' => 'Reseller Administrator - Manage client companies on behalf of reseller',
            'reseller_support' => 'Reseller Support - View and assist client companies'
        ];
        
        foreach ($resellerRoles as $roleName => $description) {
            Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                ['description' => $description]
            );
        }
        
        // Create reseller-specific permissions
        $resellerPermissions = [
            // Client Management
            'reseller.clients.view' => 'View all client companies',
            'reseller.clients.create' => 'Create new client companies',
            'reseller.clients.edit' => 'Edit client company details',
            'reseller.clients.delete' => 'Delete client companies',
            'reseller.clients.switch' => 'Switch between client company contexts',
            
            // Pricing Management
            'reseller.pricing.view' => 'View pricing tiers and margins',
            'reseller.pricing.edit' => 'Edit client pricing',
            'reseller.pricing.view_costs' => 'View cost prices',
            'reseller.pricing.view_margins' => 'View profit margins',
            
            // Billing & Invoicing
            'reseller.billing.view_all' => 'View all client bills',
            'reseller.billing.create_invoice' => 'Create invoices for clients',
            'reseller.billing.manage_payments' => 'Manage client payments',
            'reseller.billing.view_commission' => 'View commission reports',
            
            // Analytics & Reporting
            'reseller.analytics.view_all' => 'View analytics for all clients',
            'reseller.analytics.compare_clients' => 'Compare client performance',
            'reseller.analytics.export' => 'Export reseller reports',
            
            // Outbound Campaigns
            'reseller.campaigns.view_all' => 'View all client campaigns',
            'reseller.campaigns.create_for_client' => 'Create campaigns for clients',
            'reseller.campaigns.manage_templates' => 'Manage campaign templates',
            
            // Support
            'reseller.support.impersonate' => 'Impersonate client users',
            'reseller.support.view_logs' => 'View client activity logs',
            'reseller.support.manage_tickets' => 'Manage client support tickets',
        ];
        
        foreach ($resellerPermissions as $permissionName => $description) {
            Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                ['description' => $description]
            );
        }
        
        // Assign permissions to reseller roles
        $rolePermissions = [
            'reseller_owner' => array_keys($resellerPermissions), // All permissions
            
            'reseller_admin' => [
                'reseller.clients.view', 'reseller.clients.edit', 'reseller.clients.switch',
                'reseller.pricing.view', 'reseller.pricing.edit',
                'reseller.billing.view_all', 'reseller.billing.create_invoice',
                'reseller.analytics.view_all', 'reseller.analytics.export',
                'reseller.campaigns.view_all', 'reseller.campaigns.create_for_client',
                'reseller.support.impersonate'
            ],
            
            'reseller_support' => [
                'reseller.clients.view', 'reseller.clients.switch',
                'reseller.pricing.view',
                'reseller.billing.view_all',
                'reseller.analytics.view_all',
                'reseller.campaigns.view_all',
                'reseller.support.view_logs'
            ]
        ];
        
        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::findByName($roleName, 'web');
            $role->syncPermissions($permissions);
            
            // Also give them basic company permissions
            $role->givePermissionTo([
                'company.calls.view_all',
                'company.appointments.view_all',
                'company.analytics.view_all',
                'company.team.view'
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove reseller permissions
        Permission::where('name', 'LIKE', 'reseller.%')->delete();
        
        // Remove reseller roles
        $roles = ['reseller_owner', 'reseller_admin', 'reseller_support'];
        foreach ($roles as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->delete();
            }
        }
        
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};