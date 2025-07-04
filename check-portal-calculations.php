<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use App\Models\Call;
use App\Services\PrepaidBillingService;
use App\Services\BalanceMonitoringService;
use Illuminate\Support\Facades\DB;

echo "\n=== BUSINESS PORTAL BERECHNUNGS-PRÜFUNG ===\n";
echo "Datum: " . date('Y-m-d H:i:s') . "\n\n";

$billingService = app(PrepaidBillingService::class);

// Für jede aktive Firma prüfen
$companies = Company::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->whereIn('id', [1, 15]) // Nur existierende Firmen
    ->get();

foreach ($companies as $company) {
    echo "\n" . str_repeat('=', 80) . "\n";
    echo "FIRMA: {$company->name} (ID: {$company->id})\n";
    echo str_repeat('=', 80) . "\n";
    
    // 1. Billing Rate
    $billingRate = $billingService->getCompanyBillingRate($company);
    echo "\nTARIF: {$billingRate->rate_per_minute} €/Minute\n";
    
    // 2. Minuten-Berechnung für verschiedene Zeiträume
    echo "\nMINUTEN-BERECHNUNGEN:\n";
    
    // Heute
    $todaySeconds = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->whereDate('created_at', today())
        ->sum('duration_sec');
    $todayMinutes = $todaySeconds / 60;
    $todayCost = $todayMinutes * $billingRate->rate_per_minute;
    
    echo "Heute:\n";
    echo "  - Sekunden: {$todaySeconds}\n";
    echo "  - Minuten: " . round($todayMinutes, 2) . "\n";
    echo "  - Kosten: " . number_format($todayCost, 2, ',', '.') . " €\n";
    
    // Diese Woche
    $weekSeconds = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
        ->sum('duration_sec');
    $weekMinutes = $weekSeconds / 60;
    $weekCost = $weekMinutes * $billingRate->rate_per_minute;
    
    echo "\nDiese Woche:\n";
    echo "  - Sekunden: {$weekSeconds}\n";
    echo "  - Minuten: " . round($weekMinutes, 2) . "\n";
    echo "  - Kosten: " . number_format($weekCost, 2, ',', '.') . " €\n";
    
    // Dieser Monat
    $monthSeconds = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->whereMonth('created_at', date('m'))
        ->whereYear('created_at', date('Y'))
        ->sum('duration_sec');
    $monthMinutes = $monthSeconds / 60;
    $monthCost = $monthMinutes * $billingRate->rate_per_minute;
    
    echo "\nDieser Monat (Juli 2025):\n";
    echo "  - Sekunden: {$monthSeconds}\n";
    echo "  - Minuten: " . round($monthMinutes, 2) . "\n";
    echo "  - Kosten: " . number_format($monthCost, 2, ',', '.') . " €\n";
    
    // 3. Top 5 Anrufe nach Kosten
    echo "\nTOP 5 TEUERSTE ANRUFE:\n";
    $topCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->where('duration_sec', '>', 0)
        ->orderBy('duration_sec', 'desc')
        ->limit(5)
        ->get();
        
    foreach ($topCalls as $i => $call) {
        $callMinutes = $call->duration_sec / 60;
        $callCost = $callMinutes * $billingRate->rate_per_minute;
        echo ($i + 1) . ". {$call->created_at->format('d.m.Y H:i')} - ";
        echo "{$call->duration_sec} Sek. = " . round($callMinutes, 2) . " Min. = ";
        echo number_format($callCost, 2, ',', '.') . " €\n";
        echo "   Von: {$call->from_number} → {$call->to_number}\n";
    }
    
    // 4. Durchschnittswerte
    echo "\nDURCHSCHNITTSWERTE:\n";
    $avgDuration = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->where('duration_sec', '>', 0)
        ->avg('duration_sec');
    
    $avgMinutes = $avgDuration / 60;
    $avgCost = $avgMinutes * $billingRate->rate_per_minute;
    
    echo "  - Ø Anrufdauer: " . round($avgDuration, 0) . " Sekunden\n";
    echo "  - Ø Minuten: " . round($avgMinutes, 2) . " Minuten\n";
    echo "  - Ø Kosten pro Anruf: " . number_format($avgCost, 2, ',', '.') . " €\n";
    
    // 5. Validierung: Summe aller Einzelkosten vs. Gesamtkosten
    echo "\nVALIDIERUNG DER BERECHNUNGEN:\n";
    
    // Berechne Summe aller Einzelanrufe
    $allCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->whereMonth('created_at', date('m'))
        ->whereYear('created_at', date('Y'))
        ->get();
        
    $sumOfIndividualCosts = 0;
    foreach ($allCalls as $call) {
        $callCost = ($call->duration_sec / 60) * $billingRate->rate_per_minute;
        $sumOfIndividualCosts += $callCost;
    }
    
    echo "  - Summe Einzelkosten: " . number_format($sumOfIndividualCosts, 2, ',', '.') . " €\n";
    echo "  - Monatskosten (berechnet): " . number_format($monthCost, 2, ',', '.') . " €\n";
    
    $difference = abs($sumOfIndividualCosts - $monthCost);
    if ($difference < 0.01) {
        echo "  - ✅ Berechnungen stimmen überein!\n";
    } else {
        echo "  - ⚠️ Differenz: " . number_format($difference, 2, ',', '.') . " €\n";
    }
}

// 6. Gesamtübersicht
echo "\n\n" . str_repeat('=', 80) . "\n";
echo "GESAMTÜBERSICHT ALLER FIRMEN\n";
echo str_repeat('=', 80) . "\n";

$totalMinutes = 0;
$totalCost = 0;

foreach ($companies as $company) {
    $companySeconds = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->sum('duration_sec');
    
    $companyMinutes = $companySeconds / 60;
    $billingRate = $billingService->getCompanyBillingRate($company);
    $companyCost = $companyMinutes * $billingRate->rate_per_minute;
    
    echo "{$company->name}:\n";
    echo "  - Gesamtminuten: " . round($companyMinutes, 2) . "\n";
    echo "  - Gesamtkosten: " . number_format($companyCost, 2, ',', '.') . " €\n";
    
    $totalMinutes += $companyMinutes;
    $totalCost += $companyCost;
}

echo "\nGESAMT:\n";
echo "  - Minuten: " . round($totalMinutes, 2) . "\n";
echo "  - Kosten: " . number_format($totalCost, 2, ',', '.') . " €\n";

echo "\n✅ Prüfung abgeschlossen!\n\n";