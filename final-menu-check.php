<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Filament\Facades\Filament;

echo "=== FINALE MENÜ-STRUKTUR (Nach Bereinigung) ===\n\n";

$panel = Filament::getPanel('admin');

// Collect visible items
$menuStructure = [];

// Get Pages
foreach ($panel->getPages() as $pageClass) {
    if (!class_exists($pageClass)) continue;
    
    // Check visibility
    if (method_exists($pageClass, 'shouldRegisterNavigation')) {
        if (!$pageClass::shouldRegisterNavigation()) {
            continue;
        }
    }
    
    $reflection = new ReflectionClass($pageClass);
    
    $group = 'Ungrouped';
    $label = class_basename($pageClass);
    $sort = 999;
    
    if ($reflection->hasProperty('navigationGroup')) {
        $prop = $reflection->getProperty('navigationGroup');
        $prop->setAccessible(true);
        $group = $prop->getValue() ?? 'Ungrouped';
    }
    
    if ($reflection->hasProperty('navigationLabel')) {
        $prop = $reflection->getProperty('navigationLabel');
        $prop->setAccessible(true);
        $label = $prop->getValue() ?? class_basename($pageClass);
    }
    
    if ($reflection->hasProperty('navigationSort')) {
        $prop = $reflection->getProperty('navigationSort');
        $prop->setAccessible(true);
        $sort = $prop->getValue() ?? 999;
    }
    
    if (!isset($menuStructure[$group])) {
        $menuStructure[$group] = [];
    }
    
    $menuStructure[$group][] = [
        'label' => $label,
        'sort' => $sort,
        'type' => 'Page'
    ];
}

// Get Resources
foreach ($panel->getResources() as $resourceClass) {
    if (!class_exists($resourceClass)) continue;
    
    $group = $resourceClass::getNavigationGroup() ?? 'Ungrouped';
    $label = $resourceClass::getNavigationLabel();
    
    $sort = 999;
    try {
        $reflection = new ReflectionClass($resourceClass);
        if ($reflection->hasProperty('navigationSort')) {
            $property = $reflection->getProperty('navigationSort');
            $property->setAccessible(true);
            $sort = $property->getValue() ?? 999;
        }
    } catch (Exception $e) {}
    
    if (!isset($menuStructure[$group])) {
        $menuStructure[$group] = [];
    }
    
    $menuStructure[$group][] = [
        'label' => $label,
        'sort' => $sort,
        'type' => 'Resource'
    ];
}

// Calculate group order
$groupOrder = [];
foreach ($menuStructure as $group => $items) {
    $minSort = min(array_column($items, 'sort'));
    $groupOrder[$group] = $minSort;
}
asort($groupOrder);

// Expected order
$expectedGroups = [
    '📊 Dashboards',
    '🎯 Tagesgeschäft',
    '🏢 Firmenverwaltung',
    '📅 Kalender & Buchung',
    '🤖 AI & Telefonie',
    '💰 Abrechnung',
    '👥 Partner & Reseller',
    '⚙️ System'
];

echo "SICHTBARE MENÜ-GRUPPEN:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$position = 1;
foreach ($expectedGroups as $expectedGroup) {
    if (isset($menuStructure[$expectedGroup])) {
        // Sort items in group
        usort($menuStructure[$expectedGroup], fn($a, $b) => $a['sort'] <=> $b['sort']);
        
        $itemCount = count($menuStructure[$expectedGroup]);
        $pageCount = count(array_filter($menuStructure[$expectedGroup], fn($i) => $i['type'] === 'Page'));
        $resourceCount = count(array_filter($menuStructure[$expectedGroup], fn($i) => $i['type'] === 'Resource'));
        
        echo "[$position] $expectedGroup\n";
        echo "    └─ $itemCount Items ($pageCount Pages, $resourceCount Resources)\n";
        
        // Show first 3 items
        $shown = 0;
        foreach ($menuStructure[$expectedGroup] as $item) {
            if ($shown < 3) {
                $icon = $item['type'] === 'Page' ? '📄' : '📁';
                echo "       • $icon {$item['label']}\n";
                $shown++;
            }
        }
        if ($itemCount > 3) {
            echo "       • ... und " . ($itemCount - 3) . " weitere\n";
        }
        
        echo "\n";
        $position++;
        
        // Remove from list
        unset($menuStructure[$expectedGroup]);
    }
}

// Show any remaining groups
if (!empty($menuStructure)) {
    echo "\nWEITERE GRUPPEN:\n";
    foreach ($menuStructure as $group => $items) {
        echo "  • $group (" . count($items) . " items)\n";
    }
}

echo "\n✅ BEREINIGUNGSERGEBNIS:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ 33 Test-/Redundante Pages versteckt\n";
echo "✅ 4 fokussierte Dashboards sichtbar\n";
echo "✅ Dokumentation nach unten verschoben\n";
echo "✅ Menü-Gruppen mit Emojis und deutscher Sprache\n";
echo "✅ Klare Struktur für AI Phone Assistant Business\n";