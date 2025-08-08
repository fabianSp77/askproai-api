<?php

// Optimize menu sorting for better workflow

$resourcesPath = '/var/www/api-gateway/app/Filament/Admin/Resources';

// New optimized sorting
$newSorting = [
    // Tagesgeschäft (100-199) - bleibt oben!
    'CallResource.php' => 110,
    'AppointmentResource.php' => 120, 
    'CustomerResource.php' => 130,
    
    // Firmenverwaltung (200-299)
    'CompanyResource.php' => 210,
    'BranchResource.php' => 220,
    'StaffResource.php' => 230,
    'ServiceResource.php' => 240,
    'MasterServiceResource.php' => 250,
    
    // Kalender & Buchung (300-399) - VORHER war 400er
    'CalcomEventTypeResource.php' => 310,
    'WorkingHourResource.php' => 320,
    'UnifiedEventTypeResource.php' => 330,
    'IntegrationResource.php' => 340,
    
    // AI & Telefonie (400-499) - VORHER war 300er  
    'RetellAgentResource.php' => 410,
    'PhoneNumberResource.php' => 420,
    'CallCampaignResource.php' => 430,
    'PromptTemplateResource.php' => 440,
    
    // Abrechnung (500-599)
    'InvoiceResource.php' => 510,
    'PrepaidBalanceResource.php' => 520,
    'BillingPeriodResource.php' => 530,
    'SubscriptionResource.php' => 540,
    'CompanyPricingResource.php' => 550,
    
    // Partner & Reseller (600-699)
    'ResellerResource.php' => 610,
    'PricingTierResource.php' => 620,
    'PortalUserResource.php' => 630,
    
    // System (700-799)
    'UserResource.php' => 710,
    'TenantResource.php' => 720,
    'ErrorCatalogResource.php' => 730,
    'GdprRequestResource.php' => 740,
];

$updated = 0;

foreach ($newSorting as $filename => $newSort) {
    $filepath = $resourcesPath . '/' . $filename;
    
    if (!file_exists($filepath)) {
        echo "⚠️  $filename not found\n";
        continue;
    }
    
    $content = file_get_contents($filepath);
    
    // Update navigationSort
    $pattern = '/protected static \?\s*int \$navigationSort\s*=\s*\d+\s*;/';
    $replacement = "protected static ?int \$navigationSort = $newSort;";
    
    if (preg_match($pattern, $content)) {
        $newContent = preg_replace($pattern, $replacement, $content);
        if ($newContent !== $content) {
            file_put_contents($filepath, $newContent);
            echo "✅ $filename: sort → $newSort\n";
            $updated++;
        }
    }
}

echo "\n📊 Updated $updated files\n\n";

// Now update the group keys for the moved items
$groupUpdates = [
    'CalcomEventTypeResource.php' => 'calendar_booking',
    'WorkingHourResource.php' => 'calendar_booking',
    'UnifiedEventTypeResource.php' => 'calendar_booking',
    'IntegrationResource.php' => 'calendar_booking',
    'RetellAgentResource.php' => 'ai_telephony',
    'PhoneNumberResource.php' => 'ai_telephony',
    'CallCampaignResource.php' => 'ai_telephony',
    'PromptTemplateResource.php' => 'ai_telephony',
];

foreach ($groupUpdates as $filename => $newGroup) {
    $filepath = $resourcesPath . '/' . $filename;
    
    if (!file_exists($filepath)) {
        continue;
    }
    
    $content = file_get_contents($filepath);
    
    // Update navigationGroupKey
    $pattern = '/protected static \?\s*string \$navigationGroupKey\s*=\s*[\'"][^\'"]+[\'"];/';
    $replacement = "protected static ?string \$navigationGroupKey = '$newGroup';";
    
    if (preg_match($pattern, $content)) {
        $newContent = preg_replace($pattern, $replacement, $content);
        if ($newContent !== $content) {
            file_put_contents($filepath, $newContent);
            echo "✅ $filename: group → $newGroup\n";
        }
    }
}

// Update translations to reorder groups
$translationFile = '/var/www/api-gateway/resources/lang/de/admin.php';
$translations = include $translationFile;

// Reorder navigation groups (this affects display order in some themes)
$translations['navigation'] = [
    // Primary groups in order
    'dashboards' => '📊 Dashboards',
    'daily_business' => '🎯 Tagesgeschäft',
    'company_management' => '🏢 Firmenverwaltung',
    'calendar_booking' => '📅 Kalender & Buchung',
    'ai_telephony' => '🤖 AI & Telefonie',
    'billing' => '💰 Abrechnung',
    'partners' => '👥 Partner & Reseller',
    'system' => '⚙️ System',
    
    // Keep old keys for compatibility
    'daily_operations' => 'Täglicher Betrieb',
    'customer_management' => 'Kundenverwaltung',
    'company_structure' => 'Unternehmensstruktur',
    'personnel_services' => 'Personal & Services',
    'integrations' => 'Integrationen',
    'billing_portal' => 'Billing & Portal',
    'business' => 'Business',
    'campaigns' => 'Kampagnen',
    'admin' => 'Administration',
    'administration' => 'Verwaltung',
    'system_monitoring' => 'System & Monitoring',
    'settings' => 'Einstellungen',
    'development' => 'Entwicklung',
];

// Save translations
$content = "<?php\n\nreturn " . var_export($translations, true) . ";\n";
file_put_contents($translationFile, $content);
file_put_contents('/var/www/api-gateway/lang/de/admin.php', $content);

echo "\n✅ Menu sorting optimized!\n";
echo "\nChanges:\n";
echo "- Kalender & Buchung moved BEFORE AI & Telefonie (more frequently used)\n";
echo "- Added 'dashboards' group for future dashboard menu items\n";
echo "- All sort orders optimized for workflow\n";