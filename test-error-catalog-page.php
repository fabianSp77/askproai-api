#!/usr/bin/env php
<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Test Error Catalog Page
echo "=== Testing Error Catalog Page ===\n\n";

// 1. Test Controller
try {
    $controller = new \App\Http\Controllers\ErrorCatalogController();
    $request = new \Illuminate\Http\Request();
    
    echo "1. Testing index method...\n";
    $response = $controller->index($request);
    
    if ($response instanceof \Illuminate\View\View) {
        echo "✅ View returned successfully\n";
        echo "View name: " . $response->name() . "\n";
        
        $data = $response->getData();
        echo "\nView data:\n";
        echo "- errors count: " . ($data['errors'] ? $data['errors']->count() : 0) . "\n";
        echo "- categories: " . count($data['categories'] ?? []) . "\n";
        echo "- allTags: " . ($data['allTags'] ? $data['allTags']->count() : 0) . "\n";
        echo "- totalErrors: " . ($data['totalErrors'] ?? 'not set') . "\n";
    } else {
        echo "❌ Unexpected response type: " . get_class($response) . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// 2. Test specific error
echo "\n2. Testing show method...\n";
try {
    $response = $controller->show('RETELL_001');
    if ($response instanceof \Illuminate\View\View) {
        echo "✅ Error detail view works\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// 3. Test view rendering
echo "\n3. Testing view rendering...\n";
try {
    $errors = \App\Models\ErrorCatalog::with('tags')->limit(5)->get();
    $allTags = \App\Models\ErrorTag::all();
    $totalErrors = \App\Models\ErrorCatalog::count();
    $categories = ['DB', 'API', 'INTEGRATION'];
    
    $html = view('errors.index', compact('errors', 'categories', 'allTags', 'totalErrors'))->render();
    
    echo "✅ View renders without errors\n";
    echo "HTML length: " . strlen($html) . " characters\n";
    
    // Check for key elements
    if (strpos($html, 'AskProAI Error Catalog') !== false) {
        echo "✅ Title found\n";
    } else {
        echo "❌ Title missing\n";
    }
    
    if (strpos($html, 'x-data=') !== false) {
        echo "✅ Alpine.js directives found\n";
    } else {
        echo "❌ Alpine.js directives missing\n";
    }
    
} catch (\Exception $e) {
    echo "❌ View rendering error: " . $e->getMessage() . "\n";
}