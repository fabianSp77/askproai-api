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
use Illuminate\Support\Facades\DB;
use App\Mail\AppointmentReminder;
use App\Mail\AppointmentConfirmation;
use App\Jobs\SendAppointmentEmailJob;
use App\Notifications\PushNotification;
use App\Services\MCP\TwilioMCPServer;
use App\Helpers\TranslationHelper;
use App\Models\NotificationTemplate;

class NotificationService
{
    protected array $channels = ['email', 'sms', 'push', 'whatsapp'];
    protected ?TwilioMCPServer $twilioMCP = null;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->twilioMCP = new TwilioMCPServer();
    }
    
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
                $company = $appointment->company;
                
                // Skip if Cal.com handles reminders
                if ($this->shouldSkipNotification($company, 'appointment')) {
                    Log::info('Skipping 24h reminder - handled by Cal.com', [
                        'appointment_id' => $appointment->id
                    ]);
                    $appointment->update(['reminder_24h_sent_at' => now()]);
                    continue;
                }
                
                // Email (only if not handled by Cal.com)
                if ($appointment->customer->email && !$this->isCalcomHandlingEmails($company)) {
                    Mail::to($appointment->customer->email)
                        ->send(new AppointmentReminder($appointment, '24_hours'));
                }
                
                // SMS (only if Twilio provider)
                if ($appointment->customer->phone && 
                    $this->canSendSms($appointment) && 
                    $company->notification_provider === 'twilio') {
                    $this->sendSms(
                        $appointment->customer->phone,
                        $this->getSmsTemplate('24h_reminder', $appointment),
                        $appointment->company_id,
                        $appointment->customer_id,
                        $appointment->id
                    );
                }
                
                // WhatsApp (only if Twilio provider)
                if ($appointment->customer->whatsapp_opt_in && 
                    $company->notification_provider === 'twilio') {
                    $this->sendWhatsApp(
                        $appointment->customer->phone,
                        $this->getSmsTemplate('24h_reminder', $appointment),
                        $appointment->company_id,
                        $appointment->customer_id,
                        $appointment->id
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
                        "Erinnerung: Ihr Termin beginnt in 30 Minuten um {$appointment->starts_at->format('H:i')} Uhr.",
                        $appointment->company_id,
                        $appointment->customer_id,
                        $appointment->id
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
        $company = $appointment->company;
        
        // Check if Cal.com handles notifications
        if ($this->shouldSkipNotification($company, 'appointment')) {
            Log::info('Skipping notification - handled by Cal.com', [
                'appointment_id' => $appointment->id,
                'company_id' => $company->id
            ]);
            return;
        }
        
        // Email (only if not handled by Cal.com)
        if ($appointment->customer->email && !$this->isCalcomHandlingEmails($company)) {
            Mail::to($appointment->customer->email)
                ->send(new AppointmentConfirmation($appointment));
        }
        
        // SMS (only if Twilio provider is selected)
        if ($appointment->customer->phone && 
            $appointment->customer->sms_opt_in && 
            $company->notification_provider === 'twilio') {
            $this->sendSms(
                $appointment->customer->phone,
                $this->getSmsTemplate('confirmation', $appointment),
                $appointment->company_id,
                $appointment->customer_id,
                $appointment->id
            );
        }
        
        // Calendar Invite
        $this->sendCalendarInvite($appointment);
    }
    
    /**
     * Sende SMS
     */
    protected function sendSms(string $phone, string $message, ?int $companyId = null, ?int $customerId = null, ?int $appointmentId = null): bool
    {
        try {
            if (!$this->twilioMCP) {
                Log::error('TwilioMCPServer not initialized');
                return false;
            }
            
            $result = $this->twilioMCP->sendSms([
                'to' => $phone,
                'message' => $message,
                'company_id' => $companyId,
                'customer_id' => $customerId,
                'appointment_id' => $appointmentId
            ]);
            
            return $result['success'] ?? false;
        } catch (\Exception $e) {
            Log::error('SMS sending failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Sende WhatsApp
     */
    protected function sendWhatsApp(string $phone, string $message, ?int $companyId = null, ?int $customerId = null, ?int $appointmentId = null): bool
    {
        try {
            if (!$this->twilioMCP) {
                Log::error('TwilioMCPServer not initialized');
                return false;
            }
            
            $result = $this->twilioMCP->sendWhatsapp([
                'to' => $phone,
                'message' => $message,
                'company_id' => $companyId,
                'customer_id' => $customerId,
                'appointment_id' => $appointmentId
            ]);
            
            return $result['success'] ?? false;
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
     * SMS-Template generieren mit Mehrsprachigkeit
     */
    protected function getSmsTemplate(string $type, Appointment $appointment): string
    {
        // Determine customer language
        $language = $appointment->customer->preferred_language ?? 
                   $appointment->company->default_language ?? 
                   'de';
        
        // Try to get template from database
        $templateKey = match($type) {
            '24h_reminder' => 'appointment.reminder.24h',
            '2h_reminder' => 'appointment.reminder.2h',
            '30m_reminder' => 'appointment.reminder.30m',
            'confirmation' => 'appointment.confirmed',
            default => null
        };
        
        if ($templateKey) {
            $notificationTemplate = TranslationHelper::getNotificationTemplate(
                $appointment->company,
                $templateKey,
                'sms',
                $language,
                [
                    'date' => $appointment->starts_at->format($language === 'de' ? 'd.m.Y' : 'M d, Y'),
                    'time' => $appointment->starts_at->format($language === 'de' ? 'H:i' : 'g:i A'),
                    'staff_name' => $appointment->staff->name,
                    'service_name' => $appointment->service?->name ?? '',
                    'branch_address' => $appointment->branch->address ?? '',
                    'company_name' => $appointment->company->name,
                    'phone' => $appointment->branch->phone ?? $appointment->company->phone
                ]
            );
            
            if ($notificationTemplate && isset($notificationTemplate['body'])) {
                return $notificationTemplate['body'];
            }
        }
        
        // Fallback to hardcoded templates
        $templates = [
            'de' => [
                '24h_reminder' => "Erinnerung: Termin morgen um {time} Uhr bei {staff}. Adresse: {location}. Antworten Sie mit ABSAGE zum Stornieren.",
                '2h_reminder' => "Ihr Termin heute um {time} Uhr bei {staff}. Bitte seien Sie pünktlich.",
                '30m_reminder' => "Ihr Termin beginnt in 30 Minuten um {time} Uhr bei {staff}.",
                'confirmation' => "Termin bestätigt: {date} um {time} Uhr bei {staff}. Speichern Sie diese SMS."
            ],
            'en' => [
                '24h_reminder' => "Reminder: Appointment tomorrow at {time} with {staff}. Address: {location}. Reply CANCEL to cancel.",
                '2h_reminder' => "Your appointment today at {time} with {staff}. Please be on time.",
                '30m_reminder' => "Your appointment starts in 30 minutes at {time} with {staff}.",
                'confirmation' => "Appointment confirmed: {date} at {time} with {staff}. Save this SMS."
            ]
        ];
        
        $languageTemplates = $templates[$language] ?? $templates['de'];
        $template = $languageTemplates[$type] ?? '';
        
        // Replace variables
        $replacements = [
            '{date}' => $appointment->starts_at->format($language === 'de' ? 'd.m.Y' : 'M d, Y'),
            '{time}' => $appointment->starts_at->format($language === 'de' ? 'H:i' : 'g:i A'),
            '{staff}' => $appointment->staff->name,
            '{location}' => $appointment->branch->address ?? ''
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
    
    /**
     * WhatsApp-Template mit Mehrsprachigkeit
     */
    protected function getWhatsAppTemplate(string $type, Appointment $appointment): array
    {
        // Determine customer language
        $language = $appointment->customer->preferred_language ?? 
                   $appointment->company->default_language ?? 
                   'de';
        
        // Map language codes for WhatsApp
        $whatsappLang = match($language) {
            'de' => 'de',
            'en' => 'en_US',
            'es' => 'es',
            'fr' => 'fr',
            'it' => 'it',
            default => 'de'
        };
        
        return [
            'name' => $type . '_template',
            'language' => ['code' => $whatsappLang],
            'components' => [
                [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $appointment->starts_at->format($language === 'de' ? 'd.m.Y' : 'M d, Y')],
                        ['type' => 'text', 'text' => $appointment->starts_at->format($language === 'de' ? 'H:i' : 'g:i A')],
                        ['type' => 'text', 'text' => $appointment->staff->name],
                        ['type' => 'text', 'text' => $appointment->service?->name ?? ''],
                        ['type' => 'text', 'text' => $appointment->branch->address ?? '']
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
    
    /**
     * Check if notifications should be skipped (handled by Cal.com)
     */
    protected function shouldSkipNotification(Company $company, string $type = 'appointment'): bool
    {
        // If Cal.com is the provider and handles notifications, skip all appointment notifications
        if ($company->notification_provider === 'calcom' && 
            $company->calcom_handles_notifications && 
            $type === 'appointment') {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if Cal.com is handling email notifications
     */
    protected function isCalcomHandlingEmails(Company $company): bool
    {
        return $company->notification_provider === 'calcom' && 
               $company->calcom_handles_notifications;
    }
    
    /**
     * Send appointment cancelled notification
     */
    public function sendAppointmentCancelledNotification(Appointment $appointment, ?string $reason = null): void
    {
        try {
            // Dispatch email job
            SendAppointmentEmailJob::dispatch(
                $appointment,
                'cancellation',
                $appointment->customer->preferred_language ?? 'de',
                $reason
            );
            
            // Send SMS if enabled
            if ($appointment->customer->phone && $appointment->customer->sms_opt_in && $this->canSendSms($appointment)) {
                $message = $appointment->customer->preferred_language === 'en'
                    ? "Your appointment on {$appointment->starts_at->format('M d')} at {$appointment->starts_at->format('g:i A')} has been cancelled."
                    : "Ihr Termin am {$appointment->starts_at->format('d.m.')} um {$appointment->starts_at->format('H:i')} Uhr wurde abgesagt.";
                    
                if ($reason) {
                    $message .= $appointment->customer->preferred_language === 'en'
                        ? " Reason: {$reason}"
                        : " Grund: {$reason}";
                }
                
                $this->sendSms(
                    $appointment->customer->phone, 
                    $message,
                    $appointment->company_id,
                    $appointment->customer_id,
                    $appointment->id
                );
            }
            
            // Send push notification if available
            if ($appointment->customer->push_token) {
                $title = $appointment->customer->preferred_language === 'en'
                    ? 'Appointment Cancelled'
                    : 'Termin abgesagt';
                    
                $body = $appointment->customer->preferred_language === 'en'
                    ? "Your appointment on {$appointment->starts_at->format('M d')} has been cancelled."
                    : "Ihr Termin am {$appointment->starts_at->format('d.m.')} wurde abgesagt.";
                    
                $this->sendPushNotification(
                    $appointment->customer->push_token,
                    $title,
                    $body,
                    [
                        'appointment_id' => $appointment->id,
                        'type' => 'cancellation',
                        'reason' => $reason
                    ]
                );
            }
            
            Log::info('Appointment cancellation notifications sent', [
                'appointment_id' => $appointment->id,
                'customer_id' => $appointment->customer_id,
                'channels' => ['email', 'sms', 'push']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to send cancellation notifications', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Send appointment rescheduled notification
     */
    public function sendAppointmentRescheduledNotification(
        Appointment $appointment,
        Carbon $oldStartTime,
        Carbon $oldEndTime,
        ?string $reason = null
    ): void {
        try {
            // Dispatch email job
            SendAppointmentEmailJob::dispatch(
                $appointment,
                'rescheduled',
                $appointment->customer->preferred_language ?? 'de',
                null, // cancellationReason
                $reason, // rescheduleReason
                $oldStartTime,
                $oldEndTime
            );
            
            // Send SMS if enabled
            if ($appointment->customer->phone && $appointment->customer->sms_opt_in && $this->canSendSms($appointment)) {
                $message = $appointment->customer->preferred_language === 'en'
                    ? "Your appointment has been rescheduled from {$oldStartTime->format('M d, g:i A')} to {$appointment->starts_at->format('M d, g:i A')}."
                    : "Ihr Termin wurde von {$oldStartTime->format('d.m., H:i')} Uhr auf {$appointment->starts_at->format('d.m., H:i')} Uhr verschoben.";
                    
                $this->sendSms(
                    $appointment->customer->phone, 
                    $message,
                    $appointment->company_id,
                    $appointment->customer_id,
                    $appointment->id
                );
            }
            
            // Send push notification if available
            if ($appointment->customer->push_token) {
                $title = $appointment->customer->preferred_language === 'en'
                    ? 'Appointment Rescheduled'
                    : 'Termin verschoben';
                    
                $body = $appointment->customer->preferred_language === 'en'
                    ? "New time: {$appointment->starts_at->format('M d, g:i A')}"
                    : "Neue Zeit: {$appointment->starts_at->format('d.m., H:i')} Uhr";
                    
                $this->sendPushNotification(
                    $appointment->customer->push_token,
                    $title,
                    $body,
                    [
                        'appointment_id' => $appointment->id,
                        'type' => 'rescheduled',
                        'old_time' => $oldStartTime->toIso8601String(),
                        'new_time' => $appointment->starts_at->toIso8601String()
                    ]
                );
            }
            
            Log::info('Appointment rescheduled notifications sent', [
                'appointment_id' => $appointment->id,
                'customer_id' => $appointment->customer_id,
                'old_time' => $oldStartTime->format('Y-m-d H:i'),
                'new_time' => $appointment->starts_at->format('Y-m-d H:i'),
                'channels' => ['email', 'sms', 'push']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to send rescheduled notifications', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}