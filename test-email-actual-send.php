<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Testing Actual Email Send ===\n\n";

try {
    $call = \App\Models\Call::where('id', 316)->first();
    if (!$call) {
        $call = \App\Models\Call::first();
    }
    
    if ($call) {
        echo "Using call ID: {$call->id}\n";
        
        // Create and send email
        $mail = new \App\Mail\CustomCallSummaryEmail(
            $call,
            'Test Email - Call Summary',
            '<p>This is a test email to verify the email sending functionality.</p>',
            [
                'summary' => true,
                'transcript' => false,
                'customerInfo' => true,
                'appointmentInfo' => true,
                'attachCSV' => false,
                'attachRecording' => false
            ]
        );
        
        // Send to test email
        $testEmail = 'test@example.com'; // Change this to a real email
        echo "Sending to: $testEmail\n";
        
        try {
            \Mail::to($testEmail)->send($mail);
            echo "✓ Email sent successfully!\n";
        } catch (\Exception $e) {
            echo "✗ Email send failed: " . $e->getMessage() . "\n";
            echo "Error type: " . get_class($e) . "\n";
            if (method_exists($e, 'getResponse')) {
                $response = $e->getResponse();
                if ($response) {
                    echo "Response body: " . $response->getBody() . "\n";
                }
            }
        }
        
    } else {
        echo "No calls found in database\n";
    }
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}