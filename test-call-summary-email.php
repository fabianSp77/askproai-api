<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Models\Company;
use App\Jobs\SendCallSummaryJob;

// Set company context to avoid tenant scope issues
$company = Company::first();
if (!$company) {
    echo "No companies found in database.\n";
    exit(1);
}

// Set the current company in the app container
app()->singleton('current_company', function () use ($company) {
    return $company;
});

// Get the latest call for this company without loading relationships yet
$call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('company_id', $company->id)
    ->latest()
    ->first();

if (!$call) {
    echo "No calls found for company {$company->name}.\n";
    exit(1);
}

echo "Testing email for call ID: {$call->id}\n";
echo "Company: {$call->company->name}\n";
echo "Created at: {$call->created_at}\n";
echo "Phone: {$call->phone_number}\n";
echo "Summary: " . ($call->summary ?: 'No summary available') . "\n\n";

// Test sending email
try {
    // Get email config from company or use test email
    $recipients = $company->call_summary_recipients ?? ['test@example.com'];
    if (empty($recipients)) {
        $recipients = ['test@example.com'];
    }
    
    echo "Sending to: " . implode(', ', $recipients) . "\n";
    
    // Dispatch the job synchronously for testing
    $job = new SendCallSummaryJob($call, $recipients, 'This is a test of the call summary system');
    $job->handle();
    
    echo "Email job executed successfully!\n";
    echo "Check your email inbox for the summary.\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}