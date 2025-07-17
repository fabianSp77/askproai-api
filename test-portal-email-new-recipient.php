<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Test Business Portal E-Mail mit neuer Adresse ===\n\n";

// Simulate portal user session
$portalUser = \App\Models\PortalUser::where('email', 'demo@askproai.de')->first();
if (!$portalUser) {
    echo "❌ Portal User nicht gefunden!\n";
    exit(1);
}

// Set session context
\Illuminate\Support\Facades\Auth::guard('portal')->login($portalUser);
app()->instance('current_company_id', $portalUser->company_id);

$callId = 229;
$timestamp = now()->format('His');
$testEmail = "test-{$timestamp}@askproai.de"; // Unique email address

echo "Test-Empfänger: $testEmail\n\n";

// Create request
$request = \Illuminate\Http\Request::create(
    "/business/api/calls/{$callId}/send-summary", 
    'POST', 
    [
        'recipients' => [$testEmail, 'fabianspitzer@icloud.com'],
        'include_transcript' => true,
        'include_csv' => true,
        'message' => 'Business Portal Test - Mit CSV und Transkript'
    ]
);

$request->setUserResolver(function () use ($portalUser) {
    return $portalUser;
});

// Get the controller
$controller = app(\App\Http\Controllers\Portal\Api\CallApiController::class);

try {
    echo "1. Sende E-Mail über Business Portal API...\n";
    $response = $controller->sendSummary($request, \App\Models\Call::find($callId));
    $responseData = json_decode($response->getContent(), true);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response: " . json_encode($responseData, JSON_PRETTY_PRINT) . "\n\n";
    
    if ($response->getStatusCode() == 200) {
        echo "✅ E-Mail wurde in die Queue gestellt!\n\n";
        
        // Check what's in the queue before processing
        echo "2. Queue Status VOR Verarbeitung:\n";
        $redis = app('redis');
        $queues = ['default', 'high', 'emails'];
        
        $totalJobs = 0;
        foreach ($queues as $queue) {
            $length = $redis->llen("queues:{$queue}");
            if ($length > 0) {
                echo "   - {$queue}: {$length} Jobs\n";
                $totalJobs += $length;
                
                // Show first job details
                $firstJob = $redis->lindex("queues:{$queue}", 0);
                if ($firstJob) {
                    $job = json_decode($firstJob, true);
                    echo "     Job: " . ($job['displayName'] ?? 'Unknown') . "\n";
                }
            }
        }
        
        if ($totalJobs > 0) {
            echo "\n3. Verarbeite Queue...\n";
            \Illuminate\Support\Facades\Artisan::call('queue:work', [
                '--stop-when-empty' => true,
                '--tries' => 1,
                '--queue' => 'default,high,emails'
            ]);
            
            $output = \Illuminate\Support\Facades\Artisan::output();
            if ($output) {
                echo $output;
            }
        } else {
            echo "\n⚠️  Keine Jobs in der Queue gefunden!\n";
        }
        
        echo "\n4. Queue Status NACH Verarbeitung:\n";
        foreach ($queues as $queue) {
            $length = $redis->llen("queues:{$queue}");
            echo "   - {$queue}: {$length} Jobs\n";
        }
        
        echo "\n5. Prüfe ob E-Mail direkt gesendet werden kann:\n";
        try {
            $call = \App\Models\Call::find($callId);
            \Illuminate\Support\Facades\Mail::to($testEmail)->send(new \App\Mail\CallSummaryEmail(
                $call,
                true,  // include transcript
                true,  // include CSV
                'DIREKTER TEST - Ohne Queue',
                'internal'
            ));
            echo "✅ Direkt-Versand erfolgreich!\n";
        } catch (\Exception $e) {
            echo "❌ Direkt-Versand fehlgeschlagen: " . $e->getMessage() . "\n";
        }
        
    }
    
} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n6. Mail-System Status:\n";
echo "   Mail Driver: " . config('mail.default') . "\n";
echo "   From Address: " . config('mail.from.address') . "\n";
echo "   Resend API Key: " . (config('services.resend.key') ? '✅ Konfiguriert' : '❌ Fehlt') . "\n";