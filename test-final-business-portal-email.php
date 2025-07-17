<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== FINAL TEST Business Portal Email ===\n\n";

// 1. Use call 232 as requested
$callId = 232;
$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($callId);

if (!$call) {
    echo "❌ Call $callId not found!\n";
    exit(1);
}

// Load relationships
$call->load(['company', 'customer', 'charge']);
if ($call->branch_id) {
    $call->branch = \App\Models\Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->find($call->branch_id);
}

echo "1. Call Details:\n";
echo "   ID: " . $call->id . "\n";
echo "   Company: " . ($call->company->name ?? 'N/A') . "\n";
echo "   Branch: " . ($call->branch->name ?? 'N/A') . "\n";
echo "   Customer: " . ($call->customer->name ?? 'Unknown') . "\n";
echo "   Phone: " . $call->from_number . "\n";
echo "   Date: " . $call->created_at->format('d.m.Y H:i') . "\n";
echo "   Duration: " . $call->duration_sec . " seconds\n";
echo "   Summary: " . substr($call->summary ?? $call->call_summary ?? 'No summary', 0, 100) . "...\n\n";

// 2. Set up context
app()->instance('current_company_id', $call->company_id);

// Clear activities
\App\Models\CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('call_id', $callId)
    ->where('activity_type', 'email_sent')
    ->delete();

// 3. Simulate Business Portal request
echo "2. Simulating Business Portal email request:\n";

$portalUser = \App\Models\PortalUser::first();
\Illuminate\Support\Facades\Auth::guard('portal')->login($portalUser);

$request = \Illuminate\Http\Request::create(
    "/api/business/calls/{$callId}/send-summary",
    'POST',
    [
        'recipients' => ['fabianspitzer@icloud.com'],
        'include_transcript' => true,
        'include_csv' => true,
        'message' => 'Diese E-Mail wurde aus dem Business Portal versendet.'
    ]
);
$request->setUserResolver(function () use ($portalUser) {
    return $portalUser;
});

$controller = app(\App\Http\Controllers\Portal\Api\CallApiController::class);

try {
    $response = $controller->sendSummary($request, $call);
    $responseData = json_decode($response->getContent(), true);
    
    echo "   Status: " . $response->getStatusCode() . "\n";
    if ($response->getStatusCode() == 200) {
        echo "   ✅ Success: " . ($responseData['message'] ?? 'OK') . "\n";
    } else {
        echo "   ❌ Error: " . json_encode($responseData) . "\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}

// 4. Wait for processing
echo "\n3. Waiting for queue processing...\n";
sleep(5);

// 5. Send a direct test for comparison
echo "\n4. Sending direct test for comparison:\n";
try {
    \Illuminate\Support\Facades\Mail::to('fabianspitzer@icloud.com')->send(new \App\Mail\CallSummaryEmail(
        $call,
        true,  // transcript
        true,  // CSV
        'Direct Test - Professional Template - ' . now()->format('H:i:s'),
        'internal'
    ));
    echo "   ✅ Direct send successful\n";
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== EXPECTED RESULTS ===\n";
echo "You should receive 2 emails:\n";
echo "1. Business Portal email with the new professional template\n";
echo "2. Direct test email for comparison\n\n";

echo "The emails should include:\n";
echo "- Professional design with company branding\n";
echo "- Customer information prominently displayed\n";
echo "- Call summary and urgency level\n";
echo "- Links to https://askproai.de (not api.askproai.de)\n";
echo "- CSV attachment with call data\n";
echo "- Footer with contact: fabian@askproai.de\n";