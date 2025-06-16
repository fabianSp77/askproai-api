<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\QueryMonitor;
use App\Services\QueryOptimizer;
use App\Services\QueryCache;

class QueryOptimizationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register QueryOptimizer as singleton
        $this->app->singleton(QueryOptimizer::class, function ($app) {
            return new QueryOptimizer();
        });
        
        // Register QueryMonitor as singleton
        $this->app->singleton(QueryMonitor::class, function ($app) {
            return new QueryMonitor();
        });
        
        // Register QueryCache as singleton
        $this->app->singleton(QueryCache::class, function ($app) {
            return new QueryCache();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Enable query monitoring in development
        if ($this->app->environment('local', 'development')) {
            $monitor = $this->app->make(QueryMonitor::class);
            $monitor->setSlowQueryThreshold(500); // 500ms in development
            $monitor->enable();
        }
        
        // Enable query monitoring in production with higher threshold
        if ($this->app->environment('production') && config('app.debug')) {
            $monitor = $this->app->make(QueryMonitor::class);
            $monitor->setSlowQueryThreshold(2000); // 2 seconds in production
            $monitor->enable();
        }
        
        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\AnalyzeQueryPerformance::class,
                \App\Console\Commands\EnableQueryMonitoring::class,
            ]);
        }
        
        // Add macros for query optimization
        $this->registerQueryMacros();
    }
    
    /**
     * Register query builder macros
     */
    private function registerQueryMacros(): void
    {
        // Add optimized macro to query builder
        \Illuminate\Database\Query\Builder::macro('optimized', function () {
            $optimizer = app(QueryOptimizer::class);
            
            // Automatically apply optimizations based on the query
            if ($this->from === 'appointments') {
                return $optimizer->optimizeAppointmentQuery($this);
            }
            
            if ($this->from === 'customers') {
                return $optimizer->optimizeCustomerQuery($this);
            }
            
            if ($this->from === 'calls') {
                return $optimizer->optimizeCallQuery($this);
            }
            
            if ($this->from === 'staff') {
                return $optimizer->optimizeStaffQuery($this);
            }
            
            return $this;
        });
        
        // Add cached aggregation macro
        \Illuminate\Database\Query\Builder::macro('cachedCount', function ($cacheMinutes = 5) {
            $cacheKey = 'query_count:' . md5($this->toSql() . serialize($this->getBindings()));
            
            return cache()->remember($cacheKey, $cacheMinutes * 60, function () {
                return $this->count();
            });
        });
        
        // Add cached sum macro
        \Illuminate\Database\Query\Builder::macro('cachedSum', function ($column, $cacheMinutes = 5) {
            $cacheKey = 'query_sum:' . md5($this->toSql() . serialize($this->getBindings()) . $column);
            
            return cache()->remember($cacheKey, $cacheMinutes * 60, function () use ($column) {
                return $this->sum($column);
            });
        });
        
        // Add force index macro for Eloquent
        \Illuminate\Database\Eloquent\Builder::macro('forceIndex', function ($index) {
            $optimizer = app(QueryOptimizer::class);
            $optimizer->forceIndex($this->getQuery(), $this->getModel()->getTable(), $index);
            return $this;
        });
        
        // Add query hint macro for Eloquent
        \Illuminate\Database\Eloquent\Builder::macro('hint', function ($hint) {
            $optimizer = app(QueryOptimizer::class);
            $optimizer->addQueryHint($this->getQuery(), $hint);
            return $this;
        });
    }
}