<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== FIX E-Mail Queue Problem ===\n\n";

// 1. Check Queue Connection
echo "1. Queue Connection:\n";
echo "   Default: " . config('queue.default') . "\n";
echo "   Mail Queue: " . config('mail.queue') . "\n\n";

// 2. Check if CallSummaryEmail implements ShouldQueue correctly
echo "2. CallSummaryEmail Class Check:\n";
$reflection = new ReflectionClass(\App\Mail\CallSummaryEmail::class);
$interfaces = $reflection->getInterfaceNames();
echo "   Implements ShouldQueue: " . (in_array('Illuminate\Contracts\Queue\ShouldQueue', $interfaces) ? 'YES' : 'NO') . "\n";
echo "   Uses Queueable: " . ($reflection->hasProperty('queue') ? 'YES' : 'NO') . "\n\n";

// 3. Clear config cache
echo "3. Clear Config Cache:\n";
\Illuminate\Support\Facades\Artisan::call('config:clear');
echo "   ✅ Config cache cleared\n\n";

// 4. Test with sync driver temporarily
echo "4. Test mit SYNC Driver (ohne Queue):\n";
config(['mail.default' => 'resend']);
config(['queue.default' => 'sync']);

try {
    $call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(228);
    
    \Illuminate\Support\Facades\Mail::to('fabianspitzer@icloud.com')->send(new \App\Mail\CallSummaryEmail(
        $call,
        true,
        true,
        'SYNC TEST - Should arrive immediately - ' . now()->format('H:i:s'),
        'internal'
    ));
    
    echo "   ✅ E-Mail mit SYNC driver gesendet!\n\n";
} catch (\Exception $e) {
    echo "   ❌ Fehler: " . $e->getMessage() . "\n\n";
}

// 5. Check Horizon Processes
echo "5. Horizon Process Check:\n";
$processes = shell_exec('ps aux | grep horizon | grep -v grep');
echo $processes ? "   Horizon läuft:\n$processes" : "   ⚠️  Horizon läuft nicht!\n";

// 6. Restart Horizon
echo "\n6. Restart Horizon:\n";
\Illuminate\Support\Facades\Artisan::call('horizon:terminate');
echo "   ✅ Horizon wird neu gestartet...\n";

// 7. Send test email with proper queue
echo "\n7. Final Test mit Redis Queue:\n";
config(['queue.default' => 'redis']);

try {
    // Clear any duplicate blocks
    \App\Models\CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('call_id', 228)
        ->where('activity_type', 'email_sent')
        ->where('created_at', '>', now()->subMinutes(5))
        ->delete();
    
    $call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(228);
    
    \Illuminate\Support\Facades\Mail::to('fabianspitzer@icloud.com')->queue(new \App\Mail\CallSummaryEmail(
        $call,
        true,
        true,
        'REDIS QUEUE TEST - After Horizon restart - ' . now()->format('H:i:s'),
        'internal'
    ));
    
    echo "   ✅ E-Mail in Redis Queue gestellt\n";
    
    // Process manually
    echo "   Verarbeite Queue...\n";
    \Illuminate\Support\Facades\Artisan::call('queue:work', [
        '--stop-when-empty' => true,
        '--tries' => 1
    ]);
    
    echo "   ✅ Queue verarbeitet\n";
    
} catch (\Exception $e) {
    echo "   ❌ Fehler: " . $e->getMessage() . "\n";
}

echo "\n=== EMPFEHLUNG ===\n";
echo "Falls E-Mails immer noch nicht ankommen:\n";
echo "1. Prüfen Sie Resend Dashboard: https://resend.com/emails\n";
echo "2. Prüfen Sie Spam-Ordner\n";
echo "3. Führen Sie aus: php artisan horizon\n";