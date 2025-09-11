<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Standard-CRUD-Rechte -------------------------------------------------
        $crud = ['view_any', 'view', 'create', 'update', 'delete', 'delete_any', 'force_delete', 'force_delete_any', 'restore', 'restore_any', 'replicate', 'reorder'];

        // Ressourcen, auf die CRUD gilt (alle Filament Resources)
        $resources = [
            'user', 'staff', 'customer', 'service', 'branch', 
            'working_hour', 'integration', 'call', 'appointment',
            'company', 'agent', 'calcom_event_type', 'calcom_booking',
            'enhanced_call' // Added Enhanced Call resource
        ];

        // 1)  Rechte erzeugen --------------------------------------------------
        foreach ($resources as $res) {
            foreach ($crud as $op) {
                Permission::findOrCreate("{$op}_{$res}");
            }
        }
        Permission::findOrCreate('access_filament');
        Permission::findOrCreate('access_admin_panel'); // Added missing admin panel permission

        // 2)  Rollen anlegen (falls fehlen) -----------------------------------
        $super = Role::findOrCreate('super_admin');
        $admin = Role::findOrCreate('admin');
        $staff = Role::findOrCreate('staff');

        // 3)  Rechte zuweisen --------------------------------------------------
        $super->syncPermissions(Permission::all());        // alles
        $admin->syncPermissions(Permission::all());        // alles (kannst du einschränken)
        // staff bekommt nur lesen – Beispiel
        $staff->syncPermissions(
            Permission::whereIn('name', [
                'view_any_user', 'view_user',
                'view_any_staff','view_staff',
                'view_any_customer', 'view_customer',
                'view_any_appointment', 'view_appointment',
            ])->get()
        );
    }
}
