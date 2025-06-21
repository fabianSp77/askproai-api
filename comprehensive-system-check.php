<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

echo "\033[1;34m=== COMPREHENSIVE SYSTEM CHECK ===\033[0m\n\n";

// Login as user
$user = User::where('email', 'fabian@askproai.de')->first();
if ($user) {
    Auth::login($user);
    echo "✅ Logged in as: {$user->email}\n\n";
}

// 1. Check all Filament Pages
echo "\033[1;33m1. CHECKING FILAMENT PAGES\033[0m\n";
$pages = [
    'Dashboard' => 'App\Filament\Admin\Pages\Dashboard',
    'QuickSetupWizard' => 'App\Filament\Admin\Pages\QuickSetupWizard',
    'StaffEventAssignment' => 'App\Filament\Admin\Pages\StaffEventAssignment',
    'StaffEventAssignmentModern' => 'App\Filament\Admin\Pages\StaffEventAssignmentModern',
    'EventTypeImportWizard' => 'App\Filament\Admin\Pages\EventTypeImportWizard',
    'SystemHealthSimple' => 'App\Filament\Admin\Pages\SystemHealthSimple',
    'CalcomLiveTest' => 'App\Filament\Admin\Pages\CalcomLiveTest',
];

foreach ($pages as $name => $class) {
    if (class_exists($class)) {
        try {
            $page = new $class();
            // Test if page can be instantiated
            echo "✅ $name - OK\n";
        } catch (\Exception $e) {
            echo "❌ $name - Error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ $name - Class not found\n";
    }
}

// 2. Check all Filament Resources
echo "\n\033[1;33m2. CHECKING FILAMENT RESOURCES\033[0m\n";
$resources = [
    'Staff' => 'App\Filament\Admin\Resources\StaffResource',
    'Appointment' => 'App\Filament\Admin\Resources\AppointmentResource',
    'Branch' => 'App\Filament\Admin\Resources\BranchResource',
    'Company' => 'App\Filament\Admin\Resources\CompanyResource',
    'Customer' => 'App\Filament\Admin\Resources\CustomerResource',
    'Service' => 'App\Filament\Admin\Resources\ServiceResource',
    'Call' => 'App\Filament\Admin\Resources\CallResource',
    'Invoice' => 'App\Filament\Admin\Resources\InvoiceResource',
    'PhoneNumber' => 'App\Filament\Admin\Resources\PhoneNumberResource',
];

foreach ($resources as $name => $class) {
    if (class_exists($class)) {
        try {
            // Check permissions
            $canView = $class::canViewAny();
            echo "✅ $name - Permissions: " . ($canView ? "OK" : "BLOCKED") . "\n";
        } catch (\Exception $e) {
            echo "❌ $name - Error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ $name - Class not found\n";
    }
}

// 3. Check Critical Database Tables
echo "\n\033[1;33m3. CHECKING DATABASE TABLES\033[0m\n";
$tables = [
    'companies', 'branches', 'staff', 'services', 'customers', 
    'appointments', 'calls', 'users', 'invoices', 'invoice_items',
    'phone_numbers', 'calcom_event_types', 'staff_event_types',
    'branch_staff', 'branch_service', 'staff_services'
];

foreach ($tables as $table) {
    try {
        $exists = DB::select("SELECT 1 FROM $table LIMIT 1");
        echo "✅ Table '$table' - OK\n";
    } catch (\Exception $e) {
        echo "❌ Table '$table' - Missing or Error\n";
    }
}

// 4. Check Critical Services
echo "\n\033[1;33m4. CHECKING CRITICAL SERVICES\033[0m\n";
$services = [
    'DashboardMetricsService' => 'App\Services\Dashboard\DashboardMetricsService',
    'AppointmentService' => 'App\Services\AppointmentService',
    'CalcomV2Service' => 'App\Services\CalcomV2Service',
    'RetellService' => 'App\Services\RetellService',
];

foreach ($services as $name => $class) {
    if (class_exists($class)) {
        try {
            $service = app($class);
            echo "✅ $name - OK\n";
        } catch (\Exception $e) {
            echo "❌ $name - Error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ $name - Class not found\n";
    }
}

// 5. Check Routes
echo "\n\033[1;33m5. CHECKING CRITICAL ROUTES\033[0m\n";
$routes = [
    '/admin' => 'GET',
    '/admin/staff' => 'GET',
    '/admin/appointments' => 'GET',
    '/admin/branches' => 'GET',
    '/admin/customers' => 'GET',
    '/api/retell/webhook' => 'POST',
    '/api/calcom/webhook' => 'POST',
];

foreach ($routes as $uri => $method) {
    $route = Route::getRoutes()->match(
        Request::create($uri, $method)
    );
    
    if ($route) {
        echo "✅ $method $uri - OK\n";
    } else {
        echo "❌ $method $uri - Not found\n";
    }
}

// Summary
echo "\n\033[1;34m=== SYSTEM STATUS ===\033[0m\n";
echo "The system check is complete. Review any ❌ items above.\n";

if ($user) {
    Auth::logout();
}