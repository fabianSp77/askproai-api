<?php

namespace App\Filament\Admin\Resources\CallResource\Widgets;

use Filament\Widgets\Widget;

class InlineTabInfoWidget extends Widget
{
    protected static string $view = 'filament.admin.resources.call-resource.widgets.inline-tab-info';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = -1; // Vor anderen Widgets anzeigen
    
    public static function canView(): bool
    {
        return true;
    }
}