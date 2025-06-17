<?php

namespace App\Services\Calcom\DTOs;

use Carbon\Carbon;

/**
 * Booking Data Transfer Object
 */
class BookingDTO extends BaseDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $uid,
        public readonly ?int $userId,
        public readonly ?int $eventTypeId,
        public readonly string $title,
        public readonly ?string $description,
        public readonly Carbon $startTime,
        public readonly Carbon $endTime,
        public readonly array $attendees,
        public readonly ?string $location,
        public readonly string $status,
        public readonly bool $paid,
        public readonly ?string $payment,
        public readonly ?int $rescheduledFromUid,
        public readonly ?string $cancellationReason,
        public readonly ?string $rejectionReason,
        public readonly array $metadata,
        public readonly array $responses,
        public readonly ?string $recurringEventId,
        public readonly ?string $smsReminderNumber,
        public readonly ?array $user,
        public readonly ?array $eventType,
        public readonly ?Carbon $createdAt,
        public readonly ?Carbon $updatedAt,
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            id: (int) $data['id'],
            uid: $data['uid'],
            userId: self::getValue($data, 'userId') ? (int) $data['userId'] : null,
            eventTypeId: self::getValue($data, 'eventTypeId') ? (int) $data['eventTypeId'] : null,
            title: $data['title'],
            description: self::getValue($data, 'description'),
            startTime: Carbon::parse($data['startTime']),
            endTime: Carbon::parse($data['endTime']),
            attendees: array_map(fn($a) => AttendeeDTO::fromArray($a), $data['attendees'] ?? []),
            location: self::getValue($data, 'location'),
            status: $data['status'],
            paid: (bool) self::getValue($data, 'paid', false),
            payment: self::getValue($data, 'payment'),
            rescheduledFromUid: self::getValue($data, 'rescheduledFromUid') ? (int) $data['rescheduledFromUid'] : null,
            cancellationReason: self::getValue($data, 'cancellationReason'),
            rejectionReason: self::getValue($data, 'rejectionReason'),
            metadata: $data['metadata'] ?? [],
            responses: $data['responses'] ?? [],
            recurringEventId: self::getValue($data, 'recurringEventId'),
            smsReminderNumber: self::getValue($data, 'smsReminderNumber'),
            user: self::getValue($data, 'user'),
            eventType: self::getValue($data, 'eventType'),
            createdAt: self::parseDateTime(self::getValue($data, 'createdAt')),
            updatedAt: self::parseDateTime(self::getValue($data, 'updatedAt')),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'userId' => $this->userId,
            'eventTypeId' => $this->eventTypeId,
            'title' => $this->title,
            'description' => $this->description,
            'startTime' => $this->startTime->toIso8601String(),
            'endTime' => $this->endTime->toIso8601String(),
            'attendees' => array_map(fn($a) => $a->toArray(), $this->attendees),
            'location' => $this->location,
            'status' => $this->status,
            'paid' => $this->paid,
            'payment' => $this->payment,
            'rescheduledFromUid' => $this->rescheduledFromUid,
            'cancellationReason' => $this->cancellationReason,
            'rejectionReason' => $this->rejectionReason,
            'metadata' => $this->metadata,
            'responses' => $this->responses,
            'recurringEventId' => $this->recurringEventId,
            'smsReminderNumber' => $this->smsReminderNumber,
            'user' => $this->user,
            'eventType' => $this->eventType,
            'createdAt' => $this->createdAt?->toIso8601String(),
            'updatedAt' => $this->updatedAt?->toIso8601String(),
        ];
    }

    /**
     * Booking status constants
     */
    public const STATUS_ACCEPTED = 'ACCEPTED';
    public const STATUS_CANCELLED = 'CANCELLED';
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_REJECTED = 'REJECTED';

    /**
     * Check if booking is confirmed
     */
    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    /**
     * Check if booking is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if booking is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if booking is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if booking is in the past
     */
    public function isPast(): bool
    {
        return $this->endTime->isPast();
    }

    /**
     * Check if booking is in the future
     */
    public function isFuture(): bool
    {
        return $this->startTime->isFuture();
    }

    /**
     * Check if booking is happening now
     */
    public function isHappeningNow(): bool
    {
        $now = Carbon::now();
        return $now->between($this->startTime, $this->endTime);
    }

    /**
     * Get duration in minutes
     */
    public function getDurationInMinutes(): int
    {
        return $this->startTime->diffInMinutes($this->endTime);
    }

    /**
     * Get primary attendee
     */
    public function getPrimaryAttendee(): ?AttendeeDTO
    {
        return $this->attendees[0] ?? null;
    }

    /**
     * Get attendee by email
     */
    public function getAttendeeByEmail(string $email): ?AttendeeDTO
    {
        foreach ($this->attendees as $attendee) {
            if ($attendee->email === $email) {
                return $attendee;
            }
        }
        return null;
    }

    /**
     * Format booking time
     */
    public function formatTime(string $format = 'M d, Y g:i A'): string
    {
        return $this->startTime->format($format) . ' - ' . $this->endTime->format('g:i A');
    }

    /**
     * Get response value by name
     */
    public function getResponse(string $name, $default = null)
    {
        return $this->responses[$name] ?? $default;
    }
}