<?php

// Add important dashboards to navigation menu

$pagesPath = '/var/www/api-gateway/app/Filament/Admin/Pages';

// Most important dashboards that should be in menu
$dashboardsToAdd = [
    'Dashboard.php' => [
        'group' => 'dashboards',
        'sort' => 10,
        'label' => 'Hauptdashboard',
        'icon' => 'heroicon-o-home',
    ],
    'AICallCenter.php' => [
        'group' => 'dashboards', 
        'sort' => 20,
        'label' => 'AI Call Center',
        'icon' => 'heroicon-o-phone',
    ],
    'SystemMonitoringDashboard.php' => [
        'group' => 'dashboards',
        'sort' => 30,
        'label' => 'System-Überwachung',
        'icon' => 'heroicon-o-computer-desktop',
    ],
    'SimpleDashboard.php' => [
        'group' => 'dashboards',
        'sort' => 40,
        'label' => 'Einfaches Dashboard',
        'icon' => 'heroicon-o-squares-2x2',
    ],
    'RetellUltimateControlCenter.php' => [
        'group' => 'ai_telephony',
        'sort' => 450,
        'label' => 'Retell Control Center',
        'icon' => 'heroicon-o-cog',
    ],
];

$updated = 0;

foreach ($dashboardsToAdd as $filename => $config) {
    $filepath = $pagesPath . '/' . $filename;
    
    if (!file_exists($filepath)) {
        echo "⚠️  $filename not found\n";
        continue;
    }
    
    $content = file_get_contents($filepath);
    $changes = [];
    
    // Check if it already has navigation settings
    $hasNavGroup = preg_match('/protected static \?\s*string \$navigationGroup/', $content);
    $hasNavSort = preg_match('/protected static \?\s*int \$navigationSort/', $content);
    $hasNavLabel = preg_match('/protected static \?\s*string \$navigationLabel/', $content);
    $hasNavIcon = preg_match('/protected static \?\s*string \$navigationIcon/', $content);
    
    // Add navigation group
    if (!$hasNavGroup) {
        $pattern = '/(class\s+\w+\s+extends\s+\w+\s*\{)/';
        $replacement = "$1\n    protected static ?string \$navigationGroup = '{$config['group']}';";
        $content = preg_replace($pattern, $replacement, $content, 1);
        $changes[] = "added navigationGroup";
    }
    
    // Add navigation sort
    if (!$hasNavSort) {
        $pattern = '/(protected static \?\s*string \$navigationGroup[^;]+;)/';
        if (preg_match($pattern, $content)) {
            $replacement = "$1\n    protected static ?int \$navigationSort = {$config['sort']};";
            $content = preg_replace($pattern, $replacement, $content, 1);
        } else {
            $pattern = '/(class\s+\w+\s+extends\s+\w+\s*\{)/';
            $replacement = "$1\n    protected static ?int \$navigationSort = {$config['sort']};";
            $content = preg_replace($pattern, $replacement, $content, 1);
        }
        $changes[] = "added navigationSort";
    }
    
    // Add navigation label
    if (!$hasNavLabel) {
        $pattern = '/(protected static \?\s*int \$navigationSort[^;]+;|protected static \?\s*string \$navigationGroup[^;]+;)/';
        if (preg_match($pattern, $content)) {
            $replacement = "$1\n    protected static ?string \$navigationLabel = '{$config['label']}';";
            $content = preg_replace($pattern, $replacement, $content, 1);
        }
        $changes[] = "added navigationLabel";
    }
    
    // Add navigation icon
    if (!$hasNavIcon) {
        $pattern = '/(protected static \?\s*string \$navigationLabel[^;]+;|protected static \?\s*int \$navigationSort[^;]+;|protected static \?\s*string \$navigationGroup[^;]+;)/';
        if (preg_match($pattern, $content)) {
            $replacement = "$1\n    protected static ?string \$navigationIcon = '{$config['icon']}';";
            $content = preg_replace($pattern, $replacement, $content, 1);
        }
        $changes[] = "added navigationIcon";
    }
    
    if (!empty($changes)) {
        file_put_contents($filepath, $content);
        echo "✅ $filename: " . implode(', ', $changes) . "\n";
        $updated++;
    } else {
        echo "   $filename: already configured\n";
    }
}

echo "\n📊 Updated $updated dashboard files\n";

// Check if Dashboard page exists and is configured
$dashboardFile = $pagesPath . '/Dashboard.php';
if (file_exists($dashboardFile)) {
    $content = file_get_contents($dashboardFile);
    
    // Make sure it has proper visibility
    if (!preg_match('/public static function shouldRegisterNavigation\(\)/', $content)) {
        echo "\n✅ Dashboard.php is visible in navigation\n";
    } else {
        echo "\n⚠️  Dashboard.php might have custom navigation logic\n";
    }
}