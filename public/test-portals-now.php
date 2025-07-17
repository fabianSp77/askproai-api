<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Force error display
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Portal Status Test</h2>";

// Test 1: Admin Appointments Route
echo "<h3>1. Testing Admin Appointments Route:</h3>";
try {
    $route = app('router')->getRoutes()->match(
        app('request')->create('/admin/appointments', 'GET')
    );
    echo "✅ Route exists: " . $route->getName() . "<br>";
    echo "Controller: " . $route->getActionName() . "<br>";
} catch (\Exception $e) {
    echo "❌ Route error: " . $e->getMessage() . "<br>";
}

// Test 2: Business Portal Login Route
echo "<h3>2. Testing Business Portal Login Route:</h3>";
try {
    $route = app('router')->getRoutes()->match(
        app('request')->create('/business/login', 'GET')
    );
    echo "✅ Route exists: " . $route->getName() . "<br>";
    echo "Controller: " . $route->getActionName() . "<br>";
} catch (\Exception $e) {
    echo "❌ Route error: " . $e->getMessage() . "<br>";
}

// Test 3: Livewire Components
echo "<h3>3. Testing Livewire Components:</h3>";
try {
    $components = \Livewire\Livewire::getAlias();
    echo "✅ Livewire components registered: " . count($components) . "<br>";
} catch (\Exception $e) {
    echo "❌ Livewire error: " . $e->getMessage() . "<br>";
}

// Test 4: Create direct links
echo "<h3>4. Direct Test Links:</h3>";
?>
<div style="margin: 20px 0;">
    <a href="/admin/appointments" style="padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; margin: 5px; display: inline-block;">
        Test Admin Appointments
    </a>
    <a href="/business/login" style="padding: 10px 20px; background: #10b981; color: white; text-decoration: none; margin: 5px; display: inline-block;">
        Test Business Login
    </a>
</div>

<h3>5. Testing Filament Resources:</h3>
<?php
try {
    $appointmentResource = \App\Filament\Admin\Resources\AppointmentResource::class;
    echo "✅ AppointmentResource exists<br>";
    
    // Test if we can get the model
    $model = $appointmentResource::getModel();
    echo "✅ Model: $model<br>";
    
    // Test query
    $query = $appointmentResource::getEloquentQuery();
    $count = $query->count();
    echo "✅ Appointments count: $count<br>";
} catch (\Exception $e) {
    echo "❌ Filament error: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Test 6: Session and Auth
echo "<h3>6. Session and Auth Status:</h3>";
$user = auth()->user();
if ($user) {
    echo "✅ Authenticated as: {$user->email}<br>";
} else {
    echo "⚠️ Not authenticated<br>";
}

echo "<br><strong>If you see this page without errors, the basic system is working.</strong>";
echo "<br>The 500 errors might be caused by missing JavaScript files or session issues.";