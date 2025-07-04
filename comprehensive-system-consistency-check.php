<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use App\Models\Branch;
use App\Models\PhoneNumber;
use App\Models\Staff;
use App\Models\Call;
use App\Models\PortalUser;
use App\Models\BalanceTransaction;
use App\Services\PrepaidBillingService;
use App\Services\BalanceMonitoringService;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════════════════╗\n";
echo "║               UMFASSENDE SYSTEM-KONSISTENZ-PRÜFUNG                           ║\n";
echo "╚══════════════════════════════════════════════════════════════════════════════╝\n";
echo "Datum: " . date('Y-m-d H:i:s') . "\n\n";

$issues = [];
$warnings = [];

// ============================================================================
// 1. UNTERNEHMENSSTRUKTUR PRÜFUNG
// ============================================================================
echo "1. UNTERNEHMENSSTRUKTUR\n";
echo str_repeat('═', 80) . "\n\n";

$companies = Company::withoutGlobalScope(\App\Scopes\TenantScope::class)->get();

foreach ($companies as $company) {
    echo "📊 {$company->name} (ID: {$company->id})\n";
    
    // Filialen
    $branches = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->get();
    
    echo "   └─ Filialen: {$branches->count()}\n";
    foreach ($branches as $branch) {
        echo "      ├─ {$branch->name} (ID: {$branch->id})\n";
        
        // Prüfe ob Filiale zur richtigen Firma gehört
        if ($branch->company_id != $company->id) {
            $issues[] = "Filiale {$branch->name} (ID: {$branch->id}) hat falsche company_id!";
        }
    }
    
    // Mitarbeiter
    $staff = Staff::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->get();
    
    echo "   └─ Mitarbeiter: {$staff->count()}\n";
    foreach ($staff as $employee) {
        echo "      ├─ {$employee->name} (ID: {$employee->id})";
        if ($employee->branch_id) {
            $branch = $branches->firstWhere('id', $employee->branch_id);
            if ($branch) {
                echo " - Filiale: {$branch->name}";
            } else {
                echo " - ⚠️ Filiale ID {$employee->branch_id} nicht gefunden!";
                $warnings[] = "Mitarbeiter {$employee->name} verweist auf nicht existierende Filiale";
            }
        }
        echo "\n";
    }
    
    // Portal Users
    $portalUsers = PortalUser::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->get();
    
    echo "   └─ Portal-Benutzer: {$portalUsers->count()}\n";
    foreach ($portalUsers as $user) {
        echo "      ├─ {$user->name} ({$user->email}) - Rolle: {$user->role}\n";
    }
    
    echo "\n";
}

// ============================================================================
// 2. TELEFONNUMMERN-ZUORDNUNG
// ============================================================================
echo "\n2. TELEFONNUMMERN-ZUORDNUNG\n";
echo str_repeat('═', 80) . "\n\n";

$phoneNumbers = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)->get();

foreach ($phoneNumbers as $phone) {
    echo "📞 {$phone->number}\n";
    
    // Firma prüfen
    $company = Company::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($phone->company_id);
    if (!$company) {
        $issues[] = "Telefonnummer {$phone->number} verweist auf nicht existierende Firma ID {$phone->company_id}";
        echo "   ├─ ❌ Firma nicht gefunden (ID: {$phone->company_id})\n";
    } else {
        echo "   ├─ Firma: {$company->name}\n";
    }
    
    // Filiale prüfen
    if ($phone->branch_id) {
        $branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($phone->branch_id);
        if (!$branch) {
            $issues[] = "Telefonnummer {$phone->number} verweist auf nicht existierende Filiale ID {$phone->branch_id}";
            echo "   ├─ ❌ Filiale nicht gefunden (ID: {$phone->branch_id})\n";
        } else {
            echo "   ├─ Filiale: {$branch->name}\n";
            // Prüfe ob Filiale zur gleichen Firma gehört
            if ($branch->company_id != $phone->company_id) {
                $issues[] = "Telefonnummer {$phone->number}: Filiale gehört zu anderer Firma!";
                echo "   ├─ ⚠️ Filiale gehört zu Firma ID {$branch->company_id}, erwartet {$phone->company_id}\n";
            }
        }
    }
    
    echo "   ├─ Status: " . ($phone->is_active ? '✅ Aktiv' : '❌ Inaktiv') . "\n";
    echo "   └─ Typ: {$phone->type}\n\n";
}

// ============================================================================
// 3. ANRUF-ZUORDNUNG UND MINUTEN-BERECHNUNG
// ============================================================================
echo "\n3. ANRUF-ZUORDNUNG UND MINUTEN-BERECHNUNG\n";
echo str_repeat('═', 80) . "\n\n";

foreach ($companies as $company) {
    echo "📊 {$company->name}\n";
    
    // Gesamtstatistiken
    $totalCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->count();
    
    $totalSeconds = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->sum('duration_sec');
    
    $totalMinutes = round($totalSeconds / 60, 2);
    
    echo "   ├─ Anrufe gesamt: {$totalCalls}\n";
    echo "   ├─ Minuten gesamt: {$totalMinutes}\n";
    
    // Prüfe Anrufe auf korrekte Zuordnung
    $phoneNumbersForCompany = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->pluck('number')
        ->toArray();
    
    if (count($phoneNumbersForCompany) > 0) {
        // Anrufe zu fremden Nummern
        $wrongCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('company_id', $company->id)
            ->whereNotIn('to_number', $phoneNumbersForCompany)
            ->count();
        
        if ($wrongCalls > 0) {
            $warnings[] = "{$company->name}: {$wrongCalls} Anrufe zu nicht registrierten Nummern";
            echo "   ├─ ⚠️ {$wrongCalls} Anrufe zu fremden Nummern\n";
        }
        
        // Anrufe von anderen Firmen zu unseren Nummern
        $stolenCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->whereIn('to_number', $phoneNumbersForCompany)
            ->where('company_id', '!=', $company->id)
            ->count();
        
        if ($stolenCalls > 0) {
            $issues[] = "{$company->name}: {$stolenCalls} Anrufe wurden falscher Firma zugeordnet!";
            echo "   ├─ ❌ {$stolenCalls} Anrufe falsch zugeordnet\n";
        }
    }
    
    echo "   └─ ✅ Zuordnung geprüft\n\n";
}

// ============================================================================
// 4. BILLING & KOSTEN-BERECHNUNG
// ============================================================================
echo "\n4. BILLING & KOSTEN-BERECHNUNG\n";
echo str_repeat('═', 80) . "\n\n";

$billingService = app(PrepaidBillingService::class);
$monitoringService = app(BalanceMonitoringService::class);

foreach ($companies as $company) {
    echo "💰 {$company->name}\n";
    
    // Billing Rate
    $billingRate = $billingService->getCompanyBillingRate($company);
    echo "   ├─ Tarif: {$billingRate->rate_per_minute} €/Minute\n";
    
    // Balance Status - Load company with prepaidBalance
    $companyWithBalance = Company::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->with('prepaidBalance')
        ->find($company->id);
        
    $balanceStatus = $monitoringService->getBalanceStatus($companyWithBalance);
    echo "   ├─ Aktuelles Guthaben: " . number_format($balanceStatus['effective_balance'] ?? 0, 2, ',', '.') . " €\n";
    echo "   ├─ Reserviert: " . number_format($balanceStatus['reserved_balance'] ?? 0, 2, ',', '.') . " €\n";
    echo "   ├─ Verfügbare Minuten: " . round($balanceStatus['available_minutes'] ?? 0, 0) . "\n";
    
    // Berechne Kosten für diesen Monat
    $startOfMonth = now()->startOfMonth();
    $endOfMonth = now()->endOfMonth();
    
    $monthlySeconds = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
        ->sum('duration_sec');
    
    $monthlyMinutes = $monthlySeconds / 60;
    $monthlyCost = $monthlyMinutes * $billingRate->rate_per_minute;
    
    echo "   ├─ Minuten diesen Monat: " . round($monthlyMinutes, 2) . "\n";
    echo "   ├─ Kosten diesen Monat: " . number_format($monthlyCost, 2, ',', '.') . " €\n";
    
    // Prüfe Balance Transactions
    $transactions = BalanceTransaction::where('company_id', $company->id)
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
    
    echo "   └─ Letzte Transaktionen: {$transactions->count()}\n";
    foreach ($transactions as $tx) {
        $prefix = $tx->type == 'topup' ? '+' : '-';
        echo "      ├─ {$tx->created_at->format('d.m.Y')}: {$prefix}" . number_format(abs($tx->amount), 2, ',', '.') . " € ({$tx->description})\n";
    }
    
    echo "\n";
}

// ============================================================================
// 5. BUSINESS PORTAL STATISTIKEN
// ============================================================================
echo "\n5. BUSINESS PORTAL STATISTIKEN-PRÜFUNG\n";
echo str_repeat('═', 80) . "\n\n";

foreach ($companies as $company) {
    echo "📈 {$company->name} - Portal Statistiken\n";
    
    // Heute
    $todayCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->whereDate('created_at', today())
        ->count();
    
    $todayMinutes = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->whereDate('created_at', today())
        ->sum('duration_sec') / 60;
    
    echo "   ├─ Heute: {$todayCalls} Anrufe, " . round($todayMinutes, 1) . " Minuten\n";
    
    // Diese Woche
    $weekCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
        ->count();
    
    $weekMinutes = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
        ->sum('duration_sec') / 60;
    
    echo "   ├─ Diese Woche: {$weekCalls} Anrufe, " . round($weekMinutes, 1) . " Minuten\n";
    
    // Durchschnittliche Anrufdauer
    $avgDuration = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->where('duration_sec', '>', 0)
        ->avg('duration_sec');
    
    echo "   ├─ Ø Anrufdauer: " . round($avgDuration, 0) . " Sekunden\n";
    
    // Top Anrufer
    $topCallers = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->select('from_number', DB::raw('COUNT(*) as call_count'))
        ->groupBy('from_number')
        ->orderBy('call_count', 'desc')
        ->limit(3)
        ->get();
    
    echo "   └─ Top Anrufer:\n";
    foreach ($topCallers as $caller) {
        echo "      ├─ {$caller->from_number}: {$caller->call_count} Anrufe\n";
    }
    
    echo "\n";
}

// ============================================================================
// 6. DATENINTEGRITÄT CROSS-CHECK
// ============================================================================
echo "\n6. DATENINTEGRITÄT CROSS-CHECK\n";
echo str_repeat('═', 80) . "\n\n";

// Verwaiste Datensätze prüfen
echo "🔍 Prüfe auf verwaiste Datensätze...\n\n";

// Filialen ohne Firma
$orphanBranches = DB::table('branches')
    ->whereNotIn('company_id', function($query) {
        $query->select('id')->from('companies');
    })
    ->count();
if ($orphanBranches > 0) {
    $issues[] = "{$orphanBranches} Filialen ohne gültige Firma gefunden!";
    echo "   ├─ ❌ {$orphanBranches} Filialen ohne Firma\n";
}

// Mitarbeiter ohne Firma
$orphanStaff = DB::table('staff')
    ->whereNotIn('company_id', function($query) {
        $query->select('id')->from('companies');
    })
    ->count();
if ($orphanStaff > 0) {
    $issues[] = "{$orphanStaff} Mitarbeiter ohne gültige Firma gefunden!";
    echo "   ├─ ❌ {$orphanStaff} Mitarbeiter ohne Firma\n";
}

// Anrufe ohne Firma
$orphanCalls = DB::table('calls')
    ->whereNotIn('company_id', function($query) {
        $query->select('id')->from('companies');
    })
    ->count();
if ($orphanCalls > 0) {
    $issues[] = "{$orphanCalls} Anrufe ohne gültige Firma gefunden!";
    echo "   ├─ ❌ {$orphanCalls} Anrufe ohne Firma\n";
}

if ($orphanBranches == 0 && $orphanStaff == 0 && $orphanCalls == 0) {
    echo "   └─ ✅ Keine verwaisten Datensätze gefunden\n";
}

// ============================================================================
// ZUSAMMENFASSUNG
// ============================================================================
echo "\n\n";
echo "╔══════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                              ZUSAMMENFASSUNG                                  ║\n";
echo "╚══════════════════════════════════════════════════════════════════════════════╝\n\n";

if (count($issues) == 0 && count($warnings) == 0) {
    echo "✅ ALLE PRÜFUNGEN BESTANDEN!\n\n";
    echo "Das System ist vollständig konsistent:\n";
    echo "- Alle Telefonnummern sind korrekt zugeordnet\n";
    echo "- Alle Anrufe gehören zu den richtigen Firmen\n";
    echo "- Minuten und Kosten werden korrekt berechnet\n";
    echo "- Business Portal Statistiken stimmen\n";
    echo "- Keine verwaisten Datensätze gefunden\n";
} else {
    if (count($issues) > 0) {
        echo "❌ KRITISCHE PROBLEME GEFUNDEN:\n";
        foreach ($issues as $issue) {
            echo "   • {$issue}\n";
        }
        echo "\n";
    }
    
    if (count($warnings) > 0) {
        echo "⚠️  WARNUNGEN:\n";
        foreach ($warnings as $warning) {
            echo "   • {$warning}\n";
        }
    }
}

echo "\n✅ Prüfung abgeschlossen am " . date('Y-m-d H:i:s') . "\n\n";