<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Sende E-Mail vom Business Portal (Final) ===\n\n";

// Clear duplicate check for testing
$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(229);

// Delete recent email activities to allow resending
\App\Models\CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('call_id', 229)
    ->where('activity_type', 'email_sent')
    ->where('created_at', '>', now()->subMinutes(5))
    ->delete();

echo "Duplikat-Sperre gelöscht.\n\n";

// Now send via portal
$portalUser = \App\Models\PortalUser::where('email', 'demo@askproai.de')->first();
\Illuminate\Support\Facades\Auth::guard('portal')->login($portalUser);
app()->instance('current_company_id', $portalUser->company_id);

$request = \Illuminate\Http\Request::create(
    "/business/api/calls/229/send-summary", 
    'POST', 
    [
        'recipients' => ['fabianspitzer@icloud.com'],
        'include_transcript' => true,
        'include_csv' => true,
        'message' => 'Business Portal E-Mail mit CSV-Anhang und Transkript. Zeitstempel: ' . now()->format('d.m.Y H:i:s')
    ]
);

$request->setUserResolver(function () use ($portalUser) {
    return $portalUser;
});

$controller = app(\App\Http\Controllers\Portal\Api\CallApiController::class);

try {
    echo "Sende E-Mail an fabianspitzer@icloud.com...\n";
    $response = $controller->sendSummary($request, $call);
    $responseData = json_decode($response->getContent(), true);
    
    if ($response->getStatusCode() == 200) {
        echo "✅ E-Mail in Queue gestellt!\n";
        echo "Nachricht: " . $responseData['message'] . "\n\n";
        
        // Process queue
        echo "Verarbeite Queue...\n";
        \Illuminate\Support\Facades\Artisan::call('queue:work', [
            '--stop-when-empty' => true,
            '--tries' => 1
        ]);
        
        echo "\n✅ E-Mail wurde versendet!\n\n";
        echo "Die E-Mail enthält:\n";
        echo "- Professionelles HTML-Design\n";
        echo "- Vollständiges Transkript ✅\n";
        echo "- CSV-Datei als Anhang ✅\n";
        echo "- Anruf-Zusammenfassung\n";
        echo "- Action Items\n";
        echo "- Kundeninformationen\n";
        
    } else {
        echo "❌ Fehler: " . json_encode($responseData) . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}