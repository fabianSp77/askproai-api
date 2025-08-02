<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Start the app
$app->boot();

echo "=== LOGIN DEBUG ANALYSIS ===\n\n";

echo "1. SESSION CONFIGURATION:\n";
echo "   Main session cookie: " . config('session.cookie') . "\n";
echo "   Main session domain: " . (config('session.domain') ?: 'null') . "\n";
echo "   Main session path: " . config('session.path') . "\n";
echo "   Main session secure: " . (config('session.secure') ? 'true' : 'false') . "\n";
echo "   Main session driver: " . config('session.driver') . "\n";
echo "   Main session files: " . config('session.files') . "\n";
echo "\n";

echo "2. PORTAL SESSION CONFIGURATION (if exists):\n";
if (file_exists(__DIR__ . '/config/session_portal.php')) {
    $portalConfig = include __DIR__ . '/config/session_portal.php';
    echo "   Portal session cookie: " . $portalConfig['cookie'] . "\n";
    echo "   Portal session domain: " . ($portalConfig['domain'] ?: 'null') . "\n";
    echo "   Portal session path: " . $portalConfig['path'] . "\n";
    echo "   Portal session secure: " . ($portalConfig['secure'] ? 'true' : 'false') . "\n";
    echo "   Portal session files: " . $portalConfig['files'] . "\n";
} else {
    echo "   Portal session config not found\n";
}
echo "\n";

echo "3. AUTH GUARDS CONFIGURATION:\n";
$guards = config('auth.guards');
foreach ($guards as $name => $guard) {
    echo "   Guard '$name': driver={$guard['driver']}, provider={$guard['provider']}\n";
}
echo "\n";

echo "4. AUTH PROVIDERS CONFIGURATION:\n";
$providers = config('auth.providers');
foreach ($providers as $name => $provider) {
    echo "   Provider '$name': driver={$provider['driver']}, model={$provider['model']}\n";
}
echo "\n";

echo "5. SESSION FILES CHECK:\n";
$mainSessionPath = storage_path('framework/sessions');
$portalSessionPath = storage_path('framework/sessions/portal');

echo "   Main session path exists: " . (is_dir($mainSessionPath) ? 'YES' : 'NO') . "\n";
echo "   Main session path writable: " . (is_writable($mainSessionPath) ? 'YES' : 'NO') . "\n";
echo "   Portal session path exists: " . (is_dir($portalSessionPath) ? 'YES' : 'NO') . "\n";
echo "   Portal session path writable: " . (is_writable($portalSessionPath) ? 'YES' : 'NO') . "\n";

if (is_dir($mainSessionPath)) {
    $mainSessionFiles = glob($mainSessionPath . '/*');
    echo "   Main session files count: " . count($mainSessionFiles) . "\n";
}

if (is_dir($portalSessionPath)) {
    $portalSessionFiles = glob($portalSessionPath . '/*');
    echo "   Portal session files count: " . count($portalSessionFiles) . "\n";
}
echo "\n";

echo "6. ENVIRONMENT VARIABLES:\n";
$sessionEnvs = ['SESSION_DRIVER', 'SESSION_LIFETIME', 'SESSION_ENCRYPT', 'SESSION_DOMAIN', 'SESSION_SECURE_COOKIE', 'SESSION_COOKIE'];
foreach ($sessionEnvs as $env) {
    echo "   $env: " . (env($env) ?: 'not set') . "\n";
}

echo "\n=== END DEBUG ANALYSIS ===\n";
EOF < /dev/null
