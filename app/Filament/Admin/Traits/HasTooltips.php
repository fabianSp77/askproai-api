<?php

namespace App\Filament\Admin\Traits;

trait HasTooltips
{
    protected function tooltip(string $text): array
    {
        return [
            "tooltip" => $text,
        ];
    }
}
