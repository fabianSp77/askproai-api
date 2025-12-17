<?php
/**
 * Repair Broken Resources
 * Remove orphaned code fragments from bad regex replace
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
echo "║   REPAIRING BROKEN RESOURCES                                   ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$repairedCount = 0;

foreach ($resourcesToFix as $resourceFile) {
    $filePath = $resourcePath . $resourceFile;

    if (!file_exists($filePath)) {
        echo "⚠️  {$resourceFile}: File not found\n";
        continue;
    }

    $content = file_get_contents($filePath);

    // Remove orphaned code fragments (lines 37-39 type pattern)
    // Pattern: closing brace of shouldRegisterNavigation followed by orphaned comment and return false
    $pattern = '/(return \$user && \$user->hasRole\(\'super_admin\'\);\s*\}\s*)\n\s*\/\/ Hidden for regular users\s*\n\s*return false;\s*\n\s*\}/s';

    $newContent = preg_replace($pattern, '$1', $content);

    // Also handle the canViewAny case
    $pattern2 = '/(public static function canViewAny\(\): bool\s*\{\s*\/\/ ✅ Super admin can access all resources\s*\$user = Filament::auth\(\)->user\(\);\s*return \$user && \$user->hasRole\(\'super_admin\'\);\s*\}\s*)\n\s*\/\/ Hidden for regular users\s*\n\s*return false;\s*\n\s*\}/s';

    $newContent = preg_replace($pattern2, '$1', $newContent);

    if ($newContent !== $content) {
        file_put_contents($filePath, $newContent);
        echo "✅ {$resourceFile}: Repaired\n";
        $repairedCount++;
    } else {
        echo "⚠️  {$resourceFile}: No issues found\n";
    }
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                        SUMMARY                                 ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "  Resources repaired: {$repairedCount}\n";
echo "\n";

if ($repairedCount > 0) {
    echo "✅ Syntax errors fixed!\n";
    echo "   Run: php artisan optimize:clear\n";
} else {
    echo "ℹ️  All files appear to be OK\n";
}

echo "\n";
