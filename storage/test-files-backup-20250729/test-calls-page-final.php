<?php
// Final Test - Find the actual error

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Direct output to see errors immediately
ob_implicit_flush(true);
ob_end_flush();

echo "<h1>Final Calls Page Test</h1>";
echo "<pre>";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat('=', 80) . "\n\n";

try {
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    // Create a proper request with session cookie from browser
    echo "Creating request with your session...\n";
    
    // Get cookies from current request
    $cookies = $_COOKIE;
    $sessionCookie = $cookies['askproai_session'] ?? null;
    
    if (!$sessionCookie) {
        echo "❌ No session cookie found. Are you logged in?\n";
        echo "\nTo use this test:\n";
        echo "1. Log in to admin panel in your browser\n";
        echo "2. Then access this page: https://api.askproai.de/test-calls-page-final.php\n";
        exit;
    }
    
    echo "✅ Found session cookie\n\n";
    
    // Create request that mimics browser
    $request = Illuminate\Http\Request::create(
        'https://api.askproai.de/admin/calls',
        'GET',
        [],
        $cookies,  // Pass all cookies
        [],
        [
            'HTTP_HOST' => 'api.askproai.de',
            'HTTPS' => 'on',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT'] ?? 'Mozilla/5.0',
            'HTTP_REFERER' => 'https://api.askproai.de/admin',
        ]
    );
    
    echo "Handling request...\n";
    echo str_repeat('-', 40) . "\n";
    
    // Handle the request
    $response = $kernel->handle($request);
    
    echo "\nResponse Status: " . $response->getStatusCode() . "\n";
    echo "Content Type: " . $response->headers->get('Content-Type', 'N/A') . "\n";
    echo "Content Length: " . strlen($response->getContent()) . " bytes\n";
    
    if ($response->getStatusCode() == 500) {
        echo "\n❌ 500 ERROR DETECTED!\n\n";
        
        $content = $response->getContent();
        
        // Extract error details
        if (preg_match('/<title>(.*?)<\/title>/i', $content, $matches)) {
            echo "Page Title: " . strip_tags($matches[1]) . "\n";
        }
        
        // Look for error message
        if (preg_match('/<div[^>]*class="[^"]*message[^"]*"[^>]*>(.*?)<\/div>/s', $content, $matches)) {
            $errorMessage = trim(strip_tags($matches[1]));
            echo "\n🔴 ERROR MESSAGE:\n";
            echo str_repeat('=', 60) . "\n";
            echo $errorMessage . "\n";
            echo str_repeat('=', 60) . "\n";
        }
        
        // Look for exception type
        if (preg_match('/exception[^"]*"[^>]*>([^<]+)</', $content, $matches)) {
            echo "\nException Type: " . $matches[1] . "\n";
        }
        
        // Look for file and line
        if (preg_match('/in <[^>]+>([^<]+)<\/[^>]+> on line <[^>]+>(\d+)</', $content, $matches)) {
            echo "File: " . $matches[1] . "\n";
            echo "Line: " . $matches[2] . "\n";
        }
        
        // Look for SQL errors
        if (strpos($content, 'SQLSTATE') !== false) {
            echo "\n⚠️  DATABASE ERROR DETECTED\n";
            if (preg_match('/SQLSTATE\[([^\]]+)\][^:]*: (.+?)(?:\s+\(|$)/m', $content, $matches)) {
                echo "SQL Error Code: " . $matches[1] . "\n";
                echo "SQL Error: " . $matches[2] . "\n";
            }
        }
        
        // Look for view errors
        if (strpos($content, 'View [') !== false && strpos($content, 'not found') !== false) {
            echo "\n⚠️  VIEW NOT FOUND ERROR\n";
            if (preg_match('/View \[([^\]]+)\] not found/', $content, $matches)) {
                echo "Missing View: " . $matches[1] . "\n";
            }
        }
        
        // Look for class not found
        if (strpos($content, 'Class') !== false && strpos($content, 'not found') !== false) {
            echo "\n⚠️  CLASS NOT FOUND ERROR\n";
            if (preg_match('/Class[^"\']*["\']([^"\']+)["\'][^"\']*not found/', $content, $matches)) {
                echo "Missing Class: " . $matches[1] . "\n";
            }
        }
        
        // Look for stack trace
        if (preg_match('/<ol[^>]*class="[^"]*trace[^"]*"[^>]*>(.*?)<\/ol>/s', $content, $matches)) {
            echo "\nStack Trace (first 3 entries):\n";
            if (preg_match_all('/<li[^>]*>(.*?)<\/li>/s', $matches[1], $traceMatches)) {
                foreach (array_slice($traceMatches[1], 0, 3) as $i => $trace) {
                    $cleanTrace = strip_tags($trace);
                    $cleanTrace = preg_replace('/\s+/', ' ', $cleanTrace);
                    echo ($i + 1) . ". " . trim($cleanTrace) . "\n";
                }
            }
        }
        
        // Save full error
        $timestamp = date('Y-m-d_H-i-s');
        $errorFile = "/var/www/api-gateway/storage/logs/calls-error-{$timestamp}.html";
        file_put_contents($errorFile, $content);
        echo "\n📁 Full error HTML saved to:\n$errorFile\n";
        
        // Try to get the raw exception from Laravel's handler
        echo "\n\nAttempting to get raw exception...\n";
        
    } elseif ($response->getStatusCode() == 302) {
        echo "\n🔄 REDIRECT\n";
        echo "Location: " . $response->headers->get('Location') . "\n";
        
        if (strpos($response->headers->get('Location'), 'login') !== false) {
            echo "\n⚠️  You are being redirected to login.\n";
            echo "This means your session is not valid.\n";
            echo "Please log in first in your browser, then run this test.\n";
        }
        
    } elseif ($response->getStatusCode() == 200) {
        echo "\n✅ SUCCESS! Page loaded correctly.\n";
        
        $content = $response->getContent();
        echo "\nPage characteristics:\n";
        echo "- Has Livewire: " . (strpos($content, 'wire:') !== false ? 'Yes' : 'No') . "\n";
        echo "- Has Alpine: " . (strpos($content, 'x-data') !== false ? 'Yes' : 'No') . "\n";
        echo "- Has 'Anrufe': " . (strpos($content, 'Anrufe') !== false ? 'Yes' : 'No') . "\n";
        echo "- Has 'Calls': " . (strpos($content, 'Calls') !== false ? 'Yes' : 'No') . "\n";
    }
    
    $kernel->terminate($request, $response);
    
} catch (\Throwable $e) {
    echo "\n\n❌ PHP EXCEPTION:\n";
    echo str_repeat('=', 60) . "\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack Trace:\n";
    echo $e->getTraceAsString();
}

echo "\n</pre>";

// Add a form to test with current session
if (!isset($sessionCookie)) {
    echo '<form method="get">
        <p>Or paste your session cookie here:</p>
        <input type="text" name="session" placeholder="askproai_session cookie value" style="width: 500px;">
        <button type="submit">Test with this session</button>
    </form>';
}
?>