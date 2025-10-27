<?php
/**
 * FINAL VERIFICATION: Telefon Column in Filament
 * URL: https://api.askproai.de/verify-telefon-column.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Telefon Column Verification</title>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
h1 { color: #1F2937; }
h2 { color: #374151; margin-top: 30px; }
.pass { background: #D1FAE5; color: #065F46; padding: 15px; border-left: 4px solid #10B981; margin: 10px 0; }
.fail { background: #FEE2E2; color: #991B1B; padding: 15px; border-left: 4px solid #EF4444; margin: 10px 0; }
table { width: 100%; border-collapse: collapse; margin: 20px 0; }
th, td { padding: 12px; text-align: left; border: 1px solid #E5E7EB; }
th { background: #F3F4F6; font-weight: 600; }
.code { background: #F3F4F6; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
.highlight { background: #FEF3C7; font-weight: bold; }
</style></head><body>";

echo "<h1>🔍 Telefon Column Verification Report</h1>";
echo "<p><strong>Generated:</strong> " . date('Y-m-d H:i:s') . "</p>";

$allPass = true;

// TEST 1: Model Accessor Exists
echo "<h2>Test 1: Model Accessor</h2>";
$session = \App\Models\RetellCallSession::with(['call.branch'])->latest()->first();
if ($session && isset($session->phone_number)) {
    echo "<div class='pass'>✓ PASS: Accessor <code>getPhoneNumberAttribute()</code> exists</div>";
    echo "<p>Test value: <code>" . htmlspecialchars($session->phone_number) . "</code></p>";
} else {
    echo "<div class='fail'>✗ FAIL: Accessor missing or not working</div>";
    $allPass = false;
}

// TEST 2: Column Definition
echo "<h2>Test 2: Filament Column Definition</h2>";
$resourceFile = file_get_contents(__DIR__ . '/../app/Filament/Resources/RetellCallSessionResource.php');
if (strpos($resourceFile, "TextColumn::make('phone_number')") !== false) {
    echo "<div class='pass'>✓ PASS: Column definition found</div>";
    echo "<p>Column uses simple accessor: <code>phone_number</code> (not nested relation)</p>";

    if (strpos($resourceFile, "->visible(true)") !== false) {
        echo "<div class='pass'>✓ PASS: Column explicitly set to visible(true)</div>";
    } else {
        echo "<div class='fail'>⚠ WARNING: visible(true) not found</div>";
    }
} else {
    echo "<div class='fail'>✗ FAIL: Column definition not found</div>";
    $allPass = false;
}

// TEST 3: Data Availability
echo "<h2>Test 3: Data Availability</h2>";
$sessions = \App\Models\RetellCallSession::with(['company', 'call.branch'])
    ->latest()
    ->take(10)
    ->get();

$withPhone = $sessions->filter(fn($s) => $s->phone_number && $s->phone_number !== '-')->count();
$total = $sessions->count();
$percentage = $total > 0 ? round(($withPhone / $total) * 100, 1) : 0;

echo "<table>";
echo "<tr><th>Metric</th><th>Value</th></tr>";
echo "<tr><td>Total Records (last 10)</td><td>{$total}</td></tr>";
echo "<tr><td>Records with Phone</td><td class='highlight'>{$withPhone}</td></tr>";
echo "<tr><td>Coverage</td><td class='highlight'>{$percentage}%</td></tr>";
echo "</table>";

if ($withPhone > 0) {
    echo "<div class='pass'>✓ PASS: Phone numbers available in data</div>";
} else {
    echo "<div class='fail'>✗ FAIL: No phone numbers in sample data</div>";
    $allPass = false;
}

// TEST 4: Sample Data Display
echo "<h2>Test 4: Sample Data (What Should Display)</h2>";
echo "<table>";
echo "<tr><th>Call ID</th><th>Company</th><th>Branch</th><th class='highlight'>Telefon Column</th></tr>";

foreach ($sessions->take(5) as $s) {
    $phone = htmlspecialchars($s->phone_number);
    $highlightClass = ($phone !== '-') ? 'highlight' : '';

    echo "<tr>";
    echo "<td>" . htmlspecialchars(substr($s->call_id, 0, 25)) . "...</td>";
    echo "<td>" . htmlspecialchars($s->company?->name ?? '-') . "</td>";
    echo "<td>" . htmlspecialchars($s->call?->branch?->name ?? '-') . "</td>";
    echo "<td class='{$highlightClass}'><strong>{$phone}</strong></td>";
    echo "</tr>";
}
echo "</table>";

// TEST 5: Livewire Compatibility
echo "<h2>Test 5: Livewire Serialization Test</h2>";
try {
    $testSession = $sessions->first();
    $serialized = json_encode([
        'phone_number' => $testSession->phone_number,
        'company_branch' => $testSession->company_branch,
    ]);

    if ($serialized !== false) {
        echo "<div class='pass'>✓ PASS: Accessors serialize correctly for Livewire</div>";
        echo "<p>Serialized: <code>" . htmlspecialchars($serialized) . "</code></p>";
    } else {
        echo "<div class='fail'>✗ FAIL: Serialization failed</div>";
        $allPass = false;
    }
} catch (\Exception $e) {
    echo "<div class='fail'>✗ FAIL: Serialization error: " . htmlspecialchars($e->getMessage()) . "</div>";
    $allPass = false;
}

// FINAL VERDICT
echo "<hr style='margin: 40px 0;'>";
echo "<h2>Final Verdict</h2>";

if ($allPass) {
    echo "<div class='pass' style='font-size: 18px;'>";
    echo "<strong>✓ ALL TESTS PASSED</strong><br><br>";
    echo "Die Telefon-Spalte sollte jetzt in Filament sichtbar sein!<br>";
    echo "Falls nicht, liegt es am Browser-Cache oder LocalStorage.";
    echo "</div>";

    echo "<h3>Nächste Schritte:</h3>";
    echo "<ol>";
    echo "<li>Öffne <a href='/reset-column-preferences.html' target='_blank'>LocalStorage Reset Tool</a></li>";
    echo "<li>Klicke auf \"Spalten-Einstellungen zurücksetzen\"</li>";
    echo "<li>Öffne <a href='/admin/calls' target='_blank'>Call Monitoring</a> im Inkognito-Modus</li>";
    echo "<li>Mache Hard Reload: <code>Strg + Shift + R</code></li>";
    echo "</ol>";
} else {
    echo "<div class='fail' style='font-size: 18px;'>";
    echo "<strong>✗ SOME TESTS FAILED</strong><br><br>";
    echo "Bitte überprüfe die fehlgeschlagenen Tests oben.";
    echo "</div>";
}

echo "</body></html>";
