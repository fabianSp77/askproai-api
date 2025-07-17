<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Models\Company;
use App\Mail\CallSummaryEmail;
use Illuminate\Support\Facades\Mail;

// Get the latest call without tenant scope
$call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->latest()
    ->first();

if (!$call) {
    echo "No calls found in database.\n";
    exit(1);
}

// Manually load company to avoid tenant scope
$company = Company::find($call->company_id);

echo "Testing direct email send for call ID: {$call->id}\n";
echo "Company: " . ($company ? $company->name : 'N/A') . "\n";
echo "Created at: {$call->created_at}\n";
echo "Phone: {$call->phone_number}\n";
echo "Summary: " . ($call->summary ?: 'No summary available') . "\n\n";

// Test sending email directly
try {
    // Send email
    Mail::to('test@example.com')
        ->send(new CallSummaryEmail(
            $call,
            true,  // include transcript
            true,  // include CSV
            'This is a test of the call summary email system',
            'test'
        ));
    
    echo "Email sent successfully!\n";
    echo "Check mail log at: storage/logs/mail.log\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}