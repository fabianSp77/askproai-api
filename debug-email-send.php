<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Email Send Debug ===\n\n";

// 1. Check mail configuration
echo "1. Mail Configuration:\n";
echo "Mail Driver: " . config('mail.default') . "\n";
echo "Mail Host: " . config('mail.mailers.smtp.host') . "\n";
echo "Mail Port: " . config('mail.mailers.smtp.port') . "\n";
echo "Mail From: " . config('mail.from.address') . "\n";
echo "Mail Username: " . (config('mail.mailers.smtp.username') ? 'SET' : 'NOT SET') . "\n";
echo "Mail Password: " . (config('mail.mailers.smtp.password') ? 'SET' : 'NOT SET') . "\n\n";

// 2. Check if Resend driver is configured
echo "2. Resend Configuration:\n";
if (config('mail.default') === 'resend' || isset(config('mail.mailers')['resend'])) {
    echo "Resend API Key: " . (config('services.resend.key') ? 'SET (' . substr(config('services.resend.key'), 0, 10) . '...)' : 'NOT SET') . "\n";
    echo "Resend is " . (config('mail.default') === 'resend' ? 'DEFAULT' : 'AVAILABLE') . " driver\n";
} else {
    echo "Resend driver not configured\n";
}
echo "\n";

// 3. Test email template
echo "3. Testing Email Template:\n";
try {
    $call = \App\Models\Call::where('id', 316)->first();
    if (!$call) {
        $call = \App\Models\Call::first();
    }
    
    if ($call) {
        echo "Using call ID: {$call->id}\n";
        
        // Create mail instance
        $mail = new \App\Mail\CustomCallSummaryEmail(
            $call,
            'Test Email',
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
        
        // Try to render it
        $content = $mail->content();
        echo "✓ Email content prepared\n";
        
        // Check the view exists
        $viewPath = resource_path('views/' . str_replace('.', '/', $content->view) . '.blade.php');
        if (file_exists($viewPath)) {
            echo "✓ Email template exists: {$content->view}\n";
        } else {
            echo "✗ Email template missing: {$content->view}\n";
        }
        
        // Try to render
        try {
            $html = view($content->view, $content->with)->render();
            echo "✓ Email rendered successfully (length: " . strlen($html) . " bytes)\n";
        } catch (\Exception $e) {
            echo "✗ Email render error: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "No calls found in database\n";
    }
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n4. Testing SMTP Connection:\n";
if (config('mail.default') === 'smtp') {
    $host = config('mail.mailers.smtp.host');
    $port = config('mail.mailers.smtp.port');
    
    $fp = @fsockopen($host, $port, $errno, $errstr, 5);
    if ($fp) {
        echo "✓ SMTP connection successful to $host:$port\n";
        fclose($fp);
    } else {
        echo "✗ SMTP connection failed: $errstr ($errno)\n";
    }
} else {
    echo "Not using SMTP driver\n";
}

echo "\n5. Recent Email Jobs:\n";
$failedJobs = \DB::table('failed_jobs')
    ->where('payload', 'like', '%Mail%')
    ->orderBy('failed_at', 'desc')
    ->limit(5)
    ->get();

if ($failedJobs->count() > 0) {
    echo "Found {$failedJobs->count()} failed email jobs:\n";
    foreach ($failedJobs as $job) {
        echo "- Failed at: {$job->failed_at}\n";
        echo "  Exception: " . substr($job->exception, 0, 200) . "...\n\n";
    }
} else {
    echo "No failed email jobs found\n";
}