<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class QuickDocs extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-bolt';
    protected static ?string $navigationLabel = 'Quick Docs';
    protected static ?string $slug = 'quick-docs';
    protected static string $view = 'filament.admin.pages.quick-docs';
    protected static ?int $navigationSort = 2;
    
    public static function getNavigationGroup(): ?string
    {
        return 'System';
    }
    
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole([
            'super_admin', 
            'Super Admin',
            'company_admin',
            'Company Admin'
        ]);
    }
    
    public $criticalDocs = [];
    public $processDocs = [];
    public $technicalDocs = [];
    
    public function mount(): void
    {
        // Kritische Business-Dokumente (mit Diagrammen)
        $this->criticalDocs = [
            [
                'title' => '🚀 5-Min Onboarding',
                'description' => 'Neukunde in 5 Minuten live schalten',
                'url' => '/mkdocs/5-MINUTEN_ONBOARDING_PLAYBOOK/',
                'features' => ['Interaktive Checkliste', 'Branchen-Templates', 'Fehler-Diagnose'],
                'icon' => 'rocket-launch',
                'color' => 'success',
            ],
            [
                'title' => '🚨 Notfall-Response',
                'description' => '24/7 Krisenmanagement Playbook',
                'url' => '/mkdocs/EMERGENCY_RESPONSE_PLAYBOOK/',
                'features' => ['Grün/Gelb/Rot Zonen', 'Auto-Healing', 'Eskalations-Kette'],
                'icon' => 'fire',
                'color' => 'danger',
            ],
            [
                'title' => '🔍 Troubleshooting',
                'description' => 'Problem → Lösung in max 5 Klicks',
                'url' => '/mkdocs/TROUBLESHOOTING_DECISION_TREE/',
                'features' => ['Decision Trees', 'Auto-Fix Commands', 'Live Diagnose'],
                'icon' => 'magnifying-glass',
                'color' => 'warning',
            ],
        ];
        
        // Prozess-Visualisierungen (mit Diagrammen)
        $this->processDocs = [
            [
                'title' => '📞 Anruf → Termin',
                'description' => 'Kompletter Datenfluss visualisiert',
                'url' => '/mkdocs/PHONE_TO_APPOINTMENT_FLOW/',
                'features' => ['5 Phasen Detail', 'Latenz-Metriken', 'Debug Points'],
                'icon' => 'phone',
                'color' => 'info',
            ],
            [
                'title' => '📊 KPI Dashboard',
                'description' => 'ROI beweisen & Erfolg messen',
                'url' => '/mkdocs/KPI_DASHBOARD_TEMPLATE/',
                'features' => ['ROI Calculator', 'Live Metriken', 'Branchen-KPIs'],
                'icon' => 'chart-bar',
                'color' => 'success',
            ],
            [
                'title' => '🏥 Health Monitor',
                'description' => 'Service Status & Auto-Healing',
                'url' => '/mkdocs/INTEGRATION_HEALTH_MONITOR/',
                'features' => ['Live Status', 'Circuit Breaker', 'Alert Rules'],
                'icon' => 'heart',
                'color' => 'primary',
            ],
        ];
        
        // Technische Dokumentation
        $this->technicalDocs = [
            [
                'title' => '📚 Hauptdokumentation',
                'description' => 'CLAUDE.md - Alles auf einen Blick',
                'url' => '/mkdocs/CLAUDE/',
                'icon' => 'book-open',
            ],
            [
                'title' => '🏗️ Service Architektur',
                'description' => '69 Services visualisiert',
                'url' => '/mkdocs/architecture/services/',
                'icon' => 'cube',
            ],
            [
                'title' => '🔐 API Reference',
                'description' => 'Endpoints & Webhooks',
                'url' => '/mkdocs/api/webhooks/',
                'icon' => 'key',
            ],
        ];
    }
}