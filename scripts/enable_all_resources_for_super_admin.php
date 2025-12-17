<?php
/**
 * Enable All Hidden Resources for Super Admin
 * Patches shouldRegisterNavigation() to return true for super_admin
 */

$resourcesToFix = [
    'AppointmentModificationResource.php',
    'BalanceBonusTierResource.php',
    'CompanyAssignmentConfigResource.php',
    'ConversationFlowResource.php', // Table missing but should be visible for super_admin
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
echo "║   ENABLING ALL RESOURCES FOR SUPER ADMIN                      ║\n";
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

    // Check if shouldRegisterNavigation exists
    if (!preg_match('/public static function shouldRegisterNavigation\(\): bool/', $content)) {
        echo "⚠️  {$resourceFile}: No shouldRegisterNavigation() found\n";
        $skippedCount++;
        continue;
    }

    // Pattern to match the old simple return false
    $oldPattern = '/public static function shouldRegisterNavigation\(\): bool\s*\{\s*return false;\s*\}/s';

    // New pattern with super_admin check
    $newCode = 'public static function shouldRegisterNavigation(): bool
    {
        // ✅ Super admin can see all resources
        if (auth()->check() && auth()->user()->hasRole(\'super_admin\')) {
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
    echo "🎉 SUCCESS: {$fixedCount} resources now visible for super_admin!\n";
    echo "\n";
    echo "Next steps:\n";
    echo "  1. Clear cache: php artisan optimize:clear\n";
    echo "  2. Login to /admin as super_admin\n";
    echo "  3. Verify all resources are visible\n";
} else {
    echo "⚠️  No resources were modified.\n";
}

echo "\n";
