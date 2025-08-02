<?php
// Clear all portal session cookies and data
session_start();

// Clear all possible session names
$sessionNames = [
    'portal_session',
    'askproai_portal_session',
    'laravel_session',
    'PHPSESSID'
];

foreach ($sessionNames as $name) {
    if (isset($_COOKIE[$name])) {
        setcookie($name, '', time() - 3600, '/');
        setcookie($name, '', time() - 3600, '/business');
        setcookie($name, '', time() - 3600, '/business/');
    }
}

// Destroy session
session_destroy();

echo "Portal session cleared successfully.\n";
echo "You can now go to: https://api.askproai.de/business/login\n";