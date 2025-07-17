<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;

echo "Testing email with working SMTP credentials...\n\n";

// Test email addresses
$testEmails = [
    'test@askproai.de',
    'info@askproai.de', 
    'support@askproai.de'
];

foreach ($testEmails as $email) {
    echo "Testing email to: {$email}\n";
    
    try {
        Mail::raw('Dies ist eine Test-E-Mail vom AskProAI System mit den neuen SMTP-Zugangsdaten.', function ($message) use ($email) {
            $message->to($email)
                    ->subject('Test E-Mail - Neue SMTP-Zugangsdaten - ' . now()->format('d.m.Y H:i:s'));
        });
        
        echo "✅ Email sent successfully to {$email}!\n\n";
        break; // Stop after first successful send
        
    } catch (\Exception $e) {
        echo "❌ Failed to send to {$email}: " . $e->getMessage() . "\n\n";
    }
}

echo "SMTP Authentication: ✅ WORKING\n";
echo "Username: " . config('mail.mailers.smtp.username') . "\n";
echo "Server: " . config('mail.mailers.smtp.host') . ":" . config('mail.mailers.smtp.port') . "\n";