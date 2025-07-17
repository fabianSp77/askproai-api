<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Call;
use App\Models\PortalUser;
use App\Models\CallActivity;
use App\Mail\CallSummaryEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

// Get test portal user
$user = PortalUser::where('email', 'test@askproai.de')->first();
if (!$user) {
    echo "Test user not found. Please create test@askproai.de first.\n";
    exit(1);
}

// Set company context
app()->instance('current_company_id', $user->company_id);

// Get any call
$call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('company_id', $user->company_id)
    ->orderBy('created_at', 'desc')
    ->first();

if (!$call) {
    // Get any call from any company
    $call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->orderBy('created_at', 'desc')
        ->first();
}

if (!$call) {
    echo "No calls found in database.\n";
    exit(1);
}

echo "Found call ID: {$call->id}\n";
$summary = $call->call_summary ?? $call->summary ?? 'No summary available';
echo "Call summary: " . substr($summary, 0, 100) . "...\n";
echo "Company ID: {$call->company_id}\n";

// Update company context with the call's company
app()->instance('current_company_id', $call->company_id);

// Test the send summary functionality
$recipients = ['test@example.com'];
$message = 'Test message from automated test';

try {
    echo "\nTesting send summary to: " . implode(', ', $recipients) . "\n";
    
    // Send email
    foreach ($recipients as $recipient) {
        Mail::to($recipient)->send(new CallSummaryEmail(
            $call,
            true, // includeTranscript
            false, // includeCsv
            $message,
            'internal' // recipientType
        ));
    }
    
    echo "✅ Email sent successfully!\n";
    
    // Log activity
    CallActivity::log($call, CallActivity::TYPE_EMAIL_SENT, 'Zusammenfassung versendet', [
        'user_id' => $user->id,
        'is_system' => false,
        'description' => 'E-Mail an ' . count($recipients) . ' Empfänger versendet',
        'metadata' => [
            'recipients' => $recipients,
            'subject' => 'Test Email',
            'included_recording' => false,
            'included_transcript' => true,
            'sent_by' => $user->name
        ]
    ]);
    
    echo "✅ Activity logged successfully!\n";
    
    // Check if activity was created
    $activities = $call->activities()->get();
    echo "\nTotal activities for this call: " . $activities->count() . "\n";
    
    $lastActivity = $activities->first();
    if ($lastActivity) {
        echo "Last activity: " . $lastActivity->title . " at " . $lastActivity->created_at . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}