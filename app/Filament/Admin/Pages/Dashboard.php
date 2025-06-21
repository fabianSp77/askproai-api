<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static string $routePath = '/';
    
    public function mount(): void
    {
        // Redirect to operational dashboard
        redirect()->to('/admin/dashboard');
    }
}