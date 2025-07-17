<?php

// Temporarily disable problematic JavaScript files for demo

$filesToRename = [
    '/var/www/api-gateway/public/js/alpine-error-handler.js' => '/var/www/api-gateway/public/js/alpine-error-handler.js.disabled',
    '/var/www/api-gateway/public/js/widget-display-fix.js' => '/var/www/api-gateway/public/js/widget-display-fix.js.disabled',
];

echo "🔧 Deaktiviere problematische Scripts für Demo...\n\n";

foreach ($filesToRename as $from => $to) {
    if (file_exists($from)) {
        if (rename($from, $to)) {
            echo "✅ Deaktiviert: " . basename($from) . "\n";
        } else {
            echo "❌ Fehler beim Deaktivieren: " . basename($from) . "\n";
        }
    } else {
        echo "⚠️  Nicht gefunden: " . basename($from) . "\n";
    }
}

// Clear cache
echo "\n🗑️  Cache wird geleert...\n";
exec('php artisan optimize:clear', $output);
foreach ($output as $line) {
    echo $line . "\n";
}

echo "\n✅ Fertig! Buttons sollten jetzt funktionieren.\n";
echo "📌 Nach der Demo wieder aktivieren mit: php restore-scripts.php\n";