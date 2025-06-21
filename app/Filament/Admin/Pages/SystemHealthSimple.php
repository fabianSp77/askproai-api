<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;

class SystemHealthSimple extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-heart';
    protected static ?string $navigationLabel = 'System Health (Simple)';
    protected static ?string $navigationGroup = 'System & Überwachung';
    protected static string $view = 'filament.admin.pages.system-health-simple';
    protected static ?int $navigationSort = 8;
    
    public static function shouldRegisterNavigation(): bool
    {
        return false; // Deaktiviert - redundante Monitoring-Seite
    }
    
    public int $health = 95;
    public int $calls = 42;
    public int $queue = 12;
    
    public function mount(): void
    {
        Log::info('SystemHealthSimple mounting');
    }
}