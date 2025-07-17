<?php
// Test if admin panel renders
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('/admin', 'GET');

// Set up authentication
$request->setUserResolver(function () use ($app) {
    return \App\Models\User::find(6); // Your user ID
});

try {
    $response = $kernel->handle($request);
    
    echo "<h1>Response Status: " . $response->getStatusCode() . "</h1>";
    echo "<h2>Response Headers:</h2><pre>";
    print_r($response->headers->all());
    echo "</pre>";
    
    $content = $response->getContent();
    echo "<h2>Response Length: " . strlen($content) . " bytes</h2>";
    
    if (strlen($content) < 1000) {
        echo "<h2>Full Response:</h2>";
        echo "<pre>" . htmlspecialchars($content) . "</pre>";
    } else {
        echo "<h2>First 2000 characters:</h2>";
        echo "<pre>" . htmlspecialchars(substr($content, 0, 2000)) . "</pre>";
        
        echo "<h2>Last 1000 characters:</h2>";
        echo "<pre>" . htmlspecialchars(substr($content, -1000)) . "</pre>";
    }
    
} catch (\Exception $e) {
    echo "<h1>Error: " . $e->getMessage() . "</h1>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>