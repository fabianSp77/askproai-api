<?php

// Direct test of tenant view page
session_start();

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Bootstrap Laravel kernel
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;

// Login admin
$admin = User::where('email', 'admin@askproai.de')->first();
if ($admin) {
    Auth::login($admin);
    echo "Logged in as: " . $admin->email . "\n\n";
}

// Get tenant
$tenant = Tenant::find(1);
if (!$tenant) {
    echo "Tenant not found\n";
    exit;
}

echo "Tenant Data:\n";
echo "============\n";
echo "ID: " . $tenant->id . "\n";
echo "Name: " . $tenant->name . "\n";
echo "Email: " . $tenant->email . "\n";
echo "Active: " . ($tenant->is_active ? 'Yes' : 'No') . "\n";
echo "Balance: " . ($tenant->balance_cents / 100) . " EUR\n";
echo "\n";

// Check if ViewTenant page exists
$viewPageClass = 'App\Filament\Admin\Resources\TenantResource\Pages\ViewTenant';
if (class_exists($viewPageClass)) {
    echo "✅ ViewTenant page class exists\n";
} else {
    echo "❌ ViewTenant page class not found\n";
}

// Check if TenantResource has infolist
$resourceClass = 'App\Filament\Admin\Resources\TenantResource';
if (method_exists($resourceClass, 'infolist')) {
    echo "✅ TenantResource has infolist() method\n";
    
    // Check infolist configuration
    try {
        // We can't directly test the infolist without Livewire context
        // But we can check if it's properly defined
        $reflection = new ReflectionMethod($resourceClass, 'infolist');
        $parameters = $reflection->getParameters();
        
        if (count($parameters) === 1 && $parameters[0]->getType()) {
            $typeName = $parameters[0]->getType()->getName();
            if (strpos($typeName, 'Infolist') !== false) {
                echo "✅ Infolist method signature is correct\n";
            }
        }
    } catch (Exception $e) {
        echo "⚠️  Could not analyze infolist method: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ TenantResource missing infolist() method\n";
}

echo "\nURL to test: https://api.askproai.de/admin/tenants/1\n";
echo "\nIf the page is empty, possible causes:\n";
echo "1. Livewire component not loading properly\n";
echo "2. JavaScript errors preventing render\n";
echo "3. Missing or incorrect view templates\n";
echo "4. Authentication/session issues\n";