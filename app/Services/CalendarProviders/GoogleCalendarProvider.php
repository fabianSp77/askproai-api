<?php

namespace App\Services\CalendarProviders;

use App\Contracts\CalendarProviderInterface;
use Carbon\Carbon;
use Google\Client;
use Google\Service\Calendar;
use Illuminate\Support\Collection;

class GoogleCalendarProvider implements CalendarProviderInterface
{
    protected Client $client;
    protected Calendar $service;
    protected array $config;
    
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->initializeClient();
    }
    
    protected function initializeClient(): void
    {
        $this->client = new Client();
        $this->client->setClientId($this->config['client_id']);
        $this->client->setClientSecret($this->config['client_secret']);
        $this->client->setAccessToken($this->config['access_token']);
        
        if ($this->client->isAccessTokenExpired() && isset($this->config['refresh_token'])) {
            $this->client->fetchAccessTokenWithRefreshToken($this->config['refresh_token']);
        }
        
        $this->service = new Calendar($this->client);
    }
    
    public function getName(): string
    {
        return 'google_calendar';
    }
    
    public function testConnection(): bool
    {
        try {
            $this->service->calendarList->listCalendarList(['maxResults' => 1]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function getEventTypes(): Collection
    {
        // Google Calendar hat keine "Event Types" - wir simulieren sie durch Kalender
        $calendars = $this->service->calendarList->listCalendarList();
        
        return collect($calendars->getItems())->map(function($calendar) {
            return [
                'id' => $calendar->getId(),
                'name' => $calendar->getSummary(),
                'description' => $calendar->getDescription(),
                'duration_minutes' => 30, // Standard-Dauer
                'color' => $calendar->getBackgroundColor(),
            ];
        });
    }
    
    public function getEventType(string $eventTypeId): ?array
    {
        try {
            $calendar = $this->service->calendars->get($eventTypeId);
            return [
                'id' => $calendar->getId(),
                'name' => $calendar->getSummary(),
                'description' => $calendar->getDescription(),
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
    
    public function getAvailableSlots(string $eventTypeId, Carbon $startDate, Carbon $endDate): Collection
    {
        $freeBusyRequest = new \Google\Service\Calendar\FreeBusyRequest();
        $freeBusyRequest->setTimeMin($startDate->toRfc3339String());
        $freeBusyRequest->setTimeMax($endDate->toRfc3339String());
        $freeBusyRequest->setItems([['id' => $eventTypeId]]);
        
        $freeBusy = $this->service->freebusy->query($freeBusyRequest);
        $busySlots = $freeBusy->getCalendars()[$eventTypeId]->getBusy();
        
        // Generiere verfügbare Slots basierend auf busy-Zeiten
        return $this->generateAvailableSlots($startDate, $endDate, collect($busySlots));
    }
    
    public function createBooking(array $bookingData): array
    {
        $event = new \Google\Service\Calendar\Event([
            'summary' => $bookingData['title'] ?? 'Termin',
            'description' => $bookingData['description'] ?? '',
            'start' => [
                'dateTime' => Carbon::parse($bookingData['start'])->toRfc3339String(),
                'timeZone' => $bookingData['timezone'] ?? 'Europe/Berlin',
            ],
            'end' => [
                'dateTime' => Carbon::parse($bookingData['end'])->toRfc3339String(),
                'timeZone' => $bookingData['timezone'] ?? 'Europe/Berlin',
            ],
            'attendees' => isset($bookingData['attendees']) ? 
                array_map(fn($email) => ['email' => $email], $bookingData['attendees']) : [],
        ]);
        
        $calendarId = $bookingData['calendar_id'] ?? 'primary';
        $createdEvent = $this->service->events->insert($calendarId, $event);
        
        return [
            'id' => $createdEvent->getId(),
            'html_link' => $createdEvent->getHtmlLink(),
            'status' => $createdEvent->getStatus(),
        ];
    }
    
    public function updateBooking(string $bookingId, array $data): array
    {
        $calendarId = $data['calendar_id'] ?? 'primary';
        $event = $this->service->events->get($calendarId, $bookingId);
        
        if (isset($data['start'])) {
            $event->setStart([
                'dateTime' => Carbon::parse($data['start'])->toRfc3339String(),
                'timeZone' => $data['timezone'] ?? 'Europe/Berlin',
            ]);
        }
        
        if (isset($data['end'])) {
            $event->setEnd([
                'dateTime' => Carbon::parse($data['end'])->toRfc3339String(),
                'timeZone' => $data['timezone'] ?? 'Europe/Berlin',
            ]);
        }
        
        if (isset($data['title'])) {
            $event->setSummary($data['title']);
        }
        
        $updatedEvent = $this->service->events->update($calendarId, $bookingId, $event);
        
        return [
            'id' => $updatedEvent->getId(),
            'status' => $updatedEvent->getStatus(),
        ];
    }
    
    public function cancelBooking(string $bookingId, string $reason = null): bool
    {
        try {
            $calendarId = 'primary'; // Sollte aus Config kommen
            $this->service->events->delete($calendarId, $bookingId);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function handleWebhook(array $payload): array
    {
        // Google Calendar Push Notifications
        return [
            'provider' => $this->getName(),
            'event' => $payload['X-Goog-Resource-State'] ?? 'unknown',
            'resource_id' => $payload['X-Goog-Resource-ID'] ?? null,
        ];
    }
    
    public function validateConfig(array $config): bool
    {
        return isset($config['client_id']) 
            && isset($config['client_secret']) 
            && isset($config['access_token']);
    }
    
    protected function generateAvailableSlots(Carbon $start, Carbon $end, Collection $busySlots): Collection
    {
        $slots = collect();
        $slotDuration = 30; // Minuten
        $current = $start->copy();
        
        while ($current->lt($end)) {
            $slotEnd = $current->copy()->addMinutes($slotDuration);
            $isBusy = false;
            
            foreach ($busySlots as $busy) {
                $busyStart = Carbon::parse($busy->getStart());
                $busyEnd = Carbon::parse($busy->getEnd());
                
                if ($current->lt($busyEnd) && $slotEnd->gt($busyStart)) {
                    $isBusy = true;
                    break;
                }
            }
            
            if (!$isBusy) {
                $slots->push([
                    'start' => $current->toIso8601String(),
                    'end' => $slotEnd->toIso8601String(),
                ]);
            }
            
            $current->addMinutes($slotDuration);
        }
        
        return $slots;
    }
}