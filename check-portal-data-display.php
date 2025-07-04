<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use App\Models\Call;
use App\Models\PhoneNumber;
use Illuminate\Support\Facades\DB;

echo "\n=== BUSINESS PORTAL DATEN-ANZEIGE PRÜFUNG ===\n";
echo "Datum: " . date('Y-m-d H:i:s') . "\n\n";

// Für jede Firma prüfen wir, was im Portal angezeigt werden würde
$companies = Company::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->orderBy('id')
    ->get();

foreach ($companies as $company) {
    echo "\n" . str_repeat('=', 80) . "\n";
    echo "FIRMA: {$company->name} (ID: {$company->id})\n";
    echo str_repeat('=', 80) . "\n";
    
    // 1. Telefonnummern der Firma
    echo "\n1. TELEFONNUMMERN:\n";
    $phoneNumbers = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->get();
    
    foreach ($phoneNumbers as $phone) {
        echo "   • {$phone->number}\n";
        echo "     - Status: " . ($phone->is_active ? 'Aktiv' : 'Inaktiv') . "\n";
        echo "     - Typ: {$phone->type}\n";
        if ($phone->branch_id) {
            $branch = DB::table('branches')->where('id', $phone->branch_id)->first();
            if ($branch) {
                echo "     - Filiale: {$branch->name}\n";
            }
        }
        
        // Anrufe für diese Nummer
        $callsForNumber = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('to_number', $phone->number)
            ->count();
        
        $callsCorrectCompany = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('to_number', $phone->number)
            ->where('company_id', $company->id)
            ->count();
            
        echo "     - Anrufe gesamt: {$callsForNumber}\n";
        echo "     - Anrufe mit korrekter Firma: {$callsCorrectCompany}\n";
        
        if ($callsForNumber != $callsCorrectCompany) {
            echo "     ⚠️  WARNUNG: " . ($callsForNumber - $callsCorrectCompany) . " Anrufe mit falscher Firma!\n";
        }
    }
    
    // 2. Anruf-Statistiken
    echo "\n2. ANRUF-STATISTIKEN:\n";
    
    // Heute
    $todayCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->whereDate('created_at', date('Y-m-d'))
        ->count();
        
    // Diese Woche
    $weekCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->where('created_at', '>=', now()->startOfWeek())
        ->count();
        
    // Dieser Monat
    $monthCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->whereMonth('created_at', date('m'))
        ->whereYear('created_at', date('Y'))
        ->count();
        
    echo "   • Heute: {$todayCalls} Anrufe\n";
    echo "   • Diese Woche: {$weekCalls} Anrufe\n";
    echo "   • Dieser Monat: {$monthCalls} Anrufe\n";
    
    // 3. Letzte 5 Anrufe
    echo "\n3. LETZTE 5 ANRUFE:\n";
    $recentCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
        
    if ($recentCalls->isEmpty()) {
        echo "   Keine Anrufe vorhanden.\n";
    } else {
        foreach ($recentCalls as $call) {
            echo "   • {$call->created_at->format('d.m.Y H:i:s')}\n";
            echo "     Von: {$call->from_number}\n";
            echo "     An: {$call->to_number}\n";
            echo "     Dauer: " . ($call->duration_sec ?? 0) . " Sekunden\n";
            
            // Prüfe ob die Ziel-Nummer zur Firma gehört
            $phoneExists = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->where('company_id', $company->id)
                ->where('number', $call->to_number)
                ->exists();
                
            if (!$phoneExists) {
                echo "     ⚠️  WARNUNG: Ziel-Nummer gehört nicht zur Firma!\n";
            }
            echo "\n";
        }
    }
    
    // 4. Datenintegrität prüfen
    echo "\n4. DATENINTEGRITÄT:\n";
    
    // Anrufe ohne passende Telefonnummer
    $orphanedCalls = DB::table('calls')
        ->where('company_id', $company->id)
        ->whereNotIn('to_number', function($query) use ($company) {
            $query->select('number')
                  ->from('phone_numbers')
                  ->where('company_id', $company->id);
        })
        ->count();
        
    if ($orphanedCalls > 0) {
        echo "   ⚠️  {$orphanedCalls} Anrufe zu nicht registrierten Nummern!\n";
        
        // Zeige die betroffenen Nummern
        $orphanedNumbers = DB::table('calls')
            ->where('company_id', $company->id)
            ->whereNotIn('to_number', function($query) use ($company) {
                $query->select('number')
                      ->from('phone_numbers')
                      ->where('company_id', $company->id);
            })
            ->select('to_number', DB::raw('COUNT(*) as count'))
            ->groupBy('to_number')
            ->get();
            
        foreach ($orphanedNumbers as $num) {
            echo "      - {$num->to_number}: {$num->count} Anrufe\n";
        }
    } else {
        echo "   ✅ Alle Anrufe gehen an registrierte Nummern der Firma\n";
    }
    
    // Branches ohne Telefonnummern
    $branchesWithoutPhone = DB::table('branches')
        ->where('company_id', $company->id)
        ->whereNotIn('id', function($query) {
            $query->select('branch_id')
                  ->from('phone_numbers')
                  ->whereNotNull('branch_id');
        })
        ->get();
        
    if ($branchesWithoutPhone->count() > 0) {
        echo "   ℹ️  Filialen ohne Telefonnummer:\n";
        foreach ($branchesWithoutPhone as $branch) {
            echo "      - {$branch->name}\n";
        }
    }
}

echo "\n\n" . str_repeat('=', 80) . "\n";
echo "EMPFEHLUNGEN:\n";
echo str_repeat('=', 80) . "\n";

// Generelle Empfehlungen basierend auf den Findings
$totalOrphaned = DB::table('calls as c')
    ->whereNotExists(function($query) {
        $query->select(DB::raw(1))
              ->from('phone_numbers as pn')
              ->whereRaw('pn.number = c.to_number')
              ->whereRaw('pn.company_id = c.company_id');
    })
    ->count();

if ($totalOrphaned > 0) {
    echo "\n1. Es gibt {$totalOrphaned} Anrufe zu nicht registrierten Telefonnummern.\n";
    echo "   → Diese sollten überprüft und ggf. den korrekten Nummern zugeordnet werden.\n";
}

// Prüfe auf inaktive Nummern mit Anrufen
$inactiveWithCalls = DB::table('phone_numbers as pn')
    ->where('is_active', false)
    ->whereExists(function($query) {
        $query->select(DB::raw(1))
              ->from('calls as c')
              ->whereRaw('c.to_number = pn.number')
              ->where('c.created_at', '>', now()->subDays(30));
    })
    ->count();

if ($inactiveWithCalls > 0) {
    echo "\n2. Es gibt {$inactiveWithCalls} inaktive Telefonnummern mit kürzlichen Anrufen.\n";
    echo "   → Diese sollten möglicherweise reaktiviert werden.\n";
}

echo "\n✅ Prüfung abgeschlossen!\n\n";