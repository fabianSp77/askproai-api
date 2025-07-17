<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Authenticate as admin
\Illuminate\Support\Facades\Auth::loginUsingId(6);

echo "<h2>Appointment Display Test</h2>";

// 1. Check Appointments table
echo "<h3>1. Appointments in Database:</h3>";
$appointments = \App\Models\Appointment::with(['customer', 'staff', 'service', 'branch'])->get();
echo "Total appointments found: <strong>" . $appointments->count() . "</strong><br><br>";

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Customer</th><th>Staff</th><th>Service</th><th>Start</th><th>Status</th><th>Company ID</th></tr>";

foreach ($appointments->take(5) as $apt) {
    echo "<tr>";
    echo "<td>{$apt->id}</td>";
    echo "<td>" . ($apt->customer ? $apt->customer->first_name . ' ' . $apt->customer->last_name : 'N/A') . "</td>";
    echo "<td>" . ($apt->staff ? $apt->staff->first_name . ' ' . $apt->staff->last_name : 'N/A') . "</td>";
    echo "<td>" . ($apt->service ? $apt->service->name : 'N/A') . "</td>";
    echo "<td>" . ($apt->starts_at ? \Carbon\Carbon::parse($apt->starts_at)->format('d.m.Y H:i') : 'N/A') . "</td>";
    echo "<td>{$apt->status}</td>";
    echo "<td>{$apt->company_id}</td>";
    echo "</tr>";
}
echo "</table>";

// 2. Check Filament Query
echo "<h3>2. Filament Query Test:</h3>";
$query = \App\Filament\Admin\Resources\AppointmentResource::getEloquentQuery();
echo "SQL: " . $query->toSql() . "<br>";
echo "Bindings: " . json_encode($query->getBindings()) . "<br>";
echo "Count with Filament query: <strong>" . $query->count() . "</strong><br><br>";

// 3. Check user roles
echo "<h3>3. Current User Info:</h3>";
$user = auth()->user();
echo "User: {$user->email}<br>";
echo "Company ID: {$user->company_id}<br>";
echo "Roles: ";
foreach ($user->roles as $role) {
    echo $role->name . " ";
}
echo "<br><br>";

// 4. Test without any scopes
echo "<h3>4. Without any global scopes:</h3>";
$withoutScopes = \App\Models\Appointment::withoutGlobalScopes()->count();
echo "Total appointments without scopes: <strong>$withoutScopes</strong><br><br>";

echo "<br><a href='/admin/appointments' style='padding: 10px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px;'>Go to Appointments Page</a>";

// 5. Clear Livewire cache
echo "<h3>5. Clearing Livewire Cache:</h3>";
\Illuminate\Support\Facades\Artisan::call('livewire:clear');
echo "✅ Livewire cache cleared<br>";