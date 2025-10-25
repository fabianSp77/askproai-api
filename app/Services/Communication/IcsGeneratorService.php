<?php

namespace App\Services\Communication;

use App\Models\Appointment;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;
use Spatie\IcalendarGenerator\Components\Timezone;
use Spatie\IcalendarGenerator\Components\Alert;
use Spatie\IcalendarGenerator\Enums\EventStatus;
use Spatie\IcalendarGenerator\Enums\Display;
use Spatie\IcalendarGenerator\Properties\TextProperty;
use Carbon\Carbon;
use DateTime;
use DateTimeZone;

class IcsGeneratorService
{
    /**
     * Generate ICS for composite appointment
     */
    public function generateCompositeIcs(Appointment $appointment): string
    {
        // 🔧 FIX 2025-10-25: Bug #3 - Simplified timezone handling for Spatie v3
        // Old: Manual timezone transitions with withStandardTransition/withDaylightTransition (v2 API - doesn't exist in v3)
        // New: Let Spatie auto-generate timezone components (simpler and handles DST correctly)

        // Create main event for total timespan
        $event = Event::create()
            ->uniqueIdentifier($appointment->composite_group_uid . '@askpro.ai')
            ->startsAt(Carbon::parse($appointment->starts_at, 'Europe/Berlin'))
            ->endsAt(Carbon::parse($appointment->ends_at, 'Europe/Berlin'))
            ->name($appointment->service->name . ' (inkl. Einwirkzeit)')
            ->description($this->buildCompositeDescription($appointment))
            ->address($this->getFullAddress($appointment))
            ->status(EventStatus::confirmed())
            ->transparent(false) // OPAQUE - blocks calendar
            ->organizer($this->getOrganizerEmail($appointment))
            ->attendee($appointment->customer->email, $appointment->customer->name)
            ->createdAt(Carbon::parse($appointment->created_at));

        // Add alarms (reminders)
        $event = $event->alert(
            Alert::display($appointment->service->name . ' - Termin morgen')
                ->triggerBeforeStart(new \DateInterval('P1D')) // 24 hours before
        )->alert(
            Alert::display($appointment->service->name . ' - Termin in 1 Stunde')
                ->triggerBeforeStart(new \DateInterval('PT1H')) // 1 hour before
        );

        // Build calendar - Spatie v3 auto-generates timezone components from event datetimes
        $calendar = Calendar::create()
            ->productIdentifier('-//AskProAI//Appointment//DE')
            ->event($event)
            ->refreshInterval(new \DateInterval('PT1H'));

        return $calendar->get();
    }

    /**
     * Generate ICS for simple appointment
     */
    public function generateSimpleIcs(Appointment $appointment): string
    {
        // 🔧 FIX 2025-10-25: Bug #3 - Simplified timezone handling for Spatie v3 (same as composite)

        $event = Event::create()
            ->uniqueIdentifier($appointment->id . '@askpro.ai')
            ->startsAt(Carbon::parse($appointment->starts_at, 'Europe/Berlin'))
            ->endsAt(Carbon::parse($appointment->ends_at, 'Europe/Berlin'))
            ->name($appointment->service->name)
            ->description($this->buildSimpleDescription($appointment))
            ->address($this->getFullAddress($appointment))
            ->status(EventStatus::confirmed())
            ->transparent(false)
            ->organizer($this->getOrganizerEmail($appointment))
            ->attendee($appointment->customer->email, $appointment->customer->name)
            ->createdAt(Carbon::parse($appointment->created_at));

        // Add reminders
        $event = $event->alert(
            Alert::display($appointment->service->name . ' - Termin morgen')
                ->triggerBeforeStart(new \DateInterval('P1D'))
        )->alert(
            Alert::display($appointment->service->name . ' - Termin in 1 Stunde')
                ->triggerBeforeStart(new \DateInterval('PT1H'))
        );

        // Spatie v3 auto-generates timezone components
        $calendar = Calendar::create()
            ->productIdentifier('-//AskProAI//Appointment//DE')
            ->event($event)
            ->refreshInterval(new \DateInterval('PT1H'));

        return $calendar->get();
    }

    /**
     * Generate cancellation ICS
     */
    public function generateCancellationIcs(Appointment $appointment): string
    {
        $timezone = Timezone::create('Europe/Berlin');

        $event = Event::create()
            ->uniqueIdentifier(($appointment->composite_group_uid ?? $appointment->id) . '@askpro.ai')
            ->startsAt(Carbon::parse($appointment->starts_at, 'Europe/Berlin'))
            ->endsAt(Carbon::parse($appointment->ends_at, 'Europe/Berlin'))
            ->name('[STORNIERT] ' . $appointment->service->name)
            ->description('Dieser Termin wurde storniert.')
            ->status(EventStatus::cancelled())
            ->organizer($this->getOrganizerEmail($appointment))
            ->attendee($appointment->customer->email, $appointment->customer->name);

        $calendar = Calendar::create()
            ->productIdentifier('-//AskProAI//Appointment//DE')
            ->method('CANCEL')
            ->withoutAutoTimezoneComponents()
            ->timezone($timezone)
            ->event($event);

        return $calendar->get();
    }

    /**
     * Build description for composite appointment (minimal PII)
     */
    private function buildCompositeDescription(Appointment $appointment): string
    {
        $segments = $appointment->getSegments();
        $description = "Service: {$appointment->service->name}\n";
        $description .= "Filiale: {$appointment->branch->name}\n";
        $description .= "Gesamtdauer: " . $appointment->starts_at->diffInMinutes($appointment->ends_at) . " Minuten\n";

        if (!empty($segments)) {
            $description .= "\nAblauf:\n";
            foreach ($segments as $index => $segment) {
                $segmentStart = Carbon::parse($segment['starts_at']);
                $segmentEnd = Carbon::parse($segment['ends_at']);
                $description .= ($index + 1) . ". {$segment['name']}: ";
                $description .= $segmentStart->format('H:i') . " - " . $segmentEnd->format('H:i') . "\n";
            }
        }

        $description .= "\nBestätigungscode: " . substr($appointment->composite_group_uid ?? $appointment->id, 0, 8);

        return $description;
    }

    /**
     * Build description for simple appointment
     */
    private function buildSimpleDescription(Appointment $appointment): string
    {
        $description = "Service: {$appointment->service->name}\n";
        $description .= "Filiale: {$appointment->branch->name}\n";

        if ($appointment->staff) {
            $description .= "Mitarbeiter: {$appointment->staff->name}\n";
        }

        $description .= "Dauer: " . $appointment->starts_at->diffInMinutes($appointment->ends_at) . " Minuten\n";
        $description .= "\nBestätigungscode: " . substr($appointment->id, 0, 8);

        return $description;
    }

    /**
     * Get full address for location
     */
    private function getFullAddress(Appointment $appointment): string
    {
        $branch = $appointment->branch;
        $parts = array_filter([
            $branch->name,
            $branch->address,
            $branch->postal_code . ' ' . $branch->city,
            $branch->country
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get organizer email
     */
    private function getOrganizerEmail(Appointment $appointment): string
    {
        return 'termine@' . ($appointment->company->domain ?? 'askpro.ai');
    }

    /**
     * Test DST boundary handling
     */
    public function testDstBoundary(): array
    {
        $testDates = [
            '2024-03-31 01:30:00', // Spring forward
            '2024-03-31 02:30:00',
            '2024-10-27 02:30:00', // Fall back
            '2024-10-27 03:30:00',
        ];

        $results = [];

        foreach ($testDates as $dateStr) {
            $date = Carbon::parse($dateStr, 'Europe/Berlin');
            $results[$dateStr] = [
                'original' => $dateStr,
                'carbon' => $date->toIso8601String(),
                'timezone' => $date->timezone->getName(),
                'offset' => $date->getOffset() / 3600,
                'isDst' => $date->isDST()
            ];
        }

        return $results;
    }
}