<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Check CSRF middleware
$csrfMiddleware = $app->make(\App\Http\Middleware\VerifyCsrfToken::class);
$reflection = new ReflectionClass($csrfMiddleware);
$property = $reflection->getProperty('except');
$property->setAccessible(true);
$except = $property->getValue($csrfMiddleware);

echo "CSRF Token Exceptions:\n";
print_r($except);

// Test if route would be excluded
$testUrls = [
    'admin-v2/api/login',
    '/admin-v2/api/login',
    'https://api.askproai.de/admin-v2/api/login'
];

echo "\nTesting URLs:\n";
foreach ($testUrls as $url) {
    $request = \Illuminate\Http\Request::create($url, 'POST');
    $shouldSkip = false;
    
    foreach ($except as $pattern) {
        if ($request->is($pattern)) {
            $shouldSkip = true;
            break;
        }
    }
    
    echo "$url: " . ($shouldSkip ? "SKIP CSRF" : "REQUIRES CSRF") . "\n";
}