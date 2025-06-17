<?php

namespace App\Services\Calcom\DTOs;

use Carbon\Carbon;

/**
 * Available Slot Data Transfer Object
 */
class SlotDTO extends BaseDTO
{
    public function __construct(
        public readonly Carbon $start,
        public readonly Carbon $end,
        public readonly array $attendees,
        public readonly ?int $eventTypeId,
        public readonly ?string $eventTypeSlug,
        public readonly array $metadata,
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            start: Carbon::parse($data['start']),
            end: Carbon::parse($data['end']),
            attendees: $data['attendees'] ?? [],
            eventTypeId: self::getValue($data, 'eventTypeId') ? (int) $data['eventTypeId'] : null,
            eventTypeSlug: self::getValue($data, 'eventTypeSlug'),
            metadata: $data['metadata'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'start' => $this->start->toIso8601String(),
            'end' => $this->end->toIso8601String(),
            'attendees' => $this->attendees,
            'eventTypeId' => $this->eventTypeId,
            'eventTypeSlug' => $this->eventTypeSlug,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Get duration in minutes
     */
    public function getDurationInMinutes(): int
    {
        return $this->start->diffInMinutes($this->end);
    }

    /**
     * Check if slot is in the past
     */
    public function isPast(): bool
    {
        return $this->start->isPast();
    }

    /**
     * Check if slot is today
     */
    public function isToday(): bool
    {
        return $this->start->isToday();
    }

    /**
     * Check if slot is within a certain time range
     */
    public function isWithinRange(Carbon $start, Carbon $end): bool
    {
        return $this->start->between($start, $end);
    }

    /**
     * Format slot as human-readable string
     */
    public function format(string $format = 'M d, Y g:i A'): string
    {
        return $this->start->format($format) . ' - ' . $this->end->format('g:i A');
    }

    /**
     * Get slot as calendar event array
     */
    public function toCalendarEvent(): array
    {
        return [
            'title' => 'Available',
            'start' => $this->start->toIso8601String(),
            'end' => $this->end->toIso8601String(),
            'available' => true,
            'eventTypeId' => $this->eventTypeId,
            'eventTypeSlug' => $this->eventTypeSlug,
        ];
    }
}