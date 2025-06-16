<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class EventManagementPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Event Type permissions
        $eventTypePermissions = [
            'view_event_types',
            'view_all_event_types',
            'create_event_types',
            'edit_event_types',
            'delete_event_types',
            'manage_staff_assignments',
            'sync_event_types',
        ];

        foreach ($eventTypePermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create Analytics permissions
        $analyticsPermissions = [
            'view_analytics',
            'view_all_analytics',
            'export_analytics',
        ];

        foreach ($analyticsPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create Booking permissions
        $bookingPermissions = [
            'create_bookings',
            'view_bookings',
            'edit_bookings',
            'cancel_bookings',
            'view_all_bookings',
        ];

        foreach ($bookingPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create Notification permissions
        $notificationPermissions = [
            'send_notifications',
            'manage_notification_settings',
            'view_notification_log',
        ];

        foreach ($notificationPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Assign permissions to roles if they exist
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo([
            ...$eventTypePermissions,
            ...$analyticsPermissions,
            ...$bookingPermissions,
            ...$notificationPermissions,
        ]);

        $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $managerRole->givePermissionTo([
            'view_event_types',
            'create_event_types',
            'edit_event_types',
            'manage_staff_assignments',
            'sync_event_types',
            'view_analytics',
            'view_bookings',
            'create_bookings',
            'edit_bookings',
            'cancel_bookings',
            'send_notifications',
        ]);

        $staffRole = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $staffRole->givePermissionTo([
            'view_event_types',
            'view_bookings',
            'create_bookings',
            'edit_bookings',
        ]);
    }
}