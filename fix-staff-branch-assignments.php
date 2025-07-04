<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Staff;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

echo "\n=== MITARBEITER-FILIAL KORREKTUR ===\n";
echo "Datum: " . date('Y-m-d H:i:s') . "\n\n";

// Prüfe fehlerhafte Zuordnungen
$staff = Staff::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->whereNotNull('branch_id')
    ->get();

$issues = [];

foreach ($staff as $employee) {
    $branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->find($employee->branch_id);
    
    if (!$branch) {
        $issues[] = [
            'staff' => $employee,
            'problem' => 'branch_not_found'
        ];
    } elseif ($branch->company_id != $employee->company_id) {
        $issues[] = [
            'staff' => $employee,
            'problem' => 'wrong_company',
            'branch' => $branch
        ];
    }
}

echo "Gefundene Probleme: " . count($issues) . "\n\n";

if (count($issues) > 0) {
    foreach ($issues as $issue) {
        $employee = $issue['staff'];
        $company = Company::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->find($employee->company_id);
        
        echo "Mitarbeiter: {$employee->name} (Firma: {$company->name})\n";
        
        if ($issue['problem'] == 'branch_not_found') {
            echo "  Problem: Filiale ID {$employee->branch_id} existiert nicht\n";
            
            // Finde die richtige Filiale für diese Firma
            $correctBranch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->where('company_id', $employee->company_id)
                ->first();
            
            if ($correctBranch) {
                echo "  Lösung: Setze auf Filiale '{$correctBranch->name}' (ID: {$correctBranch->id})\n";
                
                // Korrigiere die Zuordnung
                $employee->branch_id = $correctBranch->id;
                $employee->save();
                
                echo "  ✅ Korrigiert!\n";
            } else {
                echo "  ⚠️ Keine Filiale für diese Firma gefunden - setze branch_id auf NULL\n";
                $employee->branch_id = null;
                $employee->save();
            }
        }
        
        echo "\n";
    }
    
    echo "\n✅ Alle Probleme wurden behoben!\n";
} else {
    echo "✅ Keine Probleme gefunden - alle Zuordnungen sind korrekt!\n";
}

// Zeige aktuelle Zuordnungen
echo "\n\nAKTUELLE ZUORDNUNGEN:\n";
echo str_repeat('-', 80) . "\n";

$companies = Company::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->whereIn('id', [1, 15])
    ->get();

foreach ($companies as $company) {
    echo "\n{$company->name}:\n";
    
    $staff = Staff::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->get();
    
    foreach ($staff as $employee) {
        echo "  - {$employee->name}";
        if ($employee->branch_id) {
            $branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->find($employee->branch_id);
            if ($branch) {
                echo " → {$branch->name}";
            }
        } else {
            echo " → (Keine Filiale)";
        }
        echo "\n";
    }
}