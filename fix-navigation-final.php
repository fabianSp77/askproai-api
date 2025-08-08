<?php

// Fix all navigation issues completely

$resourcesPath = '/var/www/api-gateway/app/Filament/Admin/Resources';
$fixes = [];

// Find all resource files
$files = glob($resourcesPath . '/*.php');

foreach ($files as $file) {
    $filename = basename($file);
    
    // Skip base classes
    if (in_array($filename, ['BaseResource.php', 'EnhancedResource.php', 'EnhancedResourceSimple.php'])) {
        continue;
    }
    
    $content = file_get_contents($file);
    $originalContent = $content;
    $changes = [];
    
    // 1. Replace EnhancedResourceSimple with BaseResource
    if (strpos($content, 'extends EnhancedResourceSimple') !== false) {
        $content = str_replace('extends EnhancedResourceSimple', 'extends BaseResource', $content);
        $changes[] = "Changed parent class to BaseResource";
    }
    
    // 2. Remove the hardcoded $navigationGroup property
    $pattern = '/protected static \?\s*string \$navigationGroup\s*=\s*null\s*;/';
    if (preg_match($pattern, $content)) {
        $content = preg_replace($pattern, '', $content);
        $changes[] = "Removed hardcoded navigationGroup = null";
    }
    
    // 3. Remove any getNavigationGroup() method that returns null or hardcoded string
    $pattern = '/public static function getNavigationGroup\(\)[^{]*\{[^}]*return\s+(?:null|\'[^\']+\'|"[^"]+");[^}]*\}/s';
    if (preg_match($pattern, $content)) {
        $content = preg_replace($pattern, '', $content);
        $changes[] = "Removed old getNavigationGroup() method";
    }
    
    // 4. Clean up extra blank lines
    $content = preg_replace('/\n\s*\n\s*\n/', "\n\n", $content);
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        $fixes[$filename] = $changes;
    }
}

// Now ensure all resources have proper navigationGroupKey
$navigationMapping = [
    'CallResource.php' => 'daily_operations',
    'AppointmentResource.php' => 'daily_operations',
    'CustomerResource.php' => 'customer_management',
    'CompanyResource.php' => 'company_structure',
    'BranchResource.php' => 'company_structure',
    'StaffResource.php' => 'company_structure',
    'ServiceResource.php' => 'personnel_services',
    'UserResource.php' => 'admin',
    'PrepaidBalanceResource.php' => 'billing_portal',
    'InvoiceResource.php' => 'billing_portal',
    'BillingPeriodResource.php' => 'billing_portal',
    'IntegrationResource.php' => 'integrations',
    'RetellAgentResource.php' => 'integrations',
    'CalcomEventTypeResource.php' => 'integrations',
    'ResellerResource.php' => 'business',
    'PricingTierResource.php' => 'business',
    'CallCampaignResource.php' => 'campaigns',
];

foreach ($navigationMapping as $filename => $groupKey) {
    $file = $resourcesPath . '/' . $filename;
    if (!file_exists($file)) {
        continue;
    }
    
    $content = file_get_contents($file);
    
    // Check if navigationGroupKey is already set correctly
    if (!preg_match('/protected static \?\s*string \$navigationGroupKey\s*=\s*[\'"]' . $groupKey . '[\'"];/', $content)) {
        // Find existing navigationGroupKey or add it
        if (preg_match('/protected static \?\s*string \$navigationGroupKey\s*=\s*[^;]+;/', $content)) {
            // Replace existing
            $content = preg_replace(
                '/protected static \?\s*string \$navigationGroupKey\s*=\s*[^;]+;/',
                'protected static ?string $navigationGroupKey = \'' . $groupKey . '\';',
                $content
            );
            $fixes[$filename][] = "Updated navigationGroupKey to '$groupKey'";
        } else {
            // Add new after $model declaration
            $pattern = '/(protected static \?\s*string \$model\s*=\s*[^;]+;)/';
            if (preg_match($pattern, $content, $matches)) {
                $replacement = $matches[1] . "\n\n    protected static ?string \$navigationGroupKey = '" . $groupKey . "';";
                $content = preg_replace($pattern, $replacement, $content, 1);
                $fixes[$filename][] = "Added navigationGroupKey = '$groupKey'";
            }
        }
        
        file_put_contents($file, $content);
    }
}

echo "Navigation fixes completed:\n\n";
foreach ($fixes as $file => $changes) {
    echo "✅ $file:\n";
    foreach ($changes as $change) {
        echo "   - $change\n";
    }
}

echo "\nTotal files fixed: " . count($fixes) . "\n";