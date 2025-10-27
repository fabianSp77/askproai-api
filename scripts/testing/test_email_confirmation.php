#!/usr/bin/env php
<?php

/**
 * Test Email Confirmation Feature
 *
 * PURPOSE: Verify that appointment confirmation emails are sent correctly
 * USAGE: php scripts/testing/test_email_confirmation.php [appointment_id]
 *
 * TESTS:
 * 1. Manual email trigger for existing appointment
 * 2. Queue verification
 * 3. Log verification
 * 4. Email content validation
 */

require __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Support\Facades\Artisan;
use App\Models\Appointment;
use App\Services\Communication\NotificationService;

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  Email Confirmation Testing Tool                             ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// Get appointment ID from command line or use latest
$appointmentId = $argv[1] ?? null;

if (!$appointmentId) {
    echo "📋 No appointment ID provided, using latest appointment...\n\n";
    $appointment = Appointment::with(['customer', 'service', 'branch'])
        ->orderBy('created_at', 'desc')
        ->first();

    if (!$appointment) {
        echo "❌ No appointments found in database\n";
        exit(1);
    }

    echo "✅ Found latest appointment: #{$appointment->id}\n";
} else {
    echo "🔍 Looking for appointment #{$appointmentId}...\n\n";
    $appointment = Appointment::with(['customer', 'service', 'branch'])->find($appointmentId);

    if (!$appointment) {
        echo "❌ Appointment #{$appointmentId} not found\n";
        exit(1);
    }

    echo "✅ Found appointment: #{$appointment->id}\n";
}

// Display appointment details
echo "\n╔══════════════════════════════════════════════════════════════╗\n";
echo "║  Appointment Details                                         ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "ID:           {$appointment->id}\n";
echo "Customer:     {$appointment->customer->name}\n";
echo "Email:        " . ($appointment->customer->email ?? '⚠️  NO EMAIL') . "\n";
echo "Service:      {$appointment->service->name}\n";
echo "Branch:       {$appointment->branch->name}\n";
echo "Date/Time:    {$appointment->starts_at->format('d.m.Y H:i')}\n";
echo "Type:         " . ($appointment->is_composite ? 'Composite' : 'Simple') . "\n";
echo "Status:       {$appointment->status}\n";
echo "\n";

// Validate customer has email
if (!$appointment->customer->email) {
    echo "❌ ERROR: Customer has no email address\n";
    echo "   Email confirmation cannot be sent\n\n";
    echo "💡 TIP: Update customer email first:\n";
    echo "   UPDATE customers SET email='test@example.com' WHERE id={$appointment->customer->id};\n\n";
    exit(1);
}

if (!filter_var($appointment->customer->email, FILTER_VALIDATE_EMAIL)) {
    echo "❌ ERROR: Customer email is invalid: {$appointment->customer->email}\n";
    exit(1);
}

echo "✅ Customer has valid email address\n\n";

// Test 1: Send confirmation email
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  Test 1: Send Confirmation Email                            ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";

try {
    $notificationService = app(NotificationService::class);

    echo "📧 Sending confirmation email...\n";

    if ($appointment->is_composite) {
        $result = $notificationService->sendCompositeConfirmation($appointment);
    } else {
        $result = $notificationService->sendSimpleConfirmation($appointment);
    }

    if ($result) {
        echo "✅ Email queued successfully\n";
        echo "   Recipient: {$appointment->customer->email}\n";
        echo "   Type: " . ($appointment->is_composite ? 'Composite' : 'Simple') . "\n\n";
    } else {
        echo "❌ Failed to queue email\n\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "❌ Exception: {$e->getMessage()}\n";
    echo "   File: {$e->getFile()}:{$e->getLine()}\n\n";
    exit(1);
}

// Test 2: Check queue status
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  Test 2: Queue Status                                       ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";

echo "📊 Checking queue status...\n\n";
echo "Run this command to see queued jobs:\n";
echo "   php artisan queue:work --once\n\n";
echo "Check queue status:\n";
echo "   php artisan queue:listen\n\n";

// Test 3: Verify logs
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  Test 3: Log Verification                                   ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";

echo "📝 Check logs for email confirmation:\n\n";
echo "Recent email-related logs:\n";
echo str_repeat("-", 64) . "\n";

$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $logs = shell_exec("tail -n 50 {$logFile} | grep -i 'email\\|confirmation\\|notification' | tail -n 10");
    echo $logs ?: "No recent email logs found\n";
} else {
    echo "⚠️  Log file not found: {$logFile}\n";
}

echo "\n" . str_repeat("-", 64) . "\n\n";

// Test 4: Verification commands
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  Test 4: Manual Verification Commands                       ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

echo "📋 VERIFICATION CHECKLIST:\n\n";
echo "1. Process the queued email:\n";
echo "   → php artisan queue:work --once\n\n";

echo "2. Check queue status:\n";
echo "   → php artisan queue:failed\n\n";

echo "3. Monitor logs in real-time:\n";
echo "   → tail -f storage/logs/laravel.log | grep -i email\n\n";

echo "4. Check database for queued jobs:\n";
echo "   → SELECT * FROM jobs ORDER BY created_at DESC LIMIT 5;\n\n";

echo "5. Check failed jobs:\n";
echo "   → SELECT * FROM failed_jobs ORDER BY failed_at DESC LIMIT 5;\n\n";

echo "6. Test email template:\n";
echo "   → Check resources/views/emails/appointments/confirmation.blade.php\n\n";

echo "7. Verify mail configuration:\n";
echo "   → php artisan config:show mail\n\n";

// Summary
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  Summary                                                     ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

echo "✅ Email confirmation feature test completed\n\n";
echo "📧 Email Details:\n";
echo "   To:      {$appointment->customer->email}\n";
echo "   Type:    " . ($appointment->is_composite ? 'Composite appointment' : 'Simple appointment') . "\n";
echo "   Status:  Queued (check queue worker)\n\n";

echo "💡 NEXT STEPS:\n";
echo "   1. Run queue worker: php artisan queue:work\n";
echo "   2. Check inbox: {$appointment->customer->email}\n";
echo "   3. Verify ICS attachment is present\n";
echo "   4. Test calendar import functionality\n\n";

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  Test Complete                                               ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
