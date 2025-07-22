<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class SimpleDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Simple Dashboard';

    protected static string $view = 'filament.admin.pages.simple-dashboard';

    public static function canAccess(): bool
    {
        // Allow access to everyone for testing
        return true;
    }

    public function getTitle(): string
    {
        return 'Simple Dashboard - Test';
    }

    public function getHeading(): string
    {
        return 'Simple Dashboard Test';
    }
}
