<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class TestMinimalDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $title = 'Test Minimal Dashboard';
    protected static ?string $navigationLabel = 'Test Dashboard';
    protected static ?int $navigationSort = 999;
    protected static ?string $navigationGroup = 'System';
    protected static string $view = 'filament.admin.pages.test-minimal-dashboard';
    
    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasRole(['Super Admin', 'super_admin', 'developer']) || $user->email === 'dev@askproai.de');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
    
    public $testData = 'Hello World';
    
    public function mount(): void
    {
        // Do nothing - minimal test
    }
}