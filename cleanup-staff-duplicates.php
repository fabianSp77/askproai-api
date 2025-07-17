<?php
// Clean up duplicate staff members

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Bereinigung von Mitarbeiter-Duplikaten ===\n\n";

// Find duplicates
$duplicates = \DB::select("
    SELECT company_id, email, COUNT(*) as count, GROUP_CONCAT(id ORDER BY created_at DESC) as ids
    FROM staff 
    WHERE email IS NOT NULL AND email != ''
    GROUP BY company_id, email 
    HAVING COUNT(*) > 1
");

if (empty($duplicates)) {
    echo "Keine Duplikate gefunden. Keine Bereinigung notwendig.\n";
    exit(0);
}

echo "Gefundene Duplikate: " . count($duplicates) . "\n\n";

$totalDeleted = 0;

foreach ($duplicates as $dup) {
    $company = \App\Models\Company::withoutGlobalScopes()->find($dup->company_id);
    echo "Company: " . ($company ? $company->name : 'Unknown') . " (ID: {$dup->company_id})\n";
    echo "E-Mail: {$dup->email}\n";
    echo "Anzahl Duplikate: {$dup->count}\n";
    
    $ids = explode(',', $dup->ids);
    
    // Keep the newest (first in the list, as we ordered by created_at DESC)
    $keepId = $ids[0];
    $deleteIds = array_slice($ids, 1);
    
    $keepStaff = \App\Models\Staff::withoutGlobalScopes()->find($keepId);
    echo "Behalte: ID {$keepId} - {$keepStaff->name}, erstellt: {$keepStaff->created_at}";
    
    // If the one we want to keep is not active, but one of the others is, swap
    if (!$keepStaff->active) {
        foreach ($deleteIds as $checkId) {
            $checkStaff = \App\Models\Staff::withoutGlobalScopes()->find($checkId);
            if ($checkStaff && $checkStaff->active) {
                echo " (inaktiv, tausche mit aktivem Eintrag)\n";
                $keepId = $checkId;
                $deleteIds = array_diff($ids, [$keepId]);
                $keepStaff = $checkStaff;
                echo "Neu - Behalte: ID {$keepId} - {$keepStaff->name}, erstellt: {$keepStaff->created_at}";
                break;
            }
        }
    }
    echo "\n";
    
    // Show what will be deleted
    echo "Lösche folgende Duplikate:\n";
    foreach ($deleteIds as $deleteId) {
        $deleteStaff = \App\Models\Staff::withoutGlobalScopes()->find($deleteId);
        if ($deleteStaff) {
            echo "  - ID {$deleteId}: {$deleteStaff->name}, aktiv: " . ($deleteStaff->active ? 'Ja' : 'Nein') . 
                 ", erstellt: {$deleteStaff->created_at}\n";
        }
    }
    
    // Ask for confirmation
    echo "\nDuplikate löschen? (j/n): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    
    if (trim($line) === 'j') {
        // Check for related data before deleting
        foreach ($deleteIds as $deleteId) {
            // Check for appointments
            $appointments = \DB::table('appointments')->where('staff_id', $deleteId)->count();
            if ($appointments > 0) {
                echo "  WARNUNG: Staff ID {$deleteId} hat {$appointments} Termine!\n";
                echo "  Übertrage Termine auf ID {$keepId}...\n";
                \DB::table('appointments')->where('staff_id', $deleteId)->update(['staff_id' => $keepId]);
            }
            
            // Check for staff_services
            $services = \DB::table('staff_services')->where('staff_id', $deleteId)->count();
            if ($services > 0) {
                echo "  Übertrage {$services} Service-Zuordnungen...\n";
                // Only transfer if not already assigned to keeper
                $existingServices = \DB::table('staff_services')
                    ->where('staff_id', $keepId)
                    ->pluck('service_id')
                    ->toArray();
                    
                $toTransfer = \DB::table('staff_services')
                    ->where('staff_id', $deleteId)
                    ->whereNotIn('service_id', $existingServices)
                    ->get();
                    
                foreach ($toTransfer as $service) {
                    \DB::table('staff_services')->insert([
                        'staff_id' => $keepId,
                        'service_id' => $service->service_id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
                
                \DB::table('staff_services')->where('staff_id', $deleteId)->delete();
            }
            
            // Delete the duplicate
            \DB::table('staff')->where('id', $deleteId)->delete();
            $totalDeleted++;
            echo "  ✓ Gelöscht: ID {$deleteId}\n";
        }
    } else {
        echo "  Übersprungen.\n";
    }
    
    echo "\n---\n\n";
}

echo "=== Zusammenfassung ===\n";
echo "Insgesamt {$totalDeleted} Duplikate gelöscht.\n";

// Clean up the script file
if (file_exists(__DIR__ . '/check-staff-duplicates.php')) {
    unlink(__DIR__ . '/check-staff-duplicates.php');
}