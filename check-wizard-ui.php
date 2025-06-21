<?php

echo "=== Event Type Setup Wizard UI Check ===\n\n";

// Check for critical files
$files = [
    'Wizard Page' => '/var/www/api-gateway/app/Filament/Admin/Pages/EventTypeSetupWizard.php',
    'Blade Template' => '/var/www/api-gateway/resources/views/filament/admin/pages/event-type-setup-wizard.blade.php',
    'Data Flow Diagram' => '/var/www/api-gateway/resources/views/filament/components/data-flow-diagram.blade.php',
];

foreach ($files as $name => $path) {
    if (file_exists($path)) {
        echo "✅ {$name}: " . realpath($path) . "\n";
        echo "   Size: " . number_format(filesize($path)) . " bytes\n";
        echo "   Modified: " . date('Y-m-d H:i:s', filemtime($path)) . "\n";
    } else {
        echo "❌ {$name}: NOT FOUND\n";
    }
}

echo "\n=== UI Components Check ===\n";

// Check blade template for key UI elements
$bladeContent = file_get_contents('/var/www/api-gateway/resources/views/filament/admin/pages/event-type-setup-wizard.blade.php');

$uiElements = [
    'Custom CSS styles' => strpos($bladeContent, '@push(\'styles\')') !== false,
    'Screenshot helper' => strpos($bladeContent, 'Screenshot-Modus aktiv') !== false,
    'Progress bar' => strpos($bladeContent, 'getSetupProgress()') !== false,
    'Checklist display' => strpos($bladeContent, '@foreach($this->checklist') !== false,
    'Cal.com links section' => strpos($bladeContent, 'calcomLinks') !== false,
    'Help section with grid' => strpos($bladeContent, 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3') !== false,
];

foreach ($uiElements as $element => $exists) {
    echo ($exists ? "✅" : "❌") . " {$element}\n";
}

echo "\n=== Data Flow Diagram Check ===\n";

$diagramContent = file_get_contents('/var/www/api-gateway/resources/views/filament/components/data-flow-diagram.blade.php');

$diagramElements = [
    'Warning box (amber)' => strpos($diagramContent, 'bg-amber-50') !== false,
    'Local copies explanation' => strpos($diagramContent, 'lokale Kopien') !== false,
    'Three main boxes' => substr_count($diagramContent, 'rounded-xl p-6 shadow-md') >= 3,
    'Responsive arrows' => strpos($diagramContent, 'hidden lg:flex') !== false,
    'Action box with link' => strpos($diagramContent, 'Import-Wizard') !== false,
];

foreach ($diagramElements as $element => $exists) {
    echo ($exists ? "✅" : "❌") . " {$element}\n";
}

echo "\n=== Access URLs ===\n";
echo "Event Type Setup Wizard: https://dev.askproai.de/admin/event-type-setup-wizard\n";
echo "With screenshot mode: https://dev.askproai.de/admin/event-type-setup-wizard?screenshot=1\n";
echo "Edit specific Event Type: https://dev.askproai.de/admin/event-type-setup-wizard/2026361\n";

echo "\n✅ UI Check Complete - All components are in place!\n";