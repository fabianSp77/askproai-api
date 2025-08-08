<?php

// Finale Korrekturen für die Navigation

$pagesPath = '/var/www/api-gateway/app/Filament/Admin/Pages';

echo "=== FINALE NAVIGATION FIXES ===\n\n";

// 1. FIX SYSTEM MONITOR - Muss in Dashboards Gruppe sein
$systemMonitorFile = $pagesPath . '/SystemMonitoringDashboard.php';
if (file_exists($systemMonitorFile)) {
    $content = file_get_contents($systemMonitorFile);
    
    // Fix navigation group
    $content = preg_replace(
        '/protected static \?\s*string \$navigationGroup\s*=\s*[^;]+;/',
        'protected static ?string $navigationGroup = "📊 Dashboards";',
        $content
    );
    
    // Fix navigation label  
    $content = preg_replace(
        '/protected static \?\s*string \$navigationLabel\s*=\s*[^;]+;/',
        'protected static ?string $navigationLabel = "System Monitor";',
        $content
    );
    
    // Fix navigation sort
    $content = preg_replace(
        '/protected static \?\s*int \$navigationSort\s*=\s*\d+\s*;/',
        'protected static ?int $navigationSort = 40;',
        $content
    );
    
    file_put_contents($systemMonitorFile, $content);
    echo "✅ SystemMonitoringDashboard: Gruppe, Label und Sort korrigiert\n";
}

// 2. FIX AI CALL CENTER LABEL
$aiCallFile = $pagesPath . '/AICallCenter.php';
if (file_exists($aiCallFile)) {
    $content = file_get_contents($aiCallFile);
    
    // Update label to "AI Operations"
    $content = preg_replace(
        '/protected static \?\s*string \$navigationLabel\s*=\s*[^;]+;/',
        'protected static ?string $navigationLabel = "AI Operations";',
        $content
    );
    
    file_put_contents($aiCallFile, $content);
    echo "✅ AICallCenter: Label zu 'AI Operations' geändert\n";
}

// 3. VERSTECKE ALLE DOKUMENTATIONS-PAGES (außer einer)
$docsToHide = [
    'DocumentationHub.php',
    'QuickDocs.php', 
    'QuickDocsEnhanced.php',
    'QuickDocsSimple.php',
    'DocumentationPage.php', // Keep this but move to bottom
];

foreach ($docsToHide as $filename) {
    $filepath = $pagesPath . '/' . $filename;
    
    if (!file_exists($filepath)) {
        continue;
    }
    
    $content = file_get_contents($filepath);
    
    // Add or update shouldRegisterNavigation
    if (!preg_match('/public static function shouldRegisterNavigation\(\)/', $content)) {
        $content = preg_replace(
            '/(class\s+\w+\s+extends\s+\w+\s*\{)/',
            "$1\n\n    public static function shouldRegisterNavigation(): bool\n    {\n        return false; // Versteckt - redundante Dokumentation\n    }",
            $content,
            1
        );
    } else {
        $content = preg_replace(
            '/public static function shouldRegisterNavigation\(\)[^}]+\{[^}]+\}/',
            'public static function shouldRegisterNavigation(): bool
    {
        return false; // Versteckt - redundante Dokumentation
    }',
            $content
        );
    }
    
    file_put_contents($filepath, $content);
    echo "🚫 $filename versteckt\n";
}

// 4. FEATURE FLAGS WIEDER SICHTBAR MACHEN
$featureFlagsFile = $pagesPath . '/FeatureFlagManager.php';
if (file_exists($featureFlagsFile)) {
    $content = file_get_contents($featureFlagsFile);
    
    // Ensure it's visible
    if (preg_match('/public static function shouldRegisterNavigation\(\)/', $content)) {
        $content = preg_replace(
            '/public static function shouldRegisterNavigation\(\)[^}]+\{[^}]+\}/',
            'public static function shouldRegisterNavigation(): bool
    {
        return true; // Sichtbar in Navigation
    }',
            $content
        );
    }
    
    // Ensure correct group
    $content = preg_replace(
        '/protected static \?\s*string \$navigationGroup\s*=\s*[^;]+;/',
        'protected static ?string $navigationGroup = "⚙️ System";',
        $content
    );
    
    file_put_contents($featureFlagsFile, $content);
    echo "✅ FeatureFlagManager: Wieder sichtbar in System-Gruppe\n";
}

// 5. ENSURE CORRECT DASHBOARD NAMES
$dashboardCorrections = [
    'SimpleDashboard.php' => [
        'label' => '"Übersicht"',
        'group' => '"📊 Dashboards"',
        'sort' => 10
    ],
    'EventAnalyticsDashboard.php' => [
        'label' => '"Analytics & Trends"',
        'group' => '"📊 Dashboards"',
        'sort' => 20
    ],
    'AICallCenter.php' => [
        'label' => '"AI Operations"',
        'group' => '"📊 Dashboards"',
        'sort' => 30
    ],
    'SystemMonitoringDashboard.php' => [
        'label' => '"System Monitor"',
        'group' => '"📊 Dashboards"',
        'sort' => 40
    ]
];

echo "\n📊 DASHBOARD LABELS KORRIGIEREN:\n";
foreach ($dashboardCorrections as $filename => $config) {
    $filepath = $pagesPath . '/' . $filename;
    
    if (!file_exists($filepath)) {
        echo "   ⚠️  $filename nicht gefunden\n";
        continue;
    }
    
    $content = file_get_contents($filepath);
    
    // Update navigationLabel
    $content = preg_replace(
        '/protected static \?\s*string \$navigationLabel\s*=\s*[^;]+;/',
        'protected static ?string $navigationLabel = ' . $config['label'] . ';',
        $content
    );
    
    // Update navigationGroup
    $content = preg_replace(
        '/protected static \?\s*string \$navigationGroup\s*=\s*[^;]+;/',
        'protected static ?string $navigationGroup = ' . $config['group'] . ';',
        $content
    );
    
    // Update navigationSort
    $content = preg_replace(
        '/protected static \?\s*int \$navigationSort\s*=\s*\d+\s*;/',
        'protected static ?int $navigationSort = ' . $config['sort'] . ';',
        $content
    );
    
    file_put_contents($filepath, $content);
    echo "   ✅ " . trim($config['label'], '"') . " korrigiert\n";
}

echo "\n✅ Alle Navigation-Fixes angewendet!\n";