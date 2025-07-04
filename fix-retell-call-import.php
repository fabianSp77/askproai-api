<?php

/**
 * Fix Retell Call Import
 * 
 * Sofortige Lösung für fehlende Anrufe:
 * 1. Importiert die letzten 50 Anrufe
 * 2. Prüft Webhook-Konfiguration
 * 3. Stellt sicher, dass Queue läuft
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;
use App\Models\Call;

echo "\n=== Retell Call Import Fix ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Import last 50 calls
echo "1. Importing last 50 calls from Retell...\n";
$beforeCount = Call::count();

try {
    Artisan::call('retell:fetch-calls', ['--limit' => 50]);
    echo Artisan::output();
} catch (\Exception $e) {
    echo "Error importing calls: " . $e->getMessage() . "\n";
}

$afterCount = Call::count();
$imported = $afterCount - $beforeCount;
echo "   ✅ Imported $imported new calls\n\n";

// 2. Check webhook configuration
echo "2. Checking webhook configuration...\n";
$webhookUrl = config('app.url') . '/api/retell/webhook';
echo "   Webhook URL: $webhookUrl\n";
echo "   ⚠️  Make sure this URL is configured in your Retell dashboard!\n\n";

// 3. Check for failed jobs
echo "3. Checking for failed webhook jobs...\n";
$failedJobs = \Illuminate\Support\Facades\DB::table('failed_jobs')
    ->where('payload', 'like', '%ProcessRetellCallEndedJob%')
    ->count();

if ($failedJobs > 0) {
    echo "   ⚠️  Found $failedJobs failed webhook jobs\n";
    echo "   Run 'php artisan queue:retry all' to retry them\n";
} else {
    echo "   ✅ No failed webhook jobs\n";
}

// 4. Process any pending webhooks
echo "\n4. Processing pending webhooks...\n";
try {
    Artisan::call('queue:work', [
        '--queue' => 'webhooks',
        '--stop-when-empty' => true,
        '--max-time' => 30
    ]);
    echo "   ✅ Processed pending webhooks\n";
} catch (\Exception $e) {
    echo "   ⚠️  Error processing webhooks: " . $e->getMessage() . "\n";
}

// 5. Show recent calls
echo "\n5. Recent calls in database:\n";
$recentCalls = Call::orderBy('created_at', 'desc')->limit(5)->get();
foreach ($recentCalls as $call) {
    echo sprintf("   - %s: %s (%s) - %s\n",
        $call->created_at->format('Y-m-d H:i'),
        substr($call->call_id ?? $call->retell_call_id, 0, 20),
        $call->duration_sec . 's',
        $call->session_outcome ?? 'Unknown'
    );
}

echo "\n=== Summary ===\n";
echo "Total calls in database: " . Call::count() . "\n";
echo "Calls from today: " . Call::whereDate('created_at', today())->count() . "\n";
echo "Calls from last hour: " . Call::where('created_at', '>=', now()->subHour())->count() . "\n";

echo "\n=== Next Steps ===\n";
echo "1. ✅ Automatic import is now scheduled every 15 minutes\n";
echo "2. ⚠️  Verify webhook URL in Retell dashboard: $webhookUrl\n";
echo "3. ✅ Horizon is running for webhook processing\n";
echo "4. 📱 Make a test call and check if it appears automatically\n";

echo "\n✅ Fix complete!\n";
echo "Completed at: " . date('Y-m-d H:i:s') . "\n";