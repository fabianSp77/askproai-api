#!/usr/bin/env php
<?php

/**
 * Fix Navigation Properly
 * Removes redundant imports and ensures all resources work correctly
 */

echo "🔧 Fixing Navigation Issues Properly...\n\n";

$basePath = '/var/www/api-gateway/app/Filament/Admin/Resources/';
$issuesFixed = 0;

// 1. Remove redundant BaseResource imports
$files = glob($basePath . '*.php');
foreach ($files as $file) {
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Remove redundant import (BaseResource is in same namespace)
    $content = str_replace(
        "use App\Filament\Admin\Resources\BaseResource;\n",
        '',
        $content
    );
    
    // Also remove if it has extra line break
    $content = str_replace(
        "use App\Filament\Admin\Resources\BaseResource;\n\n",
        '',
        $content
    );
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        echo "✅ Fixed redundant import in: " . basename($file) . "\n";
        $issuesFixed++;
    }
}

// 2. Fix CompanyResource specifically - it should use BaseResource too
$companyResource = $basePath . 'CompanyResource.php';
if (file_exists($companyResource)) {
    $content = file_get_contents($companyResource);
    $originalContent = $content;
    
    // Change from Resource to BaseResource
    if (!str_contains($content, 'extends BaseResource')) {
        $content = str_replace(
            'use Filament\Resources\Resource;',
            '',
            $content
        );
        
        $content = str_replace(
            'class CompanyResource extends Resource',
            'class CompanyResource extends BaseResource',
            $content
        );
        
        // Replace hardcoded navigation group with key
        $content = str_replace(
            'protected static ?string $navigationGroup = null;',
            'protected static ?string $navigationGroupKey = \'company_structure\';',
            $content
        );
        
        // Remove the getNavigationGroup method since BaseResource handles it
        $content = preg_replace(
            '/public static function getNavigationGroup\(\)[^{]*\{[^}]*return __\(\'admin\.navigation\.system\'\);[^}]*\}/s',
            '',
            $content
        );
        
        if ($content !== $originalContent) {
            file_put_contents($companyResource, $content);
            echo "✅ Fixed CompanyResource to use BaseResource\n";
            $issuesFixed++;
        }
    }
}

// 3. Fix other important resources that should use translation keys
$importantResources = [
    'CallResource.php' => 'daily_operations',
    'AppointmentResource.php' => 'daily_operations',
    'CustomerResource.php' => 'customer_management',
    'StaffResource.php' => 'company_structure',
    'ServiceResource.php' => 'company_structure',
    'BranchResource.php' => 'company_structure',
    'UserResource.php' => 'system_monitoring',
    'InvoiceResource.php' => 'finance_billing',
    'IntegrationResource.php' => 'integrations',
    'RetellAgentResource.php' => 'integrations',
    'BillingPeriodResource.php' => 'finance_billing',
];

foreach ($importantResources as $file => $navigationKey) {
    $filePath = $basePath . $file;
    
    if (!file_exists($filePath)) {
        continue;
    }
    
    $content = file_get_contents($filePath);
    $originalContent = $content;
    
    // Check if it already extends BaseResource
    if (!str_contains($content, 'extends BaseResource')) {
        // Change to extend BaseResource
        $content = str_replace(
            'use Filament\Resources\Resource;',
            '',
            $content
        );
        
        $className = str_replace('.php', '', $file);
        $content = str_replace(
            "class $className extends Resource",
            "class $className extends BaseResource",
            $content
        );
    }
    
    // Add navigationGroupKey if not present
    if (!str_contains($content, 'navigationGroupKey')) {
        // Find where to insert it (after $model declaration)
        $pattern = '/(protected static \?string \$model = [^;]+;)/';
        $replacement = "$1\n\n    protected static ?string \$navigationGroupKey = '$navigationKey';";
        $content = preg_replace($pattern, $replacement, $content, 1);
    }
    
    // Remove any hardcoded getNavigationGroup methods
    $content = preg_replace(
        '/public static function getNavigationGroup\(\)[^{]*\{[^}]*\}/s',
        '',
        $content
    );
    
    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        echo "✅ Fixed $file to use navigationGroupKey: $navigationKey\n";
        $issuesFixed++;
    }
}

echo "\n";
echo "====================================\n";
echo "🎉 Navigation Fixes Complete!\n";
echo "====================================\n";
echo "Issues fixed: $issuesFixed\n";
echo "\n";
echo "Now clearing all caches...\n";