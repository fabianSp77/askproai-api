<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Appointment;
use App\Models\Company;
use App\Models\PortalUser;
use Carbon\Carbon;

// Set company context
$companyId = 1;
app()->instance('current_company_id', $companyId);

echo "=== Testing Appointments Page Data ===\n\n";

// Get company
$company = Company::find($companyId);
echo "Company: {$company->name} (ID: {$company->id})\n\n";

// Count appointments
$totalAppointments = Appointment::where('company_id', $companyId)->count();
echo "Total appointments for company: $totalAppointments\n\n";

// Get appointments by status
$statuses = Appointment::where('company_id', $companyId)
    ->select('status', \DB::raw('count(*) as count'))
    ->groupBy('status')
    ->get();

echo "Appointments by status:\n";
foreach ($statuses as $status) {
    echo "  {$status->status}: {$status->count}\n";
}

// Get today's appointments
$today = Carbon::today();
$todayAppointments = Appointment::where('company_id', $companyId)
    ->whereDate('starts_at', $today)
    ->with(['customer'])
    ->get();

echo "\nToday's appointments ({$today->format('Y-m-d')}):\n";
foreach ($todayAppointments as $apt) {
    $customerName = $apt->customer ? $apt->customer->name : 'No customer';
    echo "  - ID: {$apt->id}, Status: {$apt->status}, Customer: {$customerName}, Time: {$apt->starts_at->format('H:i')}\n";
}

// Get this week's appointments
$weekStart = Carbon::now()->startOfWeek();
$weekEnd = Carbon::now()->endOfWeek();
$weekAppointments = Appointment::where('company_id', $companyId)
    ->whereBetween('starts_at', [$weekStart, $weekEnd])
    ->count();

echo "\nThis week's appointments: $weekAppointments\n";

// Get branches, staff, services for filters
$branches = $company->branches()->count();
$staff = $company->staff()->count();
$services = $company->services()->count();

echo "\nAvailable filters:\n";
echo "  Branches: $branches\n";
echo "  Staff: $staff\n";  
echo "  Services: $services\n";

// Test API controller directly
echo "\n=== Testing API Controller ===\n";

// Fake authentication for test
$portalUser = PortalUser::where('company_id', $companyId)->first();
if ($portalUser) {
    \Auth::guard('portal')->login($portalUser);
    echo "Logged in as: {$portalUser->email}\n";
    
    // Create request
    $request = new \Illuminate\Http\Request();
    
    // Call controller
    $controller = new \App\Http\Controllers\Portal\Api\AppointmentsApiController();
    $response = $controller->index($request);
    
    $data = json_decode($response->getContent(), true);
    
    if (isset($data['appointments'])) {
        echo "API returned {$data['appointments']['total']} appointments\n";
        echo "Stats: " . json_encode($data['stats']) . "\n";
    } else {
        echo "API Error: " . json_encode($data) . "\n";
    }
} else {
    echo "No portal user found for company\n";
}

echo "\n=== Test Complete ===\n";