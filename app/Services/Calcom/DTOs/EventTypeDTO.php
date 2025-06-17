<?php

namespace App\Services\Calcom\DTOs;

use Carbon\Carbon;

/**
 * Event Type Data Transfer Object
 */
class EventTypeDTO extends BaseDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $slug,
        public readonly string $title,
        public readonly ?string $description,
        public readonly int $length,
        public readonly array $locations,
        public readonly ?int $userId,
        public readonly ?int $teamId,
        public readonly ?string $eventName,
        public readonly ?string $timeZone,
        public readonly ?int $periodType,
        public readonly ?Carbon $periodStartDate,
        public readonly ?Carbon $periodEndDate,
        public readonly ?int $periodDays,
        public readonly ?int $periodCountCalendarDays,
        public readonly bool $requiresConfirmation,
        public readonly bool $disableGuests,
        public readonly bool $hideCalendarNotes,
        public readonly int $minimumBookingNotice,
        public readonly int $beforeEventBuffer,
        public readonly int $afterEventBuffer,
        public readonly ?int $seatsPerTimeSlot,
        public readonly bool $seatsShowAttendees,
        public readonly ?int $schedulingType,
        public readonly ?int $scheduleId,
        public readonly int $price,
        public readonly ?string $currency,
        public readonly ?int $slotInterval,
        public readonly ?string $successRedirectUrl,
        public readonly array $metadata,
        public readonly array $bookingFields,
        public readonly ?Carbon $createdAt,
        public readonly ?Carbon $updatedAt,
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            id: (int) $data['id'],
            slug: $data['slug'],
            title: $data['title'],
            description: self::getValue($data, 'description'),
            length: (int) $data['length'],
            locations: $data['locations'] ?? [],
            userId: self::getValue($data, 'userId') ? (int) $data['userId'] : null,
            teamId: self::getValue($data, 'teamId') ? (int) $data['teamId'] : null,
            eventName: self::getValue($data, 'eventName'),
            timeZone: self::getValue($data, 'timeZone'),
            periodType: self::getValue($data, 'periodType') ? (int) $data['periodType'] : null,
            periodStartDate: self::parseDateTime(self::getValue($data, 'periodStartDate')),
            periodEndDate: self::parseDateTime(self::getValue($data, 'periodEndDate')),
            periodDays: self::getValue($data, 'periodDays') ? (int) $data['periodDays'] : null,
            periodCountCalendarDays: self::getValue($data, 'periodCountCalendarDays') ? (int) $data['periodCountCalendarDays'] : null,
            requiresConfirmation: (bool) self::getValue($data, 'requiresConfirmation', false),
            disableGuests: (bool) self::getValue($data, 'disableGuests', false),
            hideCalendarNotes: (bool) self::getValue($data, 'hideCalendarNotes', false),
            minimumBookingNotice: (int) self::getValue($data, 'minimumBookingNotice', 0),
            beforeEventBuffer: (int) self::getValue($data, 'beforeEventBuffer', 0),
            afterEventBuffer: (int) self::getValue($data, 'afterEventBuffer', 0),
            seatsPerTimeSlot: self::getValue($data, 'seatsPerTimeSlot') ? (int) $data['seatsPerTimeSlot'] : null,
            seatsShowAttendees: (bool) self::getValue($data, 'seatsShowAttendees', false),
            schedulingType: self::getValue($data, 'schedulingType') ? (int) $data['schedulingType'] : null,
            scheduleId: self::getValue($data, 'scheduleId') ? (int) $data['scheduleId'] : null,
            price: (int) self::getValue($data, 'price', 0),
            currency: self::getValue($data, 'currency'),
            slotInterval: self::getValue($data, 'slotInterval') ? (int) $data['slotInterval'] : null,
            successRedirectUrl: self::getValue($data, 'successRedirectUrl'),
            metadata: $data['metadata'] ?? [],
            bookingFields: $data['bookingFields'] ?? [],
            createdAt: self::parseDateTime(self::getValue($data, 'createdAt')),
            updatedAt: self::parseDateTime(self::getValue($data, 'updatedAt')),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'length' => $this->length,
            'locations' => $this->locations,
            'userId' => $this->userId,
            'teamId' => $this->teamId,
            'eventName' => $this->eventName,
            'timeZone' => $this->timeZone,
            'periodType' => $this->periodType,
            'periodStartDate' => $this->periodStartDate?->toIso8601String(),
            'periodEndDate' => $this->periodEndDate?->toIso8601String(),
            'periodDays' => $this->periodDays,
            'periodCountCalendarDays' => $this->periodCountCalendarDays,
            'requiresConfirmation' => $this->requiresConfirmation,
            'disableGuests' => $this->disableGuests,
            'hideCalendarNotes' => $this->hideCalendarNotes,
            'minimumBookingNotice' => $this->minimumBookingNotice,
            'beforeEventBuffer' => $this->beforeEventBuffer,
            'afterEventBuffer' => $this->afterEventBuffer,
            'seatsPerTimeSlot' => $this->seatsPerTimeSlot,
            'seatsShowAttendees' => $this->seatsShowAttendees,
            'schedulingType' => $this->schedulingType,
            'scheduleId' => $this->scheduleId,
            'price' => $this->price,
            'currency' => $this->currency,
            'slotInterval' => $this->slotInterval,
            'successRedirectUrl' => $this->successRedirectUrl,
            'metadata' => $this->metadata,
            'bookingFields' => $this->bookingFields,
            'createdAt' => $this->createdAt?->toIso8601String(),
            'updatedAt' => $this->updatedAt?->toIso8601String(),
        ];
    }

    /**
     * Check if event type is team-based
     */
    public function isTeamEvent(): bool
    {
        return $this->teamId !== null;
    }

    /**
     * Check if event type requires payment
     */
    public function isPaid(): bool
    {
        return $this->price > 0;
    }

    /**
     * Get formatted price
     */
    public function getFormattedPrice(): string
    {
        if (!$this->isPaid()) {
            return 'Free';
        }

        $amount = $this->price / 100; // Assume price is in cents
        return sprintf('%s %.2f', $this->currency ?? 'USD', $amount);
    }

    /**
     * Get total slot duration including buffers
     */
    public function getTotalDuration(): int
    {
        return $this->beforeEventBuffer + $this->length + $this->afterEventBuffer;
    }
}