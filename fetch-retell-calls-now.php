<?php

/**
 * Manueller Call Import von Retell.ai
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Jobs\FetchRetellCallsJob;
use App\Models\Company;

echo "\n=== Retell Call Import ===\n\n";

$company = Company::first();
if (!$company) {
    echo "❌ Keine Company gefunden\n";
    exit(1);
}

echo "Company: {$company->name}\n";
echo "Starte Call-Import...\n\n";

// Setze Company Context
app()->instance('company', $company);

// Login als Admin für Context
$user = \App\Models\User::where('email', 'admin@askproai.com')->first();
if ($user) {
    \Illuminate\Support\Facades\Auth::login($user);
}

// Dispatch Job synchron
try {
    $job = new FetchRetellCallsJob($company);
    $job->handle();
    echo "\n✅ Import abgeschlossen!\n";
} catch (\Exception $e) {
    echo "❌ Fehler beim Import: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}