<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Log;

echo "=== TESTING ERROR LOGGING ===\n\n";

// Test what log level is set
echo "1. Current log configuration:\n";
echo "Default channel: " . config('logging.default') . "\n";
echo "Daily log level: " . config('logging.channels.daily.level') . "\n";
echo "ENV LOG_LEVEL: " . env('LOG_LEVEL', 'not set') . "\n";

// Test different log levels
echo "\n2. Testing log levels:\n";

Log::error('[TEST] This is an ERROR log');
echo "Logged ERROR\n";

Log::warning('[TEST] This is a WARNING log');
echo "Logged WARNING\n";

Log::info('[TEST] This is an INFO log');
echo "Logged INFO\n";

Log::debug('[TEST] This is a DEBUG log');
echo "Logged DEBUG\n";

// Check logs
echo "\n3. Checking logs:\n";
$logs = shell_exec("tail -10 /var/www/api-gateway/storage/logs/laravel-*.log | grep TEST");
echo $logs ?: "No TEST logs found\n";

// Now test with Mail channel
echo "\n4. Testing mail channel:\n";
Log::channel('mail')->error('[MAIL TEST] This is a mail error');
echo "Logged to mail channel\n";

$mailLogs = shell_exec("tail -5 /var/www/api-gateway/storage/logs/mail-*.log 2>/dev/null");
echo $mailLogs ?: "No mail logs found\n";