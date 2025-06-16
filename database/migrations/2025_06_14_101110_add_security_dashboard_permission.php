<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create the security dashboard permission
        $permission = Permission::firstOrCreate([
            'name' => 'view_security_dashboard',
            'guard_name' => 'web'
        ]);

        // Assign to super_admin role if it exists
        $superAdminRole = Role::where('name', 'super_admin')->where('guard_name', 'web')->first();
        if ($superAdminRole) {
            $superAdminRole->givePermissionTo('view_security_dashboard');
        }

        // Clear permission cache
        app()['cache']->forget('spatie.permission.cache');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::where('name', 'view_security_dashboard')->delete();
        app()['cache']->forget('spatie.permission.cache');
    }
};