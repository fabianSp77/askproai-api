<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Branch;
use Illuminate\Support\Facades\DB;

echo "=== FIX BRANCH LOOKUP FINAL ===\n";
echo "Zeit: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 50) . "\n\n";

// Test verschiedene Telefonnummern
$testNumbers = [
    '+493083793369',
    '493083793369',
    '03083793369',
    '3083793369'
];

echo "1. TESTE BRANCH LOOKUP:\n";
echo str_repeat("-", 40) . "\n";

foreach ($testNumbers as $number) {
    echo "\nTeste: $number\n";
    
    // Direct
    $branch1 = DB::table('branches')
        ->where('phone_number', $number)
        ->first();
    echo "  Direct: " . ($branch1 ? "✅ Gefunden (ID: {$branch1->id})" : "❌ Nicht gefunden") . "\n";
    
    // LIKE
    $branch2 = DB::table('branches')
        ->where('phone_number', 'LIKE', '%' . substr($number, -10) . '%')
        ->first();
    echo "  LIKE: " . ($branch2 ? "✅ Gefunden (ID: {$branch2->id})" : "❌ Nicht gefunden") . "\n";
}

// Zeige alle Branches
echo "\n2. ALLE BRANCHES:\n";
echo str_repeat("-", 40) . "\n";

$allBranches = DB::table('branches')
    ->select('id', 'name', 'phone_number', 'company_id')
    ->get();

foreach ($allBranches as $branch) {
    echo sprintf(
        "- %s | %s | %s | Company: %s\n",
        substr($branch->id, 0, 8),
        str_pad($branch->name, 20),
        $branch->phone_number ?? 'NULL',
        $branch->company_id
    );
}

// Test Model-Konvertierung
echo "\n3. TEST MODEL KONVERTIERUNG:\n";
echo str_repeat("-", 40) . "\n";

$testBranch = DB::table('branches')
    ->whereNotNull('company_id')
    ->first();

if ($testBranch) {
    echo "DB Object ID: " . $testBranch->id . "\n";
    
    try {
        $modelBranch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->find($testBranch->id);
            
        if ($modelBranch) {
            echo "✅ Model konvertiert: " . $modelBranch->name . "\n";
            echo "   ID: " . $modelBranch->id . "\n";
            echo "   Company: " . $modelBranch->company_id . "\n";
        } else {
            echo "❌ Model-Konvertierung fehlgeschlagen\n";
        }
    } catch (\Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }
}

// Fix-Vorschlag
echo "\n4. FIX VORSCHLAG:\n";
echo str_repeat("-", 40) . "\n";
echo "Das Problem ist die Branch-Lookup-Logik.\n";
echo "Die Telefonnummern-Suche muss flexibler sein.\n";