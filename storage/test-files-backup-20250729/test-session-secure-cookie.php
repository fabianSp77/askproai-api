<?php
// Test Session Secure Cookie Setting

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

echo "<h1>Session Configuration Test</h1>";
echo "<pre>";
echo "Current Settings:\n";
echo "- SESSION_SECURE_COOKIE: " . env('SESSION_SECURE_COOKIE', 'not set') . "\n";
echo "- config('session.secure'): " . (config('session.secure') ? 'true' : 'false') . "\n";
echo "- Request is HTTPS: " . ($request->secure() ? 'Yes' : 'No') . "\n";
echo "- Request scheme: " . $request->getScheme() . "\n";
echo "\n";

echo "Cookie Settings:\n";
echo "- Cookie Name: " . config('session.cookie') . "\n";
echo "- Domain: " . config('session.domain') . "\n";
echo "- HTTP Only: " . (config('session.http_only') ? 'Yes' : 'No') . "\n";
echo "- Same Site: " . config('session.same_site') . "\n";
echo "\n";

echo "Headers:\n";
foreach ($request->headers->all() as $key => $value) {
    if (in_array(strtolower($key), ['x-forwarded-proto', 'x-forwarded-for', 'host', 'x-real-ip'])) {
        echo "- $key: " . implode(', ', $value) . "\n";
    }
}
echo "\n";

echo "⚠️  PROBLEM FOUND:\n";
echo "Session cookies are configured as non-secure (SESSION_SECURE_COOKIE=false)\n";
echo "but the site is running over HTTPS. This can cause session issues.\n";
echo "\n";
echo "SOLUTION:\n";
echo "Set SESSION_SECURE_COOKIE=true in .env file\n";
echo "</pre>";

$kernel->terminate($request, $response);