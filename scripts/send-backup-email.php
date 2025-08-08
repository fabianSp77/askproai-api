<?php
// Helper script to send backup emails via Laravel

require_once '/var/www/api-gateway/vendor/autoload.php';
$app = require_once '/var/www/api-gateway/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;

// Get arguments
$subject = $argv[1] ?? 'AskProAI Backup Notification';
$body = $argv[2] ?? 'No message body provided';
$recipient = $argv[3] ?? 'fabian@v2202503255565320322.happysrv.de';

try {
    Mail::raw($body, function ($message) use ($recipient, $subject) {
        $message->to($recipient)
                ->subject($subject);
    });
    echo "Email sent successfully\n";
    exit(0);
} catch (Exception $e) {
    echo "Failed to send email: " . $e->getMessage() . "\n";
    exit(1);
}