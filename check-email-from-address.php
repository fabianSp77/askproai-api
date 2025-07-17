<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== CHECK E-Mail From Address Problem ===\n\n";

// 1. Check current mail config
echo "1. Aktuelle Mail-Konfiguration:\n";
echo "   From Address: " . config('mail.from.address') . "\n";
echo "   From Name: " . config('mail.from.name') . "\n";
echo "   Mail Driver: " . config('mail.default') . "\n\n";

// 2. Test with different configurations
$testConfigs = [
    [
        'from' => 'info@askproai.de',
        'name' => 'AskProAI',
        'description' => 'Original (möglicherweise nicht verifiziert)'
    ],
    [
        'from' => 'onboarding@resend.dev',
        'name' => 'AskProAI System',
        'description' => 'Resend Default (garantiert verifiziert)'
    ]
];

$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(227);
if (!$call) {
    echo "❌ Call 227 nicht gefunden!\n";
    exit(1);
}
app()->instance('current_company_id', $call->company_id);

foreach ($testConfigs as $config) {
    echo "2. Test mit: {$config['from']} ({$config['description']})\n";
    
    // Temporarily change config
    config(['mail.from.address' => $config['from']]);
    config(['mail.from.name' => $config['name']]);
    
    try {
        // Clear duplicate check
        \App\Models\CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('call_id', 227)
            ->where('activity_type', 'email_sent')
            ->where('created_at', '>', now()->subMinutes(5))
            ->delete();
        
        // Send email
        \Illuminate\Support\Facades\Mail::to('fabianspitzer@icloud.com')->send(new \App\Mail\CallSummaryEmail(
            $call,
            true,
            false, // No CSV for test
            'From-Address Test: ' . $config['from'] . ' - ' . now()->format('H:i:s'),
            'internal'
        ));
        
        echo "   ✅ E-Mail versendet\n\n";
        
    } catch (\Exception $e) {
        echo "   ❌ Fehler: " . $e->getMessage() . "\n\n";
    }
}

echo "3. LÖSUNG:\n";
echo "   Wenn 'onboarding@resend.dev' funktioniert aber 'info@askproai.de' nicht:\n";
echo "   → Die Domain muss in Resend verifiziert werden!\n\n";

echo "   TEMPORÄRE LÖSUNG:\n";
echo "   Ändern Sie in .env:\n";
echo "   MAIL_FROM_ADDRESS=\"onboarding@resend.dev\"\n\n";

echo "   PERMANENTE LÖSUNG:\n";
echo "   1. Gehen Sie zu https://resend.com/domains\n";
echo "   2. Fügen Sie 'askproai.de' hinzu\n";
echo "   3. Fügen Sie die DNS-Records hinzu\n";
echo "   4. Warten Sie auf Verifizierung\n";
echo "   5. Ändern Sie zurück zu MAIL_FROM_ADDRESS=\"info@askproai.de\"\n";