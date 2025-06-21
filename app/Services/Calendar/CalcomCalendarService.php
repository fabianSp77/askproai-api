<?php

namespace App\Services\Calendar;

use App\Services\CalcomV2Service;
use Illuminate\Support\Facades\Http;

class CalcomCalendarService extends BaseCalendarService
{
    private $calcomService;
    
    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->calcomService = app(CalcomV2Service::class);
        
        if (isset($config['api_key'])) {
            // CalcomV2Service uses constructor injection for API key
            // This needs to be handled differently
        }
    }
    
    public function getProviderName(): string
    {
        return 'Cal.com';
    }
    
    public function getEventTypes(): array
    {
        try {
            $eventTypes = $this->calcomService->getEventTypes();
            
            return array_map(function ($eventType) {
                return [
                    'external_id' => (string) $eventType['id'],
                    'name' => $eventType['title'] ?? $eventType['slug'],
                    'description' => $eventType['description'] ?? null,
                    'duration_minutes' => $eventType['length'] ?? 30,
                    'provider_data' => $eventType
                ];
            }, $eventTypes['event_types'] ?? []);
            
        } catch (\Exception $e) {
            $this->logError('Failed to fetch event types', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    public function createEventType(array $data): array
    {
        // Cal.com API implementation
        throw new \Exception('Not implemented yet');
    }
    
    public function updateEventType(string $id, array $data): array
    {
        throw new \Exception('Not implemented yet');
    }
    
    public function deleteEventType(string $id): bool
    {
        throw new \Exception('Not implemented yet');
    }
    
    public function checkAvailability(string $eventTypeId, \DateTime $start, \DateTime $end): array
    {
        return $this->calcomService->checkAvailability(
            $eventTypeId,
            $start->format('c'),
            $end->format('c')
        );
    }
    
    public function createBooking(array $data): array
    {
        return $this->calcomService->bookAppointment(
            $data['event_type_id'],
            $data['start_time'],
            $data['end_time'],
            $data['customer_data'],
            $data['notes'] ?? null
        );
    }
    
    public function cancelBooking(string $bookingId): bool
    {
        throw new \Exception('Not implemented yet');
    }
    
    public function validateConnection(): bool
    {
        try {
            $result = $this->calcomService->getEventTypes();
            return !empty($result);
        } catch (\Exception $e) {
            return false;
        }
    }
}
