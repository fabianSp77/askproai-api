<?php

/**
 * Script to update all navigation groups in Resources and Pages
 * to match the new navigation structure
 */

$basePath = __DIR__;

// Mapping of old groups to new groups
$groupMapping = [
    // Direct mappings
    'Dashboard' => 'Dashboard',
    'Täglicher Betrieb' => 'Täglicher Betrieb',
    'Einrichtung' => 'Einrichtung & Konfiguration',
    'Billing' => 'Abrechnung',
    'Abrechnung' => 'Abrechnung', // German version
    'Verwaltung' => 'Unternehmensstruktur', // Most items should go here
    'System' => 'System & Verwaltung',
    'System & Monitoring' => 'System & Verwaltung',
    'System Monitoring' => 'System & Verwaltung',
    'Berichte' => 'Berichte & Analysen',
    'Compliance' => 'Compliance & Sicherheit',
    'Integrationen' => 'Einrichtung & Konfiguration',
    'Kundenverwaltung' => 'Täglicher Betrieb',
    'Personal & Services' => 'Unternehmensstruktur',
    'Einstellungen' => 'Einrichtung & Konfiguration',
];

// Special cases based on resource/page name
$specialCases = [
    'CompanyResource' => 'Unternehmensstruktur',
    'BranchResource' => 'Unternehmensstruktur',
    'StaffResource' => 'Unternehmensstruktur',
    'MasterServiceResource' => 'Unternehmensstruktur',
    'PhoneNumberResource' => 'Unternehmensstruktur',
    'CalcomEventTypeResource' => 'Unternehmensstruktur',
    'WorkingHourResource' => 'Unternehmensstruktur',
    'CustomerResource' => 'Täglicher Betrieb',
    'AppointmentResource' => 'Täglicher Betrieb',
    'CallResource' => 'Täglicher Betrieb',
    'InvoiceResource' => 'Abrechnung',
    'BillingPeriodResource' => 'Abrechnung',
    'SubscriptionResource' => 'Abrechnung',
    'PricingPlanResource' => 'Abrechnung',
    'ServiceAddonResource' => 'Abrechnung',
    'UserResource' => 'System & Verwaltung',
    'GdprRequestResource' => 'Compliance & Sicherheit',
    'ReportsAndAnalytics' => 'Berichte & Analysen',
    'GDPRManagement' => 'Compliance & Sicherheit',
    'TwoFactorAuthentication' => 'Compliance & Sicherheit',
];

$updatedFiles = [];
$errors = [];

// Function to update navigation group in a file
function updateNavigationGroup($filePath, $groupMapping, $specialCases) {
    $content = file_get_contents($filePath);
    $originalContent = $content;
    $fileName = basename($filePath, '.php');
    
    // Check for special cases first
    $newGroup = null;
    foreach ($specialCases as $pattern => $group) {
        if (strpos($fileName, $pattern) !== false || strpos($fileName, str_replace('Resource', '', $pattern)) !== false) {
            $newGroup = $group;
            break;
        }
    }
    
    // Find current navigationGroup
    if (preg_match('/protected\s+static\s+\?string\s+\$navigationGroup\s*=\s*[\'"]([^\'"]*)[\'"]\s*;/', $content, $matches)) {
        $currentGroup = $matches[1];
        
        // If no special case, use mapping
        if (!$newGroup) {
            $newGroup = $groupMapping[$currentGroup] ?? $currentGroup;
        }
        
        // Update the navigationGroup
        $content = preg_replace(
            '/protected\s+static\s+\?string\s+\$navigationGroup\s*=\s*[\'"][^\'"]*)[\'"]\s*;/',
            "protected static ?string \$navigationGroup = '{$newGroup}';",
            $content
        );
        
        // Update navigationSort if needed
        if ($content !== $originalContent) {
            // Adjust sort values based on new structure
            $sortValue = getSortValueForGroup($newGroup, $fileName);
            if ($sortValue !== null) {
                $content = preg_replace(
                    '/protected\s+static\s+\?int\s+\$navigationSort\s*=\s*\d+\s*;/',
                    "protected static ?int \$navigationSort = {$sortValue};",
                    $content
                );
            }
        }
        
        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            return [
                'file' => $filePath,
                'oldGroup' => $currentGroup,
                'newGroup' => $newGroup,
                'status' => 'updated'
            ];
        }
    }
    
    return null;
}

// Function to get sort value based on group and resource
function getSortValueForGroup($group, $fileName) {
    $sortValues = [
        'Dashboard' => 0,
        'Täglicher Betrieb' => 10,
        'Unternehmensstruktur' => 20,
        'Einrichtung & Konfiguration' => 30,
        'Abrechnung' => 40,
        'Berichte & Analysen' => 50,
        'System & Verwaltung' => 60,
        'Compliance & Sicherheit' => 70,
    ];
    
    $baseSortValue = $sortValues[$group] ?? 80;
    
    // Add specific offset based on resource type
    $offset = 0;
    if (strpos($fileName, 'Company') !== false) $offset = 1;
    elseif (strpos($fileName, 'Branch') !== false) $offset = 2;
    elseif (strpos($fileName, 'Staff') !== false) $offset = 3;
    elseif (strpos($fileName, 'Service') !== false) $offset = 4;
    elseif (strpos($fileName, 'Appointment') !== false) $offset = 1;
    elseif (strpos($fileName, 'Call') !== false) $offset = 2;
    elseif (strpos($fileName, 'Customer') !== false) $offset = 3;
    
    return $baseSortValue + $offset;
}

// Process Resources
$resourcesPath = $basePath . '/app/Filament/Admin/Resources';
if (is_dir($resourcesPath)) {
    $resources = glob($resourcesPath . '/*Resource.php');
    foreach ($resources as $resource) {
        $result = updateNavigationGroup($resource, $groupMapping, $specialCases);
        if ($result) {
            $updatedFiles[] = $result;
        }
    }
}

// Process Pages
$pagesPath = $basePath . '/app/Filament/Admin/Pages';
if (is_dir($pagesPath)) {
    $pages = glob($pagesPath . '/*.php');
    foreach ($pages as $page) {
        $result = updateNavigationGroup($page, $groupMapping, $specialCases);
        if ($result) {
            $updatedFiles[] = $result;
        }
    }
}

// Output results
echo "Navigation Group Update Results\n";
echo "==============================\n\n";

if (count($updatedFiles) > 0) {
    echo "Updated " . count($updatedFiles) . " files:\n\n";
    foreach ($updatedFiles as $update) {
        echo "- " . basename($update['file']) . "\n";
        echo "  Old: {$update['oldGroup']}\n";
        echo "  New: {$update['newGroup']}\n\n";
    }
} else {
    echo "No files needed updating.\n";
}

if (count($errors) > 0) {
    echo "\nErrors:\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
}

echo "\nDone!\n";