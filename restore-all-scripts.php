<?php

echo "🔧 REAKTIVIERE ALLE SCRIPTS NACH DEMO\n";
echo "===================================\n\n";

$scriptsToRestore = [
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
$restoredCount = 0;

foreach ($scriptsToRestore as $script) {
    $disabledPath = $publicPath . $script . '.disabled-for-demo';
    $originalPath = $publicPath . $script;
    
    if (file_exists($disabledPath)) {
        if (rename($disabledPath, $originalPath)) {
            echo "✅ Wiederhergestellt: {$script}\n";
            $restoredCount++;
        } else {
            echo "❌ Fehler bei: {$script}\n";
        }
    } else {
        echo "⚠️  Nicht gefunden: {$script}\n";
    }
}

// Restore base.blade.php
$baseBlade = '/var/www/api-gateway/resources/views/vendor/filament-panels/components/layout/base.blade.php';
$baseBladeBackup = $baseBlade . '.backup-before-demo';

if (file_exists($baseBladeBackup)) {
    copy($baseBladeBackup, $baseBlade);
    echo "\n✅ base.blade.php wiederhergestellt\n";
}

// Restore css-fix.blade.php
$cssFix = '/var/www/api-gateway/resources/views/vendor/filament-panels/components/layout/css-fix.blade.php';
$cssFixBackup = $cssFix . '.backup';

if (file_exists($cssFixBackup)) {
    copy($cssFixBackup, $cssFix);
    echo "✅ css-fix.blade.php wiederhergestellt\n";
}

echo "\n📊 Zusammenfassung:\n";
echo "==================\n";
echo "✅ {$restoredCount} Scripts wiederhergestellt\n";
echo "✅ System wieder im Originalzustand\n";