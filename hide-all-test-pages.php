<?php

// Verstecke ALLE Test- und redundanten Pages auf einmal

$pagesPath = '/var/www/api-gateway/app/Filament/Admin/Pages';

$pagesToHide = [
    // Test Pages
    'TestLivewirePage.php',
    'TestMinimalDashboard.php',
    'WidgetTestPage.php',
    'SystemDebug.php',
    'WorkingCalls.php',
    'SimpleCalls.php',
    
    // Redundante Dashboards
    'SimplestDashboard.php',
    'OptimizedDashboard.php',
    'PerformanceOptimizedDashboard.php',
    'OptimizedOperationalDashboard.php',
    'CustomerBillingDashboard.php',
    'Dashboard.php', // Redirect only
    
    // Alte/Redundante Pages
    'QuickSetupRedirect.php',
    'RetellAgentEditor.php',
    'RetellAgentEditorNext.php',
    'RetellAgentEditorUnified.php',
    'SimpleOnboarding.php',
    'SystemHealthBasic.php',
    'SystemImprovements.php',
    'SystemMonitoring.php',
    'BasicCompanyConfig.php',
    'CalcomSyncStatus.php',
    'CompanyConfigStatus.php',
    'QuantumSystemMonitoring.php',
    'RetellAgentImportWizard.php',
    'RetellConfigurationCenter.php',
    'StaffEventAssignment.php', // Use Modern version instead
    'ApiHealthMonitor.php',
    'WebhookMonitor.php',
    'OnboardingWizardPage.php', // Duplicate of QuickSetupWizardV2
    
    // MCP Pages (nur für Developer)
    'MCPControlCenter.php',
    'MCPDashboard.php', 
    'MCPServerDashboard.php',
];

$hidden = 0;

foreach ($pagesToHide as $filename) {
    $filepath = $pagesPath . '/' . $filename;
    
    if (!file_exists($filepath)) {
        continue;
    }
    
    $content = file_get_contents($filepath);
    
    // Add or update shouldRegisterNavigation to return false
    if (!preg_match('/public static function shouldRegisterNavigation\(\)/', $content)) {
        // Add the method
        $content = preg_replace(
            '/(class\s+\w+\s+extends\s+\w+\s*\{)/',
            "$1\n\n    public static function shouldRegisterNavigation(): bool\n    {\n        return false; // Hidden from navigation\n    }",
            $content,
            1
        );
    } else {
        // Update existing method
        $content = preg_replace(
            '/public static function shouldRegisterNavigation\(\)[^}]+\{[^}]+\}/',
            'public static function shouldRegisterNavigation(): bool
    {
        return false; // Hidden from navigation
    }',
            $content
        );
    }
    
    file_put_contents($filepath, $content);
    echo "🚫 $filename hidden\n";
    $hidden++;
}

echo "\n✅ Hidden $hidden pages from navigation\n";

// Verify main dashboards are visible
$mainDashboards = [
    'SimpleDashboard.php' => 'Übersicht',
    'EventAnalyticsDashboard.php' => 'Analytics & Trends',
    'AICallCenter.php' => 'AI Operations',
    'SystemMonitoringDashboard.php' => 'System Monitor'
];

echo "\n📊 Verifying main dashboards are visible:\n";
foreach ($mainDashboards as $filename => $name) {
    $filepath = $pagesPath . '/' . $filename;
    
    if (!file_exists($filepath)) {
        echo "   ❌ $name ($filename) - NOT FOUND\n";
        continue;
    }
    
    $content = file_get_contents($filepath);
    
    // Check if has shouldRegisterNavigation
    if (preg_match('/public static function shouldRegisterNavigation\(\)[^}]+return\s+false/', $content)) {
        echo "   ⚠️  $name - has shouldRegisterNavigation returning false\n";
        
        // Fix it - either remove or make it return true
        $content = preg_replace(
            '/public static function shouldRegisterNavigation\(\)[^}]+\{[^}]+\}/',
            'public static function shouldRegisterNavigation(): bool
    {
        return true; // Visible in navigation
    }',
            $content
        );
        file_put_contents($filepath, $content);
        echo "      ✅ Fixed - now visible\n";
    } else {
        echo "   ✅ $name - visible\n";
    }
}

echo "\n🎯 Navigation should now show only essential pages!\n";