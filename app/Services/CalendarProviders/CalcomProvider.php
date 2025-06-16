<?php

namespace App\Services\CalendarProviders;

use App\Contracts\CalendarProviderInterface;
use App\Services\CalcomService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class CalcomProvider implements CalendarProviderInterface
{
    protected CalcomService $calcomService;
    protected array $config;
    
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->calcomService = new CalcomService();
    }
    
    public function getName(): string
    {
        return 'cal.com';
    }
    
    public function testConnection(): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config['api_key'],
            ])->get($this->config['api_url'] . '/me');
            
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function getEventTypes(): Collection
    {
        return collect($this->calcomService->getEventTypes());
    }
    
    public function getEventType(string $eventTypeId): ?array
    {
        $eventTypes = $this->getEventTypes();
        return $eventTypes->firstWhere('id', $eventTypeId);
    }
    
    public function getAvailableSlots(string $eventTypeId, Carbon $startDate, Carbon $endDate): Collection
    {
        // Implementierung der Cal.com Slots API
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->config['api_key'],
        ])->get($this->config['api_url'] . "/availability/{$eventTypeId}", [
            'dateFrom' => $startDate->toIso8601String(),
            'dateTo' => $endDate->toIso8601String(),
        ]);
        
        if ($response->successful()) {
            return collect($response->json('slots', []));
        }
        
        return collect();
    }
    
    public function createBooking(array $bookingData): array
    {
        return $this->calcomService->createBooking($bookingData);
    }
    
    public function updateBooking(string $bookingId, array $data): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->config['api_key'],
        ])->patch($this->config['api_url'] . "/bookings/{$bookingId}", $data);
        
        return $response->json();
    }
    
    public function cancelBooking(string $bookingId, string $reason = null): bool
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->config['api_key'],
        ])->delete($this->config['api_url'] . "/bookings/{$bookingId}", [
            'cancellationReason' => $reason,
        ]);
        
        return $response->successful();
    }
    
    public function handleWebhook(array $payload): array
    {
        // Verarbeite Cal.com spezifische Webhook-Events
        $event = $payload['triggerEvent'] ?? null;
        
        return [
            'provider' => $this->getName(),
            'event' => $event,
            'booking_id' => $payload['payload']['bookingId'] ?? null,
            'status' => $this->mapWebhookEventToStatus($event),
        ];
    }
    
    public function validateConfig(array $config): bool
    {
        return isset($config['api_key']) && isset($config['api_url']);
    }
    
    protected function mapWebhookEventToStatus(string $event): string
    {
        return match($event) {
            'BOOKING_CREATED' => 'created',
            'BOOKING_RESCHEDULED' => 'rescheduled',
            'BOOKING_CANCELLED' => 'cancelled',
            default => 'unknown',
        };
    }
}