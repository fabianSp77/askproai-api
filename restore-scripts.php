<?php

// Restore JavaScript files after demo

$filesToRestore = [
    '/var/www/api-gateway/public/js/alpine-error-handler.js.disabled' => '/var/www/api-gateway/public/js/alpine-error-handler.js',
    '/var/www/api-gateway/public/js/widget-display-fix.js.disabled' => '/var/www/api-gateway/public/js/widget-display-fix.js',
];

echo "🔧 Reaktiviere Scripts nach Demo...\n\n";

foreach ($filesToRestore as $from => $to) {
    if (file_exists($from)) {
        if (rename($from, $to)) {
            echo "✅ Reaktiviert: " . basename($to) . "\n";
        } else {
            echo "❌ Fehler beim Reaktivieren: " . basename($to) . "\n";
        }
    } else {
        echo "⚠️  Nicht gefunden: " . basename($from) . "\n";
    }
}

echo "\n✅ Scripts wurden wiederhergestellt.\n";