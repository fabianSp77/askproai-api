<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PhoneNumber;
use App\Models\Call;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

echo "\n=== DATENBANK KONSISTENZ-PRÜFUNG ===\n";
echo "Datum: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Übersicht aller Firmen
echo "1. FIRMEN-ÜBERSICHT:\n";
echo str_repeat('-', 80) . "\n";

$companies = Company::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->orderBy('id')
    ->get();

foreach ($companies as $company) {
    echo "Firma ID {$company->id}: {$company->name}\n";
    
    // Telefonnummern der Firma
    $phoneNumbers = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->get();
    
    echo "  Telefonnummern: " . $phoneNumbers->count() . "\n";
    foreach ($phoneNumbers as $phone) {
        $status = $phone->is_active ? 'aktiv' : 'inaktiv';
        echo "    - {$phone->number} ({$status}, Typ: {$phone->type}";
        if ($phone->branch_id) {
            $branch = DB::table('branches')->find($phone->branch_id);
            echo ", Filiale: " . ($branch ? $branch->name : 'ID: ' . $phone->branch_id);
        }
        echo ")\n";
    }
    
    // Anrufe der Firma
    $totalCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->count();
    
    echo "  Gesamte Anrufe: {$totalCalls}\n";
    echo "\n";
}

// 2. Konsistenz-Prüfung: Anrufe vs. Telefonnummern
echo "\n2. KONSISTENZ-PRÜFUNG: ANRUFE VS. TELEFONNUMMERN\n";
echo str_repeat('-', 80) . "\n";

// Prüfe ob Anrufe zu den richtigen Firmen zugeordnet sind
$phoneNumbers = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('is_active', true)
    ->get();

$inconsistencies = [];

foreach ($phoneNumbers as $phone) {
    // Alle Anrufe für diese Nummer
    $calls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('to_number', $phone->number)
        ->select('company_id', DB::raw('COUNT(*) as count'))
        ->groupBy('company_id')
        ->get();
    
    foreach ($calls as $callGroup) {
        if ($callGroup->company_id != $phone->company_id) {
            $wrongCompany = Company::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->find($callGroup->company_id);
            
            $inconsistencies[] = [
                'phone' => $phone->number,
                'expected_company_id' => $phone->company_id,
                'expected_company' => $phone->company->name ?? 'Unknown',
                'actual_company_id' => $callGroup->company_id,
                'actual_company' => $wrongCompany->name ?? 'Unknown',
                'call_count' => $callGroup->count
            ];
        }
    }
}

if (count($inconsistencies) > 0) {
    echo "⚠️  WARNUNG: Inkonsistenzen gefunden!\n\n";
    foreach ($inconsistencies as $issue) {
        echo "Telefonnummer: {$issue['phone']}\n";
        echo "  Erwartete Firma: {$issue['expected_company']} (ID: {$issue['expected_company_id']})\n";
        echo "  Tatsächliche Firma: {$issue['actual_company']} (ID: {$issue['actual_company_id']})\n";
        echo "  Betroffene Anrufe: {$issue['call_count']}\n\n";
    }
} else {
    echo "✅ Alle Anrufe sind korrekt den Firmen zugeordnet!\n";
}

// 3. Anrufe ohne zugeordnete Telefonnummer
echo "\n3. ANRUFE OHNE REGISTRIERTE TELEFONNUMMER\n";
echo str_repeat('-', 80) . "\n";

$allPhoneNumbers = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->pluck('number')
    ->toArray();

$unmatchedCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->whereNotIn('to_number', $allPhoneNumbers)
    ->select('to_number', 'company_id', DB::raw('COUNT(*) as count'))
    ->groupBy(['to_number', 'company_id'])
    ->get();

if ($unmatchedCalls->count() > 0) {
    echo "⚠️  Anrufe zu nicht registrierten Nummern:\n\n";
    foreach ($unmatchedCalls as $call) {
        $company = Company::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($call->company_id);
        echo "Nummer: {$call->to_number}\n";
        echo "  Firma: " . ($company ? $company->name : 'Unknown') . " (ID: {$call->company_id})\n";
        echo "  Anrufe: {$call->count}\n\n";
    }
} else {
    echo "✅ Alle Anrufe gehen an registrierte Telefonnummern!\n";
}

// 4. Letzte Anrufe pro Firma
echo "\n4. LETZTE ANRUFE PRO FIRMA\n";
echo str_repeat('-', 80) . "\n";

foreach ($companies as $company) {
    $lastCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->orderBy('created_at', 'desc')
        ->limit(3)
        ->get();
    
    if ($lastCalls->count() > 0) {
        echo "\n{$company->name} (ID: {$company->id}):\n";
        foreach ($lastCalls as $call) {
            echo "  - {$call->created_at}: {$call->from_number} → {$call->to_number}";
            echo " (" . ($call->duration_sec ?? 0) . " Sek.)\n";
        }
    }
}

// 5. Zusammenfassung
echo "\n\n5. ZUSAMMENFASSUNG\n";
echo str_repeat('-', 80) . "\n";

$totalCompanies = Company::withoutGlobalScope(\App\Scopes\TenantScope::class)->count();
$totalPhoneNumbers = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)->count();
$activePhoneNumbers = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)->where('is_active', true)->count();
$totalCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->count();

echo "Firmen gesamt: {$totalCompanies}\n";
echo "Telefonnummern gesamt: {$totalPhoneNumbers} (davon {$activePhoneNumbers} aktiv)\n";
echo "Anrufe gesamt: {$totalCalls}\n";
echo "Inkonsistenzen gefunden: " . count($inconsistencies) . "\n";

echo "\n✅ Prüfung abgeschlossen!\n\n";