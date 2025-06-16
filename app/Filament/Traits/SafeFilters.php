<?php

namespace App\Filament\Traits;

use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

trait SafeFilters
{
    /**
     * Create a safe select filter that handles null relationships
     */
    protected function safeSelectFilter(string $name, string $relationship, string $titleAttribute = 'name'): SelectFilter
    {
        return SelectFilter::make($name)
            ->relationship($relationship, $titleAttribute)
            ->searchable()
            ->preload()
            ->multiple()
            ->query(function (Builder $query, array $data) use ($name) {
                try {
                    if (!empty($data['values'])) {
                        return $query->whereIn($name, $data['values']);
                    }
                } catch (\Exception $e) {
                    Log::error('Filter error', [
                        'filter' => $name,
                        'error' => $e->getMessage(),
                        'data' => $data
                    ]);
                }
                return $query;
            });
    }
    
    /**
     * Create a safe date range filter
     */
    protected function safeDateRangeFilter(string $column, string $label = 'Datum'): Filter
    {
        return Filter::make($column . '_range')
            ->label($label)
            ->form([
                DatePicker::make($column . '_from')
                    ->label('Von'),
                DatePicker::make($column . '_until')
                    ->label('Bis'),
            ])
            ->query(function (Builder $query, array $data) use ($column) {
                try {
                    return $query
                        ->when(
                            $data[$column . '_from'] ?? null,
                            fn (Builder $query, $date): Builder => $query->whereDate($column, '>=', $date),
                        )
                        ->when(
                            $data[$column . '_until'] ?? null,
                            fn (Builder $query, $date): Builder => $query->whereDate($column, '<=', $date),
                        );
                } catch (\Exception $e) {
                    Log::error('Date filter error', [
                        'column' => $column,
                        'error' => $e->getMessage(),
                        'data' => $data
                    ]);
                    return $query;
                }
            });
    }
    
    /**
     * Safe filter wrapper that catches all exceptions
     */
    protected function safeFilter(callable $filterCreator): ?Filter
    {
        try {
            return $filterCreator();
        } catch (\Exception $e) {
            Log::error('Filter creation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
    
    /**
     * Get safe filters array, removing any null values
     */
    protected function getSafeFilters(array $filters): array
    {
        return array_filter($filters, fn($filter) => $filter !== null);
    }
}