<?php

namespace App\Mail;

use App\Models\PortalUser;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PortalRegistrationConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PortalUser $user
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ihre Registrierung bei AskProAI',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.portal-registration-confirmation',
        );
    }
}