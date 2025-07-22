<?php

return [
    'enabled' => true,
    'auto_login' => false, // Disabled auto-login to fix auth issues
    'demo_email' => 'demo@askproai.de', // Standardized demo email
    'skip_auth_check' => false, // Re-enable auth checks
    'disable_2fa' => true, // Keep 2FA disabled for demo
    'disable_csrf' => true, // Disable CSRF for demo to prevent 419 errors
];