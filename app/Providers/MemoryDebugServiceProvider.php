<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class MemoryDebugServiceProvider extends ServiceProvider
{
    private static array $modelCounts = [];
    private static int $queryCount = 0;
    private static bool $enabled = false;

    public function boot()
    {
        // Enable only when memory debugging flag is set
        self::$enabled = config('app.debug_memory', false) ||
                         request()->hasHeader('X-Debug-Memory');

        if (!self::$enabled) {
            return;
        }

        // Track model instantiation
        Model::retrieved(function (Model $model) {
            $this->trackModel($model, 'retrieved');
        });

        Model::created(function (Model $model) {
            $this->trackModel($model, 'created');
        });

        Model::updated(function (Model $model) {
            $this->trackModel($model, 'updated');
        });

        // Track queries
        DB::listen(function ($query) {
            self::$queryCount++;

            $current = memory_get_usage(true);

            // Log queries that cause large memory jumps
            if (self::$queryCount > 1) {
                static $lastMemory = 0;
                $delta = $current - $lastMemory;

                if ($delta > 10 * 1024 * 1024) { // 10MB jump
                    Log::warning('Large memory jump after query', [
                        'query' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time,
                        'delta_mb' => round($delta / 1024 / 1024, 2),
                        'current_mb' => round($current / 1024 / 1024, 2),
                    ]);
                }

                $lastMemory = $current;
            }
        });

        // Log summary on request termination
        app()->terminating(function () {
            if (self::$enabled && !empty(self::$modelCounts)) {
                $summary = $this->generateSummary();

                if ($summary['peak_mb'] > 1536) {
                    Log::warning('Model instantiation summary', $summary);
                }
            }
        });
    }

    private function trackModel(Model $model, string $event): void
    {
        $class = get_class($model);
        $key = "{$class}::{$event}";

        if (!isset(self::$modelCounts[$key])) {
            self::$modelCounts[$key] = [
                'count' => 0,
                'first_seen_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            ];
        }

        self::$modelCounts[$key]['count']++;
        self::$modelCounts[$key]['last_seen_mb'] = round(memory_get_usage(true) / 1024 / 1024, 2);
    }

    private function generateSummary(): array
    {
        $sorted = self::$modelCounts;
        uasort($sorted, fn($a, $b) => $b['count'] <=> $a['count']);

        return [
            'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'current_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'total_queries' => self::$queryCount,
            'model_counts' => $sorted,
            'top_models' => array_slice($sorted, 0, 10, true),
            'total_model_instances' => array_sum(array_column($sorted, 'count')),
        ];
    }
}
