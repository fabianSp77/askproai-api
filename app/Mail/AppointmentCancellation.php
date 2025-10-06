<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentCancellation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Appointment $appointment;
    public ?string $reason;

    /**
     * Create a new message instance.
     */
    public function __construct(Appointment $appointment, ?string $reason = null)
    {
        $this->appointment = $appointment;
        $this->reason = $reason;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $company = $this->appointment->company->name ?? config('app.name');

        return new Envelope(
            subject: "Terminabsage - {$company}",
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
            markdown: 'emails.appointments.cancellation',
            with: [
                'appointment' => $this->appointment,
                'reason' => $this->reason
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
        return [];
    }
}