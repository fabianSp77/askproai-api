<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Grund­einstellungen
    |--------------------------------------------------------------------------
    */

    'enabled'   => env('FILAMENT_SHIELD_ENABLED', true),
    'auth_guard'=> env('FILAMENT_SHIELD_GUARD',  'web'),

    /*
    |--------------------------------------------------------------------------
    | Berechtigungs-Präfixe
    |--------------------------------------------------------------------------
    */

    'permission_prefixes' => [
        'view',
        'view_any',
        'create',
        'update',
        'restore',
        'restore_any',
        'replicate',
        'reorder',
        'delete',
        'delete_any',
        'force_delete',
        'force_delete_any',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rollen / Policies
    |--------------------------------------------------------------------------
    */

    'generate_policies'      => true,
    'super_admin_role_name'  => env('FILAMENT_SHIELD_SUPER_ADMIN_ROLE', 'super_admin'),
    'register_permissions_on_sync' => true,

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */

    'cache' => [
        'enabled' => true,
        'ttl'     => 60 * 60 * 24,   // 24 h
    ],
];
