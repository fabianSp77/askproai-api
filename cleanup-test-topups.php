<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\BalanceTopup;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

echo "\n=== Bereinigung der Test-Guthaben-Transaktionen ===\n\n";

// Finde die Krückeberg Servicegruppe
$company = Company::where('name', 'like', '%Krückeberg%')->first();
if (!$company) {
    $company = Company::find(1); // Fallback auf ID 1
}

echo "Firma: {$company->name} (ID: {$company->id})\n\n";

// Zeige alle 100€ Transaktionen
$testTopups = BalanceTopup::where('company_id', $company->id)
    ->where('amount', 100)
    ->get();

echo "Gefundene 100€ Transaktionen:\n";
echo str_pad("ID", 5) . str_pad("Status", 15) . str_pad("Erstellt", 20) . str_pad("Kunde", 30) . "Stripe Session\n";
echo str_repeat("-", 100) . "\n";

foreach ($testTopups as $topup) {
    echo str_pad($topup->id, 5) . 
         str_pad($topup->status, 15) . 
         str_pad($topup->created_at->format('Y-m-d H:i:s'), 20) . 
         str_pad($topup->customer_name ?? 'N/A', 30) . 
         substr($topup->stripe_checkout_session_id ?? 'N/A', 0, 30) . "\n";
}

echo "\n";

// Bestätigung
echo "Diese " . count($testTopups) . " Transaktionen werden gelöscht.\n";
echo "Möchten Sie fortfahren? (ja/nein): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);

if (trim($line) != 'ja') {
    echo "\nAbgebrochen.\n";
    exit;
}

// Lösche die Transaktionen
try {
    DB::beginTransaction();
    
    $deletedCount = 0;
    foreach ($testTopups as $topup) {
        // Log the deletion
        \Log::info('Deleting test topup', [
            'topup_id' => $topup->id,
            'company_id' => $topup->company_id,
            'amount' => $topup->amount,
            'status' => $topup->status,
            'deleted_by' => 'cleanup_script'
        ]);
        
        $topup->delete();
        $deletedCount++;
    }
    
    DB::commit();
    
    echo "\n✅ Erfolgreich {$deletedCount} Transaktionen gelöscht.\n";
    
    // Zeige verbleibende Transaktionen
    $remainingTopups = BalanceTopup::where('company_id', $company->id)->get();
    
    echo "\nVerbleibende Transaktionen:\n";
    echo str_pad("ID", 5) . str_pad("Betrag", 10) . str_pad("Status", 15) . str_pad("Erstellt", 20) . "\n";
    echo str_repeat("-", 50) . "\n";
    
    foreach ($remainingTopups as $topup) {
        echo str_pad($topup->id, 5) . 
             str_pad(number_format($topup->amount, 2) . ' €', 10) . 
             str_pad($topup->status, 15) . 
             str_pad($topup->created_at->format('Y-m-d H:i:s'), 20) . "\n";
    }
    
    // Aktualisiere das Guthaben
    $totalBalance = $remainingTopups->where('status', 'completed')->sum('amount');
    echo "\n💰 Aktuelles Guthaben (nur abgeschlossene Transaktionen): " . number_format($totalBalance, 2) . " €\n";
    
} catch (\Exception $e) {
    DB::rollback();
    echo "\n❌ Fehler beim Löschen: " . $e->getMessage() . "\n";
}

echo "\n";