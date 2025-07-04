#!/usr/bin/env php
<?php

echo "🔄 Manueller Retell Call Import\n";
echo "Zeit: " . date('Y-m-d H:i:s') . " (Berliner Zeit)\n\n";

// Führe den Import aus
$output = shell_exec('cd /var/www/api-gateway && php artisan retell:fetch-calls --limit=50 2>&1');
echo $output . "\n";

// Log in Datei
$logFile = '/var/www/api-gateway/storage/logs/manual-retell-import.log';
$logEntry = date('Y-m-d H:i:s') . " - Import ausgeführt\n" . $output . "\n---\n";
file_put_contents($logFile, $logEntry, FILE_APPEND);