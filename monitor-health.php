#!/usr/bin/php
<?php
/**
 * AskProAI Health Monitor
 * Quick monitoring script to check system health
 */

$checks = [];
$hasErrors = false;

// Check 1: API Health Endpoint
echo "Checking API Health... ";
$healthResponse = @file_get_contents('https://api.askproai.de/api/health');
if ($healthResponse) {
    $health = json_decode($healthResponse, true);
    if ($health['status'] === 'healthy') {
        echo "\033[32m✓ OK\033[0m\n";
        $checks['api'] = true;
    } else {
        echo "\033[31m✗ UNHEALTHY\033[0m\n";
        $checks['api'] = false;
        $hasErrors = true;
    }
} else {
    echo "\033[31m✗ UNREACHABLE\033[0m\n";
    $checks['api'] = false;
    $hasErrors = true;
}

// Check 2: Database Connection
echo "Checking Database... ";
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=askproai_db', 'askproai_user', 'lkZ57Dju9EDjrMxn');
    $stmt = $pdo->query("SELECT COUNT(*) FROM companies");
    $count = $stmt->fetchColumn();
    echo "\033[32m✓ OK ($count companies)\033[0m\n";
    $checks['database'] = true;
} catch (Exception $e) {
    echo "\033[31m✗ ERROR: " . $e->getMessage() . "\033[0m\n";
    $checks['database'] = false;
    $hasErrors = true;
}

// Check 3: Redis
echo "Checking Redis... ";
try {
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);
    $redis->ping();
    echo "\033[32m✓ OK\033[0m\n";
    $checks['redis'] = true;
} catch (Exception $e) {
    echo "\033[31m✗ ERROR\033[0m\n";
    $checks['redis'] = false;
    $hasErrors = true;
}

// Check 4: Disk Space
echo "Checking Disk Space... ";
$diskFree = disk_free_space('/');
$diskTotal = disk_total_space('/');
$diskUsedPercent = round((($diskTotal - $diskFree) / $diskTotal) * 100, 2);
if ($diskUsedPercent < 80) {
    echo "\033[32m✓ OK ({$diskUsedPercent}% used)\033[0m\n";
    $checks['disk'] = true;
} else {
    echo "\033[31m✗ WARNING ({$diskUsedPercent}% used)\033[0m\n";
    $checks['disk'] = false;
    $hasErrors = true;
}

// Check 5: Recent Errors in Laravel Log
echo "Checking Laravel Logs... ";
$logFile = '/var/www/api-gateway/storage/logs/laravel.log';
if (file_exists($logFile)) {
    $lastLines = `tail -100 $logFile | grep -c "ERROR"`;
    $errorCount = intval(trim($lastLines));
    if ($errorCount < 10) {
        echo "\033[32m✓ OK ($errorCount errors in last 100 lines)\033[0m\n";
        $checks['logs'] = true;
    } else {
        echo "\033[31m✗ WARNING ($errorCount errors in last 100 lines)\033[0m\n";
        $checks['logs'] = false;
        $hasErrors = true;
    }
} else {
    echo "\033[33m? LOG FILE NOT FOUND\033[0m\n";
    $checks['logs'] = null;
}

// Check 6: Queue Workers
echo "Checking Queue Workers... ";
$horizonStatus = `cd /var/www/api-gateway && php artisan horizon:status 2>&1`;
if (strpos($horizonStatus, 'Horizon is running') !== false) {
    echo "\033[32m✓ OK (Horizon running)\033[0m\n";
    $checks['queue'] = true;
} else {
    echo "\033[31m✗ NOT RUNNING\033[0m\n";
    $checks['queue'] = false;
    $hasErrors = true;
}

// Check 7: SSL Certificate
echo "Checking SSL Certificate... ";
$certInfo = `echo | openssl s_client -servername api.askproai.de -connect api.askproai.de:443 2>/dev/null | openssl x509 -noout -dates 2>/dev/null`;
if (strpos($certInfo, 'notAfter') !== false) {
    preg_match('/notAfter=(.+)/', $certInfo, $matches);
    $expiryDate = strtotime($matches[1]);
    $daysUntilExpiry = round(($expiryDate - time()) / 86400);
    if ($daysUntilExpiry > 7) {
        echo "\033[32m✓ OK (expires in $daysUntilExpiry days)\033[0m\n";
        $checks['ssl'] = true;
    } else {
        echo "\033[31m✗ EXPIRING SOON ($daysUntilExpiry days)\033[0m\n";
        $checks['ssl'] = false;
        $hasErrors = true;
    }
} else {
    echo "\033[31m✗ UNABLE TO CHECK\033[0m\n";
    $checks['ssl'] = null;
}

// Summary
echo "\n";
echo "=====================================\n";
echo "SUMMARY: ";
if (!$hasErrors) {
    echo "\033[32mALL SYSTEMS OPERATIONAL\033[0m\n";
} else {
    echo "\033[31mISSUES DETECTED\033[0m\n";
}
echo "=====================================\n";

// Exit with appropriate code
exit($hasErrors ? 1 : 0);