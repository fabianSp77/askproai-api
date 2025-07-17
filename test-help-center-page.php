#!/usr/bin/env php
<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Testing Help Center ===\n\n";

// 1. Test main page
echo "1. Testing Help Center index...\n";
try {
    $response = app()->call('\App\Http\Controllers\HelpCenterController@index');
    if ($response instanceof \Illuminate\View\View) {
        echo "✅ Help Center index works\n";
        $data = $response->getData();
        echo "- categories: " . count($data['categories'] ?? []) . "\n";
        echo "- popularArticles: " . count($data['popularArticles'] ?? []) . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// 2. Test article
echo "\n2. Testing article view...\n";
try {
    $controller = new \App\Http\Controllers\HelpCenterController();
    $request = new \Illuminate\Http\Request();
    $response = $controller->article('getting-started', 'first-call', $request);
    
    if ($response instanceof \Illuminate\View\View) {
        echo "✅ Article view works\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// 3. Check markdown files
echo "\n3. Checking markdown files...\n";
$basePath = resource_path('docs/help-center');
$categories = ['account', 'appointments', 'billing', 'faq', 'getting-started', 'troubleshooting'];

foreach ($categories as $category) {
    $files = glob("$basePath/$category/*.md");
    echo "- $category: " . count($files) . " files\n";
}

// 4. Test search
echo "\n4. Testing search functionality...\n";
try {
    $request = new \Illuminate\Http\Request(['q' => 'termin']);
    $response = app()->call('\App\Http\Controllers\HelpCenterController@search', ['request' => $request]);
    
    if ($response instanceof \Illuminate\View\View) {
        echo "✅ Search works\n";
    }
} catch (\Exception $e) {
    echo "❌ Search error: " . $e->getMessage() . "\n";
}