#!/usr/bin/env php
<?php

/**
 * Emergency API Key Rotation Script
 * Run this immediately to secure all API keys
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Services\Security\ApiKeyEncryptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "=== EMERGENCY API KEY ROTATION ===\n";
echo "Starting at: " . date('Y-m-d H:i:s') . "\n\n";

$encryptionService = new ApiKeyEncryptionService();

// Step 1: Backup current keys (encrypted)
echo "Step 1: Creating encrypted backup of current keys...\n";
$backupFile = storage_path('app/api-keys-backup-' . date('Y-m-d-H-i-s') . '.json');
$backup = [];

$companies = Company::all();
foreach ($companies as $company) {
    $backup[$company->id] = [
        'name' => $company->name,
        'calcom_api_key' => $company->getRawOriginal('calcom_api_key'),
        'retell_api_key' => $company->getRawOriginal('retell_api_key'),
        'encrypted_at' => now()->toIso8601String()
    ];
}

file_put_contents($backupFile, json_encode($backup, JSON_PRETTY_PRINT));
echo "Backup saved to: $backupFile\n\n";

// Step 2: Encrypt all plain text keys
echo "Step 2: Encrypting all plain text API keys...\n";
$encrypted = 0;
$errors = 0;

DB::beginTransaction();

try {
    foreach ($companies as $company) {
        $updated = false;
        
        // Get raw values to bypass accessors
        $calcomKey = $company->getRawOriginal('calcom_api_key');
        $retellKey = $company->getRawOriginal('retell_api_key');
        
        // Encrypt Cal.com key if not already encrypted
        if (!empty($calcomKey) && !$encryptionService->isEncrypted($calcomKey)) {
            try {
                $encrypted_key = $encryptionService->encrypt($calcomKey);
                DB::table('companies')
                    ->where('id', $company->id)
                    ->update(['calcom_api_key' => $encrypted_key]);
                $updated = true;
                echo "  ✓ Encrypted Cal.com key for: {$company->name}\n";
            } catch (\Exception $e) {
                echo "  ✗ Failed to encrypt Cal.com key for {$company->name}: {$e->getMessage()}\n";
                $errors++;
            }
        }
        
        // Encrypt Retell key if not already encrypted
        if (!empty($retellKey) && !$encryptionService->isEncrypted($retellKey)) {
            try {
                $encrypted_key = $encryptionService->encrypt($retellKey);
                DB::table('companies')
                    ->where('id', $company->id)
                    ->update(['retell_api_key' => $encrypted_key]);
                $updated = true;
                echo "  ✓ Encrypted Retell.ai key for: {$company->name}\n";
            } catch (\Exception $e) {
                echo "  ✗ Failed to encrypt Retell.ai key for {$company->name}: {$e->getMessage()}\n";
                $errors++;
            }
        }
        
        if ($updated) {
            $encrypted++;
        }
    }
    
    DB::commit();
    echo "\nSuccessfully encrypted $encrypted companies' API keys.\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n✗ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "Transaction rolled back. No changes were made.\n";
    exit(1);
}

// Step 3: Generate new keys recommendation
echo "\n=== NEXT STEPS ===\n";
echo "1. API keys are now encrypted in the database\n";
echo "2. To generate NEW API keys (recommended):\n";
echo "   - Cal.com: Login to cal.com → Settings → API Keys → Generate New\n";
echo "   - Retell.ai: Login to retell.ai → Settings → API Keys → Create New\n";
echo "3. Update the new keys using: php artisan security:rotate-keys\n";
echo "4. Update webhook secrets in external services\n";
echo "5. Restart services: sudo systemctl restart php8.3-fpm\n";

// Step 4: Check environment variables
echo "\n=== ENVIRONMENT VARIABLES TO UPDATE ===\n";
$envVars = [
    'DEFAULT_CALCOM_API_KEY',
    'CALCOM_V2_API_KEY',
    'DEFAULT_RETELL_API_KEY',
    'RETELL_TOKEN',
    'RETELL_WEBHOOK_SECRET',
    'CALCOM_WEBHOOK_SECRET'
];

foreach ($envVars as $var) {
    $value = env($var);
    if ($value) {
        echo "- $var: " . (strlen($value) > 20 ? substr($value, 0, 10) . '...' . substr($value, -10) : $value) . "\n";
    } else {
        echo "- $var: NOT SET\n";
    }
}

// Step 5: Security check
echo "\n=== SECURITY STATUS ===\n";
$plainTextCount = DB::table('companies')
    ->where(function($query) {
        $query->whereNotNull('calcom_api_key')
              ->whereRaw("calcom_api_key NOT LIKE 'eyJ%'");
    })
    ->orWhere(function($query) {
        $query->whereNotNull('retell_api_key')
              ->whereRaw("retell_api_key NOT LIKE 'eyJ%'");
    })
    ->count();

if ($plainTextCount > 0) {
    echo "⚠️  WARNING: $plainTextCount companies still have plain text API keys!\n";
} else {
    echo "✓ All API keys are encrypted\n";
}

// Log the rotation
Log::channel('security')->info('Emergency API key rotation completed', [
    'encrypted_count' => $encrypted,
    'error_count' => $errors,
    'plain_text_remaining' => $plainTextCount,
    'backup_file' => $backupFile
]);

echo "\nCompleted at: " . date('Y-m-d H:i:s') . "\n";
echo "Logs saved to: storage/logs/security.log\n";