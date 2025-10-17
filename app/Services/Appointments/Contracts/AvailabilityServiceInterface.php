<?php

namespace App\Services\Appointments\Contracts;

use Carbon\Carbon;

interface AvailabilityServiceInterface
{
    /**
     * Return all available slots for the given service and week start (Monday).
     */
    public function getWeekAvailability(string $serviceId, Carbon $weekStart): array;

    /**
     * Provide metadata describing the requested week (number, range, flags).
     */
    public function getWeekMetadata(Carbon $weekStart): array;

    /**
     * Clear cached availability entries for the service.
     */
    public function clearServiceCache(string $serviceId, int $weeksToInvalidate = 4): void;

    /**
     * Warm up the cache for the next week without blocking the caller.
     */
    public function prefetchNextWeek(string $serviceId, Carbon $currentWeekStart): void;
}
