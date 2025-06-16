<?php

return [

    /* ----------------------------------------------------------
     |  Externe Services
     * --------------------------------------------------------- */

    'calcom' => [
        'api_key'        => env('CALCOM_API_KEY'),
        'base_url'       => env('CALCOM_BASE_URL', 'https://api.cal.com/v1'),
        'team_slug'      => env('CALCOM_TEAM_SLUG'),
        'event_type_id'  => env('CALCOM_EVENT_TYPE_ID'),
        'webhook_secret' => env('CALCOM_WEBHOOK_SECRET'),
    ],

    'retell' => [
        'webhook_secret' => env('RETELL_WEBHOOK_SECRET'),
    ],

    // ... (weitere Service-Configs wie stripe, mailgun, etc.)

];
