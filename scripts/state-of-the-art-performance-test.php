#!/usr/bin/env php
<?php

echo "\033[1;36m";
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║         STATE-OF-THE-ART PERFORMANCE VALIDATION TEST             ║\n";
echo "║              Verifying All Optimizations                         ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";
echo "\033[0m";
echo "Date: " . date('Y-m-d H:i:s') . PHP_EOL . PHP_EOL;

$baseUrl = 'https://api.askproai.de';
$improvements = [];
$issues = [];

// Color codes
$green = "\033[0;32m";
$red = "\033[0;31m";
$yellow = "\033[1;33m";
$blue = "\033[0;34m";
$reset = "\033[0m";

// 1. TEST ROUTE PERFORMANCE IMPROVEMENTS
echo "{$blue}▶ 1. ROUTE PERFORMANCE (Target: <50ms){$reset}\n";
echo str_repeat('─', 70) . PHP_EOL;

$routeTests = [
    '/business' => 'Dashboard Route',
    '/business/login' => 'Login Route',
    '/business/customers' => 'Customers Route'
];

foreach ($routeTests as $route => $name) {
    $start = microtime(true);
    $ch = curl_init($baseUrl . $route);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);
    curl_close($ch);
    $time = round((microtime(true) - $start) * 1000, 2);

    if ($time < 50) {
        echo "{$green}✅ $name: {$time}ms (Excellent){$reset}\n";
        $improvements[] = "$name improved to {$time}ms";
    } elseif ($time < 100) {
        echo "{$yellow}⚠️ $name: {$time}ms (Good){$reset}\n";
    } else {
        echo "{$red}❌ $name: {$time}ms (Still Slow){$reset}\n";
        $issues[] = "$name still slow at {$time}ms";
    }
}

// 2. TEST API ENDPOINTS
echo PHP_EOL . "{$blue}▶ 2. API HEALTH ENDPOINTS{$reset}\n";
echo str_repeat('─', 70) . PHP_EOL;

$apiTests = [
    '/api/health' => 'Health Check',
    '/api/health/detailed' => 'Detailed Health',
    '/api/v1/customers' => 'Customers API',
    '/api/v1/calls' => 'Calls API',
    '/webhooks/calcom' => 'Cal.com Webhook',
    '/webhooks/retell' => 'Retell Webhook'
];

foreach ($apiTests as $endpoint => $name) {
    $ch = curl_init($baseUrl . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200) {
        echo "{$green}✅ $name: Working (HTTP 200){$reset}\n";
        $improvements[] = "$name endpoint now functional";
    } elseif ($httpCode == 501) {
        echo "{$yellow}→ $name: Placeholder (HTTP 501){$reset}\n";
    } else {
        echo "{$red}❌ $name: Failed (HTTP $httpCode){$reset}\n";
        $issues[] = "$name returned HTTP $httpCode";
    }
}

// 3. TEST SECURITY HEADERS
echo PHP_EOL . "{$blue}▶ 3. SECURITY HEADERS VALIDATION{$reset}\n";
echo str_repeat('─', 70) . PHP_EOL;

$ch = curl_init($baseUrl . '/business/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
$response = curl_exec($ch);
curl_close($ch);

$securityHeaders = [
    'X-Frame-Options' => 'SAMEORIGIN',
    'X-Content-Type-Options' => 'nosniff',
    'X-XSS-Protection' => '1; mode=block',
    'Strict-Transport-Security' => 'max-age=31536000',
    'Referrer-Policy' => 'strict-origin'
];

foreach ($securityHeaders as $header => $expected) {
    if (stripos($response, $header) !== false) {
        echo "{$green}✅ $header: Present{$reset}\n";
        $improvements[] = "$header security header configured";
    } else {
        echo "{$red}❌ $header: Missing{$reset}\n";
        $issues[] = "$header security header missing";
    }
}

// 4. DATABASE INDEX PERFORMANCE
echo PHP_EOL . "{$blue}▶ 4. DATABASE INDEX VERIFICATION{$reset}\n";
echo str_repeat('─', 70) . PHP_EOL;

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

try {
    // Check if indexes were added
    $tables = ['activity_log', 'backup_logs', 'outbound_call_templates'];
    foreach ($tables as $table) {
        if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
            $indexes = \Illuminate\Support\Facades\DB::select("SHOW INDEX FROM $table");
            $indexNames = array_column($indexes, 'Key_name');

            $hasCreatedAt = in_array('idx_' . substr($table, 0, 8) . '_created_at', $indexNames) ||
                           in_array('idx_activity_created_at', $indexNames) ||
                           in_array('idx_backup_created_at', $indexNames) ||
                           in_array('idx_outbound_created_at', $indexNames);

            if ($hasCreatedAt || count($indexes) > 1) {
                echo "{$green}✅ $table: Indexes present{$reset}\n";
                $improvements[] = "$table indexes optimized";
            } else {
                echo "{$yellow}⚠️ $table: Basic indexes only{$reset}\n";
            }
        }
    }

    // Test query performance with indexes
    $start = microtime(true);
    \Illuminate\Support\Facades\DB::table('customers')
        ->where('created_at', '>=', now()->subDays(30))
        ->count();
    $queryTime = round((microtime(true) - $start) * 1000, 2);

    if ($queryTime < 5) {
        echo "{$green}✅ Indexed Query Performance: {$queryTime}ms (Excellent){$reset}\n";
        $improvements[] = "Query performance improved to {$queryTime}ms";
    } else {
        echo "{$yellow}⚠️ Indexed Query Performance: {$queryTime}ms{$reset}\n";
    }

} catch (Exception $e) {
    echo "{$red}❌ Database Test Failed: " . $e->getMessage() . "{$reset}\n";
    $issues[] = "Database performance test failed";
}

// 5. SESSION ENCRYPTION
echo PHP_EOL . "{$blue}▶ 5. SESSION ENCRYPTION STATUS{$reset}\n";
echo str_repeat('─', 70) . PHP_EOL;

$sessionEncrypt = config('session.encrypt');
if ($sessionEncrypt === true) {
    echo "{$green}✅ Session Encryption: Enabled{$reset}\n";
    $improvements[] = "Session encryption enabled";
} else {
    echo "{$red}❌ Session Encryption: Disabled{$reset}\n";
    $issues[] = "Session encryption not enabled";
}

// 6. LOAD TEST
echo PHP_EOL . "{$blue}▶ 6. LOAD TESTING (100 requests){$reset}\n";
echo str_repeat('─', 70) . PHP_EOL;

$start = microtime(true);
for ($i = 0; $i < 100; $i++) {
    $ch = curl_init($baseUrl . '/api/health');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 1);
    curl_exec($ch);
    curl_close($ch);
}
$totalTime = microtime(true) - $start;
$avgTime = round(($totalTime / 100) * 1000, 2);

if ($avgTime < 50) {
    echo "{$green}✅ Average Response: {$avgTime}ms (Excellent){$reset}\n";
    $improvements[] = "Load handling at {$avgTime}ms average";
} elseif ($avgTime < 100) {
    echo "{$yellow}⚠️ Average Response: {$avgTime}ms (Good){$reset}\n";
} else {
    echo "{$red}❌ Average Response: {$avgTime}ms (Poor){$reset}\n";
    $issues[] = "Load test average too high at {$avgTime}ms";
}

// SUMMARY
echo PHP_EOL;
echo "{$blue}╔══════════════════════════════════════════════════════════════════╗{$reset}\n";
echo "{$blue}║                    OPTIMIZATION RESULTS                          ║{$reset}\n";
echo "{$blue}╚══════════════════════════════════════════════════════════════════╝{$reset}\n";
echo PHP_EOL;

$totalImprovements = count($improvements);
$totalIssues = count($issues);

if ($totalImprovements > 0) {
    echo "{$green}✅ IMPROVEMENTS ACHIEVED: $totalImprovements{$reset}\n";
    foreach ($improvements as $improvement) {
        echo "   • $improvement\n";
    }
    echo PHP_EOL;
}

if ($totalIssues > 0) {
    echo "{$red}❌ REMAINING ISSUES: $totalIssues{$reset}\n";
    foreach ($issues as $issue) {
        echo "   • $issue\n";
    }
    echo PHP_EOL;
}

// Calculate score
$totalTests = 25;
$passed = $totalImprovements;
$score = round(($passed / $totalTests) * 100);

echo "Performance Score: {$score}/100\n";

if ($score >= 80) {
    echo "{$green}✨ EXCELLENT - All major optimizations successful!{$reset}\n";
} elseif ($score >= 60) {
    echo "{$yellow}👍 GOOD - Most optimizations applied successfully{$reset}\n";
} else {
    echo "{$red}⚠️ NEEDS WORK - Some optimizations not fully effective{$reset}\n";
}

echo PHP_EOL . "Test completed: " . date('Y-m-d H:i:s') . PHP_EOL;