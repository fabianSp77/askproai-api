<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Branch;

// Branch ID oder Name angeben
$branchIdentifier = $argv[1] ?? null;

if (!$branchIdentifier) {
    echo "Usage: php delete-branch.php <branch-id-or-name>\n";
    exit(1);
}

// Branch finden
$branch = Branch::where('id', $branchIdentifier)
    ->orWhere('name', 'LIKE', "%{$branchIdentifier}%")
    ->first();

if (!$branch) {
    echo "Branch nicht gefunden: {$branchIdentifier}\n";
    exit(1);
}

echo "Gefundene Filiale:\n";
echo "ID: {$branch->id}\n";
echo "Name: {$branch->name}\n";
echo "Unternehmen: {$branch->company->name}\n";
echo "Stadt: {$branch->city}\n\n";

echo "Möchten Sie diese Filiale wirklich löschen? (j/n): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);

if (trim($line) === 'j') {
    // Soft Delete
    $branch->delete();
    echo "Filiale wurde gelöscht (Soft Delete).\n";
    
    echo "Möchten Sie die Filiale endgültig löschen? (j/n): ";
    $line = fgets($handle);
    
    if (trim($line) === 'j') {
        // Hard Delete
        $branch->forceDelete();
        echo "Filiale wurde endgültig gelöscht.\n";
    }
} else {
    echo "Löschvorgang abgebrochen.\n";
}

fclose($handle);