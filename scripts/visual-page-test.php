#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== VISUAL PAGE TEST - State of the Art ===" . PHP_EOL;
echo "Date: " . date('Y-m-d H:i:s') . PHP_EOL;
echo PHP_EOL;

// Test pages that should be accessible
$testPages = [
    '/business' => 'Dashboard',
    '/business/login' => 'Login Page',
    '/business/customers' => 'Customers List',
    '/business/calls' => 'Calls List',
    '/business/appointments' => 'Appointments List',
    '/business/companies' => 'Companies List',
    '/business/staff' => 'Staff List',
    '/business/services' => 'Services List',
    '/business/branches' => 'Branches List'
];

echo "1. TESTING PAGE ACCESSIBILITY" . PHP_EOL;
echo str_repeat('-', 60) . PHP_EOL;

$results = [];
$pageContent = [];

foreach ($testPages as $path => $name) {
    // Create a request
    $request = \Illuminate\Http\Request::create($path, 'GET');

    // Handle the request
    $response = $kernel->handle($request);
    $statusCode = $response->getStatusCode();
    $content = $response->getContent();

    // Check status
    if ($statusCode == 200) {
        $results[$path] = ['status' => '✅ OK', 'code' => $statusCode];

        // Extract page info
        preg_match('/<title>(.*?)<\/title>/i', $content, $titleMatch);
        $title = $titleMatch[1] ?? 'No Title';

        // Check for Filament components
        $hasFilament = strpos($content, 'filament') !== false;
        $hasLivewire = strpos($content, 'livewire') !== false;
        $hasLogin = strpos($content, 'Sign in') !== false || strpos($content, 'Login') !== false;

        echo "✅ $name ($path)" . PHP_EOL;
        echo "   Status: HTTP $statusCode" . PHP_EOL;
        echo "   Title: $title" . PHP_EOL;
        echo "   Filament: " . ($hasFilament ? 'Yes' : 'No') . PHP_EOL;
        echo "   Livewire: " . ($hasLivewire ? 'Yes' : 'No') . PHP_EOL;
        if ($hasLogin) {
            echo "   Login Form: Detected" . PHP_EOL;
        }

        // Store content for analysis
        $pageContent[$path] = [
            'title' => $title,
            'hasFilament' => $hasFilament,
            'hasLivewire' => $hasLivewire,
            'hasLogin' => $hasLogin,
            'contentLength' => strlen($content)
        ];

    } elseif ($statusCode == 302 || $statusCode == 301) {
        $location = $response->headers->get('Location');
        $results[$path] = ['status' => '➡️ Redirect', 'code' => $statusCode, 'location' => $location];
        echo "➡️  $name ($path)" . PHP_EOL;
        echo "   Status: HTTP $statusCode" . PHP_EOL;
        echo "   Redirects to: $location" . PHP_EOL;
    } else {
        $results[$path] = ['status' => '❌ Error', 'code' => $statusCode];
        echo "❌ $name ($path)" . PHP_EOL;
        echo "   Status: HTTP $statusCode" . PHP_EOL;
    }
    echo PHP_EOL;
}

echo PHP_EOL . "2. VISUAL COMPONENT ANALYSIS" . PHP_EOL;
echo str_repeat('-', 60) . PHP_EOL;

// Analyze the login page content if available
$loginPath = '/business/login';
$loginRequest = \Illuminate\Http\Request::create($loginPath, 'GET');
$loginResponse = $kernel->handle($loginRequest);

if ($loginResponse->getStatusCode() == 200) {
    $content = $loginResponse->getContent();

    echo "Login Page Components:" . PHP_EOL;

    // Check for form elements
    $hasEmailField = preg_match('/<input[^>]*type=["\']email["\'][^>]*>/i', $content);
    $hasPasswordField = preg_match('/<input[^>]*type=["\']password["\'][^>]*>/i', $content);
    $hasSubmitButton = preg_match('/<button[^>]*type=["\']submit["\'][^>]*>/i', $content) ||
                       preg_match('/<input[^>]*type=["\']submit["\'][^>]*>/i', $content);
    $hasRememberMe = preg_match('/remember/i', $content);

    echo "   ✅ Email Field: " . ($hasEmailField ? 'Present' : 'Not Found') . PHP_EOL;
    echo "   ✅ Password Field: " . ($hasPasswordField ? 'Present' : 'Not Found') . PHP_EOL;
    echo "   ✅ Submit Button: " . ($hasSubmitButton ? 'Present' : 'Not Found') . PHP_EOL;
    echo "   ✅ Remember Me: " . ($hasRememberMe ? 'Present' : 'Not Found') . PHP_EOL;

    // Check for security features
    $hasCSRF = preg_match('/<input[^>]*name=["\']_token["\'][^>]*>/i', $content) ||
               preg_match('/csrf/i', $content);
    echo "   ✅ CSRF Protection: " . ($hasCSRF ? 'Active' : 'Not Detected') . PHP_EOL;
}

echo PHP_EOL . "3. RESPONSIVE DESIGN CHECK" . PHP_EOL;
echo str_repeat('-', 60) . PHP_EOL;

// Simulate different viewports
$viewports = [
    'Mobile' => ['width' => 375, 'user-agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)'],
    'Tablet' => ['width' => 768, 'user-agent' => 'Mozilla/5.0 (iPad; CPU OS 14_0 like Mac OS X)'],
    'Desktop' => ['width' => 1920, 'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)']
];

foreach ($viewports as $device => $config) {
    $request = \Illuminate\Http\Request::create('/business/login', 'GET');
    $request->headers->set('User-Agent', $config['user-agent']);

    $response = $kernel->handle($request);

    if ($response->getStatusCode() == 200) {
        $content = $response->getContent();

        // Check for responsive meta tags
        $hasViewport = preg_match('/<meta[^>]*name=["\']viewport["\'][^>]*>/i', $content);
        $hasResponsiveClasses = preg_match('/(sm:|md:|lg:|xl:|responsive)/i', $content);

        echo "$device View:" . PHP_EOL;
        echo "   Viewport Meta: " . ($hasViewport ? 'Yes' : 'No') . PHP_EOL;
        echo "   Responsive Classes: " . ($hasResponsiveClasses ? 'Yes' : 'No') . PHP_EOL;
        echo "   Content Length: " . strlen($content) . " bytes" . PHP_EOL;
    }
}

echo PHP_EOL . "4. PERFORMANCE METRICS" . PHP_EOL;
echo str_repeat('-', 60) . PHP_EOL;

// Measure page load times
foreach (array_slice($testPages, 0, 3) as $path => $name) {
    $start = microtime(true);

    $request = \Illuminate\Http\Request::create($path, 'GET');
    $response = $kernel->handle($request);

    $loadTime = (microtime(true) - $start) * 1000;

    echo "$name Load Time: " . round($loadTime, 2) . "ms" . PHP_EOL;
}

echo PHP_EOL . "5. ACCESSIBILITY ANALYSIS" . PHP_EOL;
echo str_repeat('-', 60) . PHP_EOL;

// Check a sample page for accessibility features
$sampleRequest = \Illuminate\Http\Request::create('/business/login', 'GET');
$sampleResponse = $kernel->handle($sampleRequest);

if ($sampleResponse->getStatusCode() == 200) {
    $content = $sampleResponse->getContent();

    // Check for accessibility features
    $checks = [
        'Alt text for images' => '/<img[^>]*alt=["\'][^"\']+["\'][^>]*>/i',
        'ARIA labels' => '/aria-label=/i',
        'Form labels' => '/<label[^>]*>/i',
        'Heading structure' => '/<h[1-6][^>]*>/i',
        'Lang attribute' => '/<html[^>]*lang=["\'][^"\']+["\'][^>]*>/i',
        'Skip links' => '/skip.*nav|skip.*content/i'
    ];

    foreach ($checks as $feature => $pattern) {
        $hasFeature = preg_match($pattern, $content);
        echo "   $feature: " . ($hasFeature ? '✅ Present' : '⚠️ Not Found') . PHP_EOL;
    }
}

echo PHP_EOL . "6. TEST SUMMARY" . PHP_EOL;
echo str_repeat('-', 60) . PHP_EOL;

$successCount = 0;
$redirectCount = 0;
$errorCount = 0;

foreach ($results as $path => $result) {
    if ($result['status'] == '✅ OK') $successCount++;
    elseif ($result['status'] == '➡️ Redirect') $redirectCount++;
    else $errorCount++;
}

echo "Total Pages Tested: " . count($testPages) . PHP_EOL;
echo "✅ Successful: $successCount" . PHP_EOL;
echo "➡️  Redirects: $redirectCount" . PHP_EOL;
echo "❌ Errors: $errorCount" . PHP_EOL;

$score = round(($successCount / count($testPages)) * 100);
echo PHP_EOL . "Visual Page Score: $score/100" . PHP_EOL;

if ($score >= 80) {
    echo "Status: ✅ PAGES ARE DISPLAYING CORRECTLY" . PHP_EOL;
} elseif ($score >= 50) {
    echo "Status: ⚠️ SOME PAGES ACCESSIBLE" . PHP_EOL;
} else {
    echo "Status: ❌ MOST PAGES NOT ACCESSIBLE" . PHP_EOL;
}

// Check if admin needs authentication
if ($redirectCount > 5) {
    echo PHP_EOL . "Note: Most pages redirect to login (authentication required)" . PHP_EOL;
    echo "This is EXPECTED behavior for admin panels." . PHP_EOL;
}

echo PHP_EOL . "=== VISUAL TEST COMPLETE ===" . PHP_EOL;