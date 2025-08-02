<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions for each resource
        $resources = [
            'company',
            'branch',
            'staff',
            'customer',
            'appointment',
            'service',
            'phone_number',
            'invoice',
            'call',
            'user',
            'calcom_event_type',
        ];

        $actions = [
            'view_any',
            'view',
            'create',
            'update',
            'delete',
            'delete_any',
            'force_delete',
            'force_delete_any',
            'restore',
            'restore_any',
        ];

        // Create all permissions
        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => "{$action}_{$resource}",
                    'guard_name' => 'web'
                ]);
            }
        }

        // Create additional specific permissions
        $specificPermissions = [
            // System permissions
            'access_admin_panel',
            'access_system_settings',
            'view_system_logs',
            'manage_integrations',
            'manage_webhooks',
            'view_analytics',
            'export_data',
            'import_data',
            
            // Company management
            'manage_company_settings',
            'manage_company_billing',
            'manage_company_users',
            
            // Branch management
            'manage_branch_settings',
            'manage_branch_staff',
            'manage_branch_services',
            
            // Financial permissions
            'manage_invoices',
            'view_financial_reports',
            'manage_tax_settings',
            
            // Customer portal
            'access_customer_portal',
            'manage_own_appointments',
            'view_own_invoices',
        ];

        foreach ($specificPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        // Create roles
        
        // Super Admin - has all permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo(Permission::all());

        // Company Admin - can manage everything within their company
        $companyAdmin = Role::firstOrCreate(['name' => 'company_admin', 'guard_name' => 'web']);
        $companyAdmin->givePermissionTo([
            // Company management
            'view_company',
            'update_company',
            'manage_company_settings',
            'manage_company_billing',
            'manage_company_users',
            
            // Branch management
            'view_any_branch',
            'view_branch',
            'create_branch',
            'update_branch',
            'delete_branch',
            'manage_branch_settings',
            
            // Staff management
            'view_any_staff',
            'view_staff',
            'create_staff',
            'update_staff',
            'delete_staff',
            'manage_branch_staff',
            
            // Service management
            'view_any_service',
            'view_service',
            'create_service',
            'update_service',
            'delete_service',
            'manage_branch_services',
            
            // Customer management
            'view_any_customer',
            'view_customer',
            'create_customer',
            'update_customer',
            'delete_customer',
            
            // Appointment management
            'view_any_appointment',
            'view_appointment',
            'create_appointment',
            'update_appointment',
            'delete_appointment',
            
            // Phone number management
            'view_any_phone_number',
            'view_phone_number',
            'create_phone_number',
            'update_phone_number',
            'delete_phone_number',
            
            // Invoice management
            'view_any_invoice',
            'view_invoice',
            'create_invoice',
            'update_invoice',
            'manage_invoices',
            'view_financial_reports',
            
            // Call management
            'view_any_call',
            'view_call',
            
            // Cal.com event types
            'view_any_calcom_event_type',
            'view_calcom_event_type',
            'create_calcom_event_type',
            'update_calcom_event_type',
            
            // System access
            'access_admin_panel',
            'manage_integrations',
            'view_analytics',
            'export_data',
            'import_data',
        ]);

        // Branch Manager - can manage their branch
        $branchManager = Role::firstOrCreate(['name' => 'branch_manager', 'guard_name' => 'web']);
        $branchManager->givePermissionTo([
            // Branch viewing
            'view_branch',
            'update_branch',
            'manage_branch_settings',
            
            // Staff management in their branch
            'view_any_staff',
            'view_staff',
            'create_staff',
            'update_staff',
            'manage_branch_staff',
            
            // Service management in their branch
            'view_any_service',
            'view_service',
            'create_service',
            'update_service',
            'manage_branch_services',
            
            // Customer management
            'view_any_customer',
            'view_customer',
            'create_customer',
            'update_customer',
            
            // Appointment management
            'view_any_appointment',
            'view_appointment',
            'create_appointment',
            'update_appointment',
            
            // Call viewing
            'view_any_call',
            'view_call',
            
            // System access
            'access_admin_panel',
            'view_analytics',
            'export_data',
        ]);

        // Staff Member - basic access
        $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $staff->givePermissionTo([
            // View own branch
            'view_branch',
            
            // View colleagues
            'view_any_staff',
            'view_staff',
            
            // View services
            'view_any_service',
            'view_service',
            
            // Customer management
            'view_any_customer',
            'view_customer',
            'create_customer',
            'update_customer',
            
            // Appointment management
            'view_any_appointment',
            'view_appointment',
            'create_appointment',
            'update_appointment',
            
            // Call viewing
            'view_any_call',
            'view_call',
            
            // System access
            'access_admin_panel',
        ]);

        // Accountant - financial access
        $accountant = Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'web']);
        $accountant->givePermissionTo([
            // View company and branches
            'view_company',
            'view_any_branch',
            'view_branch',
            
            // View customers
            'view_any_customer',
            'view_customer',
            
            // View appointments
            'view_any_appointment',
            'view_appointment',
            
            // Full invoice access
            'view_any_invoice',
            'view_invoice',
            'create_invoice',
            'update_invoice',
            'manage_invoices',
            'view_financial_reports',
            'manage_tax_settings',
            
            // System access
            'access_admin_panel',
            'export_data',
        ]);

        // Customer - limited portal access
        $customer = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        $customer->givePermissionTo([
            'access_customer_portal',
            'manage_own_appointments',
            'view_own_invoices',
        ]);

        // Reseller - can manage multiple companies
        $reseller = Role::firstOrCreate(['name' => 'reseller', 'guard_name' => 'web']);
        $reseller->givePermissionTo([
            'view_any_company',
            'view_company',
            'create_company',
            'update_company',
            'access_admin_panel',
            'view_analytics',
        ]);

        // Developer - has access to debug and monitoring tools
        $developer = Role::firstOrCreate(['name' => 'developer', 'guard_name' => 'web']);
        $developer->givePermissionTo([
            'access_admin_panel',
            'view_system_logs',
            'manage_integrations',
            'manage_webhooks',
            'view_analytics',
            'export_data',
            'import_data',
            // View permissions for debugging
            'view_any_company',
            'view_company',
            'view_any_branch',
            'view_branch',
            'view_any_staff',
            'view_staff',
            'view_any_customer',
            'view_customer',
            'view_any_appointment',
            'view_appointment',
            'view_any_call',
            'view_call',
            'view_any_invoice',
            'view_invoice',
            'view_any_calcom_event_type',
            'view_calcom_event_type',
        ]);

        $this->command->info('Roles and permissions created successfully!');
    }
}