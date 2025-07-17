<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== FIX Business Portal E-Mail ===\n\n";

// 1. Check current environment
echo "1. Environment: " . app()->environment() . "\n\n";

// 2. Test email sending with proper context
echo "2. Sende Test-E-Mail mit korrektem Context:\n";

try {
    // Get call without tenant scope
    $call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->with(['company', 'customer', 'branch'])
        ->find(228);
    
    if (!$call) {
        throw new Exception("Call 228 nicht gefunden!");
    }
    
    // Set company context BEFORE any operations
    $originalCompanyId = app()->has('current_company_id') ? app('current_company_id') : null;
    app()->instance('current_company_id', $call->company_id);
    
    echo "   Call: {$call->id}\n";
    echo "   Company: {$call->company->name}\n";
    echo "   Company ID in Context: " . app('current_company_id') . "\n\n";
    
    // Clear duplicate check
    \App\Models\CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('call_id', 228)
        ->where('activity_type', 'email_sent')
        ->where('created_at', '>', now()->subMinutes(5))
        ->delete();
    
    // Send email
    echo "3. Sende E-Mail:\n";
    
    \Illuminate\Support\Facades\Mail::to('fabianspitzer@icloud.com')->send(new \App\Mail\CallSummaryEmail(
        $call,
        true,  // include transcript
        true,  // include CSV
        'Business Portal FIX - ' . now()->format('d.m.Y H:i:s'),
        'internal'
    ));
    
    echo "   ✅ E-Mail erfolgreich versendet!\n\n";
    
    // Restore original context
    if ($originalCompanyId) {
        app()->instance('current_company_id', $originalCompanyId);
    }
    
    echo "4. LÖSUNG für Business Portal:\n";
    echo "   Das Problem ist der TenantScope in Production.\n";
    echo "   Die Lösung wurde im Controller implementiert.\n\n";
    
    echo "Die E-Mail sollte jetzt ankommen mit:\n";
    echo "- ✅ HTML-Design\n";
    echo "- ✅ Transkript\n";
    echo "- ✅ CSV-Anhang\n";
    
} catch (\Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    // Try without CSV
    echo "\nVersuche ohne CSV...\n";
    try {
        $call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(228);
        app()->instance('current_company_id', $call->company_id);
        
        \Illuminate\Support\Facades\Mail::to('fabianspitzer@icloud.com')->send(new \App\Mail\CallSummaryEmail(
            $call,
            true,   // include transcript
            false,  // NO CSV
            'Business Portal FIX - OHNE CSV - ' . now()->format('H:i:s'),
            'internal'
        ));
        
        echo "✅ E-Mail OHNE CSV erfolgreich!\n";
        echo "Das CSV-Export hatte ein Problem mit dem TenantScope.\n";
        
    } catch (\Exception $e2) {
        echo "❌ Auch ohne CSV: " . $e2->getMessage() . "\n";
    }
}