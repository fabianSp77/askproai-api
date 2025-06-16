<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // This migration is currently empty as the permission system 
        // is not properly set up with Spatie Permissions.
        // Event Type permissions will be handled through the CalcomEventTypePolicy
        // and basic role checks instead.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissions = [
            'view_any_calcom::event::type',
            'view_calcom::event::type',
            'create_calcom::event::type',
            'update_calcom::event::type',
            'delete_calcom::event::type',
            'delete_any_calcom::event::type',
            'force_delete_calcom::event::type',
            'force_delete_any_calcom::event::type',
            'restore_calcom::event::type',
            'restore_any_calcom::event::type',
            'replicate_calcom::event::type',
            'reorder_calcom::event::type',
            'manage_staff_calcom::event::type',
            'sync_calcom::event::type',
        ];

        foreach ($permissions as $name) {
            Permission::where('name', $name)->delete();
        }
    }
};