<?php
// Test Company Context Fix

echo "<h1>Test Company Context Fix</h1>";
echo "<pre>";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat('=', 80) . "\n\n";

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Test with browser session
$cookies = $_COOKIE;
$sessionCookie = $cookies['askproai_session'] ?? null;

if (!$sessionCookie) {
    echo "❌ No session cookie. Please login first.\n";
    exit;
}

echo "Testing different pages...\n\n";

$pagesToTest = [
    '/admin/calls' => 'Anrufe',
    '/admin/appointments' => 'Termine', 
    '/admin/customers' => 'Kunden',
    '/admin/companies' => 'Unternehmen',
    '/admin/branches' => 'Filialen',
];

foreach ($pagesToTest as $path => $name) {
    echo "Testing $name ($path)...\n";
    echo str_repeat('-', 40) . "\n";
    
    $request = Illuminate\Http\Request::create(
        'https://api.askproai.de' . $path,
        'GET',
        [],
        $cookies,
        [],
        [
            'HTTP_HOST' => 'api.askproai.de',
            'HTTPS' => 'on',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ]
    );
    
    try {
        $response = $kernel->handle($request);
        
        $status = $response->getStatusCode();
        
        if ($status == 200) {
            echo "✅ SUCCESS - Status: $status\n";
        } elseif ($status == 302) {
            echo "🔄 REDIRECT - Location: " . $response->headers->get('Location') . "\n";
        } elseif ($status == 500) {
            echo "❌ ERROR 500\n";
            
            // Extract error
            $content = $response->getContent();
            if (preg_match('/<div[^>]*class="[^"]*message[^"]*"[^>]*>(.*?)<\/div>/s', $content, $matches)) {
                $error = trim(strip_tags($matches[1]));
                echo "   Error: " . substr($error, 0, 100) . "...\n";
            }
        } else {
            echo "⚠️  Status: $status\n";
        }
        
    } catch (\Exception $e) {
        echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// Check company context
echo "Company Context Check:\n";
echo str_repeat('-', 40) . "\n";
echo "App has 'current_company_id': " . (app()->has('current_company_id') ? 'Yes (' . app('current_company_id') . ')' : 'No') . "\n";
echo "Context source: " . (app()->has('company_context_source') ? app('company_context_source') : 'None') . "\n";

echo "</pre>";