# Email Integration

## Overview

Email integration in AskProAI handles appointment confirmations, reminders, notifications, and transactional emails. The system supports multiple email providers with template management and tracking capabilities.

## Email Configuration

### Environment Setup
```bash
# Primary Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.udag.de
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@askproai.de
MAIL_FROM_NAME="AskProAI"

# Alternative Providers
MAILGUN_DOMAIN=mg.askproai.de
MAILGUN_SECRET=key-xxxxxx
POSTMARK_TOKEN=xxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
```

### Multiple Mail Drivers
```php
// config/mail.php
'mailers' => [
    'smtp' => [
        'transport' => 'smtp',
        'host' => env('MAIL_HOST'),
        'port' => env('MAIL_PORT'),
        'encryption' => env('MAIL_ENCRYPTION'),
        'username' => env('MAIL_USERNAME'),
        'password' => env('MAIL_PASSWORD'),
    ],
    
    'mailgun' => [
        'transport' => 'mailgun',
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
    ],
    
    'postmark' => [
        'transport' => 'postmark',
        'token' => env('POSTMARK_TOKEN'),
    ],
],
```

## Email Templates

### Mailable Classes

#### Appointment Confirmation
```php
namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AppointmentConfirmation extends Mailable
{
    use Queueable, SerializesModels;
    
    public function __construct(
        public Appointment $appointment
    ) {}
    
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Terminbestätigung - ' . $this->appointment->branch->company->name,
            replyTo: [
                new Address($this->appointment->branch->email, $this->appointment->branch->name),
            ],
        );
    }
    
    public function content(): Content
    {
        return new Content(
            view: 'emails.appointment.confirmation',
            with: [
                'customerName' => $this->appointment->customer->name,
                'appointmentDate' => $this->appointment->date->format('d.m.Y'),
                'appointmentTime' => $this->appointment->time->format('H:i'),
                'serviceName' => $this->appointment->service->name,
                'branchName' => $this->appointment->branch->name,
                'branchAddress' => $this->appointment->branch->full_address,
                'branchPhone' => $this->appointment->branch->phone,
                'cancelUrl' => $this->getCancelUrl(),
                'rescheduleUrl' => $this->getRescheduleUrl(),
            ],
        );
    }
    
    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk('public', $this->appointment->ics_file_path)
                ->as('termin.ics')
                ->withMime('text/calendar'),
        ];
    }
}
```

#### Appointment Reminder
```php
class AppointmentReminder extends Mailable
{
    use Queueable, SerializesModels;
    
    public function __construct(
        public Appointment $appointment,
        public string $reminderType // '24h' or '1h'
    ) {}
    
    public function build()
    {
        $subject = $this->reminderType === '24h' 
            ? 'Terminerinnerung für morgen'
            : 'Ihr Termin in 1 Stunde';
            
        return $this->subject($subject)
            ->view('emails.appointment.reminder')
            ->with([
                'isUrgent' => $this->reminderType === '1h',
                'appointment' => $this->appointment,
            ]);
    }
}
```

### Email Templates (Blade)

#### Confirmation Template
```blade
{{-- resources/views/emails/appointment/confirmation.blade.php --}}
@component('mail::message')
# Terminbestätigung

Hallo {{ $customerName }},

Ihr Termin wurde erfolgreich gebucht:

@component('mail::panel')
**Datum:** {{ $appointmentDate }}  
**Uhrzeit:** {{ $appointmentTime }}  
**Service:** {{ $serviceName }}  
**Ort:** {{ $branchName }}  
**Adresse:** {{ $branchAddress }}
@endcomponent

## Was Sie mitbringen sollten:
- Versichertenkarte (falls vorhanden)
- Relevante Unterlagen
- Pünktliches Erscheinen (bitte 5 Minuten vorher)

@component('mail::button', ['url' => $rescheduleUrl])
Termin verschieben
@endcomponent

Falls Sie den Termin nicht wahrnehmen können, bitten wir um rechtzeitige Absage.

Mit freundlichen Grüßen,  
{{ $branchName }}

@component('mail::subcopy')
Bei Fragen erreichen Sie uns unter {{ $branchPhone }}
@endcomponent
@endcomponent
```

## Email Service

### Service Implementation
```php
namespace App\Services;

use Illuminate\Support\Facades\Mail;
use App\Models\EmailLog;

class EmailService
{
    public function sendAppointmentConfirmation(Appointment $appointment): bool
    {
        try {
            Mail::to($appointment->customer->email)
                ->cc($appointment->branch->email)
                ->send(new AppointmentConfirmation($appointment));
            
            $this->logEmail(
                $appointment->customer->email,
                'appointment_confirmation',
                'sent',
                ['appointment_id' => $appointment->id]
            );
            
            return true;
        } catch (\Exception $e) {
            $this->logEmail(
                $appointment->customer->email,
                'appointment_confirmation',
                'failed',
                ['error' => $e->getMessage()]
            );
            
            return false;
        }
    }
    
    public function sendBulkReminders(Collection $appointments): array
    {
        $results = ['sent' => 0, 'failed' => 0];
        
        foreach ($appointments as $appointment) {
            $mailable = new AppointmentReminder($appointment, '24h');
            
            try {
                Mail::to($appointment->customer->email)->queue($mailable);
                $results['sent']++;
            } catch (\Exception $e) {
                $results['failed']++;
                Log::error('Reminder email failed', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $results;
    }
    
    private function logEmail(string $to, string $type, string $status, array $metadata = [])
    {
        EmailLog::create([
            'to' => $to,
            'type' => $type,
            'status' => $status,
            'metadata' => $metadata,
            'sent_at' => $status === 'sent' ? now() : null,
        ]);
    }
}
```

## Email Tracking

### Open Tracking
```php
namespace App\Services;

class EmailTrackingService
{
    public function generateTrackingPixel(string $emailId): string
    {
        $token = Crypt::encryptString($emailId);
        return route('email.track', ['token' => $token]);
    }
    
    public function trackOpen(string $token): void
    {
        try {
            $emailId = Crypt::decryptString($token);
            
            EmailLog::where('id', $emailId)->update([
                'opened_at' => now(),
                'open_count' => DB::raw('open_count + 1'),
            ]);
        } catch (\Exception $e) {
            Log::warning('Invalid email tracking token', ['token' => $token]);
        }
    }
}
```

### Click Tracking
```php
// Add tracking to links
class TrackableLink
{
    public static function make(string $url, string $emailId, string $linkName): string
    {
        $data = [
            'url' => $url,
            'email_id' => $emailId,
            'link' => $linkName,
        ];
        
        $token = Crypt::encryptString(json_encode($data));
        
        return route('email.click', ['token' => $token]);
    }
}
```

## Transactional Emails

### Welcome Email
```php
class WelcomeEmail extends Mailable
{
    public function build()
    {
        return $this->subject('Willkommen bei AskProAI')
            ->view('emails.welcome')
            ->with([
                'company' => $this->company,
                'setupUrl' => route('admin.setup', $this->company),
                'supportEmail' => 'support@askproai.de',
            ]);
    }
}
```

### Invoice Email
```php
class InvoiceEmail extends Mailable
{
    public function build()
    {
        return $this->subject('Ihre Rechnung von AskProAI')
            ->view('emails.invoice')
            ->attach($this->invoice->pdf_path, [
                'as' => 'Rechnung-' . $this->invoice->number . '.pdf',
                'mime' => 'application/pdf',
            ]);
    }
}
```

## Email Queue Management

### Queue Configuration
```php
// Send emails via queue
Mail::to($customer->email)
    ->queue(new AppointmentConfirmation($appointment));

// Delayed sending
Mail::to($customer->email)
    ->later(now()->addMinutes(5), new AppointmentConfirmation($appointment));

// Specific queue
Mail::to($customer->email)
    ->onQueue('emails')
    ->queue(new AppointmentConfirmation($appointment));
```

### Failed Email Handling
```php
namespace App\Listeners;

class EmailFailedListener
{
    public function handle(MessageSending $event)
    {
        Log::error('Email failed to send', [
            'to' => $event->data['to'],
            'subject' => $event->data['subject'],
            'error' => $event->data['error'],
        ]);
        
        // Notify admin
        $this->notifyAdmin($event->data);
        
        // Try alternative provider
        $this->tryAlternativeProvider($event->data);
    }
}
```

## Email Personalization

### Dynamic Content
```php
class PersonalizedEmail extends Mailable
{
    public function build()
    {
        $customer = $this->appointment->customer;
        
        return $this->view('emails.personalized')
            ->with([
                'greeting' => $this->getGreeting($customer),
                'content' => $this->personalizeContent($customer),
                'recommendations' => $this->getRecommendations($customer),
            ]);
    }
    
    private function getGreeting(Customer $customer): string
    {
        $hour = now()->hour;
        $name = $customer->preferred_name ?? $customer->first_name;
        
        if ($hour < 12) {
            return "Guten Morgen, {$name}";
        } elseif ($hour < 18) {
            return "Guten Tag, {$name}";
        } else {
            return "Guten Abend, {$name}";
        }
    }
}
```

## Email Analytics

### Analytics Dashboard
```php
class EmailAnalytics
{
    public function getStats(string $period = 'month'): array
    {
        $logs = EmailLog::where('created_at', '>=', now()->sub($period, 1))->get();
        
        return [
            'total_sent' => $logs->where('status', 'sent')->count(),
            'open_rate' => $this->calculateOpenRate($logs),
            'click_rate' => $this->calculateClickRate($logs),
            'bounce_rate' => $this->calculateBounceRate($logs),
            'by_type' => $logs->groupBy('type')->map->count(),
            'hourly_distribution' => $this->getHourlyDistribution($logs),
        ];
    }
}
```

## Spam Prevention

### Best Practices
```php
class SpamPrevention
{
    public function validateEmail(Mailable $email): array
    {
        $issues = [];
        
        // Check SPF/DKIM
        if (!$this->hasValidSPF()) {
            $issues[] = 'Missing or invalid SPF record';
        }
        
        // Check content
        if ($this->hasSpamKeywords($email->render())) {
            $issues[] = 'Contains spam trigger words';
        }
        
        // Check images
        if ($this->hasTooManyImages($email)) {
            $issues[] = 'Too many images relative to text';
        }
        
        return $issues;
    }
}
```

## Testing

### Email Testing
```php
// Test email sending
php artisan email:test recipient@example.com --template=appointment_confirmation

// Preview email in browser
Route::get('/email/preview/{template}', function ($template) {
    $appointment = Appointment::first();
    return new AppointmentConfirmation($appointment);
});
```

### Unit Tests
```php
class EmailTest extends TestCase
{
    public function test_appointment_confirmation_email()
    {
        Mail::fake();
        
        $appointment = Appointment::factory()->create();
        
        Mail::to($appointment->customer->email)
            ->send(new AppointmentConfirmation($appointment));
        
        Mail::assertSent(AppointmentConfirmation::class, function ($mail) use ($appointment) {
            return $mail->appointment->id === $appointment->id &&
                   $mail->hasTo($appointment->customer->email);
        });
    }
}
```

## Related Documentation
- [SMS Integration](sms.md)
- [WhatsApp Integration](whatsapp.md)
- [Notification System](../features/notifications.md)