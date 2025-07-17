<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;

// Get the first company
$company = Company::first();

if (!$company) {
    echo "No company found.\n";
    exit(1);
}

echo "Updating notification settings for: {$company->name}\n\n";

// Update notification settings
$company->send_call_summaries = true;
$company->call_summary_recipients = ['test@example.com', 'admin@askproai.de'];
$company->include_transcript_in_summary = true;
$company->include_csv_export = true;
$company->summary_email_frequency = 'immediate';
$company->save();

echo "Settings updated:\n";
echo "- Send Call Summaries: " . ($company->send_call_summaries ? 'Yes' : 'No') . "\n";
echo "- Recipients: " . json_encode($company->call_summary_recipients) . "\n";
echo "- Include Transcript: " . ($company->include_transcript_in_summary ? 'Yes' : 'No') . "\n";
echo "- Include CSV: " . ($company->include_csv_export ? 'Yes' : 'No') . "\n";
echo "- Frequency: {$company->summary_email_frequency}\n\n";

echo "✅ Notification settings configured successfully!\n";