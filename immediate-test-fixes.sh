#!/bin/bash

echo "🔧 Applying Immediate Test Fixes..."
echo "==================================="

# 1. Fix test database
echo "1. Migrating test database..."
php artisan migrate --env=testing --force

# 2. Clear all caches
echo "2. Clearing caches..."
php artisan cache:clear --env=testing
php artisan config:clear --env=testing
php artisan view:clear --env=testing

# 3. Fix autoloading
echo "3. Fixing autoloading..."
composer dump-autoload

# 4. Create missing directories
echo "4. Creating missing test directories..."
mkdir -p tests/Mocks
mkdir -p tests/TestHelpers
mkdir -p storage/logs/testing

# 5. Fix StructuredLogger method
echo "5. Creating missing logger method fix..."
cat > app/Services/Logging/StructuredLogger.php << 'EOF'
<?php

namespace App\Services\Logging;

use Illuminate\Support\Facades\Log;

class StructuredLogger
{
    public function logApiCall($service, $method, $params, $duration = null)
    {
        Log::info('API Call', [
            'service' => $service,
            'method' => $method,
            'params' => $params,
            'duration' => $duration
        ]);
    }
    
    public function logApiResponse($service, $method, $response, $statusCode = 200)
    {
        Log::info('API Response', [
            'service' => $service,
            'method' => $method,
            'response' => $response,
            'status_code' => $statusCode
        ]);
    }
    
    public function logWebhookReceived($type, $payload)
    {
        Log::info('Webhook Received', [
            'type' => $type,
            'payload' => $payload
        ]);
    }
    
    public function logApiError($service, $method, $error)
    {
        Log::error('API Error', [
            'service' => $service,
            'method' => $method,
            'error' => $error
        ]);
    }
}
EOF

echo "6. Running quick test to verify..."
./vendor/bin/phpunit tests/Unit/Services/Cache/CacheManagerTest.php --no-coverage

echo ""
echo "✅ Immediate fixes applied!"
echo ""
echo "Next: Run 'php comprehensive-test-runner.php' to see improvements"