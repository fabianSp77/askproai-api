<?php
// Test Filament configuration
require __DIR__.'/../vendor/autoload.php';

try {
    $app = require_once __DIR__.'/../bootstrap/app.php';
    
    // Check if Filament is installed
    if (!class_exists(\Filament\FilamentManager::class)) {
        echo "ERROR: Filament not installed!\n";
        exit(1);
    }
    
    echo "Filament installed: YES\n";
    
    // Check panels
    $panels = \Filament\Facades\Filament::getPanels();
    echo "Panels registered: " . count($panels) . "\n";
    
    foreach ($panels as $id => $panel) {
        echo "  - Panel: $id\n";
        echo "    Path: " . $panel->getPath() . "\n";
        echo "    ID: " . $panel->getId() . "\n";
    }
    
    // Try to get admin panel
    try {
        $adminPanel = \Filament\Facades\Filament::getPanel('admin');
        echo "\nAdmin panel found!\n";
        echo "  Path: " . $adminPanel->getPath() . "\n";
        echo "  Login route: " . $adminPanel->getLoginRouteSlug() . "\n";
    } catch (\Exception $e) {
        echo "\nAdmin panel error: " . $e->getMessage() . "\n";
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}