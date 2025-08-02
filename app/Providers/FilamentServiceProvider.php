<?php

namespace App\Providers;

use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Override default toggleable behavior for all columns
        Column::macro('toggleableByDefault', function () {
            return $this->toggleable(isToggledHiddenByDefault: false);
        });
        
        // Make all TextColumns visible by default
        TextColumn::configureUsing(function (TextColumn $column): void {
            // If column is toggleable but not explicitly set to hidden, make it visible
            if (method_exists($column, 'isToggleable') && $column->isToggleable()) {
                $column->toggleable(isToggledHiddenByDefault: false);
            }
        });
    }
}