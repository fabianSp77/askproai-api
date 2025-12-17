<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class AppointmentConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Appointment $appointment;
    private ?string $icsContent;
    private bool $includeIcs;

    /**
     * Create a new message instance.
     */
    public function __construct(Appointment $appointment, ?string $icsContent = null)
    {
        $this->appointment = $appointment;
        $this->icsContent = $icsContent;
        $this->includeIcs = !empty($icsContent);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $company = $this->appointment->company->name ?? config('app.name');

        return new Envelope(
            subject: "Terminbestätigung - {$company}",
            from: config('mail.from.address'),
            replyTo: $this->appointment->branch->email ?? config('mail.reply_to.address')
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.appointments.confirmation',
            with: [
                'appointment' => $this->appointment,
                'includeIcs' => $this->includeIcs
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
        if (!$this->includeIcs || !$this->icsContent) {
            return [];
        }

        return [
            Attachment::fromData(fn () => $this->icsContent, 'termin_' . $this->appointment->id . '.ics')
                ->withMime('text/calendar')
        ];
    }
}
