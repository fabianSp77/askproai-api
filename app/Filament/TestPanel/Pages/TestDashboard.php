<?php

namespace App\Filament\TestPanel\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class TestDashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    # KEIN $slug – Filament vergibt ihn automatisch
}
