<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    protected $fillable = [
        'name',
        'guard_name',
        'description',
        'module',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Create default permissions for the system
     */
    public static function createDefaultPermissions(): void
    {
        $modules = [
            'system' => ['access_admin', 'view_logs', 'manage_settings', 'manage_users', 'manage_roles'],
            'company' => ['view', 'create', 'update', 'delete', 'manage_billing', 'manage_integrations', 'bulk_delete'],
            'branch' => ['view', 'create', 'update', 'delete', 'manage_services', 'manage_staff', 'bulk_delete'],
            'staff' => ['view', 'create', 'update', 'delete', 'manage_schedule', 'bulk_delete'],
            'service' => ['view', 'create', 'update', 'delete', 'manage_pricing', 'bulk_delete'],
            'customer' => ['view', 'create', 'update', 'delete', 'export', 'import', 'bulk_delete'],
            'appointment' => ['view', 'create', 'update', 'delete', 'reschedule', 'cancel', 'bulk_delete'],
            'call' => ['view', 'create', 'update', 'delete', 'manage_recordings', 'bulk_delete'],
            'phone_number' => ['view', 'create', 'update', 'delete', 'bulk_delete'],
        ];

        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                $permissionName = "{$module}.{$action}";
                static::firstOrCreate(
                    ['name' => $permissionName],
                    ['guard_name' => 'web']
                );
            }
        }
    }
}
