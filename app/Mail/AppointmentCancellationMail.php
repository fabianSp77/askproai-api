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

class AppointmentCancellationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Appointment $appointment,
        public ?string $cancellationReason = null,
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
            view: 'emails.appointment.cancellation',
            with: [
                'appointment' => $this->appointment,
                'customer' => $this->appointment->customer,
                'staff' => $this->appointment->staff,
                'service' => $this->appointment->service,
                'branch' => $this->appointment->branch,
                'company' => $this->appointment->branch->company,
                'cancellationReason' => $this->cancellationReason,
                'rebookUrl' => $this->getRebookUrl(),
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
            'de' => 'Terminabsage - :date um :time Uhr',
            'en' => 'Appointment Cancellation - :date at :time',
        ];
        
        $template = $translations[app()->getLocale()] ?? $translations['de'];
        
        return __($template, [
            'date' => $this->appointment->starts_at->format('d.m.Y'),
            'time' => $this->appointment->starts_at->format('H:i'),
        ]);
    }

    /**
     * Get rebooking URL
     */
    protected function getRebookUrl(): string
    {
        return url('/appointments/book?service=' . $this->appointment->service_id . '&branch=' . $this->appointment->branch_id);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        \Log::error('Failed to send appointment cancellation email', [
            'appointment_id' => $this->appointment->id,
            'customer_email' => $this->appointment->customer->email,
            'error' => $exception->getMessage(),
        ]);
    }
}