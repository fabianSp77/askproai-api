<?php

// Reorganize navigation for AskProAI

$resourcesPath = '/var/www/api-gateway/app/Filament/Admin/Resources';

// New optimized structure
$navigationStructure = [
    // Group 1: Tagesgeschäft (Sort 100-199)
    'CallResource.php' => [
        'group' => 'daily_business',
        'sort' => 110,
    ],
    'AppointmentResource.php' => [
        'group' => 'daily_business', 
        'sort' => 120,
    ],
    'CustomerResource.php' => [
        'group' => 'daily_business',
        'sort' => 130,
    ],
    
    // Group 2: Firmenverwaltung (Sort 200-299)
    'CompanyResource.php' => [
        'group' => 'company_management',
        'sort' => 210,
    ],
    'BranchResource.php' => [
        'group' => 'company_management',
        'sort' => 220,
    ],
    'StaffResource.php' => [
        'group' => 'company_management',
        'sort' => 230,
    ],
    'ServiceResource.php' => [
        'group' => 'company_management',
        'sort' => 240,
    ],
    'MasterServiceResource.php' => [
        'group' => 'company_management',
        'sort' => 250,
    ],
    
    // Group 3: AI & Telefonie (Sort 300-399)
    'RetellAgentResource.php' => [
        'group' => 'ai_telephony',
        'sort' => 310,
    ],
    'CallCampaignResource.php' => [
        'group' => 'ai_telephony',
        'sort' => 320,
    ],
    'PhoneNumberResource.php' => [
        'group' => 'ai_telephony',
        'sort' => 330,
    ],
    'PromptTemplateResource.php' => [
        'group' => 'ai_telephony',
        'sort' => 340,
    ],
    
    // Group 4: Kalender & Buchung (Sort 400-499)
    'CalcomEventTypeResource.php' => [
        'group' => 'calendar_booking',
        'sort' => 410,
    ],
    'UnifiedEventTypeResource.php' => [
        'group' => 'calendar_booking',
        'sort' => 420,
    ],
    'WorkingHourResource.php' => [
        'group' => 'calendar_booking',
        'sort' => 430,
    ],
    'WorkingHoursResource.php' => [
        'skip' => true, // Duplicate
    ],
    'IntegrationResource.php' => [
        'group' => 'calendar_booking',
        'sort' => 440,
    ],
    
    // Group 5: Abrechnung (Sort 500-599)
    'InvoiceResource.php' => [
        'group' => 'billing',
        'sort' => 510,
    ],
    'PrepaidBalanceResource.php' => [
        'group' => 'billing',
        'sort' => 520,
    ],
    'BillingPeriodResource.php' => [
        'group' => 'billing',
        'sort' => 530,
    ],
    'SubscriptionResource.php' => [
        'group' => 'billing',
        'sort' => 540,
    ],
    'CompanyPricingResource.php' => [
        'group' => 'billing',
        'sort' => 550,
    ],
    
    // Group 6: Partner & Reseller (Sort 600-699)
    'ResellerResource.php' => [
        'group' => 'partners',
        'sort' => 610,
    ],
    'PricingTierResource.php' => [
        'group' => 'partners',
        'sort' => 620,
    ],
    'PortalUserResource.php' => [
        'group' => 'partners',
        'sort' => 630,
    ],
    
    // Group 7: System (Sort 700-799)
    'UserResource.php' => [
        'group' => 'system',
        'sort' => 710,
    ],
    'TenantResource.php' => [
        'group' => 'system',
        'sort' => 720,
    ],
    'ErrorCatalogResource.php' => [
        'group' => 'system',
        'sort' => 730,
    ],
    'GdprRequestResource.php' => [
        'group' => 'system',
        'sort' => 740,
    ],
    
    // Skip these (test/duplicate resources)
    'AppointmentResourceUpdated.php' => [
        'skip' => true,
    ],
    'DummyCompanyResource.php' => [
        'skip' => true,
    ],
];

$updated = 0;
$skipped = 0;

foreach ($navigationStructure as $filename => $config) {
    $filepath = $resourcesPath . '/' . $filename;
    
    if (!file_exists($filepath)) {
        echo "⚠️  $filename not found\n";
        continue;
    }
    
    if (isset($config['skip']) && $config['skip']) {
        echo "⏭️  Skipping $filename\n";
        $skipped++;
        continue;
    }
    
    $content = file_get_contents($filepath);
    $changes = [];
    
    // Update navigationGroupKey
    if (isset($config['group'])) {
        $pattern = '/protected static \?\s*string \$navigationGroupKey\s*=\s*[\'"][^\'"]+[\'"]\s*;/';
        $replacement = "protected static ?string \$navigationGroupKey = '{$config['group']}';";
        
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $replacement, $content);
            $changes[] = "group -> {$config['group']}";
        } else {
            // Add after $model
            $pattern = '/(protected static \?\s*string \$model\s*=\s*[^;]+;)/';
            $replacement = "$1\n\n    protected static ?string \$navigationGroupKey = '{$config['group']}';";
            $content = preg_replace($pattern, $replacement, $content, 1);
            $changes[] = "added group {$config['group']}";
        }
    }
    
    // Update navigationSort
    if (isset($config['sort'])) {
        $pattern = '/protected static \?\s*int \$navigationSort\s*=\s*\d+\s*;/';
        $replacement = "protected static ?int \$navigationSort = {$config['sort']};";
        
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $replacement, $content);
            $changes[] = "sort -> {$config['sort']}";
        } else {
            // Add after navigationGroupKey or model
            $pattern = '/(protected static \?\s*string \$navigationGroupKey\s*=\s*[^;]+;|protected static \?\s*string \$model\s*=\s*[^;]+;)/';
            $replacement = "$1\n\n    protected static ?int \$navigationSort = {$config['sort']};";
            $content = preg_replace($pattern, $replacement, $content, 1);
            $changes[] = "added sort {$config['sort']}";
        }
    }
    
    if (!empty($changes)) {
        file_put_contents($filepath, $content);
        echo "✅ $filename: " . implode(', ', $changes) . "\n";
        $updated++;
    } else {
        echo "   $filename: no changes needed\n";
    }
}

echo "\n📊 Summary: $updated files updated, $skipped files skipped\n";

// Now update the translation file
$translationFile = '/var/www/api-gateway/resources/lang/de/admin.php';
$translations = include $translationFile;

// Update navigation groups
$translations['navigation'] = [
    // Main groups (in display order)
    'daily_business' => '🎯 Tagesgeschäft',
    'company_management' => '🏢 Firmenverwaltung', 
    'ai_telephony' => '🤖 AI & Telefonie',
    'calendar_booking' => '📅 Kalender & Buchung',
    'billing' => '💰 Abrechnung',
    'partners' => '👥 Partner & Reseller',
    'system' => '⚙️ System',
    
    // Keep old keys for backward compatibility
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

// Save updated translations
$content = "<?php\n\nreturn " . var_export($translations, true) . ";\n";
file_put_contents($translationFile, $content);
file_put_contents('/var/www/api-gateway/lang/de/admin.php', $content);

echo "\n✅ Translations updated with new groups and emojis\n";