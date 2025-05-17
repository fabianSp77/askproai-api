<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Shield aktivieren / deaktivieren
    |--------------------------------------------------------------------------
    |
    | Auf "false" setzen, wenn du Shield ganz abschalten willst.
    | Standard bleibt "true".
    |
    */
    'enable' => true,

    /*
    |--------------------------------------------------------------------------
    | Voreinstellungen
    |--------------------------------------------------------------------------
    */
    'super_admin_role_name' => 'super_admin',
    'panel_fqcn'            => \Filament\Panel::class, // Standard-Admin-Panel
];
