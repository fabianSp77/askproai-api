<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

// Get company and check calls
$company = \App\Models\Company::find(1);
echo "Company: " . $company->name . " (ID: " . $company->id . ")\n\n";

// Set the company context for TenantScope
app()->instance('current_company_id', 1);

// Check different time ranges
$ranges = ['today', 'week', 'month', 'year'];
foreach ($ranges as $range) {
    echo "=== Range: $range ===\n";
    
    switch ($range) {
        case 'today':
            $start = now()->startOfDay();
            break;
        case 'week':
            $start = now()->startOfWeek();
            break;
        case 'month':
            $start = now()->startOfMonth();
            break;
        case 'year':
            $start = now()->startOfYear();
            break;
    }
    
    $calls = \App\Models\Call::where('company_id', 1)
        ->where('created_at', '>=', $start)
        ->get();
        
    $avgDuration = $calls->where('duration_sec', '>', 0)->avg('duration_sec') ?? 0;
    
    echo "Calls: " . $calls->count() . "\n";
    echo "Avg Duration: " . round($avgDuration) . " seconds (" . gmdate('i:s', $avgDuration) . ")\n";
    
    if ($calls->count() > 0) {
        echo "First call: " . $calls->first()->created_at . "\n";
        echo "Last call: " . $calls->last()->created_at . "\n";
    }
    echo "\n";
}

// Check phone numbers
echo "=== Company Phone Numbers ===\n";
$phoneNumbers = \App\Models\PhoneNumber::where('company_id', 1)->get();
foreach ($phoneNumbers as $phone) {
    echo "- " . $phone->number . " (Active: " . ($phone->is_active ? 'Yes' : 'No') . ")\n";
}
echo "\n";
