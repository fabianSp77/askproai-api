<?php
// Temporärer Fix: Füge Redis Options ohne Prefix zur database.php hinzu

$configFile = __DIR__ . '/config/database.php';
$content = file_get_contents($configFile);

// Suche nach der Redis-Konfiguration
$redisPattern = "/'redis' => \[/";
$replacement = "'redis' => [
        
        'client' => env('REDIS_CLIENT', 'phpredis'),
        
        'options' => [
            'prefix' => env('REDIS_PREFIX', ''),
        ],";

// Ersetze die Redis-Konfiguration
$newContent = preg_replace($redisPattern, $replacement, $content, 1);

// Speichere die Datei
file_put_contents($configFile, $newContent);

echo "✅ Redis Prefix in database.php angepasst\n";

// Cache leeren
shell_exec('php artisan config:clear');
echo "✅ Config Cache geleert\n";