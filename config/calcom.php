<?php

return [
    /* Basis-URL der REST-API  */
    'base_url'  => env('CALCOM_BASE', 'https://api.cal.com'),

    /* Dein Secret-Key  */
    'api_key'   => env('CALCOM_API_KEY'),

    /* EINE der beiden Varianten setzen                           *
     * Mit deinem Test klappt → teamUsername (= Workspace-Slug)   */
    'team_slug' => env('CALCOM_TEAM_SLUG'),   // z. B. askproai
    'user_slug' => env('CALCOM_USER_SLUG'),   // leer lassen
];
