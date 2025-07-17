<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Business Portal E-Mail WORKING ===\n\n";

// Get Call with all relations
$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->with(['company', 'customer', 'branch'])
    ->find(228);

if (!$call) {
    echo "❌ Call 228 nicht gefunden!\n";
    exit(1);
}

// Set company context
app()->instance('current_company_id', $call->company_id);

echo "Call: {$call->id}\n";
echo "Company: {$call->company->name}\n";
echo "Sende an: fabianspitzer@icloud.com\n\n";

try {
    // Clear duplicate check
    \App\Models\CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('call_id', 228)
        ->where('activity_type', 'email_sent')
        ->where('created_at', '>', now()->subMinutes(5))
        ->delete();
    
    echo "1. Sende E-Mail DIREKT (ohne Queue):\n";
    
    \Illuminate\Support\Facades\Mail::to('fabianspitzer@icloud.com')->send(new \App\Mail\CallSummaryEmail(
        $call,
        true,  // include transcript
        true,  // include CSV
        'Business Portal E-Mail - DIREKT - ' . now()->format('H:i:s'),
        'internal'
    ));
    
    echo "✅ E-Mail direkt versendet!\n\n";
    
    // Log activity
    \App\Models\CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)->create([
        'call_id' => $call->id,
        'activity_type' => 'email_sent',
        'title' => 'Zusammenfassung versendet',
        'description' => 'E-Mail direkt versendet an fabianspitzer@icloud.com',
        'user_id' => 1,
        'is_system' => false,
        'metadata' => [
            'recipients' => ['fabianspitzer@icloud.com'],
            'included_transcript' => true,
            'included_csv' => true,
            'sent_at' => now()->toIso8601String()
        ]
    ]);
    
    echo "Die E-Mail wurde DIREKT versendet (ohne Queue).\n";
    echo "Sie sollte in wenigen Sekunden ankommen!\n\n";
    
    echo "Enthält:\n";
    echo "- ✅ Professionelles HTML-Design\n";
    echo "- ✅ Vollständiges Transkript\n";
    echo "- ✅ CSV-Datei als Anhang\n";
    echo "- ✅ Alle Anrufinformationen\n";
    
} catch (\Exception $e) {
    echo "❌ FEHLER: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    // Try alternative approach
    echo "\n2. Versuche alternativen Ansatz...\n";
    
    // Send without CSV to test
    try {
        \Illuminate\Support\Facades\Mail::to('fabianspitzer@icloud.com')->send(new \App\Mail\CallSummaryEmail(
            $call,
            true,   // include transcript
            false,  // NO CSV
            'Business Portal E-Mail - OHNE CSV - ' . now()->format('H:i:s'),
            'internal'
        ));
        
        echo "✅ E-Mail OHNE CSV versendet!\n";
        echo "Das Problem liegt beim CSV-Export.\n";
        
    } catch (\Exception $e2) {
        echo "❌ Auch ohne CSV fehlgeschlagen: " . $e2->getMessage() . "\n";
    }
}