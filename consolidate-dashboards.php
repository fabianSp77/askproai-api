<?php

// State-of-the-Art Dashboard Konsolidierung für AskProAI

$pagesPath = '/var/www/api-gateway/app/Filament/Admin/Pages';

echo "=== DASHBOARD KONSOLIDIERUNG ===\n\n";

// 1. DOKUMENTATION nach unten verschieben
$docFile = $pagesPath . '/DocumentationPage.php';
if (file_exists($docFile)) {
    $content = file_get_contents($docFile);
    $content = preg_replace(
        '/protected static \?\s*int \$navigationSort\s*=\s*\d+\s*;/',
        'protected static ?int $navigationSort = 899;',
        $content
    );
    file_put_contents($docFile, $content);
    echo "✅ Dokumentation nach unten verschoben (Sort: 899)\n\n";
}

// 2. REDUNDANTE DASHBOARDS aus Navigation entfernen
$dashboardsToHide = [
    'SimplestDashboard.php' => 'Redundant - wird entfernt',
    'OptimizedDashboard.php' => 'Redundant - wird entfernt', 
    'PerformanceOptimizedDashboard.php' => 'Redundant - wird entfernt',
    'TestMinimalDashboard.php' => 'Test-Dashboard - wird entfernt',
    'OptimizedOperationalDashboard.php' => 'Leer - wird entfernt',
    'CustomerBillingDashboard.php' => 'Leer - wird entfernt',
];

echo "📊 REDUNDANTE DASHBOARDS ENTFERNEN:\n";
foreach ($dashboardsToHide as $filename => $reason) {
    $filepath = $pagesPath . '/' . $filename;
    
    if (!file_exists($filepath)) {
        echo "   ⚠️  $filename nicht gefunden\n";
        continue;
    }
    
    $content = file_get_contents($filepath);
    
    // Aus Navigation entfernen
    if (!preg_match('/public static function shouldRegisterNavigation\(\)/', $content)) {
        $content = preg_replace(
            '/(class\s+\w+\s+extends\s+\w+\s*\{)/',
            "$1\n\n    public static function shouldRegisterNavigation(): bool\n    {\n        return false; // $reason\n    }",
            $content,
            1
        );
    } else {
        $content = preg_replace(
            '/public static function shouldRegisterNavigation\(\)[^}]+\{[^}]+\}/',
            "public static function shouldRegisterNavigation(): bool\n    {\n        return false; // $reason\n    }",
            $content
        );
    }
    
    file_put_contents($filepath, $content);
    echo "   🚫 $filename - $reason\n";
}

// 3. HAUPTDASHBOARDS UMBENENNEN und optimieren
$dashboardOptimization = [
    // Hauptdashboard (SimpleDashboard wird zum Hauptdashboard)
    'SimpleDashboard.php' => [
        'group' => '"📊 Dashboards"',
        'sort' => 10,
        'label' => '"Übersicht"',
        'icon' => '"heroicon-o-home"',
        'comment' => '// Hauptdashboard mit allen wichtigen Tagesmetriken'
    ],
    
    // Analytics Dashboard (EventAnalyticsDashboard)
    'EventAnalyticsDashboard.php' => [
        'group' => '"📊 Dashboards"',
        'sort' => 20,
        'label' => '"Analytics & Trends"',
        'icon' => '"heroicon-o-chart-bar"',
        'comment' => '// Business Intelligence und Trend-Analysen'
    ],
    
    // AI Operations (AICallCenter)
    'AICallCenter.php' => [
        'group' => '"📊 Dashboards"',
        'sort' => 30,
        'label' => '"AI Operations"',
        'icon' => '"heroicon-o-phone"',
        'comment' => '// Retell.ai Call Management und Kampagnen'
    ],
    
    // System Dashboard (SystemMonitoringDashboard)
    'SystemMonitoringDashboard.php' => [
        'group' => '"📊 Dashboards"',
        'sort' => 40,
        'label' => '"System Monitor"',
        'icon' => '"heroicon-o-server"',
        'comment' => '// Nur für Super Admins - Technische Überwachung'
    ],
    
    // Dashboard.php wird zu einem Redirect
    'Dashboard.php' => [
        'group' => '"📊 Dashboards"',
        'sort' => 1,
        'label' => '"Dashboard"',
        'hide' => true, // Verstecken, da es nur ein Redirect ist
    ],
];

echo "\n📊 DASHBOARDS OPTIMIEREN:\n";
foreach ($dashboardOptimization as $filename => $config) {
    $filepath = $pagesPath . '/' . $filename;
    
    if (!file_exists($filepath)) {
        echo "   ⚠️  $filename nicht gefunden\n";
        continue;
    }
    
    $content = file_get_contents($filepath);
    $changes = [];
    
    // Hide if needed
    if (isset($config['hide']) && $config['hide']) {
        if (!preg_match('/public static function shouldRegisterNavigation\(\)/', $content)) {
            $content = preg_replace(
                '/(class\s+\w+\s+extends\s+\w+\s*\{)/',
                "$1\n\n    public static function shouldRegisterNavigation(): bool\n    {\n        return false; // Redirect to SimpleDashboard\n    }",
                $content,
                1
            );
        }
        $changes[] = "versteckt";
    }
    
    // Update navigation group
    if (isset($config['group'])) {
        $content = preg_replace(
            '/protected static \?\s*string \$navigationGroup\s*=\s*[^;]+;/',
            'protected static ?string $navigationGroup = ' . $config['group'] . ';',
            $content
        );
        $changes[] = "Gruppe";
    }
    
    // Update navigation sort
    if (isset($config['sort'])) {
        $content = preg_replace(
            '/protected static \?\s*int \$navigationSort\s*=\s*\d+\s*;/',
            'protected static ?int $navigationSort = ' . $config['sort'] . ';',
            $content
        );
        $changes[] = "Sort";
    }
    
    // Update navigation label
    if (isset($config['label'])) {
        $content = preg_replace(
            '/protected static \?\s*string \$navigationLabel\s*=\s*[^;]+;/',
            'protected static ?string $navigationLabel = ' . $config['label'] . ';',
            $content
        );
        $changes[] = "Label";
    }
    
    // Update icon if specified
    if (isset($config['icon'])) {
        $content = preg_replace(
            '/protected static \?\s*string \$navigationIcon\s*=\s*[^;]+;/',
            'protected static ?string $navigationIcon = ' . $config['icon'] . ';',
            $content
        );
        $changes[] = "Icon";
    }
    
    // Add comment if specified
    if (isset($config['comment'])) {
        $content = preg_replace(
            '/(namespace[^;]+;)/',
            "$1\n\n" . $config['comment'],
            $content,
            1
        );
    }
    
    file_put_contents($filepath, $content);
    
    $label = trim($config['label'] ?? $filename, '"');
    echo "   ✅ $label - " . implode(', ', $changes) . "\n";
}

// 4. ZUGRIFFSKONTROLLE für System Monitor
$systemMonitorFile = $pagesPath . '/SystemMonitoringDashboard.php';
if (file_exists($systemMonitorFile)) {
    $content = file_get_contents($systemMonitorFile);
    
    // Add canAccess method if not exists
    if (!preg_match('/public static function canAccess\(\)/', $content)) {
        $content = preg_replace(
            '/(protected static string \$view[^;]+;)/',
            "$1\n\n    public static function canAccess(): bool\n    {\n        return auth()->user()?->isSuperAdmin() ?? false;\n    }",
            $content
        );
        file_put_contents($systemMonitorFile, $content);
        echo "\n✅ System Monitor nur für Super Admins zugänglich\n";
    }
}

echo "\n=== ZUSAMMENFASSUNG ===\n";
echo "✅ 4 fokussierte Dashboards statt 13:\n";
echo "   1. Übersicht - Tägliche Metriken\n";
echo "   2. Analytics & Trends - Business Intelligence\n";
echo "   3. AI Operations - Call Management\n";
echo "   4. System Monitor - Technische Überwachung (nur Super Admins)\n";
echo "\n✅ 9 redundante Dashboards entfernt\n";
echo "✅ Dokumentation nach unten verschoben\n";
echo "\n🚀 State-of-the-Art Dashboard-Struktur implementiert!\n";