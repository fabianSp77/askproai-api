<?php
/**
 * Column Debug Test
 * URL: https://api.askproai.de/test-column-debug.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "<h1>Filament Column Debug Test</h1>";
echo "<p>Timestamp: " . date('Y-m-d H:i:s') . "</p>";
echo "<hr>";

// Read the Resource file
$resourceFile = __DIR__ . '/../app/Filament/Resources/RetellCallSessionResource.php';
$content = file_get_contents($resourceFile);

// Find all TextColumn definitions
preg_match_all('/TextColumn::make\([\'"]([^\'"]+)[\'"]\).*?->label\([\'"]([^\'"]+)[\'"]\)/s', $content, $matches, PREG_SET_ORDER);

echo "<h2>Defined Columns in RetellCallSessionResource:</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>#</th><th>Column Name</th><th>Label</th></tr>";

$found_phone = false;
foreach ($matches as $i => $match) {
    $columnName = $match[1];
    $label = $match[2];

    if (strpos($columnName, 'phone') !== false || $label === 'Telefon') {
        $found_phone = true;
        echo "<tr style='background: #90EE90;'>";
    } else {
        echo "<tr>";
    }

    echo "<td>" . ($i + 1) . "</td>";
    echo "<td><strong>" . htmlspecialchars($columnName) . "</strong></td>";
    echo "<td>" . htmlspecialchars($label) . "</td>";
    echo "</tr>";
}

echo "</table>";

if ($found_phone) {
    echo "<p style='color: green; font-size: 20px;'><strong>✓ TELEFON-SPALTE GEFUNDEN IM CODE!</strong></p>";
} else {
    echo "<p style='color: red; font-size: 20px;'><strong>✗ Telefon-Spalte NICHT gefunden</strong></p>";
}

// Test data availability
echo "<hr>";
echo "<h2>Data Availability Test (Last 5 Calls):</h2>";

$sessions = \App\Models\RetellCallSession::with(['company', 'call.branch'])
    ->latest()
    ->take(5)
    ->get();

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Call ID</th><th>Company</th><th>Branch</th><th>Phone Number</th></tr>";

foreach ($sessions as $session) {
    $phone = $session->call?->branch?->phone_number ?? 'NULL';

    if ($phone && $phone !== 'NULL' && $phone !== '-') {
        echo "<tr style='background: #90EE90;'>";
    } else {
        echo "<tr style='background: #FFB6C1;'>";
    }

    echo "<td>" . htmlspecialchars($session->call_id) . "</td>";
    echo "<td>" . htmlspecialchars($session->company?->name ?? 'NULL') . "</td>";
    echo "<td>" . htmlspecialchars($session->call?->branch?->name ?? 'NULL') . "</td>";
    echo "<td><strong>" . htmlspecialchars($phone) . "</strong></td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";
echo "<h2>Browser Cache Instructions:</h2>";
echo "<ul>";
echo "<li><strong>Chrome/Edge:</strong> Ctrl + Shift + R (Windows) oder Cmd + Shift + R (Mac)</li>";
echo "<li><strong>Firefox:</strong> Ctrl + F5 (Windows) oder Cmd + Shift + R (Mac)</li>";
echo "<li><strong>Safari:</strong> Cmd + Option + R</li>";
echo "<li><strong>Oder:</strong> Inkognito-/Privat-Modus öffnen</li>";
echo "</ul>";

echo "<p><a href='/admin/calls' target='_blank' style='font-size: 20px;'>→ Zur Call Monitoring Seite</a></p>";
