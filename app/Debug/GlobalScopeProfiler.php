<?php

namespace App\Debug;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Log;
use ReflectionClass;

/**
 * Profile global scopes to identify memory-heavy operations
 */
class GlobalScopeProfiler
{
    private static array $scopeStats = [];
    private static bool $enabled = false;

    public static function enable(): void
    {
        self::$enabled = true;
    }

    public static function profileModel(Model $model): void
    {
        if (!self::$enabled) {
            return;
        }

        $reflection = new ReflectionClass($model);
        $className = $reflection->getName();

        // Check for global scopes
        $globalScopes = $model->getGlobalScopes();

        if (empty($globalScopes)) {
            return;
        }

        foreach ($globalScopes as $identifier => $scope) {
            $scopeClass = is_object($scope) ? get_class($scope) : 'closure';

            $key = "{$className}::{$scopeClass}";

            if (!isset(self::$scopeStats[$key])) {
                self::$scopeStats[$key] = [
                    'model' => $className,
                    'scope' => $scopeClass,
                    'apply_count' => 0,
                    'first_seen_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                    'is_closure' => $scopeClass === 'closure',
                ];
            }

            self::$scopeStats[$key]['apply_count']++;
            self::$scopeStats[$key]['last_seen_mb'] = round(memory_get_usage(true) / 1024 / 1024, 2);
        }
    }

    public static function report(): void
    {
        if (empty(self::$scopeStats)) {
            return;
        }

        // Sort by apply count
        uasort(self::$scopeStats, fn($a, $b) => $b['apply_count'] <=> $a['apply_count']);

        Log::info('Global scope profiling report', [
            'total_unique_scopes' => count(self::$scopeStats),
            'top_scopes' => array_slice(self::$scopeStats, 0, 20, true),
            'closure_scopes' => array_filter(self::$scopeStats, fn($s) => $s['is_closure']),
        ]);
    }

    public static function reset(): void
    {
        self::$scopeStats = [];
    }
}
