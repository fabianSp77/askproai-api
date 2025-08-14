<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Laravel\Horizon\Contracts\SupervisorRepository;

class HorizonHealth
{
    /** true = queues healthy, false = stalled/offline */
    public static function ok(): bool
    {
        return Cache::remember('horizon.health', 15, function () {
            try {
                /** @var SupervisorRepository $repo */
                $repo = app(SupervisorRepository::class);
                foreach ($repo->all() as $sup) {
                    if ($sup->status !== 'running') {
                        return false;
                    }
                }

                return true;
            } catch (\Throwable $e) {
                return false;
            }
        });
    }
}
