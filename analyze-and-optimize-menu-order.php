<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Filament\Facades\Filament;

// Get admin panel
$panel = Filament::getPanel('admin');
$resources = $panel->getResources();

// Collect all resources with their groups and sort orders
$groupedResources = [];
$groupSortOrders = [];

foreach ($resources as $resourceClass) {
    $group = $resourceClass::getNavigationGroup() ?? 'Ungrouped';
    
    // Skip disabled resources
    if (strpos($resourceClass, 'Disabled') !== false) {
        continue;
    }
    
    $sort = 999;
    try {
        $reflection = new ReflectionClass($resourceClass);
        $property = $reflection->getProperty('navigationSort');
        $property->setAccessible(true);
        $sort = $property->getValue() ?? 999;
    } catch (Exception $e) {}
    
    if (!isset($groupedResources[$group])) {
        $groupedResources[$group] = [];
        $groupSortOrders[$group] = $sort; // Use first item's sort as group sort
    }
    
    // Update group sort to use minimum sort value in group
    $groupSortOrders[$group] = min($groupSortOrders[$group], $sort);
    
    $groupedResources[$group][] = [
        'name' => class_basename($resourceClass),
        'label' => $resourceClass::getNavigationLabel(),
        'sort' => $sort,
    ];
}

// Sort groups by their minimum sort order
asort($groupSortOrders);

echo "=== AKTUELLE MENÜ-REIHENFOLGE ===\n\n";
$position = 1;
foreach ($groupSortOrders as $group => $groupSort) {
    echo "[$position. Position - Sort: $groupSort] $group\n";
    
    // Sort items within group
    usort($groupedResources[$group], function($a, $b) {
        return $a['sort'] <=> $b['sort'];
    });
    
    foreach ($groupedResources[$group] as $item) {
        echo "   └─ [{$item['sort']}] {$item['label']}\n";
    }
    echo "\n";
    $position++;
}

// Check for dashboards/pages
echo "=== DASHBOARDS & PAGES ===\n";
$pages = $panel->getPages();
foreach ($pages as $pageClass) {
    echo "- " . class_basename($pageClass) . "\n";
}

echo "\n=== WORKFLOW-ANALYSE ===\n\n";
echo "Typischer Tagesablauf eines Nutzers:\n";
echo "1. 🌅 MORGENS: Dashboard checken → Heutige Termine & Anrufe\n";
echo "2. 📞 TAGSÜBER: Live-Anrufe verfolgen → Neue Termine anlegen\n";
echo "3. 👥 KUNDENKONTAKT: Kundeninfos abrufen → Services buchen\n";
echo "4. 💼 VERWALTUNG: Firmen/Filialen/Mitarbeiter (seltener)\n";
echo "5. 🤖 KONFIGURATION: AI-Settings (noch seltener)\n";
echo "6. 💰 MONATSENDE: Abrechnung & Rechnungen\n";
echo "7. ⚙️ ADMIN: System-Settings (sehr selten)\n";

echo "\n=== VORSCHLAG NEUE SORTIERUNG ===\n\n";

$newOrder = [
    // Sort 0-99: Dashboards (ganz oben!)
    "📊 Dashboards" => [
        "items" => [
            "Dashboard" => 10,
            "AI Call Center" => 20,
            "System Monitoring" => 30,
        ],
        "group_sort" => 10
    ],
    
    // Sort 100-199: Tagesgeschäft
    "🎯 Tagesgeschäft" => [
        "items" => [
            "Anrufe" => 110,
            "Termine" => 120,
            "Kunden" => 130,
        ],
        "group_sort" => 100
    ],
    
    // Sort 200-299: Firmenverwaltung
    "🏢 Firmenverwaltung" => [
        "items" => [
            "Firmen" => 210,
            "Filialen" => 220,
            "Mitarbeiter" => 230,
            "Dienstleistungen" => 240,
            "Master Services" => 250,
        ],
        "group_sort" => 200
    ],
    
    // Sort 300-399: Kalender & Buchung
    "📅 Kalender & Buchung" => [
        "items" => [
            "Cal.com Events" => 310,
            "Arbeitszeiten" => 320,
            "Event Types" => 330,
            "Integrationen" => 340,
        ],
        "group_sort" => 300
    ],
    
    // Sort 400-499: AI & Telefonie
    "🤖 AI & Telefonie" => [
        "items" => [
            "Retell Agenten" => 410,
            "Telefonnummern" => 420,
            "Anrufkampagnen" => 430,
            "Prompt Templates" => 440,
        ],
        "group_sort" => 400
    ],
    
    // Sort 500-599: Abrechnung
    "💰 Abrechnung" => [
        "items" => [
            "Rechnungen" => 510,
            "Prepaid Guthaben" => 520,
            "Abrechnungszeiträume" => 530,
            "Abonnements" => 540,
            "Preismodelle" => 550,
        ],
        "group_sort" => 500
    ],
    
    // Sort 600-699: Partner
    "👥 Partner & Reseller" => [
        "items" => [
            "Reseller" => 610,
            "Preisstufen" => 620,
            "Portal-Benutzer" => 630,
        ],
        "group_sort" => 600
    ],
    
    // Sort 700-799: System
    "⚙️ System" => [
        "items" => [
            "Benutzer" => 710,
            "Mandanten" => 720,
            "Fehlerprotokolle" => 730,
            "DSGVO-Anfragen" => 740,
        ],
        "group_sort" => 700
    ],
];

foreach ($newOrder as $group => $config) {
    echo "$group (Sort: {$config['group_sort']})\n";
    foreach ($config['items'] as $item => $sort) {
        echo "   └─ [$sort] $item\n";
    }
    echo "\n";
}

echo "=== BEGRÜNDUNG DER NEUEN SORTIERUNG ===\n\n";
echo "1. DASHBOARDS GANZ OBEN (NEU!):\n";
echo "   - Erster Blick des Tages\n";
echo "   - Wichtigste KPIs sofort sichtbar\n";
echo "   - Schneller Überblick über System-Status\n\n";

echo "2. TAGESGESCHÄFT DIREKT DANACH:\n";
echo "   - Häufigste Aktionen (80% der Zeit)\n";
echo "   - Anrufe → Termine → Kunden (logischer Flow)\n\n";

echo "3. FIRMENVERWALTUNG:\n";
echo "   - Wichtig aber nicht täglich\n";
echo "   - Strukturelle Einstellungen\n\n";

echo "4. KALENDER VOR AI:\n";
echo "   - Kalender wird häufiger genutzt als AI-Config\n";
echo "   - Arbeitszeiten sind operative Aufgaben\n\n";

echo "5. AI & TELEFONIE WEITER UNTEN:\n";
echo "   - Einmal eingerichtet, selten geändert\n";
echo "   - Technische Konfiguration\n\n";

echo "6. ABRECHNUNG:\n";
echo "   - Monatliche/periodische Nutzung\n";
echo "   - Wichtig aber nicht täglich\n\n";

echo "7. PARTNER & SYSTEM GANZ UNTEN:\n";
echo "   - Administrative Funktionen\n";
echo "   - Sehr seltene Nutzung\n";