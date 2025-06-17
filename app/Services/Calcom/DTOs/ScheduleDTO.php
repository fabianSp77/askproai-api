<?php

namespace App\Services\Calcom\DTOs;

use Carbon\Carbon;

/**
 * Schedule Data Transfer Object
 */
class ScheduleDTO extends BaseDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?int $userId,
        public readonly string $timeZone,
        public readonly array $availability,
        public readonly bool $isDefault,
        public readonly ?Carbon $createdAt,
        public readonly ?Carbon $updatedAt,
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            id: (int) $data['id'],
            name: $data['name'],
            userId: self::getValue($data, 'userId') ? (int) $data['userId'] : null,
            timeZone: $data['timeZone'] ?? 'UTC',
            availability: $data['availability'] ?? [],
            isDefault: (bool) self::getValue($data, 'isDefault', false),
            createdAt: self::parseDateTime(self::getValue($data, 'createdAt')),
            updatedAt: self::parseDateTime(self::getValue($data, 'updatedAt')),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'userId' => $this->userId,
            'timeZone' => $this->timeZone,
            'availability' => $this->availability,
            'isDefault' => $this->isDefault,
            'createdAt' => $this->createdAt?->toIso8601String(),
            'updatedAt' => $this->updatedAt?->toIso8601String(),
        ];
    }

    /**
     * Get availability for a specific day
     */
    public function getAvailabilityForDay(int $day): array
    {
        return array_filter($this->availability, fn($slot) => $slot['days'] === $day);
    }

    /**
     * Check if available on a specific day
     */
    public function isAvailableOnDay(int $day): bool
    {
        return !empty($this->getAvailabilityForDay($day));
    }

    /**
     * Get all working days
     */
    public function getWorkingDays(): array
    {
        $days = [];
        
        foreach ($this->availability as $slot) {
            if (!in_array($slot['days'], $days)) {
                $days[] = $slot['days'];
            }
        }
        
        sort($days);
        return $days;
    }

    /**
     * Format schedule summary
     */
    public function getSummary(): string
    {
        $workingDays = $this->getWorkingDays();
        
        if (empty($workingDays)) {
            return 'No availability';
        }
        
        $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $dayStrings = array_map(fn($day) => $dayNames[$day], $workingDays);
        
        return implode(', ', $dayStrings);
    }
}