<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Branch;
use App\Models\Company;

echo "=== Aktuelle Datenbank-Struktur ===\n\n";

// Companies
$companies = Company::count();
echo "Anzahl Companies: $companies\n";

// Branches
$branches = Branch::count();
echo "Anzahl Branches: $branches\n";

// Branch Felder prüfen
$branch = new Branch();
$fillable = $branch->getFillable();
echo "\nBranch fillable Felder:\n";
foreach ($fillable as $field) {
    echo "- $field\n";
}

// Prüfe ob calcom_event_type_id existiert
if (Schema::hasColumn('branches', 'calcom_event_type_id')) {
    echo "\n✅ calcom_event_type_id Feld existiert bereits\n";
} else {
    echo "\n❌ calcom_event_type_id Feld muss noch hinzugefügt werden\n";
}
