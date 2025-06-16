<?php

namespace App\Filament\Admin\Resources\Concerns;

use Filament\Tables\Table;

/**
 * Apply column toggle configuration globally or conditionally
 */
trait ConfiguresColumnToggle
{
    /**
     * Apply default column toggle configuration to a table.
     * This can be overridden in specific resources.
     */
    protected static function applyDefaultColumnToggleConfiguration(Table $table): Table
    {
        // Count toggleable columns
        $toggleableCount = 0;
        foreach ($table->getColumns() as $column) {
            if (method_exists($column, 'isToggleable') && $column->isToggleable()) {
                $toggleableCount++;
            }
        }
        
        // Apply configuration based on column count
        if ($toggleableCount > 10) {
            // Many columns: Use 2-column layout with larger dropdown
            $table
                ->columnToggleFormMaxHeight('min(70vh, 600px)')
                ->columnToggleFormWidth('lg')
                ->columnToggleFormColumns(2);
        } elseif ($toggleableCount > 5) {
            // Medium number of columns
            $table
                ->columnToggleFormMaxHeight('min(60vh, 500px)')
                ->columnToggleFormWidth('md')
                ->columnToggleFormColumns(1);
        }
        // For fewer columns, use Filament's defaults
        
        return $table;
    }
    
    /**
     * Check if the table needs column toggle optimization
     */
    protected static function needsColumnToggleOptimization(Table $table): bool
    {
        $toggleableCount = 0;
        foreach ($table->getColumns() as $column) {
            if (method_exists($column, 'isToggleable') && $column->isToggleable()) {
                $toggleableCount++;
            }
        }
        
        return $toggleableCount > 5;
    }
}