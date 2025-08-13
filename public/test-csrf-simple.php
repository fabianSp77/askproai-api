<?php

// Simple test without bootstrapping the whole app
require __DIR__.'/../vendor/autoload.php';

// Load the middleware class
require __DIR__.'/../app/Http/Middleware/VerifyCsrfToken.php';

$middleware = new \App\Http\Middleware\VerifyCsrfToken(
    app(), 
    app('encrypter')
);

// Use reflection to access the protected property
$reflection = new ReflectionClass($middleware);
$property = $reflection->getProperty('except');
$property->setAccessible(true);
$except = $property->getValue($middleware);

echo "CSRF Token Exceptions:\n";
print_r($except);

echo "\nMiddleware file location: " . (new ReflectionClass($middleware))->getFileName() . "\n";