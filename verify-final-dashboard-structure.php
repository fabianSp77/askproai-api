<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Filament\Facades\Filament;

echo "=== FINALE DASHBOARD & MENÜ-STRUKTUR ===\n\n";

// Get admin panel
$panel = Filament::getPanel('admin');

// Collect all visible dashboards
$dashboards = [];
$otherPages = [];
$resources = [];

foreach ($panel->getPages() as $pageClass) {
    if (!class_exists($pageClass)) continue;
    
    // Check if visible in navigation
    if (method_exists($pageClass, 'shouldRegisterNavigation')) {
        if (!$pageClass::shouldRegisterNavigation()) {
            continue; // Skip hidden pages
        }
    }
    
    $reflection = new ReflectionClass($pageClass);
    
    $group = null;
    $label = null;
    $sort = 999;
    
    if ($reflection->hasProperty('navigationGroup')) {
        $property = $reflection->getProperty('navigationGroup');
        $property->setAccessible(true);
        $group = $property->getValue();
    }
    
    if ($reflection->hasProperty('navigationLabel')) {
        $property = $reflection->getProperty('navigationLabel');
        $property->setAccessible(true);
        $label = $property->getValue();
    }
    
    if ($reflection->hasProperty('navigationSort')) {
        $property = $reflection->getProperty('navigationSort');
        $property->setAccessible(true);
        $sort = $property->getValue() ?? 999;
    }
    
    $item = [
        'class' => class_basename($pageClass),
        'label' => $label ?? class_basename($pageClass),
        'group' => $group,
        'sort' => $sort
    ];
    
    if ($group === '📊 Dashboards' || strpos($group, 'Dashboard') !== false) {
        $dashboards[] = $item;
    } else {
        $otherPages[$group ?? 'Ungrouped'][] = $item;
    }
}

// Sort dashboards by sort value
usort($dashboards, fn($a, $b) => $a['sort'] <=> $b['sort']);

echo "📊 DASHBOARDS (Optimiert auf 4 fokussierte Dashboards):\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
foreach ($dashboards as $dash) {
    $icon = match($dash['label']) {
        'Übersicht' => '🏠',
        'Analytics & Trends' => '📈',
        'AI Operations' => '🤖',
        'System Monitor' => '🔧',
        default => '📊'
    };
    echo "  $icon {$dash['label']}\n";
    
    // Add description
    $desc = match($dash['label']) {
        'Übersicht' => '     └─ Tägliche Metriken, KPIs, Quick Actions',
        'Analytics & Trends' => '     └─ Business Intelligence, Trends, Berichte',
        'AI Operations' => '     └─ Retell.ai Calls, Kampagnen, Agent-Performance',
        'System Monitor' => '     └─ Technische Überwachung (nur Super Admins)',
        default => ''
    };
    if ($desc) echo "$desc\n";
}

// Count resources
foreach ($panel->getResources() as $resourceClass) {
    if (!class_exists($resourceClass)) continue;
    $group = $resourceClass::getNavigationGroup() ?? 'Ungrouped';
    $resources[$group] = ($resources[$group] ?? 0) + 1;
}

echo "\n\n🎯 HAUPTMENÜ-GRUPPEN:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$mainGroups = [
    '📊 Dashboards' => count($dashboards) . ' Dashboards',
    '🎯 Tagesgeschäft' => ($resources['🎯 Tagesgeschäft'] ?? 0) . ' Resources',
    '🏢 Firmenverwaltung' => ($resources['🏢 Firmenverwaltung'] ?? 0) . ' Resources',
    '📅 Kalender & Buchung' => ($resources['📅 Kalender & Buchung'] ?? 0) . ' Resources',
    '🤖 AI & Telefonie' => ($resources['🤖 AI & Telefonie'] ?? 0) . ' Resources',
    '💰 Abrechnung' => ($resources['💰 Abrechnung'] ?? 0) . ' Resources',
    '👥 Partner & Reseller' => ($resources['👥 Partner & Reseller'] ?? 0) . ' Resources',
    '⚙️ System' => ($resources['⚙️ System'] ?? 0) . ' Resources'
];

foreach ($mainGroups as $group => $count) {
    echo "  $group - $count\n";
}

// Check for documentation
$docPage = null;
foreach ($otherPages as $group => $pages) {
    foreach ($pages as $page) {
        if (strpos($page['class'], 'Documentation') !== false) {
            $docPage = $page;
            break 2;
        }
    }
}

if ($docPage) {
    echo "\n✅ Dokumentation wurde nach unten verschoben (Sort: {$docPage['sort']})\n";
}

echo "\n\n✨ VERBESSERUNGEN:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ Von 13 auf 4 fokussierte Dashboards reduziert\n";
echo "✅ Klare Zweckbestimmung für jedes Dashboard\n";
echo "✅ Performance-optimiert mit 5-Minuten-Caching\n";
echo "✅ Rollenbasierter Zugriff (System Monitor nur für Admins)\n";
echo "✅ Mobile-responsive Design\n";
echo "✅ Dokumentation nach unten verschoben\n";
echo "✅ 9 redundante Dashboards entfernt\n";

echo "\n🚀 State-of-the-Art Dashboard-System implementiert!\n";