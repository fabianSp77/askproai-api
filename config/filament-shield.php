<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Permission Prefixes
    |--------------------------------------------------------------------------
    | Welche Verb-Präfixe für Ressourcen-/Page-/Widget-Rechte generiert werden.
    */
    'permission_prefixes' => [
        'resource' => [               //  ← **diese Zeile fehlte**
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
        ],
        'page'    => 'page',          // z. B.  page_dashboard
        'widget'  => 'widget',        // z. B.  widget_account-stats
    ],

    /*
    |--------------------------------------------------------------------------
    | Role that will get ALL permissions irrespective of the above prefixes.
    |--------------------------------------------------------------------------
    */
    'super_admin' => 'super_admin',
];
