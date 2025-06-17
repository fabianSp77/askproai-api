<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== VERIFYING CORE TABLES ===\n\n";

$coreTables = [
    // Firmen & Struktur
    'companies' => 'Mandanten',
    'branches' => 'Filialen/Standorte',
    'phone_numbers' => 'Telefonnummern',
    
    // Personen
    'users' => 'System-Benutzer',
    'staff' => 'Mitarbeiter',
    'customers' => 'Kunden',
    
    // Termine & Anrufe
    'appointments' => 'Termine',
    'calls' => 'Anrufe',
    
    // Services
    'services' => 'Dienstleistungen',
    'staff_services' => 'Mitarbeiter-Services',
    'staff_event_types' => 'Mitarbeiter-Events',
    'working_hours' => 'Arbeitszeiten',
    
    // Cal.com
    'calcom_event_types' => 'Event Types',
    'calcom_bookings' => 'Buchungen',
    'calcom_sync_logs' => 'Sync Logs',
    
    // System
    'migrations' => 'Migrations',
    'jobs' => 'Queue Jobs',
    'failed_jobs' => 'Failed Jobs',
    'cache' => 'Cache',
    'cache_locks' => 'Cache Locks',
    
    // Sicherheit
    'permissions' => 'Berechtigungen',
    'roles' => 'Rollen',
    'role_has_permissions' => 'Rollen-Berechtigungen',
    'model_has_roles' => 'Model-Rollen',
    'model_has_permissions' => 'Model-Berechtigungen',
    
    // Bezahlung
    'invoices' => 'Rechnungen',
    'billing_periods' => 'Abrechnungszeiträume',
    'company_pricing' => 'Firmen-Preise',
    'branch_pricing_overrides' => 'Filial-Preise'
];

$found = 0;
$missing = [];

echo "Checking core tables...\n";
echo str_repeat("-", 60) . "\n";

foreach ($coreTables as $table => $description) {
    if (Schema::hasTable($table)) {
        $count = DB::table($table)->count();
        echo "✅ {$table} ({$description}) - {$count} records\n";
        $found++;
    } else {
        echo "❌ {$table} ({$description}) - MISSING\n";
        $missing[] = $table;
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "SUMMARY:\n";
echo "Core tables found: {$found}/" . count($coreTables) . "\n";

if (!empty($missing)) {
    echo "\nMISSING TABLES:\n";
    foreach ($missing as $table) {
        echo "- {$table}\n";
    }
}

// Show all remaining tables
$allTables = DB::select('SHOW TABLES');
$tableField = 'Tables_in_' . env('DB_DATABASE', 'askproai_db');
$remainingTables = array_map(function($t) use ($tableField) {
    return $t->$tableField;
}, $allTables);

$extraTables = array_diff($remainingTables, array_keys($coreTables));

if (!empty($extraTables)) {
    echo "\nEXTRA TABLES (not in core list):\n";
    foreach ($extraTables as $table) {
        echo "- {$table}\n";
    }
}

echo "\nTotal tables: " . count($remainingTables) . "\n";