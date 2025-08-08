#!/usr/bin/env php
<?php

/**
 * Fix Navigation Consistency Script
 * Updates all Filament Resources to use consistent navigation with translations
 */

$fixes = [
    // Resource => [navigationGroupKey, extend_from_base]
    'PrepaidBalanceResource.php' => ['billing_portal', true],
    'CompanyPricingResource.php' => ['billing', true],
    'TenantResource.php' => ['system_administration', true],
    'MasterServiceResource.php' => ['company_structure', true],
    'SubscriptionResource.php' => ['billing', true],
    'UnifiedEventTypeResource.php' => ['personnel_services', true],
    'CustomerResource.php' => ['customer_management', false], // Already has method
    'ResellerResource.php' => ['business', true],
    'WorkingHoursResource.php' => ['personnel_services', true],
    'WorkingHourResource.php' => ['personnel_services', true],
    'PortalUserResource.php' => ['administration', true],
    'PhoneNumberResource.php' => ['company_structure', true],
    'CallCampaignResource.php' => ['communications', true],
    'GdprRequestResource.php' => ['system_administration', true],
    'ErrorCatalogResource.php' => ['system_management', true],
    'PromptTemplateResource.php' => ['system', true],
    'PricingTierResource.php' => ['finance_billing', true],
];

$basePath = '/var/www/api-gateway/app/Filament/Admin/Resources/';

foreach ($fixes as $file => $config) {
    [$navigationKey, $extendFromBase] = $config;
    $filePath = $basePath . $file;
    
    if (!file_exists($filePath)) {
        echo "⚠️  File not found: $file\n";
        continue;
    }
    
    $content = file_get_contents($filePath);
    $originalContent = $content;
    
    // 1. Change to extend BaseResource if needed
    if ($extendFromBase) {
        if (!str_contains($content, 'extends BaseResource')) {
            $content = str_replace(
                'use Filament\Resources\Resource;',
                '',
                $content
            );
            
            $content = str_replace(
                'class ' . str_replace('.php', '', $file) . ' extends Resource',
                'class ' . str_replace('.php', '', $file) . ' extends BaseResource',
                $content
            );
            
            // Add BaseResource import if not present
            if (!str_contains($content, 'use App\Filament\Admin\Resources\BaseResource;')) {
                $content = str_replace(
                    'namespace App\Filament\Admin\Resources;',
                    "namespace App\Filament\Admin\Resources;\n\nuse App\Filament\Admin\Resources\BaseResource;",
                    $content
                );
            }
        }
    }
    
    // 2. Replace hardcoded navigationGroup with navigationGroupKey
    $patterns = [
        '/protected static \?string \$navigationGroup = [\'"].*?[\'"]\;/' => 
            "protected static ?string \$navigationGroupKey = '$navigationKey';",
        '/protected static \?string \$navigationGroup = null\;/' => 
            "protected static ?string \$navigationGroupKey = '$navigationKey';",
    ];
    
    foreach ($patterns as $pattern => $replacement) {
        $content = preg_replace($pattern, $replacement, $content);
    }
    
    // 3. Remove getNavigationGroup method if we're extending BaseResource
    if ($extendFromBase && str_contains($content, 'public static function getNavigationGroup()')) {
        // Remove the entire method
        $content = preg_replace(
            '/public static function getNavigationGroup\(\)[^{]*\{[^}]*\}/s',
            '',
            $content
        );
    }
    
    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        echo "✅ Fixed: $file\n";
    } else {
        echo "ℹ️  Already correct or no changes needed: $file\n";
    }
}

// Special case for CallResource.enterprise.php if it exists
$enterpriseFile = $basePath . 'CallResource.enterprise.php';
if (file_exists($enterpriseFile)) {
    $content = file_get_contents($enterpriseFile);
    $content = str_replace(
        'protected static ?string $navigationGroup = \'Täglicher Betrieb\';',
        'protected static ?string $navigationGroupKey = \'daily_operations\';',
        $content
    );
    file_put_contents($enterpriseFile, $content);
    echo "✅ Fixed: CallResource.enterprise.php\n";
}

echo "\n✨ Navigation consistency fixes completed!\n";
echo "Clear cache with: php artisan optimize:clear\n";