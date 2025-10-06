<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Login as admin
$user = \App\Models\User::where('email', 'fabian@askproai.de')->first();
Auth::login($user);

echo "Logged in as: " . $user->email . "\n";

// Create request
$request = Illuminate\Http\Request::create(
    '/admin/branches/34c4d48e-4753-4715-9c30-c55843a943e8/edit',
    'GET'
);

// Set session
$request->setLaravelSession($app['session.store']);
Auth::setUser($user);

try {
    echo "Testing branch edit page...\n";

    $response = $kernel->handle($request);

    echo "Response Status: " . $response->getStatusCode() . "\n";

    if ($response->getStatusCode() == 500) {
        echo "\n❌ 500 ERROR FOUND!\n\n";

        // Get error from response
        $content = $response->getContent();

        // Look for error message
        if (preg_match('/<div class="text-2xl.*?>(.*?)<\/div>/s', $content, $matches)) {
            echo "Error Title: " . strip_tags($matches[1]) . "\n";
        }

        if (preg_match('/<div class="mt-4 text-sm.*?>(.*?)<\/div>/s', $content, $matches)) {
            echo "Error Message: " . strip_tags($matches[1]) . "\n";
        }

        // Save full response for inspection
        file_put_contents('/tmp/error-page.html', $content);
        echo "\nFull error page saved to /tmp/error-page.html\n";
    }

} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}