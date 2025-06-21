<?php

namespace App\Filament\Admin\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait HasEagerLoading
{
    /**
     * Get the relationships that should be eager loaded
     * 
     * @return array
     */
    protected static function getEagerLoadRelations(): array
    {
        return [];
    }
    
    /**
     * Apply eager loading to the query
     * 
     * @param Builder $query
     * @return Builder
     */
    protected static function applyEagerLoading(Builder $query): Builder
    {
        $relations = static::getEagerLoadRelations();
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        return $query;
    }
    
    /**
     * Get the table query with eager loading applied
     * 
     * @return Builder
     */
    protected static function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        
        return static::applyEagerLoading($query);
    }
    
    /**
     * Analyze and log N+1 queries in development
     * 
     * @param Model $record
     * @return void
     */
    protected static function analyzeN1Queries(Model $record): void
    {
        if (config('app.debug')) {
            $loaded = $record->getRelations();
            $model = get_class($record);
            
            // Log which relations are loaded
            if (empty($loaded)) {
                \Log::debug("N+1 Alert: No relations loaded for {$model}");
            }
        }
    }
}