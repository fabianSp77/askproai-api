<?php

namespace App\Filament\Admin\Traits;

trait HasConsistentNavigation
{
    public static function getNavigationGroup(): ?string
    {
        return static::$navigationGroup ?? null;
    }
    
    public static function getNavigationSort(): ?int
    {
        return static::$navigationSort ?? 999;
    }
}
