<?php
/**
 * Check companies table to understand ID mismatch
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;

echo "=== COMPANIES TABLE ===\n\n";

// 1. List all companies
$companies = Company::all();
echo "Total companies: " . $companies->count() . "\n\n";

foreach ($companies as $company) {
    echo "Company: {$company->name}\n";
    echo "  ID: {$company->id}\n";
    echo "  Cal.com Team ID: " . ($company->calcom_team_id ?? 'NULL') . "\n";
    echo "  Created: {$company->created_at}\n";
    echo "\n";
}

// 2. Check if UUID '7fc13e06-ba89-4c54-a2d9-ecabe50abb7a' exists
echo "=== CHECKING UUID ===\n\n";
$friseur1 = Company::find('7fc13e06-ba89-4c54-a2d9-ecabe50abb7a');
if ($friseur1) {
    echo "✅ Found company with UUID 7fc13e06-ba89-4c54-a2d9-ecabe50abb7a\n";
    echo "   Name: {$friseur1->name}\n";
    echo "   Cal.com Team ID: {$friseur1->calcom_team_id}\n\n";
} else {
    echo "❌ No company found with UUID 7fc13e06-ba89-4c54-a2d9-ecabe50abb7a\n\n";
}

// 3. Check branch
echo "=== CHECKING BRANCH ===\n\n";
$branch = Branch::find('34c4d48e-4753-4715-9c30-c55843a943e8');
if ($branch) {
    echo "✅ Found branch 'Friseur 1 zentrale'\n";
    echo "   ID: {$branch->id}\n";
    echo "   Name: {$branch->name}\n";
    echo "   Company ID: {$branch->company_id}\n\n";

    $company = Company::find($branch->company_id);
    if ($company) {
        echo "   Company: {$company->name}\n";
        echo "   Company Cal.com Team ID: " . ($company->calcom_team_id ?? 'NULL') . "\n";
    }
} else {
    echo "❌ Branch not found\n\n";
}

// 4. Check companies table structure
echo "=== TABLE STRUCTURE ===\n\n";
$columns = DB::select("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'companies' AND column_name = 'id'");
foreach ($columns as $col) {
    echo "Column 'id' type: {$col->data_type}\n";
}
