<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "Testing Business Portal URLs...\n\n";

$urls = [
    '/business/dashboard',
    '/business/calls',
    '/business/appointments',
    '/business/appointments/create',
    '/business/billing',
    '/business/settings',
    '/business/team',
    '/business/analytics',
    '/business/customers'
];

foreach ($urls as $url) {
    try {
        $request = Illuminate\Http\Request::create($url, 'GET');
        $response = $kernel->handle($request);
        
        $status = $response->getStatusCode();
        $statusText = match($status) {
            200 => '✅ OK',
            302 => '🔄 Redirect',
            404 => '❌ Not Found',
            500 => '💥 Server Error',
            default => "❓ Status: $status"
        };
        
        echo sprintf("%-30s %s\n", $url, $statusText);
        
        if ($status === 500) {
            // Try to get error message
            $content = $response->getContent();
            if (preg_match('/<title>(.*?)<\/title>/i', $content, $matches)) {
                echo "   Error: " . strip_tags($matches[1]) . "\n";
            }
        }
        
    } catch (Exception $e) {
        echo sprintf("%-30s ❌ Exception: %s\n", $url, $e->getMessage());
    }
}

echo "\nDone.\n";