<?php

// tests/bootstrap.php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

// Funktion zum einmaligen Migrieren der Test-DB
function bootstrap_run_migrations_once()
{
    // Verwende eine statische Variable oder eine temporäre Datei, um zu prüfen, ob schon migriert wurde
    $migrationMarker = __DIR__.'/../bootstrap/cache/.phpunit_migrated'; // Marker-Datei

    if (! file_exists($migrationMarker)) {
        // Laden der Laravel-Anwendung NUR für die Migration
        // Wichtig: Relative Pfade verwenden!
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        // Stelle sicher, dass die Test-DB-Konfiguration geladen wird
        // (aus phpunit.xml oder .env.testing, falls vorhanden)
        // Wir setzen sie hier nochmal explizit zur Sicherheit
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);

        echo "BOOTSTRAP: Running migrate:fresh for testing database...\n";
        try {
            Artisan::call('migrate:fresh', ['--force' => true]);
            echo "BOOTSTRAP: Migrations completed.\n";
            // Erstelle die Marker-Datei, damit dies nicht nochmal passiert
            file_put_contents($migrationMarker, 'done');
        } catch (\Throwable $e) {
            echo 'BOOTSTRAP: FATAL ERROR DURING MIGRATION: '.$e->getMessage()."\n";
            // Lösche die Marker-Datei im Fehlerfall, um es beim nächsten Mal erneut zu versuchen
            @unlink($migrationMarker);
            exit(1); // Beende den Testlauf sofort
        }
        // Laravel Instanz für Migration hier beenden/vergessen (optional)
        // $app->flush();
        // unset($app);
    } else {
        // echo "BOOTSTRAP: Migrations already run for this test session.\n";
    }
}

// Funktion direkt beim Laden dieser Datei aufrufen
bootstrap_run_migrations_once();

// Lade den Composer Autoloader (wird von phpunit.xml's bootstrap auch gemacht, aber schadet nicht)
require __DIR__.'/../vendor/autoload.php';
