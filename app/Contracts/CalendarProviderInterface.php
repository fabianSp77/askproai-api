<?php

namespace App\Contracts;

use Carbon\Carbon;
use Illuminate\Support\Collection;

interface CalendarProviderInterface
{
    /**
     * Provider-Name
     */
    public function getName(): string;
    
    /**
     * Authentifizierung testen
     */
    public function testConnection(): bool;
    
    /**
     * Event-Types abrufen
     */
    public function getEventTypes(): Collection;
    
    /**
     * Einzelnen Event-Type abrufen
     */
    public function getEventType(string $eventTypeId): ?array;
    
    /**
     * Verfügbare Slots abrufen
     */
    public function getAvailableSlots(string $eventTypeId, Carbon $startDate, Carbon $endDate): Collection;
    
    /**
     * Buchung erstellen
     */
    public function createBooking(array $bookingData): array;
    
    /**
     * Buchung aktualisieren
     */
    public function updateBooking(string $bookingId, array $data): array;
    
    /**
     * Buchung stornieren
     */
    public function cancelBooking(string $bookingId, string $reason = null): bool;
    
    /**
     * Webhook-Payload verarbeiten
     */
    public function handleWebhook(array $payload): array;
    
    /**
     * Provider-spezifische Konfiguration validieren
     */
    public function validateConfig(array $config): bool;
}