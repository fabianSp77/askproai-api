<?php

namespace App\Mail;

use App\Models\AppointmentWish;
use App\Models\Call;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * UnfulfilledAppointmentWish Mailable
 *
 * Email sent to team when a customer's appointment wish cannot be fulfilled.
 * Includes full details about the desired time, alternatives offered, and
 * action items for manual follow-up.
 */
class UnfulfilledAppointmentWish extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public AppointmentWish $wish,
        public Call $call,
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $customerName = $this->wish->customer?->name ?? 'Kunde';

        return new Envelope(
            subject: "⏰ Terminwunsch konnte nicht erfüllt werden - {$customerName}",
            from: config('mail.from.address', 'noreply@askpro.ai'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.unfulfilled-appointment-wish',
            with: [
                'wish' => $this->wish,
                'call' => $this->call,
                'customer' => $this->wish->customer,
                'rejectionReason' => $this->wish->rejection_reason_label,
                'formattedDesiredTime' => $this->wish->formattedDesiredTime,
                'alternatives' => $this->wish->alternatives_offered ?? [],
                'callUrl' => route('filament.admin.resources.calls.view', $this->call->id),
            ],
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
