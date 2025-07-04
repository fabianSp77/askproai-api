<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Process;

class DocumentationHub extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Dokumentation';
    protected static ?string $slug = 'documentation';
    protected static string $view = 'filament.admin.pages.documentation-hub';
    
    public static function getNavigationGroup(): ?string
    {
        return 'System & Verwaltung';
    }
    
    public static function getNavigationSort(): ?int
    {
        return 99;
    }
    
    public static function canAccess(): bool
    {
        // Check for both variations of role names (with spaces and underscores)
        return auth()->user()?->hasAnyRole([
            'super_admin', 
            'Super Admin',
            'company_admin',
            'Company Admin'
        ]);
    }
    
    public $documentationLinks = [];
    public $processLinks = [];
    public $quickCommands = [];
    
    public function mount(): void
    {
        // Hauptdokumentation
        $this->documentationLinks = [
            [
                'title' => '📚 Hauptdokumentation (CLAUDE.md)',
                'description' => 'Komplette Projektdokumentation mit allen Prozessen',
                'url' => '/mkdocs/CLAUDE/',
                'internal' => true,
            ],
            [
                'title' => '🚀 5-Minuten Onboarding',
                'description' => 'Schnellstart für neue Kunden',
                'url' => '/mkdocs/5-MINUTEN_ONBOARDING_PLAYBOOK/',
                'internal' => true,
            ],
            [
                'title' => '💡 Customer Success Runbook',
                'description' => 'Top 10 Kundenprobleme lösen',
                'url' => '/mkdocs/CUSTOMER_SUCCESS_RUNBOOK/',
                'internal' => true,
            ],
            [
                'title' => '🚨 Emergency Response',
                'description' => '24/7 Notfall-Prozeduren',
                'url' => '/mkdocs/EMERGENCY_RESPONSE_PLAYBOOK/',
                'internal' => true,
            ],
        ];
        
        // Prozessdokumentation
        $this->processLinks = [
            [
                'title' => '📞 Phone to Appointment Flow',
                'description' => 'Kompletter Datenfluss vom Anruf zum Termin',
                'url' => '/mkdocs/PHONE_TO_APPOINTMENT_FLOW/',
                'internal' => true,
            ],
            [
                'title' => '🔍 Troubleshooting Decision Tree',
                'description' => 'Problem zu Lösung in max 5 Klicks',
                'url' => '/mkdocs/TROUBLESHOOTING_DECISION_TREE/',
                'internal' => true,
            ],
            [
                'title' => '📊 KPI Dashboard Template',
                'description' => 'ROI und Metriken Übersicht',
                'url' => '/mkdocs/KPI_DASHBOARD_TEMPLATE/',
                'internal' => true,
            ],
            [
                'title' => '🏥 Integration Health Monitor',
                'description' => 'Live Service Status mit Alerts',
                'url' => '/mkdocs/INTEGRATION_HEALTH_MONITOR/',
                'internal' => true,
            ],
            [
                'title' => '🔄 Automatische Dokumentations-Updates',
                'description' => 'Wie das Auto-Update System funktioniert',
                'url' => '/mkdocs/',
                'internal' => true,
            ],
            [
                'title' => '🏗️ System Architektur',
                'description' => 'Services, Models und Controller Übersicht',
                'url' => '/mkdocs/architecture/services/',
                'internal' => true,
            ],
            [
                'title' => '🔐 API Dokumentation',
                'description' => 'Endpoints, Authentication und Webhooks',
                'url' => '/mkdocs/api/webhooks/',
                'internal' => true,
            ],
        ];
        
        // Quick Commands
        $this->quickCommands = [
            [
                'label' => 'Dokumentation prüfen',
                'command' => 'php artisan docs:check-updates',
                'description' => 'Prüft ob Dokumentation aktualisiert werden muss',
            ],
            [
                'label' => 'Auto-Fix anwenden',
                'command' => 'php artisan docs:check-updates --auto-fix',
                'description' => 'Wendet automatische Fixes an',
            ],
            [
                'label' => 'Git Hooks installieren',
                'command' => './scripts/setup-doc-hooks.sh',
                'description' => 'Installiert automatische Dokumentations-Prüfung',
            ],
            [
                'label' => 'MkDocs bauen',
                'command' => 'mkdocs build',
                'description' => 'Generiert Web-Dokumentation',
            ],
        ];
    }
    
    public function runCommand(string $command): void
    {
        $result = Process::run($command);
        
        if ($result->successful()) {
            $this->notify('success', 'Befehl erfolgreich ausgeführt');
        } else {
            $this->notify('danger', 'Fehler: ' . $result->errorOutput());
        }
    }
}