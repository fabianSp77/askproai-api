<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Force login as admin
$user = \App\Models\User::where('email', 'admin@askproai.de')->first();
auth()->login($user);

echo "<!DOCTYPE html><html><head><title>Appointment Debug</title></head><body>";
echo "<h1>Appointment Resource Debug</h1>";

try {
    // Test permissions
    echo "<h2>Permissions</h2>";
    echo "<p>User: " . $user->name . " (" . $user->email . ")</p>";
    echo "<p>Roles: " . $user->roles->pluck('name')->join(', ') . "</p>";
    echo "<p>Can view any: " . (\App\Filament\Admin\Resources\AppointmentResource::canViewAny() ? 'Yes' : 'No') . "</p>";
    
    // Test query
    echo "<h2>Query Test</h2>";
    $query = \App\Filament\Admin\Resources\AppointmentResource::getEloquentQuery();
    $count = $query->count();
    echo "<p>Query count: $count</p>";
    
    // Get first 5 appointments
    $appointments = $query->with(['customer', 'staff', 'service', 'branch', 'company'])->limit(5)->get();
    echo "<h3>First 5 Appointments:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Customer</th><th>Service</th><th>Status</th><th>Start</th></tr>";
    foreach ($appointments as $appointment) {
        echo "<tr>";
        echo "<td>{$appointment->id}</td>";
        echo "<td>" . ($appointment->customer ? $appointment->customer->name : 'N/A') . "</td>";
        echo "<td>" . ($appointment->service ? $appointment->service->name : 'N/A') . "</td>";
        echo "<td>{$appointment->status}</td>";
        echo "<td>{$appointment->starts_at}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test ListAppointments page
    echo "<h2>ListAppointments Page Test</h2>";
    $page = new \App\Filament\Admin\Resources\AppointmentResource\Pages\ListAppointments();
    echo "<p>Page class exists: Yes</p>";
    
    // Check for any errors
    if (function_exists('error_get_last')) {
        $error = error_get_last();
        if ($error) {
            echo "<h3>Last Error:</h3>";
            echo "<pre>" . print_r($error, true) . "</pre>";
        }
    }
    
} catch (\Exception $e) {
    echo "<h2>Error:</h2>";
    echo "<pre>" . $e->getMessage() . "\n" . $e->getTraceAsString() . "</pre>";
}

echo "</body></html>";