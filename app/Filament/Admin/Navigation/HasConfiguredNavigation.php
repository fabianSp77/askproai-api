<?php

namespace App\Filament\Admin\Navigation;

trait HasConfiguredNavigation
{
    public static function getNavigationGroup(): ?string
    {
        return static::$navigationGroup ?? null;
    }
    
    public static function getNavigationSort(): ?int
    {
        return static::$navigationSort ?? 999;
    }
    
    public static function getNavigationIcon(): string
    {
        return static::$navigationIcon ?? "heroicon-o-rectangle-stack";
    }
    
    public static function getNavigationLabel(): string
    {
        return static::$navigationLabel ?? static::getPluralModelLabel();
    }
}
