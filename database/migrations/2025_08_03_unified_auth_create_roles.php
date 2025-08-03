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
        
        // Create unified company roles if they don't exist
        $roles = [
            'company_owner' => 'Company Owner - Full access within company',
            'company_admin' => 'Company Administrator - Manage company settings and users',
            'company_manager' => 'Company Manager - Team lead with limited admin rights',
            'company_staff' => 'Company Staff - Basic user rights'
        ];
        
        foreach ($roles as $roleName => $description) {
            Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                ['description' => $description]
            );
        }
        
        // Create permissions for company users
        $permissions = [
            // Call Management
            'company.calls.view_all' => 'View all company calls',
            'company.calls.view_team' => 'View team calls only',
            'company.calls.view_own' => 'View own calls only',
            'company.calls.edit_all' => 'Edit all company calls',
            'company.calls.edit_team' => 'Edit team calls',
            'company.calls.edit_own' => 'Edit own calls',
            'company.calls.export' => 'Export call data',
            
            // Appointment Management
            'company.appointments.view_all' => 'View all company appointments',
            'company.appointments.view_team' => 'View team appointments',
            'company.appointments.view_own' => 'View own appointments',
            'company.appointments.edit_all' => 'Edit all company appointments',
            'company.appointments.edit_team' => 'Edit team appointments',
            'company.appointments.edit_own' => 'Edit own appointments',
            
            // Billing & Finance
            'company.billing.view' => 'View billing information',
            'company.billing.pay' => 'Make payments',
            'company.billing.export' => 'Export billing data',
            'company.billing.manage' => 'Manage billing settings',
            
            // Analytics
            'company.analytics.view_all' => 'View all company analytics',
            'company.analytics.view_team' => 'View team analytics',
            'company.analytics.export' => 'Export analytics data',
            
            // Team Management
            'company.team.view' => 'View team members',
            'company.team.manage' => 'Manage team members',
            
            // Settings
            'company.settings.view' => 'View company settings',
            'company.settings.manage' => 'Manage company settings',
            
            // Feedback
            'company.feedback.view_all' => 'View all feedback',
            'company.feedback.view_team' => 'View team feedback',
            'company.feedback.create' => 'Create feedback',
            'company.feedback.respond' => 'Respond to feedback',
        ];
        
        foreach ($permissions as $permissionName => $description) {
            Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                ['description' => $description]
            );
        }
        
        // Assign permissions to roles
        $rolePermissions = [
            'company_owner' => [
                'company.calls.view_all', 'company.calls.edit_all', 'company.calls.export',
                'company.appointments.view_all', 'company.appointments.edit_all',
                'company.billing.view', 'company.billing.pay', 'company.billing.export', 'company.billing.manage',
                'company.analytics.view_all', 'company.analytics.export',
                'company.team.view', 'company.team.manage',
                'company.settings.view', 'company.settings.manage',
                'company.feedback.view_all', 'company.feedback.respond'
            ],
            'company_admin' => [
                'company.calls.view_all', 'company.calls.edit_all', 'company.calls.export',
                'company.appointments.view_all', 'company.appointments.edit_all',
                'company.billing.view', 'company.billing.pay', 'company.billing.export',
                'company.analytics.view_all', 'company.analytics.export',
                'company.team.view',
                'company.settings.view',
                'company.feedback.view_all', 'company.feedback.respond'
            ],
            'company_manager' => [
                'company.calls.view_team', 'company.calls.edit_team', 'company.calls.export',
                'company.appointments.view_team', 'company.appointments.edit_team',
                'company.analytics.view_team',
                'company.team.view',
                'company.feedback.view_team'
            ],
            'company_staff' => [
                'company.calls.view_own', 'company.calls.edit_own',
                'company.appointments.view_own', 'company.appointments.edit_own',
                'company.feedback.create'
            ],
        ];
        
        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::findByName($roleName, 'web');
            $role->syncPermissions($perms);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove company roles
        $roles = ['company_owner', 'company_admin', 'company_manager', 'company_staff'];
        foreach ($roles as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->delete();
            }
        }
        
        // Remove company permissions
        Permission::where('name', 'LIKE', 'company.%')->delete();
        
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};