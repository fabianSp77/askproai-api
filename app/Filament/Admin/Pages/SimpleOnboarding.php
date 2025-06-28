<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Notifications\Notification;

class SimpleOnboarding extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';
    protected static ?string $navigationLabel = 'Schnellstart';
    protected static ?string $title = 'AskProAI Schnellstart';
    protected static ?string $slug = 'simple-onboarding';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationGroup = 'Einstellungen';
    
    protected static string $view = 'filament.admin.pages.simple-onboarding';
    
    public static function shouldRegisterNavigation(): bool
    {
        return false; // Deaktiviert - Use QuickSetupWizard instead
    }
    
    public function mount(): void
    {
        // Einfache Mount Methode ohne Abhängigkeiten
    }
    
    public function getHeading(): string
    {
        return 'Willkommen bei AskProAI';
    }
    
    public function getSubheading(): ?string
    {
        return 'Hier finden Sie eine Übersicht der wichtigsten Funktionen und erste Schritte.';
    }
}