<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Filament\Facades\Filament;

// Force admin login
$admin = \App\Models\User::where('email', 'admin@askproai.de')->first();
if ($admin) {
    auth()->login($admin);
}

echo "=== FINALE MENÜ-STRUKTUR ===\n\n";

// Get admin panel
$panel = Filament::getPanel('admin');

// Collect all items
$allItems = [];

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
    
    $allItems[] = [
        'group' => $group,
        'label' => $label,
        'sort' => $sort,
        'type' => 'Resource',
        'class' => class_basename($resourceClass)
    ];
}

// Get Pages
foreach ($panel->getPages() as $pageClass) {
    if (!class_exists($pageClass)) continue;
    
    // Skip pages that shouldn't be in navigation
    if (method_exists($pageClass, 'shouldRegisterNavigation')) {
        if (!$pageClass::shouldRegisterNavigation()) {
            continue;
        }
    }
    
    $group = null;
    $label = null;
    $sort = 999;
    
    try {
        $reflection = new ReflectionClass($pageClass);
        
        if ($reflection->hasProperty('navigationGroup')) {
            $property = $reflection->getProperty('navigationGroup');
            $property->setAccessible(true);
            $group = $property->getValue();
        }
        
        if ($reflection->hasProperty('navigationLabel')) {
            $property = $reflection->getProperty('navigationLabel');
            $property->setAccessible(true);
            $label = $property->getValue();
        } elseif (method_exists($pageClass, 'getNavigationLabel')) {
            $label = $pageClass::getNavigationLabel();
        } else {
            $label = class_basename($pageClass);
        }
        
        if ($reflection->hasProperty('navigationSort')) {
            $property = $reflection->getProperty('navigationSort');
            $property->setAccessible(true);
            $sort = $property->getValue() ?? 999;
        }
    } catch (Exception $e) {}
    
    if ($group) {
        $allItems[] = [
            'group' => $group,
            'label' => $label,
            'sort' => $sort,
            'type' => 'Page',
            'class' => class_basename($pageClass)
        ];
    }
}

// Group items
$grouped = [];
foreach ($allItems as $item) {
    $grouped[$item['group']][] = $item;
}

// Sort groups by minimum sort value
$groupOrder = [];
foreach ($grouped as $group => $items) {
    $minSort = min(array_column($items, 'sort'));
    $groupOrder[$group] = $minSort;
}
asort($groupOrder);

// Display in order
$position = 1;
$expectedOrder = [
    '📊 Dashboards',
    '🎯 Tagesgeschäft',
    '🏢 Firmenverwaltung',
    '📅 Kalender & Buchung',
    '🤖 AI & Telefonie',
    '💰 Abrechnung',
    '👥 Partner & Reseller',
    '⚙️ System'
];

echo "ERWARTETE HAUPTGRUPPEN:\n";
foreach ($expectedOrder as $i => $group) {
    $found = isset($grouped[$group]);
    $status = $found ? '✅' : '❌';
    echo ($i+1) . ". $status $group\n";
}

echo "\n\nTATSÄCHLICHE MENÜ-STRUKTUR:\n\n";

foreach ($groupOrder as $group => $minSort) {
    // Check if it's a main group
    $isMainGroup = in_array($group, $expectedOrder);
    $prefix = $isMainGroup ? '★' : ' ';
    
    echo "$prefix [$position] $group (Sort: $minSort)\n";
    
    // Sort items within group
    usort($grouped[$group], function($a, $b) {
        return $a['sort'] <=> $b['sort'];
    });
    
    // Show first 5 items only for cleaner output
    $count = 0;
    foreach ($grouped[$group] as $item) {
        $count++;
        if ($count <= 5) {
            $icon = $item['type'] === 'Page' ? '📄' : '📁';
            echo "     └─ $icon [{$item['sort']}] {$item['label']}\n";
        }
    }
    if (count($grouped[$group]) > 5) {
        $remaining = count($grouped[$group]) - 5;
        echo "     └─ ... und $remaining weitere\n";
    }
    
    echo "\n";
    $position++;
}

// Statistics
$totalResources = count(array_filter($allItems, fn($i) => $i['type'] === 'Resource'));
$totalPages = count(array_filter($allItems, fn($i) => $i['type'] === 'Page'));
$totalGroups = count($grouped);

echo "STATISTIK:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Gesamt Gruppen: $totalGroups\n";
echo "Hauptgruppen mit Emojis: " . count(array_filter(array_keys($grouped), fn($g) => preg_match('/[📊🎯🏢📅🤖💰👥⚙️]/', $g))) . "\n";
echo "Resources: $totalResources\n";
echo "Pages: $totalPages\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━\n";