<?php

namespace App\Filament\Admin\Resources\Concerns;

use Filament\Tables\Table;

trait HasManyColumns
{
    /**
     * Configure table to handle many toggleable columns properly.
     * This sets appropriate max height and width for the column toggle dropdown.
     */
    protected static function configureTableForManyColumns(Table $table): Table
    {
        return $table
            // Set max height for desktop (70vh or 600px, whichever is smaller)
            ->columnToggleFormMaxHeight('min(70vh, 600px)')
            // Increase width to accommodate longer column names
            ->columnToggleFormWidth('md')
            // Use 2 columns layout for better organization when there are many columns
            ->columnToggleFormColumns(2);
    }
}