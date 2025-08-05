<?php

namespace App\Filament\Admin\Traits;

trait HasTooltips
{
    protected static function tooltip(string $text): array
    {
        return [
            "tooltip" => $text,
        ];
    }
    
    protected static function applyTableActionTooltips(array $actions): array
    {
        // Simply return the actions without modification
        // This method exists for backward compatibility
        return $actions;
    }
}
