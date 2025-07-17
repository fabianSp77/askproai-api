#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Models\Company;
use App\Jobs\SendCallSummaryJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

// Get the latest call with company
$call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('id', 277)
    ->first();

// Load relationships without global scopes
if ($call) {
    $call->load([
        'company' => function($query) {
            $query->withoutGlobalScope(\App\Scopes\TenantScope::class);
        },
        'branch' => function($query) {
            $query->withoutGlobalScope(\App\Scopes\TenantScope::class);
        }
    ]);
}

if (!$call) {
    echo "No call found\n";
    exit(1);
}

echo "Call Details:\n";
echo "- ID: {$call->id}\n";
echo "- Company: " . ($call->company ? $call->company->name : 'N/A') . "\n";
echo "- Branch: " . ($call->branch ? $call->branch->name : 'N/A') . "\n";
echo "- Created: {$call->created_at}\n\n";

// Check company email settings
if ($call->company) {
    echo "Company Email Settings:\n";
    echo "- Send summaries: " . ($call->company->send_call_summaries ? 'Yes' : 'No') . "\n";
    echo "- Email recipients: " . ($call->company->call_summary_emails ?: 'None configured') . "\n\n";
}

// Test email configuration
echo "Testing email configuration...\n";
try {
    $config = config('mail.mailers.resend');
    echo "- Mailer: resend\n";
    echo "- API Key: " . (config('services.resend.key') ? 'Configured' : 'Not configured') . "\n";
    echo "- From address: " . config('mail.from.address') . "\n";
    echo "- From name: " . config('mail.from.name') . "\n\n";
} catch (\Exception $e) {
    echo "Error checking config: " . $e->getMessage() . "\n";
}

// Try to send a test email directly
echo "Sending test email...\n";
try {
    Mail::raw('This is a test email from AskProAI', function ($message) {
        $message->to('test@example.com')
                ->subject('Test Email from AskProAI');
    });
    echo "✅ Test email sent successfully!\n\n";
} catch (\Exception $e) {
    echo "❌ Error sending email: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n\n";
}

// Now dispatch the job
echo "Dispatching SendCallSummaryJob...\n";
try {
    SendCallSummaryJob::dispatch($call);
    echo "✅ Job dispatched successfully!\n";
    
    // Check queue
    $redis = app('redis');
    $emailsCount = $redis->llen('queues:emails');
    echo "Emails queue count: {$emailsCount}\n";
    
} catch (\Exception $e) {
    echo "❌ Error dispatching job: " . $e->getMessage() . "\n";
}