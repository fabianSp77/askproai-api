<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n🔍 Testing Portal Pages for Errors\n";
echo str_repeat('=', 50) . "\n\n";

// Test different portal pages
$pages = [
    '/admin/company-integration-portal' => 'Company Integration Portal',
    '/admin/retell-webhook-configuration' => 'Retell Webhook Configuration',
    '/admin/simple-company-integration-portal' => 'Simple Company Integration Portal',
];

// Create a test user and authenticate
$user = \App\Models\User::first();
if (!$user) {
    echo "❌ No user found. Creating test user...\n";
    $user = \App\Models\User::create([
        'name' => 'Test Admin',
        'email' => 'test@admin.com',
        'password' => bcrypt('password'),
    ]);
}

auth()->login($user);

foreach ($pages as $url => $name) {
    echo "Testing: {$name}\n";
    echo "URL: {$url}\n";
    
    try {
        // Try to create the page instance
        $routeName = str_replace('/admin/', 'filament.admin.pages.', $url);
        
        // Check if route exists
        if (!Route::has($routeName)) {
            echo "❌ Route not found: {$routeName}\n\n";
            continue;
        }
        
        // Try to access the page class directly
        $className = match($url) {
            '/admin/company-integration-portal' => \App\Filament\Admin\Pages\CompanyIntegrationPortal::class,
            '/admin/retell-webhook-configuration' => \App\Filament\Admin\Pages\RetellWebhookConfiguration::class,
            '/admin/simple-company-integration-portal' => \App\Filament\Admin\Pages\SimpleCompanyIntegrationPortal::class,
            default => null
        };
        
        if ($className && class_exists($className)) {
            echo "✅ Page class exists: {$className}\n";
            
            // Try to instantiate
            try {
                $page = new $className();
                echo "✅ Page instantiation successful\n";
            } catch (\Exception $e) {
                echo "❌ Error instantiating page: " . $e->getMessage() . "\n";
                echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
            }
        } else {
            echo "❌ Page class not found\n";
        }
        
    } catch (\Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
    
    echo "\n";
}

echo str_repeat('=', 50) . "\n";
echo "Test complete.\n\n";