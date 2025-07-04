#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Webhook\EnhancedWebhookDeduplicationService;
use App\Services\Webhook\WebhookDeduplicationService;

echo "Testing webhook deduplication service...\n\n";

// Check if the parent method is accessible
$reflection = new ReflectionClass(WebhookDeduplicationService::class);
$method = $reflection->getMethod('generateIdempotencyKey');

echo "Parent class method 'generateIdempotencyKey' visibility: ";
if ($method->isPublic()) {
    echo "public\n";
} elseif ($method->isProtected()) {
    echo "protected\n";
} elseif ($method->isPrivate()) {
    echo "private\n";
}

// Check the child class
$childReflection = new ReflectionClass(EnhancedWebhookDeduplicationService::class);
echo "\nChild class extends: " . $childReflection->getParentClass()->getName() . "\n";

// Test instantiation
try {
    $service = new EnhancedWebhookDeduplicationService();
    echo "\n✅ Service instantiated successfully\n";
    
    // Check if method exists
    if (method_exists($service, 'generateIdempotencyKey')) {
        echo "✅ Method 'generateIdempotencyKey' exists in service\n";
    } else {
        echo "❌ Method 'generateIdempotencyKey' NOT found\n";
    }
} catch (\Exception $e) {
    echo "\n❌ Error instantiating service: " . $e->getMessage() . "\n";
}

// Check for any recent changes to the files
echo "\nFile modification times:\n";
echo "Parent class: " . date('Y-m-d H:i:s', filemtime(app_path('Services/Webhook/WebhookDeduplicationService.php'))) . "\n";
echo "Child class: " . date('Y-m-d H:i:s', filemtime(app_path('Services/Webhook/EnhancedWebhookDeduplicationService.php'))) . "\n";