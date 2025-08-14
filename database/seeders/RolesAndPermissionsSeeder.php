<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Standard-CRUD-Rechte -------------------------------------------------
        $crud = ['view_any', 'view', 'create', 'update', 'delete'];

        // Ressourcen, auf die CRUD gilt (anpassen, falls du weitere hast)
        $resources = [
            'User', 'Staff', 'Customer', 'Service', 'Branch',
        ];

        // 1)  Rechte erzeugen --------------------------------------------------
        foreach ($resources as $res) {
            foreach ($crud as $op) {
                Permission::findOrCreate("{$op}_{$res}");
            }
        }
        Permission::findOrCreate('access_filament');

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
                'view_any_User', 'view_User',
                'view_any_Staff', 'view_Staff',
            ])->get()
        );
    }
}
