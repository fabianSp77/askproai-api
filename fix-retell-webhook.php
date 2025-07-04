#!/usr/bin/env php
<?php

/**
 * QUICK FIX: Temporarily bypass Retell webhook signature verification
 * 
 * This creates a bypass route that logs webhook data for debugging
 * 
 * WARNING: ONLY FOR DEBUGGING - REMOVE IN PRODUCTION!
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Colors
$red = "\033[0;31m";
$green = "\033[0;32m";
$yellow = "\033[1;33m";
$blue = "\033[0;34m";
$reset = "\033[0m";

echo "{$blue}=== Retell Webhook Quick Fix ==={$reset}\n\n";

// 1. Add temporary debug route
$routeFile = '/var/www/api-gateway/routes/api.php';
$debugRoute = '
// TEMPORARY DEBUG ROUTE - REMOVE AFTER FIXING!
Route::post(\'/retell/webhook-bypass\', function (Illuminate\Http\Request $request) {
    \Log::warning(\'[RETELL WEBHOOK BYPASS] Incoming webhook\', [
        \'headers\' => $request->headers->all(),
        \'body\' => $request->getContent(),
        \'ip\' => $request->ip(),
    ]);
    
    // Process the webhook without signature verification
    try {
        $controller = app(\App\Http\Controllers\RetellWebhookController::class);
        return $controller->processWebhook($request);
    } catch (\Exception $e) {
        \Log::error(\'[RETELL WEBHOOK BYPASS] Processing failed\', [
            \'error\' => $e->getMessage(),
            \'trace\' => $e->getTraceAsString()
        ]);
        return response()->json([\'error\' => $e->getMessage()], 500);
    }
})->name(\'retell.webhook.bypass\');
';

// Check if route already exists
$currentContent = file_get_contents($routeFile);
if (strpos($currentContent, 'retell/webhook-bypass') === false) {
    // Add before the last closing (if any) or at the end
    $newContent = $currentContent . "\n" . $debugRoute;
    file_put_contents($routeFile, $newContent);
    echo "{$green}✓ Added bypass route to routes/api.php{$reset}\n";
} else {
    echo "{$yellow}⚠ Bypass route already exists{$reset}\n";
}

// 2. Clear route cache
echo "\n{$yellow}Clearing route cache...{$reset}\n";
shell_exec('php artisan route:clear');
echo "{$green}✓ Route cache cleared{$reset}\n";

// 3. Show the new webhook URL
echo "\n{$blue}=== NEW WEBHOOK URL ==={$reset}\n";
echo "{$yellow}https://api.askproai.de/api/retell/webhook-bypass{$reset}\n";
echo "\n{$red}⚠️  IMPORTANT:{$reset}\n";
echo "1. Update this URL in Retell.ai Dashboard\n";
echo "2. This bypasses signature verification - USE ONLY FOR DEBUGGING!\n";
echo "3. Monitor logs: tail -f storage/logs/laravel.log | grep 'RETELL WEBHOOK'\n";
echo "4. Remove this route once the issue is fixed!\n";

// 4. Test the bypass route
echo "\n{$yellow}Testing bypass route...{$reset}\n";
$testUrl = 'https://api.askproai.de/api/retell/webhook-bypass';
$ch = curl_init($testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['test' => true]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 || $httpCode === 422) {
    echo "{$green}✓ Bypass route is working (HTTP $httpCode){$reset}\n";
} else {
    echo "{$red}✗ Bypass route test failed (HTTP $httpCode){$reset}\n";
    echo "Response: $response\n";
}

echo "\n{$blue}Quick fix applied!{$reset}\n";