<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Mail\AppointmentReminder;
use App\Mail\AppointmentConfirmation;
use App\Notifications\PushNotification;

class NotificationService
{
    protected array $channels = ['email', 'sms', 'push', 'whatsapp'];
    
    /**
     * Sende Terminerinnerungen
     */
    public function sendAppointmentReminders(): void
    {
        // 24 Stunden vorher
        $this->send24HourReminders();
        
        // 2 Stunden vorher
        $this->send2HourReminders();
        
        // 30 Minuten vorher
        $this->send30MinuteReminders();
    }
    
    /**
     * 24-Stunden-Erinnerung
     */
    protected function send24HourReminders(): void
    {
        $appointments = Appointment::with(['customer', 'staff', 'service'])
            ->where('starts_at', '>=', now()->addHours(23))
            ->where('starts_at', '<=', now()->addHours(25))
            ->where('status', 'confirmed')
            ->whereNull('reminder_24h_sent_at')
            ->get();
            
        foreach ($appointments as $appointment) {
            try {
                // Email
                if ($appointment->customer->email) {
                    Mail::to($appointment->customer->email)
                        ->send(new AppointmentReminder($appointment, '24_hours'));
                }
                
                // SMS
                if ($appointment->customer->phone && $this->canSendSms($appointment)) {
                    $this->sendSms(
                        $appointment->customer->phone,
                        $this->getSmsTemplate('24h_reminder', $appointment)
                    );
                }
                
                // WhatsApp
                if ($appointment->customer->whatsapp_opt_in) {
                    $this->sendWhatsApp(
                        $appointment->customer->phone,
                        $this->getWhatsAppTemplate('24h_reminder', $appointment)
                    );
                }
                
                $appointment->update(['reminder_24h_sent_at' => now()]);
                
            } catch (\Exception $e) {
                Log::error('Failed to send 24h reminder', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    /**
     * 2-Stunden-Erinnerung
     */
    protected function send2HourReminders(): void
    {
        $appointments = Appointment::with(['customer', 'staff', 'service'])
            ->where('starts_at', '>=', now()->addMinutes(110))
            ->where('starts_at', '<=', now()->addMinutes(130))
            ->where('status', 'confirmed')
            ->whereNull('reminder_2h_sent_at')
            ->get();
            
        foreach ($appointments as $appointment) {
            try {
                // Nur Email für 2h Reminder
                if ($appointment->customer->email) {
                    Mail::to($appointment->customer->email)
                        ->send(new AppointmentReminder($appointment, '2_hours'));
                }
                
                $appointment->update(['reminder_2h_sent_at' => now()]);
                
            } catch (\Exception $e) {
                Log::error('Failed to send 2h reminder', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    /**
     * 30-Minuten-Erinnerung (Push-Notification)
     */
    protected function send30MinuteReminders(): void
    {
        $appointments = Appointment::with(['customer', 'staff', 'service'])
            ->where('starts_at', '>=', now()->addMinutes(25))
            ->where('starts_at', '<=', now()->addMinutes(35))
            ->where('status', 'confirmed')
            ->whereNull('reminder_30m_sent_at')
            ->get();
            
        foreach ($appointments as $appointment) {
            try {
                // Push Notification
                if ($appointment->customer->push_token) {
                    $this->sendPushNotification(
                        $appointment->customer->push_token,
                        'Termin in 30 Minuten',
                        "Ihr Termin bei {$appointment->staff->name} beginnt um {$appointment->starts_at->format('H:i')} Uhr",
                        [
                            'appointment_id' => $appointment->id,
                            'type' => '30m_reminder'
                        ]
                    );
                }
                
                // SMS als Fallback
                if (!$appointment->customer->push_token && $appointment->customer->phone) {
                    $this->sendSms(
                        $appointment->customer->phone,
                        "Erinnerung: Ihr Termin beginnt in 30 Minuten um {$appointment->starts_at->format('H:i')} Uhr."
                    );
                }
                
                $appointment->update(['reminder_30m_sent_at' => now()]);
                
            } catch (\Exception $e) {
                Log::error('Failed to send 30m reminder', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    /**
     * Sende Terminbestätigung
     */
    public function sendAppointmentConfirmation(Appointment $appointment): void
    {
        // Email
        if ($appointment->customer->email) {
            Mail::to($appointment->customer->email)
                ->send(new AppointmentConfirmation($appointment));
        }
        
        // SMS
        if ($appointment->customer->phone && $appointment->customer->sms_opt_in) {
            $this->sendSms(
                $appointment->customer->phone,
                $this->getSmsTemplate('confirmation', $appointment)
            );
        }
        
        // Calendar Invite
        $this->sendCalendarInvite($appointment);
    }
    
    /**
     * Sende SMS
     */
    protected function sendSms(string $phone, string $message): bool
    {
        try {
            // Beispiel mit Twilio
            $response = Http::withBasicAuth(
                config('services.twilio.sid'),
                config('services.twilio.token')
            )->post('https://api.twilio.com/2010-04-01/Accounts/' . config('services.twilio.sid') . '/Messages.json', [
                'From' => config('services.twilio.from'),
                'To' => $phone,
                'Body' => $message
            ]);
            
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('SMS sending failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Sende WhatsApp
     */
    protected function sendWhatsApp(string $phone, array $template): bool
    {
        try {
            // WhatsApp Business API
            $response = Http::withToken(config('services.whatsapp.token'))
                ->post(config('services.whatsapp.url') . '/messages', [
                    'messaging_product' => 'whatsapp',
                    'to' => $phone,
                    'type' => 'template',
                    'template' => $template
                ]);
                
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('WhatsApp sending failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Sende Push-Notification
     */
    protected function sendPushNotification(string $token, string $title, string $body, array $data = []): bool
    {
        try {
            // Firebase Cloud Messaging
            $response = Http::withToken(config('services.fcm.key'))
                ->post('https://fcm.googleapis.com/fcm/send', [
                    'to' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                        'sound' => 'default'
                    ],
                    'data' => $data
                ]);
                
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Push notification failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * SMS-Template generieren
     */
    protected function getSmsTemplate(string $type, Appointment $appointment): string
    {
        $templates = [
            '24h_reminder' => "Erinnerung: Termin morgen um {time} Uhr bei {staff}. Adresse: {location}. Antworten Sie mit ABSAGE zum Stornieren.",
            '2h_reminder' => "Ihr Termin heute um {time} Uhr bei {staff}. Bitte seien Sie pünktlich.",
            'confirmation' => "Termin bestätigt: {date} um {time} Uhr bei {staff}. Speichern Sie diese SMS."
        ];
        
        $template = $templates[$type] ?? '';
        
        return str_replace([
            '{date}' => $appointment->starts_at->format('d.m.Y'),
            '{time}' => $appointment->starts_at->format('H:i'),
            '{staff}' => $appointment->staff->name,
            '{location}' => $appointment->branch->address ?? ''
        ], $template);
    }
    
    /**
     * WhatsApp-Template
     */
    protected function getWhatsAppTemplate(string $type, Appointment $appointment): array
    {
        return [
            'name' => $type . '_template',
            'language' => ['code' => 'de'],
            'components' => [
                [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $appointment->starts_at->format('d.m.Y')],
                        ['type' => 'text', 'text' => $appointment->starts_at->format('H:i')],
                        ['type' => 'text', 'text' => $appointment->staff->name]
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Prüfe ob SMS gesendet werden kann
     */
    protected function canSendSms(Appointment $appointment): bool
    {
        // Prüfe Opt-in
        if (!$appointment->customer->sms_opt_in) {
            return false;
        }
        
        // Prüfe Zeitfenster (keine SMS zwischen 21:00 und 09:00)
        $hour = now()->hour;
        if ($hour >= 21 || $hour < 9) {
            return false;
        }
        
        // Prüfe SMS-Limit using webhook_events table
        $sentToday = DB::table('webhook_events')
            ->where('provider', 'notification')
            ->where('payload->customer_id', $appointment->customer_id)
            ->where('payload->channel', 'sms')
            ->whereDate('created_at', today())
            ->count();
            
        return $sentToday < 3; // Max 3 SMS pro Tag
    }
    
    /**
     * Sende Kalender-Einladung
     */
    protected function sendCalendarInvite(Appointment $appointment): void
    {
        // ICS-Datei generieren und an Email anhängen
        $ics = $this->generateIcsFile($appointment);
        
        // Wird als Attachment zur Bestätigungs-Email hinzugefügt
        // Implementation abhängig vom Mail-System
    }
    
    /**
     * Generiere ICS-Datei
     */
    protected function generateIcsFile(Appointment $appointment): string
    {
        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//AskProAI//Appointment//EN\r\n";
        $ics .= "BEGIN:VEVENT\r\n";
        $ics .= "UID:" . $appointment->id . "@askproai.de\r\n";
        $ics .= "DTSTART:" . $appointment->starts_at->format('Ymd\THis') . "\r\n";
        $ics .= "DTEND:" . $appointment->ends_at->format('Ymd\THis') . "\r\n";
        $ics .= "SUMMARY:Termin bei " . $appointment->staff->name . "\r\n";
        $ics .= "LOCATION:" . ($appointment->branch->address ?? '') . "\r\n";
        $ics .= "END:VEVENT\r\n";
        $ics .= "END:VCALENDAR\r\n";
        
        return $ics;
    }
}