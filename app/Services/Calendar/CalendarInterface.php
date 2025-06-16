<?php

namespace App\Services\Calendar;

interface CalendarInterface
{
    public function getEventTypes(): array;
    public function createEventType(array $data): array;
    public function updateEventType(string $id, array $data): array;
    public function deleteEventType(string $id): bool;
    public function checkAvailability(string $eventTypeId, \DateTime $start, \DateTime $end): array;
    public function createBooking(array $data): array;
    public function cancelBooking(string $bookingId): bool;
    public function getProviderName(): string;
    public function validateConnection(): bool;
}
