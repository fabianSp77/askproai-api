<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

// Get admin user
$user = User::where('email', 'admin@askproai.de')->first();

if (!$user) {
    die("Admin user not found!\n");
}

// Authenticate user
Auth::login($user);
$panel = Filament::getPanel('admin');
$panel->auth()->login($user);

echo "Authenticated as: " . $user->email . "\n\n";

// Create a request for the calls page
$request = Request::create('/admin/calls', 'GET');
$request->setUserResolver(function () use ($user) {
    return $user;
});

// Set session
app()->instance('request', $request);

// Handle the request
$response = $kernel->handle($request);

echo "Response status: " . $response->getStatusCode() . "\n";
echo "Response length: " . strlen($response->getContent()) . " bytes\n\n";

// Check content
$content = $response->getContent();

// Save to file for inspection
file_put_contents('/tmp/rendered-calls-page.html', $content);
echo "Full page saved to /tmp/rendered-calls-page.html\n\n";

// Analyze content
if (strpos($content, 'Login') !== false || strpos($content, 'Anmelden') !== false) {
    echo "⚠ Login form detected - authentication may have failed\n";
}

if (strpos($content, 'fi-ta-table') !== false) {
    echo "✓ Filament table class found\n";
}

if (strpos($content, '<table') !== false) {
    echo "✓ Table element found\n";
    
    // Count table rows
    preg_match_all('/<tr[^>]*>/', $content, $rows);
    echo "  Table rows: " . count($rows[0]) . "\n";
}

if (strpos($content, 'Anrufe') !== false) {
    echo "✓ 'Anrufe' (Calls) text found\n";
}

// Check for specific call data patterns
if (preg_match('/call_[a-f0-9]{32}/', $content)) {
    echo "✓ Call IDs found in content\n";
}

// Extract title
if (preg_match('/<title>(.*?)<\/title>/s', $content, $matches)) {
    echo "\nPage title: " . trim($matches[1]) . "\n";
}