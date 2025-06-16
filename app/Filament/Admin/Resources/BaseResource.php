<?php

namespace App\Filament\Admin\Resources;

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;

abstract class BaseResource extends Resource
{
    /**
     * Define relationships to eager load for table listing
     */
    protected static array $tableRelations = [];
    
    /**
     * Define relationships to eager load for forms
     */
    protected static array $formRelations = [];
    
    /**
     * Define relationships to count for table listing
     */
    protected static array $tableCountRelations = [];
    
    /**
     * Modify the base query for tables with eager loading
     */
    public static function modifyTableQuery(Builder $query): Builder
    {
        // Apply eager loading
        if (!empty(static::$tableRelations)) {
            $query->with(static::$tableRelations);
        }
        
        // Apply relationship counts
        if (!empty(static::$tableCountRelations)) {
            $query->withCount(static::$tableCountRelations);
        }
        
        // Apply smart loading if model supports it
        $model = static::getModel();
        if (method_exists($model, 'scopeForListView')) {
            $query->forListView();
        }
        
        return $query;
    }
    
    /**
     * Modify the base query for forms with eager loading
     */
    public static function modifyFormQuery(Builder $query): Builder
    {
        // Apply eager loading for forms
        if (!empty(static::$formRelations)) {
            $query->with(static::$formRelations);
        }
        
        // Apply full loading if model supports it
        $model = static::getModel();
        if (method_exists($model, 'scopeWithFull')) {
            $query->withFull();
        }
        
        return $query;
    }
    
    /**
     * Get optimized query for exports
     */
    public static function getExportQuery(): Builder
    {
        $query = static::getEloquentQuery();
        
        // Use minimal loading for exports
        $model = static::getModel();
        if (method_exists($model, 'scopeWithMinimal')) {
            $query->withMinimal();
        }
        
        return $query;
    }
    
    /**
     * Detect and log N+1 queries in development
     */
    protected static function detectN1Queries(): void
    {
        if (config('app.debug')) {
            \DB::listen(function ($query) {
                if (preg_match('/select .* from .* where .* in \(/i', $query->sql)) {
                    logger()->warning('Potential N+1 in Filament Resource', [
                        'resource' => static::class,
                        'query' => $query->sql,
                        'time' => $query->time,
                    ]);
                }
            });
        }
    }
}