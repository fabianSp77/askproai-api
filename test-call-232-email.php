<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST Call 232 E-Mail ===\n\n";

// 1. Get call 232
$callId = 232;
$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->find($callId);

// Load relationships separately to avoid tenant scope issues
if ($call) {
    $call->load([
        'company', 
        'customer', 
        'charge'
    ]);
    
    // Load branch without tenant scope
    if ($call->branch_id) {
        $call->branch = \App\Models\Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->find($call->branch_id);
    }
}

if (!$call) {
    echo "❌ Call $callId not found!\n";
    exit(1);
}

echo "1. Call Details:\n";
echo "   ID: " . $call->id . "\n";
echo "   Customer: " . ($call->customer->name ?? 'N/A') . "\n";
echo "   Phone: " . $call->from_number . "\n";
echo "   Date: " . $call->created_at->format('d.m.Y H:i') . "\n";
echo "   Company: " . ($call->company->name ?? 'N/A') . "\n";
echo "   Branch: " . ($call->branch->name ?? 'N/A') . "\n\n";

// 2. Set up context
app()->instance('current_company_id', $call->company_id);

// 3. Clear activities
\App\Models\CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('call_id', $callId)
    ->where('activity_type', 'email_sent')
    ->delete();

// 4. Test direct send first
echo "2. Testing direct send:\n";
try {
    \Illuminate\Support\Facades\Mail::to('fabianspitzer@icloud.com')->send(new \App\Mail\CallSummaryEmail(
        $call,
        true,  // transcript
        true,  // CSV
        'Test Call 232 - Direct - ' . now()->format('H:i:s'),
        'internal'
    ));
    echo "   ✅ Direct send successful\n";
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// 5. Test queue send (as portal does)
echo "\n3. Testing queue send (as portal does):\n";

// Clear queue
$redis = app('redis');
$redis->del("queues:default");

try {
    \Illuminate\Support\Facades\Mail::to('fabianspitzer@icloud.com')->queue(new \App\Mail\CallSummaryEmail(
        $call,
        true,  // transcript
        true,  // CSV
        'Test Call 232 - Queue - ' . now()->format('H:i:s'),
        'internal'
    ));
    echo "   ✅ Queued successfully\n";
    
    // Wait for processing
    sleep(3);
    
    $remaining = $redis->llen("queues:default");
    if ($remaining == 0) {
        echo "   ✅ Queue processed\n";
    } else {
        echo "   ⚠️ Still $remaining jobs in queue\n";
    }
    
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// 6. Check email template
echo "\n4. Checking email template:\n";
$templatePath = resource_path('views/emails/call-summary.blade.php');
if (file_exists($templatePath)) {
    echo "   ✅ Email template exists\n";
    
    // Check if template has business portal links
    $content = file_get_contents($templatePath);
    if (str_contains($content, 'api.askproai.de')) {
        echo "   ⚠️ Template contains API domain links\n";
    }
    if (!str_contains($content, 'askproai.de')) {
        echo "   ⚠️ Template missing main domain links\n";
    }
} else {
    echo "   ❌ Email template not found!\n";
}

echo "\n=== NEXT STEPS ===\n";
echo "1. Check if you received 'Test Call 232' emails\n";
echo "2. I will now create a professional email template\n";