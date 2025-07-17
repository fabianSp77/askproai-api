<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Appointment;
use App\Models\Company;
use App\Models\User;

echo "=== APPOINTMENT DISPLAY DEBUG ===\n\n";

// Get admin user
$admin = User::where('email', 'admin@askproai.de')->first();
if (!$admin) {
    die("❌ Admin user not found\n");
}

echo "Admin User: {$admin->name} (ID: {$admin->id})\n";
echo "Company ID: {$admin->company_id}\n\n";

// Check appointments without scope
$totalAppointments = DB::table('appointments')->count();
echo "Total appointments in DB: $totalAppointments\n";

$companyAppointments = DB::table('appointments')
    ->where('company_id', $admin->company_id)
    ->count();
echo "Appointments for company {$admin->company_id}: $companyAppointments\n\n";

// Check with Eloquent (with scope)
echo "Checking with Eloquent model (TenantScope active):\n";
$appointments = Appointment::count();
echo "Visible appointments: $appointments\n\n";

// Check recent appointments
echo "Recent appointments for company {$admin->company_id}:\n";
$recent = DB::table('appointments')
    ->where('company_id', $admin->company_id)
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get(['id', 'status', 'starts_at', 'created_at']);

foreach ($recent as $apt) {
    echo "- ID: {$apt->id}, Status: {$apt->status}, Start: {$apt->starts_at}, Created: {$apt->created_at}\n";
}

// Check if TenantScope is being applied
echo "\nChecking TenantScope application:\n";
$query = Appointment::query();
$sql = $query->toSql();
$bindings = $query->getBindings();
echo "SQL: $sql\n";
echo "Bindings: " . json_encode($bindings) . "\n";

// Check session/auth
echo "\nAuth/Session Check:\n";
if (session()->has('company_id')) {
    echo "Session company_id: " . session('company_id') . "\n";
} else {
    echo "No company_id in session\n";
}

// Check Filament context
echo "\nFilament Context Check:\n";
$filamentUser = \Filament\Facades\Filament::auth()->user();
if ($filamentUser) {
    echo "Filament User: {$filamentUser->email}\n";
    echo "Filament User Company: {$filamentUser->company_id}\n";
} else {
    echo "No Filament user context (expected in CLI)\n";
}