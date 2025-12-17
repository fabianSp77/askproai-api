<?php
/**
 * Fix Resources with Filament Auth
 * Use Filament::auth()->user() instead of auth()->user()
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
echo "║   FIXING WITH FILAMENT AUTH                                    ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$fixedNav = 0;
$fixedView = 0;

foreach ($resourcesToFix as $resourceFile) {
    $filePath = $resourcePath . $resourceFile;

    if (!file_exists($filePath)) {
        echo "⚠️  {$resourceFile}: File not found\n";
        continue;
    }

    $content = file_get_contents($filePath);
    $modified = false;

    // Add use statement for Filament if not present
    if (!preg_match('/use Filament\\\\Facades\\\\Filament;/', $content)) {
        $content = preg_replace(
            '/(namespace [^;]+;)/',
            "$1\n\nuse Filament\\Facades\\Filament;",
            $content
        );
        $modified = true;
    }

    // Fix shouldRegisterNavigation
    $oldNavPattern = '/public static function shouldRegisterNavigation\(\): bool\s*\{\s*\/\/ ✅[^\}]+auth\(\)->check\(\) && auth\(\)->user\(\)->hasRole\([^\}]+\}/s';
    $newNavCode = 'public static function shouldRegisterNavigation(): bool
    {
        // ✅ Super admin can see all resources
        $user = Filament::auth()->user();
        return $user && $user->hasRole(\'super_admin\');
    }';

    if (preg_match($oldNavPattern, $content)) {
        $content = preg_replace($oldNavPattern, $newNavCode, $content);
        $modified = true;
        $fixedNav++;
    }

    // Fix canViewAny
    $oldViewPattern = '/public static function canViewAny\(\): bool\s*\{\s*\/\/ ✅[^\}]+\$user = auth\(\)->user\(\);[^\}]+\}/s';
    $newViewCode = 'public static function canViewAny(): bool
    {
        // ✅ Super admin can access all resources
        $user = Filament::auth()->user();
        return $user && $user->hasRole(\'super_admin\');
    }';

    if (preg_match($oldViewPattern, $content)) {
        $content = preg_replace($oldViewPattern, $newViewCode, $content);
        $modified = true;
        $fixedView++;
    }

    if ($modified) {
        file_put_contents($filePath, $content);
        echo "✅ {$resourceFile}: Fixed\n";
    } else {
        echo "⚠️  {$resourceFile}: No changes needed\n";
    }
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                        SUMMARY                                 ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "  shouldRegisterNavigation fixed: {$fixedNav}\n";
echo "  canViewAny fixed: {$fixedView}\n";
echo "\n";
echo "✅ Now using Filament::auth()->user() instead of auth()->user()\n";
echo "\n";
