<?php

namespace App\Services;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Str;

class IcsGeneratorService
{
    /**
     * Generate ICS content for an appointment
     */
    public function generateForAppointment(Appointment $appointment): string
    {
        $appointment->load(['customer', 'staff', 'service', 'branch.company']);
        
        $uid = $this->generateUid($appointment);
        $dtstart = $this->formatDateTime($appointment->starts_at);
        $dtend = $this->formatDateTime($appointment->ends_at);
        $dtstamp = $this->formatDateTime(now());
        $created = $this->formatDateTime($appointment->created_at);
        $lastModified = $this->formatDateTime($appointment->updated_at);
        
        $summary = $this->generateSummary($appointment);
        $description = $this->generateDescription($appointment);
        $location = $this->generateLocation($appointment);
        $organizer = $this->generateOrganizer($appointment);
        $attendees = $this->generateAttendees($appointment);
        
        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//AskProAI//Appointment System//DE\r\n";
        $ics .= "CALSCALE:GREGORIAN\r\n";
        $ics .= "METHOD:REQUEST\r\n";
        $ics .= "BEGIN:VEVENT\r\n";
        $ics .= "UID:{$uid}\r\n";
        $ics .= "DTSTART:{$dtstart}\r\n";
        $ics .= "DTEND:{$dtend}\r\n";
        $ics .= "DTSTAMP:{$dtstamp}\r\n";
        $ics .= "CREATED:{$created}\r\n";
        $ics .= "LAST-MODIFIED:{$lastModified}\r\n";
        $ics .= "SUMMARY:{$summary}\r\n";
        $ics .= "DESCRIPTION:{$description}\r\n";
        $ics .= "LOCATION:{$location}\r\n";
        $ics .= "STATUS:CONFIRMED\r\n";
        $ics .= "TRANSP:OPAQUE\r\n";
        $ics .= $organizer;
        $ics .= $attendees;
        $ics .= "CLASS:PUBLIC\r\n";
        $ics .= "PRIORITY:5\r\n";
        $ics .= "BEGIN:VALARM\r\n";
        $ics .= "TRIGGER:-PT1H\r\n";
        $ics .= "ACTION:DISPLAY\r\n";
        $ics .= "DESCRIPTION:Terminerinnerung\r\n";
        $ics .= "END:VALARM\r\n";
        $ics .= "END:VEVENT\r\n";
        $ics .= "END:VCALENDAR\r\n";
        
        return $ics;
    }
    
    /**
     * Generate unique ID for the event
     */
    protected function generateUid(Appointment $appointment): string
    {
        return sprintf(
            '%s-%s@%s',
            $appointment->id,
            Str::random(8),
            parse_url(config('app.url'), PHP_URL_HOST) ?? 'askproai.de'
        );
    }
    
    /**
     * Format datetime for ICS
     */
    protected function formatDateTime(Carbon $datetime): string
    {
        return $datetime->timezone('UTC')->format('Ymd\THis\Z');
    }
    
    /**
     * Generate event summary
     */
    protected function generateSummary(Appointment $appointment): string
    {
        $service = $appointment->service?->name ?? 'Termin';
        $company = $appointment->branch->company->name;
        
        return $this->escapeString("{$service} - {$company}");
    }
    
    /**
     * Generate event description
     */
    protected function generateDescription(Appointment $appointment): string
    {
        $lines = [];
        
        $lines[] = "Termin bei {$appointment->branch->company->name}";
        $lines[] = "";
        
        if ($appointment->service) {
            $lines[] = "Dienstleistung: {$appointment->service->name}";
            $lines[] = "Dauer: {$appointment->service->duration_minutes} Minuten";
            if ($appointment->service->price) {
                $lines[] = "Preis: {$appointment->service->price} €";
            }
        }
        
        if ($appointment->staff) {
            $lines[] = "Mitarbeiter: {$appointment->staff->first_name} {$appointment->staff->last_name}";
        }
        
        $lines[] = "";
        $lines[] = "Filiale: {$appointment->branch->name}";
        $lines[] = "Adresse: {$appointment->branch->address}";
        
        if ($appointment->branch->phone) {
            $lines[] = "Telefon: {$appointment->branch->phone}";
        }
        
        if ($appointment->notes) {
            $lines[] = "";
            $lines[] = "Notizen: {$appointment->notes}";
        }
        
        $lines[] = "";
        $lines[] = "Bitte erscheinen Sie pünktlich zu Ihrem Termin.";
        
        return $this->escapeString(implode("\\n", $lines));
    }
    
    /**
     * Generate location
     */
    protected function generateLocation(Appointment $appointment): string
    {
        $branch = $appointment->branch;
        
        $location = $branch->name;
        if ($branch->address) {
            $location .= ", {$branch->address}";
        }
        
        return $this->escapeString($location);
    }
    
    /**
     * Generate organizer
     */
    protected function generateOrganizer(Appointment $appointment): string
    {
        $branch = $appointment->branch;
        $email = $branch->email ?? $branch->company->email ?? 'noreply@askproai.de';
        $name = $branch->name;
        
        return "ORGANIZER;CN={$name}:mailto:{$email}\r\n";
    }
    
    /**
     * Generate attendees
     */
    protected function generateAttendees(Appointment $appointment): string
    {
        $attendees = "";
        
        // Add customer as attendee
        $customer = $appointment->customer;
        $customerName = trim("{$customer->first_name} {$customer->last_name}");
        $attendees .= "ATTENDEE;CN={$customerName};ROLE=REQ-PARTICIPANT;PARTSTAT=ACCEPTED;RSVP=TRUE:mailto:{$customer->email}\r\n";
        
        // Add staff as attendee if assigned
        if ($appointment->staff && $appointment->staff->email) {
            $staffName = trim("{$appointment->staff->first_name} {$appointment->staff->last_name}");
            $attendees .= "ATTENDEE;CN={$staffName};ROLE=REQ-PARTICIPANT;PARTSTAT=ACCEPTED;RSVP=FALSE:mailto:{$appointment->staff->email}\r\n";
        }
        
        return $attendees;
    }
    
    /**
     * Escape string for ICS format
     */
    protected function escapeString(string $string): string
    {
        // Escape special characters
        $string = str_replace('\\', '\\\\', $string);
        $string = str_replace(',', '\,', $string);
        $string = str_replace(';', '\;', $string);
        $string = str_replace("\n", '\n', $string);
        $string = str_replace("\r", '', $string);
        
        // Fold long lines (max 75 chars)
        $lines = [];
        while (strlen($string) > 75) {
            $lines[] = substr($string, 0, 75);
            $string = ' ' . substr($string, 75);
        }
        if (strlen($string) > 0) {
            $lines[] = $string;
        }
        
        return implode("\r\n", $lines);
    }
}