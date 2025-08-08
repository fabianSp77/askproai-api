<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\User;

// Login as admin  
$admin = User::where('email', 'dev@askproai.de')->first() ?? User::find(5);
auth()->login($admin);

// Test Blade compilation
$blade = app('blade.compiler');

$testTemplate = '
@if(!$companyId && auth()->user()->hasRole([\'Super Admin\', \'super_admin\']))
    <div>SHOWING: Gesamt-Übersicht aller Unternehmen</div>
@elseif($companyId)
    <div>SHOWING: Company specific view (ID: {{ $companyId }})</div>
@else
    <div>SHOWING: No data message</div>
@endif
';

$compiled = $blade->compileString($testTemplate);

echo "<!DOCTYPE html><html><head><title>Blade Test</title></head><body>";
echo "<h1>Blade Condition Test</h1>";
echo "<pre>User: " . auth()->user()->email . "</pre>";
echo "<pre>Is Super Admin: " . (auth()->user()->hasRole(['Super Admin', 'super_admin']) ? 'YES' : 'NO') . "</pre>";

// Test with different values
$tests = [
    ['companyId' => null, 'expected' => 'Gesamt-Übersicht'],
    ['companyId' => 1, 'expected' => 'Company specific'],
    ['companyId' => 0, 'expected' => 'Gesamt-Übersicht'],
];

foreach ($tests as $test) {
    $companyId = $test['companyId'];
    echo "<hr>";
    echo "<h2>Test: companyId = " . var_export($companyId, true) . "</h2>";
    echo "<p>Expected: " . $test['expected'] . "</p>";
    echo "<p>Result: ";
    
    // Evaluate the condition
    if (!$companyId && auth()->user()->hasRole(['Super Admin', 'super_admin'])) {
        echo "SHOWING: Gesamt-Übersicht aller Unternehmen ✓";
    } elseif ($companyId) {
        echo "SHOWING: Company specific view (ID: $companyId)";
    } else {
        echo "SHOWING: No data message";
    }
    echo "</p>";
}

echo "<hr><h2>Compiled Blade Template:</h2>";
echo "<pre style='background: #f0f0f0; padding: 10px; overflow-x: auto;'>";
echo htmlspecialchars($compiled);
echo "</pre>";

echo "</body></html>";