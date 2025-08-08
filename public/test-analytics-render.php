<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\User;

// Login as admin
$admin = User::where('email', 'dev@askproai.de')->first() ?? User::find(5);
auth()->login($admin);

// Simulate the page data
$companyId = null; // IMPORTANT: null to show aggregate view
$viewMode = 'combined';
$stats = [
    'total_companies' => 8,
    'total_appointments' => 100,
    'completed' => 80,
    'no_show' => 5,
    'total_calls' => 250,
    'successful_calls' => 200,
    'revenue' => 15000,
    'completion_rate' => 80,
    'no_show_rate' => 5,
    'call_success_rate' => 80,
    'avg_revenue_per_appointment' => 187.50,
];

$callMetrics = [
    'inbound' => [
        'total_calls' => 150,
        'answered_calls' => 120,
        'missed_calls' => 30,
        'answer_rate' => 80,
        'appointment_conversion_rate' => 65,
        'avg_call_duration' => 5.5,
        'peak_hours' => [
            ['hour' => 10, 'count' => 25],
            ['hour' => 14, 'count' => 20],
            ['hour' => 16, 'count' => 18],
        ]
    ],
    'outbound' => [
        'total_calls' => 100,
        'connected_calls' => 70,
        'failed_calls' => 30,
        'connect_rate' => 70,
        'qualification_rate' => 50,
        'appointment_rate' => 30,
        'campaigns' => []
    ]
];

$leadFunnelData = [
    'contacted' => 100,
    'connected' => 70,
    'qualified' => 35,
    'appointment_set' => 21,
];

$companyComparison = [
    ['company' => 'Test Company 1', 'appointments' => 50, 'completion_rate' => 85, 'calls' => 100, 'call_success_rate' => 75, 'revenue' => 5000],
    ['company' => 'Test Company 2', 'appointments' => 40, 'completion_rate' => 80, 'calls' => 80, 'call_success_rate' => 70, 'revenue' => 4000],
];

// Check if view file has the new content
$viewPath = resource_path('views/filament/admin/pages/event-analytics-dashboard.blade.php');
$viewContent = file_get_contents($viewPath);

// Extract the relevant part
$startPos = strpos($viewContent, '@if(!$companyId');
$endPos = strpos($viewContent, '@elseif($companyId)', $startPos);

if ($startPos !== false && $endPos !== false) {
    $relevantSection = substr($viewContent, $startPos, $endPos - $startPos);
    
    echo "<!DOCTYPE html>\n";
    echo "<html><head><title>Analytics Test</title>";
    echo "<link href='https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css' rel='stylesheet'>";
    echo "</head><body class='bg-gray-100 p-8'>";
    echo "<h1 class='text-2xl font-bold mb-4'>Analytics Dashboard Test Render</h1>";
    
    echo "<div class='bg-white rounded-lg shadow p-4 mb-4'>";
    echo "<h2 class='font-bold mb-2'>Condition Check:</h2>";
    echo "<pre class='bg-gray-100 p-2 rounded text-sm'>";
    echo "companyId = " . var_export($companyId, true) . " (should be NULL)\n";
    echo "User is Super Admin = " . (auth()->user()->hasRole(['Super Admin', 'super_admin']) ? 'YES' : 'NO') . "\n";
    echo "Condition: @if(!companyId && auth()->user()->hasRole(['Super Admin', 'super_admin']))\n";
    echo "Result: " . (!$companyId && auth()->user()->hasRole(['Super Admin', 'super_admin']) ? 'TRUE - Should show overview' : 'FALSE - Will not show') . "\n";
    echo "</pre>";
    echo "</div>";
    
    echo "<div class='bg-white rounded-lg shadow p-4 mb-4'>";
    echo "<h2 class='font-bold mb-2'>View Section Found (lines " . substr_count(substr($viewContent, 0, $startPos), "\n") . "-" . substr_count(substr($viewContent, 0, $endPos), "\n") . "):</h2>";
    echo "<pre class='bg-gray-100 p-2 rounded text-xs overflow-x-auto'>";
    echo htmlspecialchars(substr($relevantSection, 0, 500)) . "...";
    echo "</pre>";
    echo "</div>";
    
    // Test render with Blade
    echo "<div class='bg-white rounded-lg shadow p-4'>";
    echo "<h2 class='font-bold mb-2'>What SHOULD be displayed:</h2>";
    echo "<div class='bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6'>";
    echo "<h2 class='text-xl font-bold mb-4'>📊 Gesamt-Übersicht aller Unternehmen</h2>";
    echo "<div class='grid grid-cols-4 gap-4'>";
    echo "<div class='bg-white rounded-lg p-4'><div class='text-sm text-gray-500'>Aktive Unternehmen</div><div class='text-2xl font-bold'>8</div></div>";
    echo "<div class='bg-white rounded-lg p-4'><div class='text-sm text-gray-500'>Gesamt-Termine</div><div class='text-2xl font-bold'>100</div></div>";
    echo "<div class='bg-white rounded-lg p-4'><div class='text-sm text-gray-500'>Gesamt-Anrufe</div><div class='text-2xl font-bold'>250</div></div>";
    echo "<div class='bg-white rounded-lg p-4'><div class='text-sm text-gray-500'>Gesamt-Umsatz</div><div class='text-2xl font-bold'>15.000 €</div></div>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='mt-8 text-center'>";
    echo "<a href='/admin/event-analytics-dashboard' class='bg-blue-500 text-white px-6 py-3 rounded-lg text-lg hover:bg-blue-600'>Open Real Dashboard →</a>";
    echo "</div>";
    
    echo "</body></html>";
} else {
    echo "ERROR: Could not find the new section in the view file!";
}