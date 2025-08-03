<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;

class CommandPaletteWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.command-palette-widget';
    
    protected static ?int $sort = -2;
    
    public static function canView(): bool
    {
        return true;
    }
}