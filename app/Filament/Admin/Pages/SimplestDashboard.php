<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class SimplestDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Simplest Dashboard';
    protected static ?int $navigationSort = 997;
    protected static string $view = 'filament.admin.pages.simplest-dashboard';
    
    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasRole(['Super Admin', 'super_admin', 'developer']) || $user->email === 'dev@askproai.de');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
    
    public function mount(): void
    {
        // Do absolutely nothing
    }
}