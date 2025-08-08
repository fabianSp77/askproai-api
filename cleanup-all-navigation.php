<?php

// Vollständige Bereinigung und Vereinheitlichung der Navigation

$pagesPath = '/var/www/api-gateway/app/Filament/Admin/Pages';

// ALLE Pages mit korrekten Emoji-Gruppen zuordnen
$pageMapping = [
    // 📊 Dashboards (Sort 1-99)
    'Dashboard.php' => ['group' => '"📊 Dashboards"', 'sort' => 10, 'label' => '"Hauptdashboard"'],
    'SimpleDashboard.php' => ['group' => '"📊 Dashboards"', 'sort' => 20, 'label' => '"Einfaches Dashboard"'],
    'SimplestDashboard.php' => ['group' => '"📊 Dashboards"', 'sort' => 25, 'label' => '"Einfachstes Dashboard"'],
    'AICallCenter.php' => ['group' => '"📊 Dashboards"', 'sort' => 30, 'label' => '"AI Call Center"'],
    'SystemMonitoringDashboard.php' => ['group' => '"📊 Dashboards"', 'sort' => 40, 'label' => '"System-Überwachung"'],
    'OptimizedDashboard.php' => ['group' => '"📊 Dashboards"', 'sort' => 50, 'label' => '"Optimiertes Dashboard"'],
    'EventAnalyticsDashboard.php' => ['group' => '"📊 Dashboards"', 'sort' => 60, 'label' => '"Event Analytics"'],
    
    // 🎯 Tagesgeschäft - KEINE Pages, nur Resources
    
    // 🏢 Firmenverwaltung (Sort 200-299)
    'BusinessPortalAdmin.php' => ['group' => '"🏢 Firmenverwaltung"', 'sort' => 260, 'label' => '"Kundenportal Verwaltung"'],
    'QuickSetupWizardV2.php' => ['group' => '"🏢 Firmenverwaltung"', 'sort' => 270, 'label' => '"🚀 Setup Wizard"'],
    'OnboardingWizardPage.php' => ['group' => '"🏢 Firmenverwaltung"', 'sort' => 280, 'label' => '"🚀 5-Min Onboarding"'],
    'CompanyIntegrationPortal.php' => ['group' => '"🏢 Firmenverwaltung"', 'sort' => 290, 'label' => '"Integration Setup"'],
    
    // 📅 Kalender & Buchung (Sort 300-399)
    'EventTypeSetupWizard.php' => ['group' => '"📅 Kalender & Buchung"', 'sort' => 350, 'label' => '"Event-Type Konfiguration"'],
    'EventTypeImportWizard.php' => ['group' => '"📅 Kalender & Buchung"', 'sort' => 360, 'label' => '"Event-Type Import"'],
    'StaffEventAssignmentModern.php' => ['group' => '"📅 Kalender & Buchung"', 'sort' => 370, 'label' => '"Mitarbeiter-Zuordnung"'],
    'QuickSetupWizard.php' => ['group' => '"📅 Kalender & Buchung"', 'sort' => 380, 'label' => '"Schnell-Setup"'],
    
    // 🤖 AI & Telefonie (Sort 400-499)
    'RetellUltimateControlCenter.php' => ['group' => '"🤖 AI & Telefonie"', 'sort' => 450, 'label' => '"Retell Control Center"'],
    'KnowledgeBaseManager.php' => ['group' => '"🤖 AI & Telefonie"', 'sort' => 460, 'label' => '"Wissensdatenbank"'],
    
    // 💰 Abrechnung (Sort 500-599)
    'PricingCalculator.php' => ['group' => '"💰 Abrechnung"', 'sort' => 560, 'label' => '"Preiskalkulator"'],
    'StripePaymentLinks.php' => ['group' => '"💰 Abrechnung"', 'sort' => 570, 'label' => '"Payment Links"'],
    
    // 👥 Partner & Reseller (Sort 600-699)
    'ResellerOverview.php' => ['group' => '"👥 Partner & Reseller"', 'sort' => 605, 'label' => '"Reseller Übersicht"'],
    'CustomerPortalManagement.php' => ['group' => '"👥 Partner & Reseller"', 'sort' => 640, 'label' => '"Kundenportal"'],
    
    // ⚙️ System (Sort 700-899)
    'UserLanguageSettings.php' => ['group' => '"⚙️ System"', 'sort' => 745, 'label' => '"Spracheinstellungen"'],
    'LanguageSettings.php' => ['group' => '"⚙️ System"', 'sort' => 746, 'label' => '"Sprach-Konfiguration"'],
    'MCPControlCenter.php' => ['group' => '"⚙️ System"', 'sort' => 750, 'label' => '"MCP Control Center"'],
    'MCPDashboard.php' => ['group' => '"⚙️ System"', 'sort' => 755, 'label' => '"MCP Dashboard"'],
    'MCPServerDashboard.php' => ['group' => '"⚙️ System"', 'sort' => 760, 'label' => '"MCP Server Dashboard"'],
    'DataSync.php' => ['group' => '"⚙️ System"', 'sort' => 770, 'label' => '"Daten synchronisieren"'],
    'SimpleSyncManager.php' => ['group' => '"⚙️ System"', 'sort' => 775, 'label' => '"Daten abrufen"'],
    'IntelligentSyncManager.php' => ['group' => '"⚙️ System"', 'sort' => 780, 'label' => '"Intelligente Synchronisation"'],
    'DeploymentMonitor.php' => ['group' => '"⚙️ System"', 'sort' => 810, 'label' => '"Deployment Monitor"'],
    'FeatureFlagManager.php' => ['group' => '"⚙️ System"', 'sort' => 820, 'label' => '"Feature Flags"'],
    'BackupRestorePoints.php' => ['group' => '"⚙️ System"', 'sort' => 830, 'label' => '"Backup Restore Points"'],
    'ApiHealthMonitor.php' => ['group' => '"⚙️ System"', 'sort' => 840, 'label' => '"API Monitor"'],
    'WebhookMonitor.php' => ['group' => '"⚙️ System"', 'sort' => 850, 'label' => '"Webhook Monitor"'],
    'WebhookAnalysis.php' => ['group' => '"⚙️ System"', 'sort' => 860, 'label' => '"Webhook Analyse"'],
    'DocumentationPage.php' => ['group' => '"⚙️ System"', 'sort' => 890, 'label' => '"Dokumentation"'],
    
    // Test-Pages komplett ausblenden
    'TestLivewirePage.php' => ['hide' => true],
    'TestMinimalDashboard.php' => ['hide' => true],
    'WidgetTestPage.php' => ['hide' => true],
    'SystemDebug.php' => ['hide' => true],
    'WorkingCalls.php' => ['hide' => true],
    'SimpleCalls.php' => ['hide' => true],
];

$updated = 0;
$hidden = 0;

foreach ($pageMapping as $filename => $config) {
    $filepath = $pagesPath . '/' . $filename;
    
    if (!file_exists($filepath)) {
        continue;
    }
    
    $content = file_get_contents($filepath);
    $changes = [];
    
    // Hide test pages
    if (isset($config['hide']) && $config['hide']) {
        if (!preg_match('/public static function shouldRegisterNavigation\(\)/', $content)) {
            $content = preg_replace(
                '/(class\s+\w+\s+extends\s+\w+\s*\{)/',
                "$1\n\n    public static function shouldRegisterNavigation(): bool\n    {\n        return false;\n    }",
                $content,
                1
            );
        } else {
            $content = preg_replace(
                '/public static function shouldRegisterNavigation\(\)[^}]+\{[^}]+\}/',
                'public static function shouldRegisterNavigation(): bool
    {
        return false;
    }',
                $content
            );
        }
        file_put_contents($filepath, $content);
        echo "🚫 Hidden: $filename\n";
        $hidden++;
        continue;
    }
    
    // Update navigation group
    if (isset($config['group'])) {
        if (preg_match('/protected static \?\s*string \$navigationGroup\s*=/', $content)) {
            $content = preg_replace(
                '/protected static \?\s*string \$navigationGroup\s*=\s*[^;]+;/',
                'protected static ?string $navigationGroup = ' . $config['group'] . ';',
                $content
            );
            $changes[] = "group";
        } else {
            $content = preg_replace(
                '/(class\s+\w+\s+extends\s+\w+\s*\{)/',
                "$1\n    protected static ?string \$navigationGroup = {$config['group']};",
                $content,
                1
            );
            $changes[] = "group";
        }
    }
    
    // Update navigation sort
    if (isset($config['sort'])) {
        if (preg_match('/protected static \?\s*int \$navigationSort\s*=/', $content)) {
            $content = preg_replace(
                '/protected static \?\s*int \$navigationSort\s*=\s*\d+\s*;/',
                'protected static ?int $navigationSort = ' . $config['sort'] . ';',
                $content
            );
            $changes[] = "sort";
        } else {
            // Add after group if exists
            if (preg_match('/protected static \?\s*string \$navigationGroup/', $content)) {
                $content = preg_replace(
                    '/(protected static \?\s*string \$navigationGroup[^;]+;)/',
                    "$1\n    protected static ?int \$navigationSort = {$config['sort']};",
                    $content,
                    1
                );
            } else {
                $content = preg_replace(
                    '/(class\s+\w+\s+extends\s+\w+\s*\{)/',
                    "$1\n    protected static ?int \$navigationSort = {$config['sort']};",
                    $content,
                    1
                );
            }
            $changes[] = "sort";
        }
    }
    
    // Update navigation label
    if (isset($config['label'])) {
        if (preg_match('/protected static \?\s*string \$navigationLabel\s*=/', $content)) {
            $content = preg_replace(
                '/protected static \?\s*string \$navigationLabel\s*=\s*[^;]+;/',
                'protected static ?string $navigationLabel = ' . $config['label'] . ';',
                $content
            );
            $changes[] = "label";
        } else {
            // Add after sort or group
            if (preg_match('/protected static \?\s*int \$navigationSort/', $content)) {
                $content = preg_replace(
                    '/(protected static \?\s*int \$navigationSort[^;]+;)/',
                    "$1\n    protected static ?string \$navigationLabel = {$config['label']};",
                    $content,
                    1
                );
            } elseif (preg_match('/protected static \?\s*string \$navigationGroup/', $content)) {
                $content = preg_replace(
                    '/(protected static \?\s*string \$navigationGroup[^;]+;)/',
                    "$1\n    protected static ?string \$navigationLabel = {$config['label']};",
                    $content,
                    1
                );
            }
            $changes[] = "label";
        }
    }
    
    if (!empty($changes)) {
        file_put_contents($filepath, $content);
        echo "✅ $filename: " . implode(', ', $changes) . "\n";
        $updated++;
    }
}

echo "\n📊 Summary:\n";
echo "- Updated: $updated pages\n";
echo "- Hidden: $hidden test pages\n";
echo "\n✨ Navigation bereinigt!\n";