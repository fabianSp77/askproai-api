<?php

namespace App\Services\Calcom\DTOs;

use Carbon\Carbon;

/**
 * Attendee Data Transfer Object
 */
class AttendeeDTO extends BaseDTO
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $email,
        public readonly string $name,
        public readonly string $timeZone,
        public readonly ?string $locale,
        public readonly ?int $bookingId,
        public readonly ?string $bookingUid,
        public readonly ?Carbon $createdAt,
        public readonly ?Carbon $updatedAt,
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            id: self::getValue($data, 'id') ? (int) $data['id'] : null,
            email: $data['email'],
            name: $data['name'],
            timeZone: $data['timeZone'] ?? 'UTC',
            locale: self::getValue($data, 'locale'),
            bookingId: self::getValue($data, 'bookingId') ? (int) $data['bookingId'] : null,
            bookingUid: self::getValue($data, 'bookingUid'),
            createdAt: self::parseDateTime(self::getValue($data, 'createdAt')),
            updatedAt: self::parseDateTime(self::getValue($data, 'updatedAt')),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'timeZone' => $this->timeZone,
            'locale' => $this->locale,
            'bookingId' => $this->bookingId,
            'bookingUid' => $this->bookingUid,
            'createdAt' => $this->createdAt?->toIso8601String(),
            'updatedAt' => $this->updatedAt?->toIso8601String(),
        ];
    }

    /**
     * Get formatted name with email
     */
    public function getFullIdentifier(): string
    {
        return "{$this->name} <{$this->email}>";
    }

    /**
     * Get initials from name
     */
    public function getInitials(): string
    {
        $parts = explode(' ', $this->name);
        $initials = '';
        
        foreach ($parts as $part) {
            if (!empty($part)) {
                $initials .= strtoupper($part[0]);
            }
        }
        
        return substr($initials, 0, 2);
    }

    /**
     * Check if attendee has a specific locale
     */
    public function hasLocale(): bool
    {
        return !empty($this->locale);
    }
}