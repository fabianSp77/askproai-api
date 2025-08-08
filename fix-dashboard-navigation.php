<?php

// Fix dashboard navigation - make all dashboards appear in one group

$pagesPath = '/var/www/api-gateway/app/Filament/Admin/Pages';

// Main dashboards that should be visible
$dashboardsToFix = [
    'Dashboard.php' => [
        'group' => '"📊 Dashboards"',
        'sort' => 10,
        'label' => '"Hauptdashboard"'
    ],
    'SimpleDashboard.php' => [
        'group' => '"📊 Dashboards"', 
        'sort' => 20,
        'label' => '"Einfaches Dashboard"'
    ],
    'AICallCenter.php' => [
        'group' => '"📊 Dashboards"',
        'sort' => 30,
        'label' => '"AI Call Center"'
    ],
    'SystemMonitoringDashboard.php' => [
        'group' => '"📊 Dashboards"',
        'sort' => 40,
        'label' => '"System-Überwachung"'
    ],
    'OptimizedDashboard.php' => [
        'group' => '"📊 Dashboards"',
        'sort' => 50,
        'label' => '"Optimiertes Dashboard"'
    ],
    'EventAnalyticsDashboard.php' => [
        'group' => '"📊 Dashboards"',
        'sort' => 60,
        'label' => '"Event Analytics"'
    ],
];

// Test pages that should be hidden from navigation
$testPagesToHide = [
    'TestLivewirePage.php',
    'TestMinimalDashboard.php',
    'WidgetTestPage.php',
    'SystemDebug.php'
];

$updated = 0;
$hidden = 0;

// Fix main dashboards
foreach ($dashboardsToFix as $filename => $config) {
    $filepath = $pagesPath . '/' . $filename;
    
    if (!file_exists($filepath)) {
        echo "⚠️  $filename not found\n";
        continue;
    }
    
    $content = file_get_contents($filepath);
    $changes = [];
    
    // Update or add navigationGroup
    if (preg_match('/protected static \?\s*string \$navigationGroup\s*=\s*[^;]+;/', $content)) {
        // Replace existing
        $content = preg_replace(
            '/protected static \?\s*string \$navigationGroup\s*=\s*[^;]+;/',
            'protected static ?string $navigationGroup = ' . $config['group'] . ';',
            $content
        );
        $changes[] = "updated navigationGroup";
    } else {
        // Add new
        $content = preg_replace(
            '/(class\s+\w+\s+extends\s+\w+\s*\{)/',
            "$1\n    protected static ?string \$navigationGroup = {$config['group']};",
            $content,
            1
        );
        $changes[] = "added navigationGroup";
    }
    
    // Update or add navigationSort
    if (preg_match('/protected static \?\s*int \$navigationSort\s*=\s*\d+\s*;/', $content)) {
        $content = preg_replace(
            '/protected static \?\s*int \$navigationSort\s*=\s*\d+\s*;/',
            'protected static ?int $navigationSort = ' . $config['sort'] . ';',
            $content
        );
        $changes[] = "updated navigationSort";
    } else {
        $content = preg_replace(
            '/(protected static \?\s*string \$navigationGroup[^;]+;)/',
            "$1\n    protected static ?int \$navigationSort = {$config['sort']};",
            $content,
            1
        );
        $changes[] = "added navigationSort";
    }
    
    // Update or add navigationLabel
    if (preg_match('/protected static \?\s*string \$navigationLabel\s*=\s*[^;]+;/', $content)) {
        $content = preg_replace(
            '/protected static \?\s*string \$navigationLabel\s*=\s*[^;]+;/',
            'protected static ?string $navigationLabel = ' . $config['label'] . ';',
            $content
        );
        $changes[] = "updated navigationLabel";
    } else {
        $content = preg_replace(
            '/(protected static \?\s*int \$navigationSort[^;]+;|protected static \?\s*string \$navigationGroup[^;]+;)/',
            "$1\n    protected static ?string \$navigationLabel = {$config['label']};",
            $content,
            1
        );
        $changes[] = "added navigationLabel";
    }
    
    // Make sure it's visible in navigation
    if (preg_match('/public static function shouldRegisterNavigation\(\)[^}]+return\s+false/', $content)) {
        $content = preg_replace(
            '/public static function shouldRegisterNavigation\(\)[^}]+return\s+false/',
            'public static function shouldRegisterNavigation(): bool
    {
        return true',
            $content
        );
        $changes[] = "enabled navigation";
    }
    
    if (!empty($changes)) {
        file_put_contents($filepath, $content);
        echo "✅ $filename: " . implode(', ', $changes) . "\n";
        $updated++;
    } else {
        echo "   $filename: already configured\n";
    }
}

// Hide test pages
foreach ($testPagesToHide as $filename) {
    $filepath = $pagesPath . '/' . $filename;
    
    if (!file_exists($filepath)) {
        continue;
    }
    
    $content = file_get_contents($filepath);
    
    // Check if shouldRegisterNavigation exists
    if (preg_match('/public static function shouldRegisterNavigation\(\)/', $content)) {
        // Make it return false
        $content = preg_replace(
            '/public static function shouldRegisterNavigation\(\)[^}]+\{[^}]+\}/',
            'public static function shouldRegisterNavigation(): bool
    {
        return false;
    }',
            $content
        );
    } else {
        // Add method to hide from navigation
        $content = preg_replace(
            '/(class\s+\w+\s+extends\s+\w+\s*\{)/',
            "$1\n\n    public static function shouldRegisterNavigation(): bool\n    {\n        return false;\n    }",
            $content,
            1
        );
    }
    
    file_put_contents($filepath, $content);
    echo "🚫 Hidden: $filename\n";
    $hidden++;
}

// Also fix some other important pages
$otherPages = [
    'RetellUltimateControlCenter.php' => [
        'group' => '"🤖 AI & Telefonie"',
        'sort' => 450,
        'label' => '"Retell Control Center"'
    ],
    'BusinessPortalAdmin.php' => [
        'group' => '"🏢 Firmenverwaltung"',
        'sort' => 260,
        'label' => '"Kundenportal Verwaltung"'
    ],
    'ResellerOverview.php' => [
        'group' => '"👥 Partner & Reseller"',
        'sort' => 605,
        'label' => '"Reseller Übersicht"'
    ],
    'MCPControlCenter.php' => [
        'group' => '"⚙️ System"',
        'sort' => 750,
        'label' => '"MCP Control Center"'
    ],
    'MCPDashboard.php' => [
        'group' => '"⚙️ System"',
        'sort' => 755,
        'label' => '"MCP Dashboard"'
    ],
    'MCPServerDashboard.php' => [
        'group' => '"⚙️ System"',
        'sort' => 760,
        'label' => '"MCP Server Dashboard"'
    ],
];

foreach ($otherPages as $filename => $config) {
    $filepath = $pagesPath . '/' . $filename;
    
    if (!file_exists($filepath)) {
        continue;
    }
    
    $content = file_get_contents($filepath);
    
    // Update navigation group with emoji
    if (preg_match('/protected static \?\s*string \$navigationGroup/', $content)) {
        $content = preg_replace(
            '/protected static \?\s*string \$navigationGroup\s*=\s*[^;]+;/',
            'protected static ?string $navigationGroup = ' . $config['group'] . ';',
            $content
        );
    }
    
    // Update sort
    if (preg_match('/protected static \?\s*int \$navigationSort/', $content)) {
        $content = preg_replace(
            '/protected static \?\s*int \$navigationSort\s*=\s*\d+\s*;/',
            'protected static ?int $navigationSort = ' . $config['sort'] . ';',
            $content
        );
    }
    
    // Update label
    if (preg_match('/protected static \?\s*string \$navigationLabel/', $content)) {
        $content = preg_replace(
            '/protected static \?\s*string \$navigationLabel\s*=\s*[^;]+;/',
            'protected static ?string $navigationLabel = ' . $config['label'] . ';',
            $content
        );
    }
    
    file_put_contents($filepath, $content);
    echo "✅ $filename: updated with emoji group\n";
}

echo "\n📊 Summary:\n";
echo "- Updated $updated dashboard files\n";
echo "- Hidden $hidden test pages\n";
echo "- Fixed other pages with emoji groups\n";
echo "\n✨ All dashboards now in '📊 Dashboards' group!\n";