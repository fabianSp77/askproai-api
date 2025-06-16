<?php

namespace App\Filament\Admin\Resources\CallResource\Widgets;

use Filament\Widgets\Widget;

class SimpleTabInfoWidget extends Widget
{
    protected static string $view = 'filament.admin.resources.call-resource.widgets.simple-tab-info';
    
    protected int | string | array $columnSpan = 'full';
    
    public static function canView(): bool
    {
        return true;
    }
}