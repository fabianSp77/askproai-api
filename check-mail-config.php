<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Mail Configuration Check ===\n\n";

// Check mail configuration
echo "Mail Driver: " . config('mail.default') . "\n";
echo "Mail Host: " . config('mail.mailers.smtp.host') . "\n";
echo "Mail Port: " . config('mail.mailers.smtp.port') . "\n";
echo "Mail From Address: " . config('mail.from.address') . "\n";
echo "Mail From Name: " . config('mail.from.name') . "\n";

// Check if mail credentials are set
$username = config('mail.mailers.smtp.username');
$password = config('mail.mailers.smtp.password');
echo "Mail Username: " . ($username ? 'SET (' . substr($username, 0, 3) . '***)' : 'NOT SET') . "\n";
echo "Mail Password: " . ($password ? 'SET' : 'NOT SET') . "\n";

// Check queue configuration
echo "\n=== Queue Configuration ===\n";
echo "Queue Driver: " . config('queue.default') . "\n";

// Check if CustomCallSummaryEmail exists
echo "\n=== Mail Classes ===\n";
$mailClass = 'App\Mail\CustomCallSummaryEmail';
if (class_exists($mailClass)) {
    echo "✓ CustomCallSummaryEmail class exists\n";
} else {
    echo "✗ CustomCallSummaryEmail class NOT FOUND\n";
}

// Test sending a simple email
echo "\n=== Test Email Send ===\n";
try {
    // Get a test call
    $call = \App\Models\Call::first();
    if ($call) {
        echo "Found test call: ID {$call->id}\n";
        
        // Try to create the mail object
        $mail = new \App\Mail\CustomCallSummaryEmail(
            $call,
            'Test Subject',
            '<p>Test Message</p>',
            [
                'summary' => true,
                'transcript' => false,
                'customerInfo' => true,
                'appointmentInfo' => true,
                'attachCSV' => false,
                'attachRecording' => false
            ]
        );
        echo "✓ Mail object created successfully\n";
        
        // Check if we can render it
        try {
            $rendered = $mail->render();
            echo "✓ Mail can be rendered (length: " . strlen($rendered) . " bytes)\n";
        } catch (\Exception $e) {
            echo "✗ Mail rendering failed: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "No calls found in database\n";
    }
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

// Check mail log
echo "\n=== Recent Mail Logs ===\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $lines = file($logFile);
    $mailLines = array_filter($lines, function($line) {
        return stripos($line, 'mail') !== false || stripos($line, 'email') !== false;
    });
    $recentLines = array_slice($mailLines, -5);
    foreach ($recentLines as $line) {
        echo trim($line) . "\n";
    }
}