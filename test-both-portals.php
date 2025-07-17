<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== PORTAL TEST REPORT ===\n";
echo date('Y-m-d H:i:s') . "\n\n";

// 1. Admin Portal Appointment Test
echo "1. ADMIN PORTAL - APPOINTMENTS:\n";
echo "--------------------------------\n";

// Authenticate as admin
\Illuminate\Support\Facades\Auth::loginUsingId(6);
$adminUser = \Illuminate\Support\Facades\Auth::user();
echo "Logged in as: {$adminUser->email}\n";
echo "Company ID: {$adminUser->company_id}\n";
echo "Roles: ";
foreach ($adminUser->roles as $role) {
    echo $role->name . " ";
}
echo "\n\n";

// Check appointments
$appointments = \App\Models\Appointment::count();
echo "Appointments visible with scope: $appointments\n";

$appointmentsWithoutScope = \App\Models\Appointment::withoutGlobalScopes()->count();
echo "Appointments without scope: $appointmentsWithoutScope\n";

// Test Filament query
$filamentQuery = \App\Filament\Admin\Resources\AppointmentResource::getEloquentQuery();
$filamentCount = $filamentQuery->count();
echo "Filament query count: $filamentCount\n";
echo "SQL: " . $filamentQuery->toSql() . "\n";
echo "Bindings: " . json_encode($filamentQuery->getBindings()) . "\n\n";

// 2. Business Portal Test
echo "2. BUSINESS PORTAL - LOGIN TEST:\n";
echo "---------------------------------\n";

// Test portal user
$portalUser = \App\Models\PortalUser::where('email', 'demo@example.com')->first();
if ($portalUser) {
    echo "Portal User found: {$portalUser->email}\n";
    echo "Company: {$portalUser->company->name}\n";
    echo "Active: " . ($portalUser->is_active ? 'Yes' : 'No') . "\n";
    
    // Test password
    $passwordCorrect = \Illuminate\Support\Facades\Hash::check('password', $portalUser->password);
    echo "Password 'password' is correct: " . ($passwordCorrect ? 'Yes' : 'No') . "\n";
} else {
    echo "❌ Portal user demo@example.com not found!\n";
}

// 3. Session Test
echo "\n3. SESSION CONFIGURATION:\n";
echo "-------------------------\n";
echo "Default driver: " . config('session.driver') . "\n";
echo "Default domain: " . config('session.domain') . "\n";
echo "Default path: " . config('session.path') . "\n";
echo "Admin session config exists: " . (config('session_admin') ? 'Yes' : 'No') . "\n";
echo "Portal session config exists: " . (config('session_portal') ? 'Yes' : 'No') . "\n";

// 4. Route Test
echo "\n4. ROUTE TEST:\n";
echo "--------------\n";
$routes = [
    '/admin/appointments' => 'GET',
    '/business/login' => 'GET',
    '/business/login' => 'POST',
    '/business/dashboard' => 'GET',
    '/business/api/dashboard' => 'GET'
];

foreach ($routes as $route => $method) {
    try {
        $routeExists = \Illuminate\Support\Facades\Route::has(trim($route, '/'));
        echo "$method $route: " . ($routeExists ? '✅ Exists' : '❌ Not found') . "\n";
    } catch (\Exception $e) {
        echo "$method $route: Check manually\n";
    }
}

// 5. Clear all caches
echo "\n5. CLEARING CACHES:\n";
echo "-------------------\n";
\Illuminate\Support\Facades\Artisan::call('optimize:clear');
echo "✅ All caches cleared\n";

// 6. Fix Recommendations
echo "\n6. RECOMMENDATIONS:\n";
echo "-------------------\n";
echo "- Admin Portal: Appointments should now be visible after cache clear\n";
echo "- Business Portal: Use demo@example.com / password to test login\n";
echo "- If appointments still don't show, check browser console for JS errors\n";
echo "- If login fails with 419, clear browser cookies and try again\n";

echo "\n✅ Test complete!\n";