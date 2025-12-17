<?php
/**
 * Fix canViewAny() in all hidden resources
 * Remove or modify canViewAny() to allow super_admin access
 */

$resourcesToFix = [
    'AppointmentModificationResource.php',
    'BalanceBonusTierResource.php',
    'CompanyAssignmentConfigResource.php',
    'ConversationFlowResource.php',
    'CurrencyExchangeRateResource.php',
    'CustomerNoteResource.php',
    'InvoiceResource.php',
    'NotificationQueueResource.php',
    'NotificationTemplateResource.php',
    'PlatformCostResource.php',
    'PricingPlanResource.php',
    'ServiceStaffAssignmentResource.php',
    'TenantResource.php',
    'TransactionResource.php',
    'WorkingHourResource.php',
];

$resourcePath = __DIR__ . '/../app/Filament/Resources/';

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║   FIXING canViewAny() FOR SUPER ADMIN                         ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$fixedCount = 0;
$skippedCount = 0;
$errorCount = 0;

foreach ($resourcesToFix as $resourceFile) {
    $filePath = $resourcePath . $resourceFile;

    if (!file_exists($filePath)) {
        echo "⚠️  {$resourceFile}: File not found\n";
        $skippedCount++;
        continue;
    }

    $content = file_get_contents($filePath);

    // Check if canViewAny exists with return false
    if (!preg_match('/public static function canViewAny\(\): bool\s*\{\s*return false;/s', $content)) {
        echo "⚠️  {$resourceFile}: No blocking canViewAny() found\n";
        $skippedCount++;
        continue;
    }

    // Pattern to match simple return false in canViewAny
    $oldPattern = '/public static function canViewAny\(\): bool\s*\{\s*return false;\s*(\/\/[^\n]*)?\s*\}/s';

    // New pattern with super_admin check
    $newCode = 'public static function canViewAny(): bool
    {
        // ✅ Super admin can access all resources
        $user = auth()->user();
        if ($user && $user->hasRole(\'super_admin\')) {
            return true;
        }

        // Hidden for regular users
        return false;
    }';

    // Replace
    $newContent = preg_replace($oldPattern, $newCode, $content);

    if ($newContent === $content) {
        echo "⚠️  {$resourceFile}: Pattern not matched (might already be fixed)\n";
        $skippedCount++;
        continue;
    }

    // Write back
    if (file_put_contents($filePath, $newContent)) {
        echo "✅ {$resourceFile}: Fixed\n";
        $fixedCount++;
    } else {
        echo "❌ {$resourceFile}: Failed to write\n";
        $errorCount++;
    }
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                        SUMMARY                                 ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "  Total Resources:  " . count($resourcesToFix) . "\n";
echo "  ✅ Fixed:          {$fixedCount}\n";
echo "  ⚠️  Skipped:        {$skippedCount}\n";
echo "  ❌ Errors:         {$errorCount}\n";
echo "\n";

if ($fixedCount > 0) {
    echo "🎉 SUCCESS: {$fixedCount} resources canViewAny() now allow super_admin!\n";
    echo "\n";
    echo "Next steps:\n";
    echo "  1. Clear cache: php artisan optimize:clear\n";
    echo "  2. Refresh browser (hard refresh: Ctrl+Shift+R)\n";
    echo "  3. Verify all resources are visible\n";
} else {
    echo "⚠️  No resources were modified.\n";
}

echo "\n";
