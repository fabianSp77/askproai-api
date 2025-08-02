<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
require_once __DIR__ . "/../vendor/autoload.php";
$app = require_once __DIR__ . "/../bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create("/admin", "GET");
try {
    $response = $kernel->handle($request);
    $content = $response->getContent();
    if ($response->getStatusCode() === 500) {
        echo "500 Error detected. Response content:\n\n";
        echo $content;
    } else {
        echo "Status: " . $response->getStatusCode() . "\n";
        echo "Redirecting to: " . ($response->headers->get("Location") ?? "No redirect") . "\n";
    }
} catch (\Exception $e) {
    echo "Exception caught:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
