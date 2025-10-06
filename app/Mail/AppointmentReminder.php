<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Appointment $appointment;
    public int $hoursBeforeStart;

    /**
     * Create a new message instance.
     */
    public function __construct(Appointment $appointment, int $hoursBeforeStart = 24)
    {
        $this->appointment = $appointment;
        $this->hoursBeforeStart = $hoursBeforeStart;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $company = $this->appointment->company->name ?? config('app.name');

        return new Envelope(
            subject: "Terminerinnerung - {$company}",
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
            markdown: 'emails.appointments.reminder',
            with: [
                'appointment' => $this->appointment,
                'hoursBeforeStart' => $this->hoursBeforeStart
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