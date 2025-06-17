<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class SystemHealthBasic extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-heart';
    protected static ?string $navigationLabel = 'System Health Basic';
    protected static ?string $navigationGroup = 'System & Monitoring';
    protected static string $view = 'filament.admin.pages.system-health-basic';
    protected static ?int $navigationSort = 10;
    
    public static function shouldRegisterNavigation(): bool
    {
        return false; // Deaktiviert zugunsten von SystemHealthSimple
    }
    
    public string $message = 'This is a basic system health page';
    
    public function mount(): void
    {
        // Absolutely nothing here
    }
}