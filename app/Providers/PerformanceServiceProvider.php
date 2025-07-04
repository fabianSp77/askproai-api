<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Http\Events\RequestHandled;

class PerformanceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Disable lazy loading in production to catch N+1 queries
        if ($this->app->isProduction()) {
            Model::preventLazyLoading();
        }

        // Monitor memory usage in debug mode
        if (config('app.debug')) {
            Event::listen(RequestHandled::class, function ($event) {
                if ($event->request->is('admin/*') || $event->request->is('api/*')) {
                    $memory = round(memory_get_peak_usage() / 1024 / 1024, 2);
                    $time = round(microtime(true) - (defined('LARAVEL_START') ? LARAVEL_START : $_SERVER['REQUEST_TIME_FLOAT']), 2);
                    
                    // Log if memory usage is high or response is slow
                    if ($memory > 256 || $time > 2) {
                        Log::warning('Performance Alert', [
                            'url' => $event->request->url(),
                            'method' => $event->request->method(),
                            'memory_mb' => $memory,
                            'time_seconds' => $time,
                            'user_id' => auth()->id(),
                        ]);
                    }
                    
                    // Add headers for debugging
                    $event->response->headers->set('X-Memory-Usage', $memory . 'MB');
                    $event->response->headers->set('X-Response-Time', $time . 's');
                }
            });
        }

        // Database query monitoring
        if (config('app.debug') && config('database.log_queries', false)) {
            DB::listen(function ($query) {
                if ($query->time > 1000) { // Log slow queries (>1s)
                    Log::warning('Slow Query Detected', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time_ms' => $query->time,
                        'connection' => $query->connectionName,
                    ]);
                }
            });
        }

        // Set statement timeout for MariaDB/MySQL to prevent long-running queries
        if (config('database.default') === 'mysql') {
            try {
                // Check if we're using MariaDB
                $version = DB::selectOne("SELECT VERSION() as version");
                $isMariaDB = str_contains(strtolower($version->version), 'mariadb');
                
                if ($isMariaDB) {
                    // MariaDB uses max_statement_time (in seconds)
                    DB::statement("SET SESSION max_statement_time = 30"); // 30 seconds
                    Log::debug('MariaDB statement timeout set to 30 seconds');
                } else {
                    // MySQL 5.7.8+ uses MAX_EXECUTION_TIME (in milliseconds)
                    DB::statement("SET SESSION MAX_EXECUTION_TIME = 30000"); // 30 seconds
                    Log::debug('MySQL execution timeout set to 30 seconds');
                }
            } catch (\Exception $e) {
                // Log but don't fail - some environments might not support this
                Log::debug('Could not set query timeout', [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}