<?php

$resourcesPath = '/var/www/api-gateway/app/Filament/Admin/Resources';
$fixes = 0;

// Get all resource files
$files = glob($resourcesPath . '/*.php');

foreach ($files as $file) {
    $filename = basename($file);
    
    // Skip base classes and test resources
    if (in_array($filename, [
        'BaseResource.php', 
        'EnhancedResource.php', 
        'EnhancedResourceSimple.php',
        'AppointmentResourceUpdated.php',
        'DummyCompanyResource.php',
        'WorkingHoursResource.php' // Duplicate of WorkingHourResource
    ])) {
        continue;
    }
    
    $content = file_get_contents($file);
    $originalContent = $content;
    $changes = [];
    
    // 1. Remove HasConsistentNavigation trait usage
    $pattern = '/\s*use\s+HasConsistentNavigation\s*;\s*/';
    if (preg_match($pattern, $content)) {
        $content = preg_replace($pattern, "\n", $content);
        $changes[] = "Removed HasConsistentNavigation trait";
    }
    
    // 2. Remove the trait import
    $pattern = '/use\s+App\\\\Filament\\\\Admin\\\\Traits\\\\HasConsistentNavigation;\s*\n/';
    if (preg_match($pattern, $content)) {
        $content = preg_replace($pattern, '', $content);
        $changes[] = "Removed HasConsistentNavigation import";
    }
    
    // 3. Remove any getNavigationGroup() method that might override BaseResource
    $pattern = '/\s*public\s+static\s+function\s+getNavigationGroup\(\)[^{]*\{[^}]*return\s+[^;]+;\s*\}\s*/s';
    if (preg_match($pattern, $content)) {
        $content = preg_replace($pattern, "\n", $content);
        $changes[] = "Removed overriding getNavigationGroup() method";
    }
    
    // 4. Clean up extra blank lines
    $content = preg_replace('/\n\s*\n\s*\n/', "\n\n", $content);
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        echo "✅ $filename: " . implode(', ', $changes) . "\n";
        $fixes++;
    }
}

echo "\n📊 Fixed $fixes files\n";

// Now specifically check and fix the resources that showed as "Ungrouped"
$ungroupedResources = [
    'BranchResource.php',
    'CompanyResource.php',
    'InvoiceResource.php',
    'ServiceResource.php',
    'StaffResource.php',
    'UserResource.php',
];

echo "\nChecking ungrouped resources:\n";
foreach ($ungroupedResources as $filename) {
    $file = $resourcesPath . '/' . $filename;
    if (!file_exists($file)) {
        continue;
    }
    
    $content = file_get_contents($file);
    
    // Check if navigationGroupKey is properly set
    if (!preg_match('/protected\s+static\s+\?\s*string\s+\$navigationGroupKey/', $content)) {
        echo "❌ $filename: Missing navigationGroupKey\n";
    } else {
        // Extract the value
        if (preg_match('/protected\s+static\s+\?\s*string\s+\$navigationGroupKey\s*=\s*[\'"]([^\'"]*)[\'"]/', $content, $matches)) {
            echo "✓ $filename: navigationGroupKey = '{$matches[1]}'\n";
        }
    }
}