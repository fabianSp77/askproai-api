<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class BasicSystemStatus extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'Basic System Status';
    protected static ?string $navigationGroup = 'System & Monitoring';
    protected static string $view = 'filament.admin.pages.basic-system-status';
    protected static ?int $navigationSort = 7;
    
    public function mount(): void
    {
        // Nothing to mount - keep it simple
    }
}