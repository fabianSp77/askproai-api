<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\PortalUser;
use App\Models\Branch;

echo "=== SETUP EMERGENCY NOTIFICATION SYSTEM ===\n\n";

// Get the demo user's company (Krückeberg Servicegruppe)
$company = Company::where('id', 1)->first(); // Krückeberg Servicegruppe

if (!$company) {
    echo "ERROR: Company not found!\n";
    exit(1);
}

echo "Setting up emergency notifications for: {$company->name}\n\n";

// 1. Configure Company-wide Call Notification Settings
echo "1. Configuring company notification settings...\n";

$company->send_call_summaries = true;
$company->call_summary_recipients = [
    'notfall@krueckeberg-service.de',  // Emergency/main contact
    'geschaeftsfuehrung@krueckeberg-service.de',  // Management
    'bereitschaft@krueckeberg-service.de'  // On-call service
];
$company->include_transcript_in_summary = true;  // Include full transcript for emergency analysis
$company->include_csv_export = true;  // Include CSV for data import
$company->summary_email_frequency = 'immediate';  // Send immediately for emergencies
$company->save();

echo "   ✓ Enabled call summaries\n";
echo "   ✓ Added emergency recipients\n";
echo "   ✓ Set to immediate delivery\n";
echo "   ✓ Enabled transcript and CSV attachments\n\n";

// 2. Configure Branch-specific settings (if branches exist)
echo "2. Configuring branch settings...\n";

// Set company context for tenant scope
app()->singleton('current_company', function () use ($company) {
    return $company;
});

$branches = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('company_id', $company->id)
    ->get();
foreach ($branches as $branch) {
    // Set branch notification email for local emergencies
    $branch->notification_email = 'filiale-' . $branch->id . '@krueckeberg-service.de';
    
    // Enable call forwarding for emergency hours
    $branch->call_notification_overrides = [
        'include_transcript' => true,
        'include_csv' => true,
        'priority_keywords' => ['notfall', 'dringend', 'sofort', 'emergency', 'urgent'],
        'escalation_enabled' => true
    ];
    $branch->save();
    
    echo "   ✓ Branch {$branch->name}: {$branch->notification_email}\n";
}

if ($branches->isEmpty()) {
    echo "   ℹ No branches found\n";
}
echo "\n";

// 3. Configure user preferences for demo account
echo "3. Configuring user preferences...\n";

$demoUser = PortalUser::where('email', 'demo@askproai.de')->first();
if ($demoUser) {
    $demoUser->call_notification_preferences = [
        'receive_summaries' => true,
        'emergency_alerts' => true,
        'priority_notifications' => true,
        'quiet_hours' => false,  // Disable quiet hours for emergencies
        'notification_channels' => ['email', 'dashboard']
    ];
    $demoUser->save();
    echo "   ✓ Demo user preferences updated\n";
}

// 4. Create emergency notification template settings
echo "\n4. Setting up emergency detection rules...\n";

// Store emergency keywords and rules in company metadata
$metadata = $company->metadata ?? [];
$metadata['emergency_notification_rules'] = [
    'keywords' => [
        'german' => ['notfall', 'dringend', 'sofort', 'notarzt', 'unfall', 'kritisch'],
        'english' => ['emergency', 'urgent', 'immediate', 'critical', 'accident', 'help']
    ],
    'auto_escalate' => true,
    'escalation_delay_minutes' => 5,
    'escalation_recipients' => [
        'ceo@krueckeberg-service.de',
        'emergency-team@krueckeberg-service.de'
    ],
    'sms_notification_enabled' => false,  // Can be enabled with Twilio
    'priority_email_subject' => '🚨 [NOTFALL] Neuer Anruf erfordert sofortige Aufmerksamkeit',
    'include_audio_link' => true
];
$company->metadata = $metadata;
$company->save();

echo "   ✓ Emergency keywords configured\n";
echo "   ✓ Auto-escalation enabled (5 min delay)\n";
echo "   ✓ Priority email template set\n";

// 5. Display summary
echo "\n=== CONFIGURATION SUMMARY ===\n\n";
echo "Company: {$company->name}\n";
echo "Primary Recipients:\n";
foreach ($company->call_summary_recipients as $recipient) {
    echo "  - $recipient\n";
}
echo "\nEmergency Keywords:\n";
echo "  German: " . implode(', ', $metadata['emergency_notification_rules']['keywords']['german']) . "\n";
echo "  English: " . implode(', ', $metadata['emergency_notification_rules']['keywords']['english']) . "\n";
echo "\nSettings:\n";
echo "  - Delivery: Immediate\n";
echo "  - Transcript: Included\n";
echo "  - CSV Export: Included\n";
echo "  - Auto-Escalation: Enabled (5 min)\n";
echo "  - Priority Subject: 🚨 [NOTFALL] prefix\n";

echo "\n✅ Emergency notification system configured successfully!\n";
echo "\nTo test:\n";
echo "1. Login as demo@askproai.de\n";
echo "2. Go to Settings > Call Notifications\n";
echo "3. Verify the recipients are listed\n";
echo "4. Make a test call mentioning 'Notfall' to trigger priority handling\n";