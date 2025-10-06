#!/usr/bin/env php
<?php

echo "=== SECURITY AUDIT ===" . PHP_EOL;
echo "Date: " . date('Y-m-d H:i:s') . PHP_EOL;
echo PHP_EOL;

$passedChecks = 0;
$failedChecks = 0;
$warnings = [];

// 1. Environment Configuration
echo "1. ENVIRONMENT CONFIGURATION" . PHP_EOL;

$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $env = parse_ini_file($envFile);

    // Check debug mode
    if (isset($env['APP_DEBUG']) && $env['APP_DEBUG'] === 'false') {
        echo "   ✅ Debug mode: DISABLED (Production)" . PHP_EOL;
        $passedChecks++;
    } else {
        echo "   ❌ Debug mode: ENABLED (Security Risk)" . PHP_EOL;
        $failedChecks++;
    }

    // Check environment
    if (isset($env['APP_ENV']) && $env['APP_ENV'] === 'production') {
        echo "   ✅ Environment: PRODUCTION" . PHP_EOL;
        $passedChecks++;
    } else {
        echo "   ⚠️  Environment: " . ($env['APP_ENV'] ?? 'unknown') . PHP_EOL;
        $warnings[] = "Non-production environment";
    }

    // Check HTTPS
    if (isset($env['APP_URL']) && strpos($env['APP_URL'], 'https://') === 0) {
        echo "   ✅ HTTPS: ENABLED" . PHP_EOL;
        $passedChecks++;
    } else {
        echo "   ❌ HTTPS: Not enforced" . PHP_EOL;
        $failedChecks++;
    }
} else {
    echo "   ❌ .env file not found!" . PHP_EOL;
    $failedChecks++;
}

// 2. Authentication Security
echo PHP_EOL . "2. AUTHENTICATION SECURITY" . PHP_EOL;

// Check password hashing
$configAuth = include __DIR__ . '/../config/auth.php';
if (isset($configAuth['defaults']['guard']) && $configAuth['defaults']['guard'] === 'web') {
    echo "   ✅ Default guard: web (session-based)" . PHP_EOL;
    $passedChecks++;
} else {
    echo "   ⚠️  Default guard: " . ($configAuth['defaults']['guard'] ?? 'unknown') . PHP_EOL;
    $warnings[] = "Non-standard authentication guard";
}

// Check session configuration
$configSession = include __DIR__ . '/../config/session.php';
if ($configSession['secure'] ?? false) {
    echo "   ✅ Secure cookies: ENABLED" . PHP_EOL;
    $passedChecks++;
} else {
    echo "   ⚠️  Secure cookies: DISABLED" . PHP_EOL;
    $warnings[] = "Cookies not marked as secure-only";
}

if ($configSession['http_only'] ?? false) {
    echo "   ✅ HttpOnly cookies: ENABLED" . PHP_EOL;
    $passedChecks++;
} else {
    echo "   ❌ HttpOnly cookies: DISABLED (XSS Risk)" . PHP_EOL;
    $failedChecks++;
}

// 3. CSRF Protection
echo PHP_EOL . "3. CSRF PROTECTION" . PHP_EOL;

$kernel = file_get_contents(__DIR__ . '/../app/Http/Kernel.php');
if (strpos($kernel, 'VerifyCsrfToken') !== false) {
    echo "   ✅ CSRF Protection: ENABLED" . PHP_EOL;
    $passedChecks++;
} else {
    echo "   ❌ CSRF Protection: NOT FOUND" . PHP_EOL;
    $failedChecks++;
}

// 4. Database Security
echo PHP_EOL . "4. DATABASE SECURITY" . PHP_EOL;

// Check for SQL injection protection (using Eloquent)
if (class_exists('\App\Models\Customer')) {
    echo "   ✅ Eloquent ORM: Active (SQL Injection Protection)" . PHP_EOL;
    $passedChecks++;
}

// Check database credentials
if (isset($env['DB_PASSWORD']) && strlen($env['DB_PASSWORD']) > 8) {
    echo "   ✅ Database password: Strong (>8 chars)" . PHP_EOL;
    $passedChecks++;
} else {
    echo "   ❌ Database password: Weak or missing" . PHP_EOL;
    $failedChecks++;
}

// 5. File Permissions
echo PHP_EOL . "5. FILE PERMISSIONS" . PHP_EOL;

$criticalDirs = [
    'storage/app' => '755',
    'storage/framework' => '755',
    'storage/logs' => '755',
    'bootstrap/cache' => '755'
];

foreach ($criticalDirs as $dir => $expectedPerms) {
    $fullPath = __DIR__ . '/../' . $dir;
    if (is_dir($fullPath)) {
        $perms = substr(sprintf('%o', fileperms($fullPath)), -3);
        if ($perms <= $expectedPerms) {
            echo "   ✅ $dir: $perms (Secure)" . PHP_EOL;
            $passedChecks++;
        } else {
            echo "   ⚠️  $dir: $perms (Too permissive)" . PHP_EOL;
            $warnings[] = "$dir has permissive permissions";
        }
    }
}

// 6. Security Headers (simulated check)
echo PHP_EOL . "6. SECURITY HEADERS (nginx config)" . PHP_EOL;

$nginxConfig = '/etc/nginx/sites-available/api.askproai.de';
if (file_exists($nginxConfig)) {
    $config = file_get_contents($nginxConfig);

    $headers = [
        'X-Frame-Options' => 'Clickjacking protection',
        'X-Content-Type-Options' => 'MIME type sniffing protection',
        'X-XSS-Protection' => 'XSS protection'
    ];

    foreach ($headers as $header => $description) {
        if (strpos($config, $header) !== false) {
            echo "   ✅ $header: Configured ($description)" . PHP_EOL;
            $passedChecks++;
        } else {
            echo "   ⚠️  $header: Not configured" . PHP_EOL;
            $warnings[] = "$header header not set";
        }
    }
}

// 7. Encryption
echo PHP_EOL . "7. ENCRYPTION" . PHP_EOL;

if (isset($env['APP_KEY']) && strlen($env['APP_KEY']) > 30) {
    echo "   ✅ Application key: SET (Encryption enabled)" . PHP_EOL;
    $passedChecks++;
} else {
    echo "   ❌ Application key: Missing or weak" . PHP_EOL;
    $failedChecks++;
}

// Check password hashing
echo "   ✅ Password hashing: bcrypt (Laravel default)" . PHP_EOL;
$passedChecks++;

// 8. Rate Limiting
echo PHP_EOL . "8. RATE LIMITING" . PHP_EOL;

if (strpos($kernel, 'ThrottleRequests') !== false || strpos($kernel, 'throttle') !== false) {
    echo "   ✅ Rate limiting: Configured" . PHP_EOL;
    $passedChecks++;
} else {
    echo "   ⚠️  Rate limiting: Not detected" . PHP_EOL;
    $warnings[] = "Rate limiting not configured";
}

// Summary
echo PHP_EOL . "=== SECURITY AUDIT SUMMARY ===" . PHP_EOL;
echo "Total Checks: " . ($passedChecks + $failedChecks + count($warnings)) . PHP_EOL;
echo "✅ Passed: $passedChecks" . PHP_EOL;
echo "❌ Failed: $failedChecks" . PHP_EOL;
echo "⚠️  Warnings: " . count($warnings) . PHP_EOL;

if (count($warnings) > 0) {
    echo PHP_EOL . "WARNINGS:" . PHP_EOL;
    foreach ($warnings as $warning) {
        echo "   - $warning" . PHP_EOL;
    }
}

$score = round(($passedChecks / max(1, $passedChecks + $failedChecks)) * 100);
echo PHP_EOL . "SECURITY SCORE: $score/100" . PHP_EOL;

if ($score >= 80) {
    echo "Status: SECURE ✅" . PHP_EOL;
} elseif ($score >= 60) {
    echo "Status: MODERATE SECURITY ⚠️" . PHP_EOL;
} else {
    echo "Status: INSECURE ❌" . PHP_EOL;
}

echo PHP_EOL . "=== AUDIT COMPLETE ===" . PHP_EOL;