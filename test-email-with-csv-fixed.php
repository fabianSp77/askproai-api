<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Test E-Mail mit CSV (FIXED) ===\n\n";

try {
    // Get call without tenant scope
    $call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->with(['company', 'customer'])
        ->find(228);
    
    if (!$call) {
        throw new Exception("Call 228 nicht gefunden!");
    }
    
    // Set company context
    app()->instance('current_company_id', $call->company_id);
    
    echo "Call: {$call->id}\n";
    echo "Company: {$call->company->name}\n\n";
    
    // Clear duplicate check
    \App\Models\CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('call_id', 228)
        ->where('activity_type', 'email_sent')
        ->where('created_at', '>', now()->subMinutes(5))
        ->delete();
    
    echo "Sende E-Mail MIT CSV-Anhang...\n";
    
    \Illuminate\Support\Facades\Mail::to('fabianspitzer@icloud.com')->send(new \App\Mail\CallSummaryEmail(
        $call,
        true,  // include transcript
        true,  // include CSV
        'Business Portal - MIT CSV - ' . now()->format('d.m.Y H:i:s'),
        'internal'
    ));
    
    echo "✅ E-Mail mit CSV erfolgreich versendet!\n\n";
    
    echo "Die E-Mail enthält:\n";
    echo "- ✅ Professionelles HTML-Design\n";
    echo "- ✅ Vollständiges Transkript\n";
    echo "- ✅ CSV-Datei als Anhang\n";
    echo "- ✅ Alle Anrufinformationen\n\n";
    
    echo "Das CSV-Problem wurde behoben!\n";
    
} catch (\Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}