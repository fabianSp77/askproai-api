<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use App\Models\Appointment;
use App\Services\IcsGeneratorService;

class AppointmentConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Appointment $appointment,
        string $locale = 'de'
    ) {
        $this->appointment->load(['customer', 'staff', 'service', 'branch.company']);
        $this->locale($locale);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $company = $this->appointment->branch->company;
        
        return new Envelope(
            subject: $this->getSubject(),
            from: new \Illuminate\Mail\Mailables\Address(
                $company->email ?? config('mail.from.address'),
                $company->name
            ),
            replyTo: [
                new \Illuminate\Mail\Mailables\Address(
                    $this->appointment->branch->email ?? $company->email ?? config('mail.from.address'),
                    $this->appointment->branch->name
                )
            ]
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.appointment.confirmation',
            with: [
                'appointment' => $this->appointment,
                'customer' => $this->appointment->customer,
                'staff' => $this->appointment->staff,
                'service' => $this->appointment->service,
                'branch' => $this->appointment->branch,
                'company' => $this->appointment->branch->company,
                'cancelUrl' => $this->getCancelUrl(),
                'rescheduleUrl' => $this->getRescheduleUrl(),
                'addToCalendarUrl' => $this->getAddToCalendarUrl(),
                'locale' => app()->getLocale(),
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];
        
        // Attach ICS calendar file
        $icsContent = app(IcsGeneratorService::class)->generateForAppointment($this->appointment);
        
        $attachments[] = Attachment::fromData(
            fn () => $icsContent,
            'termin.ics'
        )->withMime('text/calendar');
        
        // Add company logo if available
        $company = $this->appointment->branch->company;
        if ($company->logo && file_exists(storage_path('app/public/' . $company->logo))) {
            $attachments[] = Attachment::fromPath(storage_path('app/public/' . $company->logo))
                ->as('logo.png')
                ->withMime('image/png');
        }
        
        return $attachments;
    }

    /**
     * Get localized subject
     */
    protected function getSubject(): string
    {
        $translations = [
            'de' => 'Terminbestätigung - :date um :time Uhr',
            'en' => 'Appointment Confirmation - :date at :time',
        ];
        
        $template = $translations[app()->getLocale()] ?? $translations['de'];
        
        return __($template, [
            'date' => $this->appointment->starts_at->format('d.m.Y'),
            'time' => $this->appointment->starts_at->format('H:i'),
        ]);
    }

    /**
     * Get cancellation URL
     */
    protected function getCancelUrl(): string
    {
        return url('/appointments/' . $this->appointment->id . '/cancel?token=' . $this->generateToken());
    }

    /**
     * Get reschedule URL
     */
    protected function getRescheduleUrl(): string
    {
        return url('/appointments/' . $this->appointment->id . '/reschedule?token=' . $this->generateToken());
    }

    /**
     * Get add to calendar URL
     */
    protected function getAddToCalendarUrl(): string
    {
        return url('/appointments/' . $this->appointment->id . '/calendar?token=' . $this->generateToken());
    }

    /**
     * Generate secure token for URLs
     */
    protected function generateToken(): string
    {
        return hash_hmac(
            'sha256',
            $this->appointment->id . $this->appointment->customer->email,
            config('app.key')
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        \Log::error('Failed to send appointment confirmation email', [
            'appointment_id' => $this->appointment->id,
            'customer_email' => $this->appointment->customer->email,
            'error' => $exception->getMessage(),
        ]);
    }
}