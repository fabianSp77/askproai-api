<?php

declare(strict_types=1);

namespace App\Contracts;

use Carbon\Carbon;

interface AvailabilityServiceInterface
{
    /**
     * Check real-time availability for a staff member
     *
     * @param string $staffId
     * @param int $eventTypeId
     * @param Carbon $date
     * @return array
     */
    public function checkRealTimeAvailability(string $staffId, int $eventTypeId, Carbon $date): array;

    /**
     * Check availability for multiple staff members
     *
     * @param array $staffIds
     * @param int $eventTypeId
     * @param Carbon $date
     * @return array
     */
    public function checkMultipleStaffAvailability(array $staffIds, int $eventTypeId, Carbon $date): array;

    /**
     * Get next available slot for a staff member
     *
     * @param string $staffId
     * @param int $eventTypeId
     * @param Carbon $fromDate
     * @param int $daysToCheck
     * @return array|null
     */
    public function getNextAvailableSlot(string $staffId, int $eventTypeId, Carbon $fromDate, int $daysToCheck = 30): ?array;
}