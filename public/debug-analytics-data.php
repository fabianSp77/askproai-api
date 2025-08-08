<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = \Illuminate\Http\Request::capture()
);

use App\Models\Company;
use App\Models\Appointment;
use Carbon\Carbon;

// Get a company with data
$company = Company::whereHas('appointments')->first();

if ($company) {
    echo "Testing with company: {$company->name} (ID: {$company->id})\n\n";
    
    // Check appointments in last 30 days
    $dateFrom = now()->subDays(30)->format('Y-m-d');
    $dateTo = now()->format('Y-m-d');
    
    $appointments = Appointment::where('company_id', $company->id)
        ->whereBetween('starts_at', [$dateFrom, $dateTo])
        ->count();
    
    echo "Appointments in last 30 days: $appointments\n";
    
    // Check if we have any appointments at all
    $totalAppointments = Appointment::where('company_id', $company->id)->count();
    echo "Total appointments for company: $totalAppointments\n";
    
    // Get date range with actual data
    $firstAppointment = Appointment::where('company_id', $company->id)
        ->orderBy('starts_at', 'asc')
        ->first();
    $lastAppointment = Appointment::where('company_id', $company->id)
        ->orderBy('starts_at', 'desc')
        ->first();
    
    if ($firstAppointment && $lastAppointment) {
        echo "\nDate range with data:\n";
        echo "First appointment: " . $firstAppointment->starts_at . "\n";
        echo "Last appointment: " . $lastAppointment->starts_at . "\n";
    }
} else {
    echo "No companies with appointments found\!\n";
}
