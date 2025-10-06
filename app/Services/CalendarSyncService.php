<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Staff;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendar;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model\Event;
use Microsoft\Graph\Model\DateTimeTimeZone;
use Microsoft\Graph\Model\Location;
use Microsoft\Graph\Model\Attendee;
use Microsoft\Graph\Model\EmailAddress;
use Microsoft\Graph\Model\ItemBody;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CalendarSyncService
{
    protected $googleClient;
    protected $graphClient;

    public function __construct()
    {
        $this->initializeGoogleClient();
        $this->initializeGraphClient();
    }

    protected function initializeGoogleClient()
    {
        $this->googleClient = new GoogleClient();
        $this->googleClient->setApplicationName(config('app.name'));
        $this->googleClient->setScopes([
            GoogleCalendar::CALENDAR,
            GoogleCalendar::CALENDAR_EVENTS
        ]);
        $this->googleClient->setAuthConfig(storage_path('app/google-calendar-credentials.json'));
        $this->googleClient->setAccessType('offline');
        $this->googleClient->setPrompt('select_account consent');
    }

    protected function initializeGraphClient()
    {
        $this->graphClient = new Graph();
    }

    public function syncToGoogle(Appointment $appointment, $action = 'create')
    {
        try {
            $staff = $appointment->staff;

            if (!$staff->google_calendar_token) {
                return false;
            }

            $this->googleClient->setAccessToken($staff->google_calendar_token);

            // Refresh token if expired
            if ($this->googleClient->isAccessTokenExpired()) {
                $refreshToken = $staff->google_refresh_token;
                if ($refreshToken) {
                    $this->googleClient->fetchAccessTokenWithRefreshToken($refreshToken);
                    $newToken = $this->googleClient->getAccessToken();

                    $staff->update([
                        'google_calendar_token' => json_encode($newToken)
                    ]);
                } else {
                    Log::warning("Google Calendar refresh token missing for staff: {$staff->id}");
                    return false;
                }
            }

            $service = new GoogleCalendar($this->googleClient);

            $event = new \Google\Service\Calendar\Event();
            $event->setSummary($appointment->service->name . ' - ' . $appointment->customer->name);
            $event->setDescription($this->buildEventDescription($appointment));
            $event->setLocation($appointment->branch->full_address);

            $start = new \Google\Service\Calendar\EventDateTime();
            $start->setDateTime($appointment->start_at->toRfc3339String());
            $start->setTimeZone('Europe/Berlin');
            $event->setStart($start);

            $end = new \Google\Service\Calendar\EventDateTime();
            $end->setDateTime($appointment->end_at->toRfc3339String());
            $end->setTimeZone('Europe/Berlin');
            $event->setEnd($end);

            // Add attendees
            $attendees = [];
            if ($appointment->customer->email) {
                $attendee = new \Google\Service\Calendar\EventAttendee();
                $attendee->setEmail($appointment->customer->email);
                $attendee->setDisplayName($appointment->customer->name);
                $attendees[] = $attendee;
            }
            $event->setAttendees($attendees);

            // Set reminders
            $reminders = new \Google\Service\Calendar\EventReminders();
            $reminders->setUseDefault(false);
            $reminders->setOverrides([
                ['method' => 'email', 'minutes' => 24 * 60],
                ['method' => 'popup', 'minutes' => 60]
            ]);
            $event->setReminders($reminders);

            // Color coding based on status
            $event->setColorId($this->getGoogleColorId($appointment->status));

            $calendarId = $staff->google_calendar_id ?? 'primary';

            switch ($action) {
                case 'create':
                    $createdEvent = $service->events->insert($calendarId, $event);
                    $appointment->update(['google_event_id' => $createdEvent->getId()]);
                    break;

                case 'update':
                    if ($appointment->google_event_id) {
                        $service->events->update($calendarId, $appointment->google_event_id, $event);
                    } else {
                        $createdEvent = $service->events->insert($calendarId, $event);
                        $appointment->update(['google_event_id' => $createdEvent->getId()]);
                    }
                    break;

                case 'delete':
                    if ($appointment->google_event_id) {
                        $service->events->delete($calendarId, $appointment->google_event_id);
                        $appointment->update(['google_event_id' => null]);
                    }
                    break;
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Google Calendar sync failed: ' . $e->getMessage(), [
                'appointment_id' => $appointment->id,
                'staff_id' => $appointment->staff_id,
                'action' => $action
            ]);
            return false;
        }
    }

    public function syncToOutlook(Appointment $appointment, $action = 'create')
    {
        try {
            $staff = $appointment->staff;

            if (!$staff->outlook_access_token) {
                return false;
            }

            // Set access token
            $this->graphClient->setAccessToken($staff->outlook_access_token);

            // Check if token needs refresh
            if ($this->isOutlookTokenExpired($staff)) {
                $newToken = $this->refreshOutlookToken($staff);
                if (!$newToken) {
                    return false;
                }
                $this->graphClient->setAccessToken($newToken);
            }

            $event = new Event();
            $event->setSubject($appointment->service->name . ' - ' . $appointment->customer->name);

            $body = new ItemBody();
            $body->setContentType('HTML');
            $body->setContent($this->buildEventDescriptionHtml($appointment));
            $event->setBody($body);

            $start = new DateTimeTimeZone();
            $start->setDateTime($appointment->start_at->format('Y-m-d\\TH:i:s'));
            $start->setTimeZone('Europe/Berlin');
            $event->setStart($start);

            $end = new DateTimeTimeZone();
            $end->setDateTime($appointment->end_at->format('Y-m-d\\TH:i:s'));
            $end->setTimeZone('Europe/Berlin');
            $event->setEnd($end);

            $location = new Location();
            $location->setDisplayName($appointment->branch->name);
            $location->setAddress($appointment->branch->full_address);
            $event->setLocation($location);

            // Add attendees
            if ($appointment->customer->email) {
                $attendee = new Attendee();
                $emailAddress = new EmailAddress();
                $emailAddress->setAddress($appointment->customer->email);
                $emailAddress->setName($appointment->customer->name);
                $attendee->setEmailAddress($emailAddress);
                $attendee->setType('required');
                $event->setAttendees([$attendee]);
            }

            // Set reminder
            $event->setReminderMinutesBeforeStart(60);
            $event->setIsReminderOn(true);

            // Set importance based on status
            if ($appointment->status === 'confirmed') {
                $event->setImportance('high');
            }

            $calendarId = $staff->outlook_calendar_id ?? 'me/calendar';

            switch ($action) {
                case 'create':
                    $createdEvent = $this->graphClient->createRequest('POST', "/users/{$calendarId}/events")
                        ->attachBody($event)
                        ->setReturnType(Event::class)
                        ->execute();
                    $appointment->update(['outlook_event_id' => $createdEvent->getId()]);
                    break;

                case 'update':
                    if ($appointment->outlook_event_id) {
                        $this->graphClient->createRequest('PATCH', "/users/{$calendarId}/events/{$appointment->outlook_event_id}")
                            ->attachBody($event)
                            ->setReturnType(Event::class)
                            ->execute();
                    } else {
                        $createdEvent = $this->graphClient->createRequest('POST', "/users/{$calendarId}/events")
                            ->attachBody($event)
                            ->setReturnType(Event::class)
                            ->execute();
                        $appointment->update(['outlook_event_id' => $createdEvent->getId()]);
                    }
                    break;

                case 'delete':
                    if ($appointment->outlook_event_id) {
                        $this->graphClient->createRequest('DELETE', "/users/{$calendarId}/events/{$appointment->outlook_event_id}")
                            ->execute();
                        $appointment->update(['outlook_event_id' => null]);
                    }
                    break;
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Outlook Calendar sync failed: ' . $e->getMessage(), [
                'appointment_id' => $appointment->id,
                'staff_id' => $appointment->staff_id,
                'action' => $action
            ]);
            return false;
        }
    }

    public function importFromGoogle(Staff $staff, Carbon $startDate, Carbon $endDate)
    {
        try {
            if (!$staff->google_calendar_token) {
                return collect();
            }

            $this->googleClient->setAccessToken($staff->google_calendar_token);

            if ($this->googleClient->isAccessTokenExpired()) {
                $this->refreshGoogleToken($staff);
            }

            $service = new GoogleCalendar($this->googleClient);
            $calendarId = $staff->google_calendar_id ?? 'primary';

            $optParams = [
                'timeMin' => $startDate->toRfc3339String(),
                'timeMax' => $endDate->toRfc3339String(),
                'singleEvents' => true,
                'orderBy' => 'startTime'
            ];

            $events = $service->events->listEvents($calendarId, $optParams);
            $importedEvents = collect();

            foreach ($events->getItems() as $googleEvent) {
                if (!$this->isExternalEvent($googleEvent)) {
                    continue;
                }

                $importedEvents->push([
                    'title' => $googleEvent->getSummary(),
                    'description' => $googleEvent->getDescription(),
                    'start' => Carbon::parse($googleEvent->getStart()->getDateTime()),
                    'end' => Carbon::parse($googleEvent->getEnd()->getDateTime()),
                    'location' => $googleEvent->getLocation(),
                    'external_id' => $googleEvent->getId(),
                    'source' => 'google'
                ]);
            }

            return $importedEvents;

        } catch (\Exception $e) {
            Log::error('Google Calendar import failed: ' . $e->getMessage());
            return collect();
        }
    }

    public function importFromOutlook(Staff $staff, Carbon $startDate, Carbon $endDate)
    {
        try {
            if (!$staff->outlook_access_token) {
                return collect();
            }

            $this->graphClient->setAccessToken($staff->outlook_access_token);

            if ($this->isOutlookTokenExpired($staff)) {
                $newToken = $this->refreshOutlookToken($staff);
                if (!$newToken) {
                    return collect();
                }
                $this->graphClient->setAccessToken($newToken);
            }

            $calendarId = $staff->outlook_calendar_id ?? 'me';

            $queryParams = [
                '$filter' => "start/dateTime ge '{$startDate->toIso8601String()}' and end/dateTime le '{$endDate->toIso8601String()}'",
                '$orderby' => 'start/dateTime',
                '$top' => 100
            ];

            $events = $this->graphClient->createRequest('GET', "/users/{$calendarId}/events")
                ->addHeaders(['Prefer' => 'outlook.timezone="Europe/Berlin"'])
                ->setQuery($queryParams)
                ->setReturnType(Event::class)
                ->execute();

            $importedEvents = collect();

            foreach ($events as $outlookEvent) {
                if (!$this->isExternalOutlookEvent($outlookEvent)) {
                    continue;
                }

                $importedEvents->push([
                    'title' => $outlookEvent->getSubject(),
                    'description' => $outlookEvent->getBody() ? $outlookEvent->getBody()->getContent() : null,
                    'start' => Carbon::parse($outlookEvent->getStart()->getDateTime()),
                    'end' => Carbon::parse($outlookEvent->getEnd()->getDateTime()),
                    'location' => $outlookEvent->getLocation() ? $outlookEvent->getLocation()->getDisplayName() : null,
                    'external_id' => $outlookEvent->getId(),
                    'source' => 'outlook'
                ]);
            }

            return $importedEvents;

        } catch (\Exception $e) {
            Log::error('Outlook Calendar import failed: ' . $e->getMessage());
            return collect();
        }
    }

    protected function buildEventDescription(Appointment $appointment): string
    {
        $description = "Termin Details:\n\n";
        $description .= "Service: {$appointment->service->name}\n";
        $description .= "Dauer: {$appointment->service->duration} Minuten\n";
        $description .= "Preis: €{$appointment->total_price}\n\n";
        $description .= "Kunde: {$appointment->customer->name}\n";
        $description .= "Telefon: {$appointment->customer->phone}\n";

        if ($appointment->customer->email) {
            $description .= "E-Mail: {$appointment->customer->email}\n";
        }

        if ($appointment->notes) {
            $description .= "\nNotizen:\n{$appointment->notes}\n";
        }

        $description .= "\nFiliale: {$appointment->branch->name}\n";
        $description .= "Adresse: {$appointment->branch->full_address}\n";
        $description .= "\nMitarbeiter: {$appointment->staff->name}";

        return $description;
    }

    protected function buildEventDescriptionHtml(Appointment $appointment): string
    {
        $html = "<h3>Termin Details</h3>";
        $html .= "<p><strong>Service:</strong> {$appointment->service->name}</p>";
        $html .= "<p><strong>Dauer:</strong> {$appointment->service->duration} Minuten</p>";
        $html .= "<p><strong>Preis:</strong> €{$appointment->total_price}</p>";
        $html .= "<hr>";
        $html .= "<p><strong>Kunde:</strong> {$appointment->customer->name}</p>";
        $html .= "<p><strong>Telefon:</strong> {$appointment->customer->phone}</p>";

        if ($appointment->customer->email) {
            $html .= "<p><strong>E-Mail:</strong> {$appointment->customer->email}</p>";
        }

        if ($appointment->notes) {
            $html .= "<hr><p><strong>Notizen:</strong><br>{$appointment->notes}</p>";
        }

        $html .= "<hr>";
        $html .= "<p><strong>Filiale:</strong> {$appointment->branch->name}</p>";
        $html .= "<p><strong>Adresse:</strong> {$appointment->branch->full_address}</p>";
        $html .= "<p><strong>Mitarbeiter:</strong> {$appointment->staff->name}</p>";

        return $html;
    }

    protected function getGoogleColorId($status): string
    {
        return match($status) {
            'pending' => '5',      // yellow
            'confirmed' => '10',   // green
            'in_progress' => '9',  // blue
            'completed' => '2',    // light green
            'cancelled' => '11',   // red
            'no_show' => '8',      // gray
            default => '7'         // cyan
        };
    }

    protected function isExternalEvent($googleEvent): bool
    {
        // Check if event was created by our system
        $extendedProperties = $googleEvent->getExtendedProperties();
        if ($extendedProperties && $extendedProperties->getPrivate()) {
            $privateProps = $extendedProperties->getPrivate();
            if (isset($privateProps['source']) && $privateProps['source'] === 'crm_system') {
                return false;
            }
        }
        return true;
    }

    protected function isExternalOutlookEvent($outlookEvent): bool
    {
        // Check if event was created by our system
        $categories = $outlookEvent->getCategories();
        if ($categories && in_array('CRM_SYSTEM', $categories)) {
            return false;
        }
        return true;
    }

    protected function refreshGoogleToken(Staff $staff): bool
    {
        try {
            $refreshToken = $staff->google_refresh_token;
            if (!$refreshToken) {
                return false;
            }

            $this->googleClient->fetchAccessTokenWithRefreshToken($refreshToken);
            $newToken = $this->googleClient->getAccessToken();

            $staff->update([
                'google_calendar_token' => json_encode($newToken)
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to refresh Google token: ' . $e->getMessage());
            return false;
        }
    }

    protected function isOutlookTokenExpired(Staff $staff): bool
    {
        if (!$staff->outlook_token_expires_at) {
            return true;
        }

        return Carbon::parse($staff->outlook_token_expires_at)->isPast();
    }

    protected function refreshOutlookToken(Staff $staff): ?string
    {
        try {
            $refreshToken = $staff->outlook_refresh_token;
            if (!$refreshToken) {
                return null;
            }

            $response = Http::asForm()->post('https://login.microsoftonline.com/common/oauth2/v2.0/token', [
                'client_id' => config('services.outlook.client_id'),
                'client_secret' => config('services.outlook.client_secret'),
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
                'scope' => 'https://graph.microsoft.com/.default'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $staff->update([
                    'outlook_access_token' => $data['access_token'],
                    'outlook_refresh_token' => $data['refresh_token'] ?? $refreshToken,
                    'outlook_token_expires_at' => now()->addSeconds($data['expires_in'])
                ]);

                return $data['access_token'];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to refresh Outlook token: ' . $e->getMessage());
            return null;
        }
    }

    public function setupWebhook(string $provider, Staff $staff): bool
    {
        try {
            if ($provider === 'google') {
                return $this->setupGoogleWebhook($staff);
            } elseif ($provider === 'outlook') {
                return $this->setupOutlookWebhook($staff);
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Failed to setup {$provider} webhook: " . $e->getMessage());
            return false;
        }
    }

    protected function setupGoogleWebhook(Staff $staff): bool
    {
        $this->googleClient->setAccessToken($staff->google_calendar_token);
        $service = new GoogleCalendar($this->googleClient);

        $channel = new \Google\Service\Calendar\Channel();
        $channel->setId('staff-' . $staff->id . '-' . uniqid());
        $channel->setType('web_hook');
        $channel->setAddress(config('app.url') . '/webhooks/google-calendar');
        $channel->setExpiration(time() + 86400 * 30); // 30 days

        $calendarId = $staff->google_calendar_id ?? 'primary';
        $watchRequest = $service->events->watch($calendarId, $channel);

        $staff->update([
            'google_webhook_id' => $watchRequest->getId(),
            'google_webhook_expires_at' => Carbon::createFromTimestamp($watchRequest->getExpiration() / 1000)
        ]);

        return true;
    }

    protected function setupOutlookWebhook(Staff $staff): bool
    {
        $this->graphClient->setAccessToken($staff->outlook_access_token);

        $subscription = [
            'changeType' => 'created,updated,deleted',
            'notificationUrl' => config('app.url') . '/webhooks/outlook-calendar',
            'resource' => "/users/{$staff->outlook_calendar_id}/events",
            'expirationDateTime' => now()->addDays(3)->toIso8601String(),
            'clientState' => 'staff-' . $staff->id
        ];

        $response = $this->graphClient->createRequest('POST', '/subscriptions')
            ->attachBody($subscription)
            ->execute();

        $staff->update([
            'outlook_webhook_id' => $response['id'],
            'outlook_webhook_expires_at' => Carbon::parse($response['expirationDateTime'])
        ]);

        return true;
    }
}