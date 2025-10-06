<?php

namespace App\Filament\Traits;

use App\Models\UserPreference;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

trait HasColumnOrdering
{
    /**
     * Apply column ordering to a table
     *
     * @param Table $table The Filament table instance
     * @param string $resourceName The resource name for preference storage
     * @return Table The modified table instance
     *
     * Note: This method reorders columns based on user preferences stored in UserPreference model.
     * It does NOT add Alpine.js attributes as the Table class doesn't support extraAttributes methods.
     */
    protected static function applyColumnOrdering(Table $table, string $resourceName): Table
    {
        try {
            // Get the user's column preferences
            $userId = auth()->id();

            // Safety check: ensure we have a valid user ID
            if (!$userId) {
                return $table;
            }

            $columnOrder = UserPreference::getColumnOrder($userId, $resourceName);
            $columnVisibility = UserPreference::getColumnVisibility($userId, $resourceName);

        // Get all columns from the table
        $columns = $table->getColumns();

        if (!empty($columnOrder)) {
            // Reorder columns based on user preference
            $orderedColumns = [];

            // First, add columns in the saved order
            foreach ($columnOrder as $columnKey) {
                foreach ($columns as $column) {
                    if ($column->getName() === $columnKey) {
                        // Apply visibility setting
                        if (isset($columnVisibility[$columnKey]) && !$columnVisibility[$columnKey]) {
                            $column->toggleable(isToggledHiddenByDefault: true);
                        }
                        $orderedColumns[] = $column;
                        break;
                    }
                }
            }

            // Then, add any new columns that weren't in the saved order
            foreach ($columns as $column) {
                $found = false;
                foreach ($orderedColumns as $orderedColumn) {
                    if ($orderedColumn->getName() === $column->getName()) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $orderedColumns[] = $column;
                }
            }

            // Apply the reordered columns back to the table
            $table->columns($orderedColumns);
        }

            // Note: Column ordering functionality works through UserPreference model
            // Alpine.js attributes removed as extraTableAttributes() method doesn't exist on Table class

            return $table;
        } catch (\Exception $e) {
            // Log the error but don't break the page
            \Log::error('HasColumnOrdering trait error', [
                'resource' => $resourceName,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return the original table if anything goes wrong
            return $table;
        }
    }

    /**
     * Get column data for the column manager
     */
    protected static function getColumnData(Table $table): array
    {
        $columns = [];

        foreach ($table->getColumns() as $column) {
            $columns[] = [
                'key' => $column->getName(),
                'label' => $column->getLabel(),
                'hiddenByDefault' => method_exists($column, 'isToggledHiddenByDefault')
                    ? $column->isToggledHiddenByDefault()
                    : false,
            ];
        }

        return $columns;
    }
}