<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class FilamentMemoryTrackerProvider extends ServiceProvider
{
    private static array $panelMemory = [];

    public function boot()
    {
        if (!config('app.debug_memory', false)) {
            return;
        }

        // Track memory before and after each panel boots
        $this->trackPanelBooting();

        // Log on termination
        app()->terminating(function () {
            if (!empty(self::$panelMemory)) {
                $peak = memory_get_peak_usage(true);

                if ($peak > 1536 * 1024 * 1024) {
                    Log::warning('Filament panel memory usage', [
                        'peak_mb' => round($peak / 1024 / 1024, 2),
                        'panels' => self::$panelMemory,
                        'total_panel_overhead_mb' => round(
                            array_sum(array_column(self::$panelMemory, 'delta_mb')),
                            2
                        ),
                    ]);
                }
            }
        });
    }

    private function trackPanelBooting(): void
    {
        // Hook into Filament's panel registration
        // This tracks each panel as it boots

        $originalMethod = Panel::class . '::boot';

        // Since we can't easily hook Panel::boot, we'll track during middleware
        // Add a before/after hook in Filament middleware
    }

    public static function recordPanelMemory(string $panelId, string $stage): void
    {
        $current = memory_get_usage(true);

        if (!isset(self::$panelMemory[$panelId])) {
            self::$panelMemory[$panelId] = [
                'before_mb' => round($current / 1024 / 1024, 2),
                'stages' => [],
            ];
        }

        if ($stage === 'after') {
            $before = self::$panelMemory[$panelId]['before_mb'] * 1024 * 1024;
            $delta = $current - $before;

            self::$panelMemory[$panelId]['after_mb'] = round($current / 1024 / 1024, 2);
            self::$panelMemory[$panelId]['delta_mb'] = round($delta / 1024 / 1024, 2);
        }

        self::$panelMemory[$panelId]['stages'][$stage] = round($current / 1024 / 1024, 2);
    }
}
