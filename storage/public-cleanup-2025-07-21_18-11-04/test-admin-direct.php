<?php
// Direct Admin Panel Test
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use App\Models\User;
use Illuminate\Support\Facades\Auth;

// Force login as admin
$adminUser = User::where('email', 'admin@askproai.de')->first();
if ($adminUser) {
    Auth::guard('web')->login($adminUser);
    echo "✓ Logged in as admin (ID: {$adminUser->id})\n\n";
    
    // Try to access admin panel directly
    echo "Attempting to access admin panel...\n";
    
    try {
        // Create request to /admin
        $adminRequest = \Illuminate\Http\Request::create('/admin', 'GET');
        $adminRequest->setLaravelSession($request->session());
        
        // Copy authentication
        $adminRequest->setUserResolver(function () use ($adminUser) {
            return $adminUser;
        });
        
        // Handle the request
        $adminResponse = $kernel->handle($adminRequest);
        
        echo "Response Status: " . $adminResponse->getStatusCode() . "\n";
        
        if ($adminResponse->getStatusCode() === 500) {
            // Extract error from response
            $content = $adminResponse->getContent();
            
            // Look for error message in HTML
            if (preg_match('/<div[^>]*class="[^"]*message[^"]*"[^>]*>(.*?)<\/div>/si', $content, $matches)) {
                echo "Error Message: " . strip_tags($matches[1]) . "\n";
            }
            
            // Try to find file and line
            if (preg_match('/in\s+([^:]+):(\d+)/i', $content, $matches)) {
                echo "Error Location: " . $matches[1] . ":" . $matches[2] . "\n";
            }
            
            // Save full error page for inspection
            file_put_contents('/tmp/admin-error.html', $content);
            echo "\nFull error saved to: /tmp/admin-error.html\n";
        } else {
            echo "Success! Admin panel accessible.\n";
        }
        
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        echo "\nStack Trace:\n";
        echo $e->getTraceAsString() . "\n";
    }
} else {
    echo "✗ Admin user not found\n";
}

echo "\nAlternative URLs to try:\n";
echo "- https://api.askproai.de/admin-working.php (working version)\n";
echo "- https://api.askproai.de/admin/login (login page)\n";
echo "- https://api.askproai.de/emergency-login (emergency access)\n";