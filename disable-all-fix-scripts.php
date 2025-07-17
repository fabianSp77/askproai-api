<?php

require_once __DIR__ . '/vendor/autoload.php';

echo "🔧 DEAKTIVIERE ALLE FIX-SCRIPTS FÜR DEMO\n";
echo "=====================================\n\n";

// Liste aller problematischen Scripts
$scriptsToDisable = [
    'operations-dashboard-alpine-fix.js',
    'alpine-error-handler.js',
    'emergency-framework-loader.js',
    'manual-framework-loader.js',
    'alpine-sidebar-store-fix.js',
    'operations-dashboard-fix.js',
    'widget-display-fix.js',
    'calls-page-widget-fix.js',
    'portal-universal-fix.js',
    'livewire-404-popup-fix.js',
    'livewire-error-suppressor.js',
    'portal-debug-helper.js',
];

$publicPath = '/var/www/api-gateway/public/js/';
$disabledCount = 0;

foreach ($scriptsToDisable as $script) {
    $fullPath = $publicPath . $script;
    $disabledPath = $fullPath . '.disabled-for-demo';
    
    if (file_exists($fullPath)) {
        if (rename($fullPath, $disabledPath)) {
            echo "✅ Deaktiviert: {$script}\n";
            $disabledCount++;
        } else {
            echo "❌ Fehler bei: {$script}\n";
        }
    } else if (file_exists($disabledPath)) {
        echo "⚠️  Bereits deaktiviert: {$script}\n";
    } else {
        echo "❓ Nicht gefunden: {$script}\n";
    }
}

echo "\n";
echo "📊 Zusammenfassung:\n";
echo "==================\n";
echo "✅ {$disabledCount} Scripts deaktiviert\n\n";

// Backup der base.blade.php erstellen
$baseBlade = '/var/www/api-gateway/resources/views/vendor/filament-panels/components/layout/base.blade.php';
$baseBladeBackup = $baseBlade . '.backup-before-demo';

if (!file_exists($baseBladeBackup)) {
    copy($baseBlade, $baseBladeBackup);
    echo "✅ Backup von base.blade.php erstellt\n";
}

// Clear all caches
echo "\n🗑️  Cache wird geleert...\n";
exec('php artisan optimize:clear', $output);
exec('php artisan filament:cache-components', $output);

echo "\n✅ FERTIG!\n";
echo "=========\n";
echo "1. Browser Cache leeren (Ctrl+F5)\n";
echo "2. Neu einloggen\n";
echo "3. Buttons sollten jetzt funktionieren!\n\n";

echo "📌 Nach der Demo wiederherstellen mit:\n";
echo "   php restore-all-scripts.php\n";