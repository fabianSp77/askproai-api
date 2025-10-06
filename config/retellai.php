<?php

return [
    'api_key' => env('RETELLAI_API_KEY', env('RETELL_TOKEN')),
    'base_url' => rtrim(env('RETELLAI_BASE_URL', env('RETELL_BASE_URL', env('RETELL_BASE', 'https://api.retell.ai'))), '/'),
    'webhook_secret' => env('RETELLAI_WEBHOOK_SECRET', env('RETELL_WEBHOOK_SECRET')),
    'log_webhooks' => env('RETELLAI_LOG_WEBHOOKS', true),
    'function_secret' => env('RETELLAI_FUNCTION_SECRET'),

    // SECURITY: allow_unsigned_webhooks permanently disabled (VULN-001 fix)
    // This option has been removed - all webhooks MUST be signed
    // DO NOT re-enable this option
];
