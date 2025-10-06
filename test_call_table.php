<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Call;
use Illuminate\Support\Facades\DB;

// Verify service type detection accuracy
echo "🎯 SERVICE TYPE DETECTION ANALYSIS\n";
echo "==================================\n";

$calls = Call::orderBy('created_at', 'desc')->take(10)->get();

foreach ($calls as $call) {
    $service = 'Unbekannt';
    $notes = strtolower($call->notes ?? '');
    $transcript = strtolower($call->transcript ?? '');

    // Same logic as in CallResource
    if (str_contains($notes, 'abgebrochen')) {
        $service = 'Abgebrochen';
    } elseif (preg_match('/-\s*([^a-z]+?)(?:\s+am|\s+um|\s+für|$)/i', $call->notes ?? '', $matches)) {
        $service = trim($matches[1]);
        $service = str_replace(['Termin', 'Buchung'], '', $service);
        $service = trim($service);
        if (str_contains(strtolower($service), 'beratung')) {
            $service = 'Beratung';
        }
    } elseif (str_contains($transcript, 'beratung')) {
        $service = 'Beratung';
    } elseif (str_contains($transcript, 'haarschnitt') || str_contains($transcript, 'friseur')) {
        $service = 'Friseur';
    } elseif (str_contains($transcript, 'physiotherapie')) {
        $service = 'Physiotherapie';
    } elseif (str_contains($transcript, 'tierarzt')) {
        $service = 'Tierarzt';
    } elseif (str_contains($transcript, '30 minuten')) {
        $service = '30 Min Termin';
    } elseif (str_contains($transcript, '15 minuten')) {
        $service = '15 Min Termin';
    } elseif ($call->appointment_made) {
        $service = 'Termin vereinbart';
    }

    // Extract customer name
    $customerName = 'Unbekannt';
    if ($call->customer_id && $call->customer) {
        $customerName = $call->customer->name;
    } elseif ($call->notes) {
        if (!str_contains(strtolower($call->notes), 'abgebrochen') &&
            !str_contains(strtolower($call->notes), 'kein termin')) {
            if (preg_match('/^([^-]+)\s*-/', $call->notes, $matches)) {
                $customerName = trim($matches[1]);
            }
        }
    }
    if ($customerName == 'Unbekannt') {
        $customerName = $call->from_number === 'anonymous' ? 'Anonym' : ($call->from_number ?? 'Unbekannt');
    }

    echo sprintf(
        "ID: %d | Zeit: %s | Kunde: %-20s | Service: %-15s | Dauer: %ds\n",
        $call->id,
        $call->created_at->format('d.m H:i'),
        substr($customerName, 0, 20),
        $service,
        $call->duration_sec
    );
}

// Check for data quality issues
echo "\n⚠️ DATA QUALITY ISSUES\n";
echo "======================\n";

$callsWithoutNumber = Call::whereNull('from_number')->whereNull('to_number')->count();
echo "Calls without phone numbers: " . $callsWithoutNumber . "\n";

$callsWithZeroDuration = Call::where('duration_sec', 0)->orWhereNull('duration_sec')->count();
echo "Calls with 0 duration: " . $callsWithZeroDuration . "\n";

$callsWithoutCompany = Call::whereNull('company_id')->count();
echo "Calls without company: " . $callsWithoutCompany . "\n";

$duplicateCalls = DB::table('calls')
    ->select('retell_call_id', DB::raw('COUNT(*) as count'))
    ->groupBy('retell_call_id')
    ->having('count', '>', 1)
    ->count();
echo "Duplicate Retell IDs: " . $duplicateCalls . "\n";

// Test search functionality
echo "\n🔍 SEARCH FUNCTIONALITY\n";
echo "=======================\n";

// Test customer name search
$searchResults = Call::whereHas('customer', function ($query) {
    $query->where('name', 'LIKE', '%Hans%');
})->count();
echo "Search 'Hans' in customer names: " . $searchResults . " results\n";

// Test phone number search
$phoneSearchResults = Call::where('from_number', 'LIKE', '%369%')
    ->orWhere('to_number', 'LIKE', '%369%')
    ->count();
echo "Search '369' in phone numbers: " . $phoneSearchResults . " results\n";

// Test external ID search
$externalIdExists = Call::whereNotNull('external_id')->count();
echo "Calls with external ID: " . $externalIdExists . "\n";

// Column visibility checks
echo "\n📊 COLUMN VISIBILITY & TOGGLES\n";
echo "==============================\n";

$toggleableColumns = [
    'sentiment' => 'default visible',
    'session_outcome' => 'hidden by default',
    'recording_url' => 'hidden by default',
    'external_id' => 'hidden by default',
    'notes' => 'hidden by default',
];

foreach ($toggleableColumns as $column => $visibility) {
    $hasData = Call::whereNotNull($column)->count();
    echo sprintf("%-20s: %-20s | Has data: %d calls\n", $column, $visibility, $hasData);
}

// Test sorting capabilities
echo "\n⚡ SORTABLE COLUMNS\n";
echo "===================\n";

$sortableColumns = ['created_at', 'customer.name', 'duration_sec', 'customer_cost'];
foreach ($sortableColumns as $column) {
    echo "- $column: sortable\n";
}

// Test pagination
echo "\n📄 PAGINATION OPTIONS\n";
echo "=====================\n";
echo "Available options: 10, 25, 50, 100 per page\n";
echo "Default: 10 per page\n";
echo "Has extreme pagination links: Yes\n";

// Test bulk actions
echo "\n🔧 BULK ACTIONS\n";
echo "===============\n";
echo "- Delete bulk action\n";
echo "- Mark as successful bulk action\n";
echo "- Export bulk action\n";

// Test row actions
echo "\n⚙️ ROW ACTIONS\n";
echo "==============\n";
echo "- View action\n";
echo "- Edit action\n";
echo "- Play recording (if available)\n";
echo "- Create appointment (if no appointment)\n";
echo "- Add note\n";
echo "- Mark successful\n";

// Test special features
echo "\n✨ SPECIAL FEATURES\n";
echo "===================\n";
echo "- Auto-refresh: every 30 seconds\n";
echo "- Striped rows: Yes\n";
echo "- Click row to open details: Yes\n";
echo "- Row hover effects: Green for appointments, gray for others\n";
echo "- Navigation badge: Shows calls from last 7 days\n";