<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class QueryOptimizer
{
    /**
     * Optimize calls with appointment queries
     */
    public static function whereHasAppointment(Builder $query): Builder
    {
        // Use indexed column if available
        if (Schema::hasColumn('calls', 'has_appointment')) {
            return $query->where('has_appointment', true);
        }
        
        // Fallback to optimized metadata query
        return $query->where(function($q) {
            $q->whereNotNull('metadata');
            
            // Use database-specific JSON queries if available
            if (DB::connection()->getDriverName() === 'mysql') {
                $q->whereRaw("JSON_CONTAINS_PATH(metadata, 'one', ?)", ['$.appointment_id']);
            } elseif (DB::connection()->getDriverName() === 'pgsql') {
                $q->whereRaw("metadata::jsonb ? ?", ['appointment_id']);
            } else {
                // Fallback to LIKE query
                $q->where('metadata', 'like', '%appointment%');
            }
        });
    }
    
    /**
     * Optimize cost calculations
     */
    public static function selectWithCost(Builder $query, string $alias = 'calculated_cost'): Builder
    {
        // Use stored column if available
        if (Schema::hasColumn('calls', 'calculated_cost')) {
            return $query->addSelect(['calculated_cost as ' . $alias]);
        }
        
        // Calculate on the fly
        return $query->addSelect(DB::raw("(duration_sec * 0.02) as " . DB::connection()->getPdo()->quote($alias)));
    }
    
    /**
     * Optimize appointment revenue queries
     */
    public static function joinServicesForPrice(Builder $query): Builder
    {
        // Only join if not already joined
        $joins = collect($query->getQuery()->joins);
        $hasServicesJoin = $joins->contains(function ($join) {
            return $join->table === 'services';
        });
        
        if (!$hasServicesJoin) {
            $query->leftJoin('services', 'appointments.service_id', '=', 'services.id');
        }
        
        return $query;
    }
    
    /**
     * Add efficient aggregation for call costs
     */
    public static function sumCallCosts(Builder $query): float
    {
        // Clone query to avoid modifying original
        $costQuery = clone $query;
        
        // Use calculated column if available
        if (Schema::hasColumn('calls', 'calculated_cost')) {
            return (float) $costQuery->sum('calculated_cost');
        }
        
        // Otherwise calculate from duration
        return (float) $costQuery->sum(DB::raw('duration_sec * 0.02'));
    }
    
    /**
     * Optimize branch active checks
     */
    public static function whereActiveBranch(Builder $query): Builder
    {
        // Use the correct column name
        return $query->where('is_active', true);
    }
    
    /**
     * Batch update has_appointment flags
     */
    public static function updateHasAppointmentFlags(): int
    {
        if (!Schema::hasColumn('calls', 'has_appointment')) {
            return 0;
        }
        
        // Skip appointment_id check since column doesn't exist
        $updated = 0;
        
        // Update calls with appointment in metadata
        if (DB::connection()->getDriverName() === 'mysql') {
            $updated += DB::update("
                UPDATE calls 
                SET has_appointment = 1 
                WHERE has_appointment = 0
                AND metadata IS NOT NULL
                AND JSON_CONTAINS_PATH(metadata, 'one', '$.appointment_id')
            ");
        } elseif (DB::connection()->getDriverName() === 'pgsql') {
            $updated += DB::update("
                UPDATE calls 
                SET has_appointment = true 
                WHERE has_appointment = false
                AND metadata IS NOT NULL
                AND metadata::jsonb ? 'appointment_id'
            ");
        } else {
            $updated += DB::table('calls')
                ->where('has_appointment', false)
                ->whereNotNull('metadata')
                ->where('metadata', 'like', '%appointment%')
                ->update(['has_appointment' => true]);
        }
        
        return $updated;
    }
}