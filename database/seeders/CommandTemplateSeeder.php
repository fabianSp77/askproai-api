<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CommandTemplate;
use App\Models\User;

class CommandTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $firstUser = User::first();
        
        // System Management Commands
        CommandTemplate::create([
            'name' => 'clear_all_caches',
            'title' => 'Cache löschen',
            'icon' => '🗑️',
            'category' => 'system',
            'description' => 'Löscht alle Caches (Application, Config, Route, View)',
            'command_template' => 'php artisan optimize:clear',
            'parameters' => [],
            'nlp_keywords' => ['cache', 'clear', 'clean', 'löschen', 'bereinigen'],
            'shortcut' => 'ctrl+shift+c',
            'is_public' => true,
            'created_by' => $firstUser?->id,
        ]);
        
        CommandTemplate::create([
            'name' => 'run_migrations',
            'title' => 'Migrationen ausführen',
            'icon' => '🔄',
            'category' => 'database',
            'description' => 'Führt alle ausstehenden Datenbank-Migrationen aus',
            'command_template' => 'php artisan migrate --force',
            'parameters' => [],
            'nlp_keywords' => ['migrate', 'migration', 'database', 'update', 'aktualisieren'],
            'shortcut' => 'ctrl+m',
            'is_public' => true,
            'created_by' => $firstUser?->id,
        ]);
        
        // Retell.ai Commands
        CommandTemplate::create([
            'name' => 'import_retell_calls',
            'title' => 'Retell Anrufe importieren',
            'icon' => '📞',
            'category' => 'retell',
            'description' => 'Importiert die neuesten Anrufe von Retell.ai',
            'command_template' => 'mcp:retell.importRecentCalls({{hours}})',
            'parameters' => [
                ['name' => 'hours', 'type' => 'number', 'default' => 24, 'description' => 'Stunden zurück']
            ],
            'nlp_keywords' => ['retell', 'calls', 'anrufe', 'import', 'fetch', 'abrufen'],
            'is_public' => true,
            'created_by' => $firstUser?->id,
        ]);
        
        CommandTemplate::create([
            'name' => 'retell_call_stats',
            'title' => 'Anruf-Statistiken anzeigen',
            'icon' => '📊',
            'category' => 'retell',
            'description' => 'Zeigt Statistiken zu Retell.ai Anrufen',
            'command_template' => 'mcp:retell.getCallStats({{days}})',
            'parameters' => [
                ['name' => 'days', 'type' => 'number', 'default' => 7, 'description' => 'Tage']
            ],
            'nlp_keywords' => ['retell', 'stats', 'statistics', 'anrufe', 'statistik'],
            'is_public' => true,
            'created_by' => $firstUser?->id,
        ]);
        
        // Cal.com Commands
        CommandTemplate::create([
            'name' => 'sync_calcom_events',
            'title' => 'Cal.com Events synchronisieren',
            'icon' => '📅',
            'category' => 'calcom',
            'description' => 'Synchronisiert Event-Typen mit Cal.com',
            'command_template' => 'php artisan calcom:sync-event-types',
            'parameters' => [],
            'nlp_keywords' => ['calcom', 'sync', 'events', 'calendar', 'kalender', 'synchronisieren'],
            'is_public' => true,
            'created_by' => $firstUser?->id,
        ]);
        
        CommandTemplate::create([
            'name' => 'check_calcom_availability',
            'title' => 'Verfügbarkeit prüfen',
            'icon' => '🕐',
            'category' => 'calcom',
            'description' => 'Prüft verfügbare Termine für einen Service',
            'command_template' => 'mcp:calcom.checkAvailability({{serviceId}}, {{date}})',
            'parameters' => [
                ['name' => 'serviceId', 'type' => 'number', 'required' => true, 'description' => 'Service ID'],
                ['name' => 'date', 'type' => 'date', 'required' => true, 'description' => 'Datum']
            ],
            'nlp_keywords' => ['availability', 'verfügbar', 'frei', 'termine', 'slots'],
            'is_public' => true,
            'created_by' => $firstUser?->id,
        ]);
        
        // Customer Management
        CommandTemplate::create([
            'name' => 'find_customer',
            'title' => 'Kunde suchen',
            'icon' => '🔍',
            'category' => 'customers',
            'description' => 'Sucht einen Kunden nach Telefonnummer',
            'command_template' => 'mcp:customer.findByPhone({{phone}})',
            'parameters' => [
                ['name' => 'phone', 'type' => 'string', 'required' => true, 'description' => 'Telefonnummer']
            ],
            'nlp_keywords' => ['customer', 'kunde', 'find', 'search', 'suchen', 'finden'],
            'is_public' => true,
            'created_by' => $firstUser?->id,
        ]);
        
        CommandTemplate::create([
            'name' => 'customer_appointments',
            'title' => 'Kundentermine anzeigen',
            'icon' => '📋',
            'category' => 'customers',
            'description' => 'Zeigt alle Termine eines Kunden',
            'command_template' => 'mcp:customer.getAppointments({{customerId}})',
            'parameters' => [
                ['name' => 'customerId', 'type' => 'number', 'required' => true, 'description' => 'Kunden ID']
            ],
            'nlp_keywords' => ['customer', 'appointments', 'termine', 'history', 'verlauf'],
            'is_public' => true,
            'created_by' => $firstUser?->id,
        ]);
        
        // Monitoring & Health
        CommandTemplate::create([
            'name' => 'system_health',
            'title' => 'System-Gesundheit prüfen',
            'icon' => '🏥',
            'category' => 'monitoring',
            'description' => 'Überprüft die Gesundheit aller Systeme',
            'command_template' => 'php artisan health:check',
            'parameters' => [],
            'nlp_keywords' => ['health', 'status', 'gesundheit', 'check', 'prüfen'],
            'is_public' => true,
            'created_by' => $firstUser?->id,
        ]);
        
        CommandTemplate::create([
            'name' => 'queue_status',
            'title' => 'Queue Status',
            'icon' => '📬',
            'category' => 'monitoring',
            'description' => 'Zeigt den Status der Warteschlangen',
            'command_template' => 'php artisan horizon:status',
            'parameters' => [],
            'nlp_keywords' => ['queue', 'horizon', 'jobs', 'warteschlange', 'status'],
            'is_public' => true,
            'created_by' => $firstUser?->id,
        ]);
        
        // Development & Testing
        CommandTemplate::create([
            'name' => 'run_tests',
            'title' => 'Tests ausführen',
            'icon' => '🧪',
            'category' => 'development',
            'description' => 'Führt die Test-Suite aus',
            'command_template' => 'php artisan test {{filter}}',
            'parameters' => [
                ['name' => 'filter', 'type' => 'string', 'description' => 'Test Filter (optional)']
            ],
            'nlp_keywords' => ['test', 'testing', 'phpunit', 'check', 'verify'],
            'is_public' => true,
            'created_by' => $firstUser?->id,
        ]);
        
        CommandTemplate::create([
            'name' => 'generate_api_docs',
            'title' => 'API Dokumentation generieren',
            'icon' => '📚',
            'category' => 'development',
            'description' => 'Generiert die API Dokumentation',
            'command_template' => 'php artisan scribe:generate',
            'parameters' => [],
            'nlp_keywords' => ['api', 'docs', 'documentation', 'generate', 'swagger'],
            'is_public' => true,
            'created_by' => $firstUser?->id,
        ]);
        
        // Business Intelligence
        CommandTemplate::create([
            'name' => 'daily_report',
            'title' => 'Tagesbericht erstellen',
            'icon' => '📈',
            'category' => 'reports',
            'description' => 'Erstellt einen Tagesbericht mit KPIs',
            'command_template' => 'mcp:reports.generateDaily({{date}})',
            'parameters' => [
                ['name' => 'date', 'type' => 'date', 'default' => 'today', 'description' => 'Datum']
            ],
            'nlp_keywords' => ['report', 'daily', 'bericht', 'täglich', 'kpi'],
            'is_public' => true,
            'created_by' => $firstUser?->id,
        ]);
        
        CommandTemplate::create([
            'name' => 'appointment_analytics',
            'title' => 'Termin-Analyse',
            'icon' => '📊',
            'category' => 'reports',
            'description' => 'Analysiert Terminbuchungen und No-Shows',
            'command_template' => 'mcp:analytics.appointmentInsights({{startDate}}, {{endDate}})',
            'parameters' => [
                ['name' => 'startDate', 'type' => 'date', 'required' => true, 'description' => 'Start'],
                ['name' => 'endDate', 'type' => 'date', 'required' => true, 'description' => 'Ende']
            ],
            'nlp_keywords' => ['analytics', 'appointments', 'analyse', 'insights', 'no-show'],
            'is_public' => true,
            'created_by' => $firstUser?->id,
        ]);
        
        // Quick Actions
        CommandTemplate::create([
            'name' => 'backup_database',
            'title' => 'Datenbank-Backup erstellen',
            'icon' => '💾',
            'category' => 'backup',
            'description' => 'Erstellt ein Backup der Datenbank',
            'command_template' => 'php artisan backup:run --only-db',
            'parameters' => [],
            'nlp_keywords' => ['backup', 'database', 'sicherung', 'export'],
            'is_public' => false,
            'created_by' => $firstUser?->id,
        ]);
        
        CommandTemplate::create([
            'name' => 'optimize_images',
            'title' => 'Bilder optimieren',
            'icon' => '🖼️',
            'category' => 'optimization',
            'description' => 'Optimiert alle Bilder für bessere Performance',
            'command_template' => 'php artisan media:optimize',
            'parameters' => [],
            'nlp_keywords' => ['optimize', 'images', 'bilder', 'compress', 'performance'],
            'is_public' => true,
            'created_by' => $firstUser?->id,
        ]);
    }
}