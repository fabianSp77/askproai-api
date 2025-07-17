#!/usr/bin/env php
<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Complete User Experience Test ===\n\n";

// Test pages
$testUrls = [
    '/errors' => 'Error Catalog',
    '/errors/RETELL_001' => 'Error Detail Page',
    '/help' => 'Help Center',
    '/help/getting-started/first-call' => 'Help Article',
    '/admin' => 'Admin Panel',
];

foreach ($testUrls as $url => $name) {
    echo "Testing: $name ($url)\n";
    
    // Simulate HTTP request
    $server = ['REQUEST_URI' => $url, 'REQUEST_METHOD' => 'GET'];
    $request = \Illuminate\Http\Request::create($url, 'GET');
    
    try {
        $response = $kernel->handle($request);
        $statusCode = $response->getStatusCode();
        $content = $response->getContent();
        
        echo "- Status: $statusCode\n";
        echo "- Content length: " . strlen($content) . " bytes\n";
        
        // Check for common issues
        if (strpos($content, 'Exception') !== false || strpos($content, 'Error') !== false) {
            if (strpos($content, 'AskProAI Error Catalog') === false) { // Ignore if it's part of the title
                echo "⚠️  Page contains error text\n";
            }
        }
        
        // Check for key elements
        if ($url === '/errors') {
            if (strpos($content, 'x-data') !== false) {
                echo "✅ Alpine.js found\n";
            } else {
                echo "❌ Alpine.js missing\n";
            }
            
            if (strpos($content, 'RETELL_001') !== false) {
                echo "✅ Error data displayed\n";
            } else {
                echo "❌ No error data visible\n";
            }
        }
        
        if ($url === '/help') {
            if (strpos($content, 'Erste Schritte') !== false) {
                echo "✅ Categories displayed\n";
            } else {
                echo "❌ Categories missing\n";
            }
        }
        
    } catch (\Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// Test responsive design elements
echo "=== Responsive Design Check ===\n";
$responsiveClasses = [
    'sm:' => 'Small breakpoint',
    'md:' => 'Medium breakpoint', 
    'lg:' => 'Large breakpoint',
    'xl:' => 'Extra large breakpoint',
    'grid-cols-1' => 'Mobile grid',
    'md:grid-cols-2' => 'Tablet grid',
    'lg:grid-cols-3' => 'Desktop grid',
];

$errorView = file_get_contents(resource_path('views/errors/index.blade.php'));
foreach ($responsiveClasses as $class => $description) {
    if (strpos($errorView, $class) !== false) {
        echo "✅ $description ($class) found\n";
    } else {
        echo "⚠️  $description ($class) missing\n";
    }
}

// Check JavaScript includes
echo "\n=== JavaScript Dependencies ===\n";
$jsLibraries = [
    'alpinejs' => 'Alpine.js for interactivity',
    'tailwindcss' => 'Tailwind CSS for styling',
];

foreach ($jsLibraries as $lib => $description) {
    if (strpos($errorView, $lib) !== false) {
        echo "✅ $description included\n";
    } else {
        echo "❌ $description missing\n";
    }
}