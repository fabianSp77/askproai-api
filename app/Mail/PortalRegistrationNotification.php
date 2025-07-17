<?php

namespace App\Mail;

use App\Models\PortalUser;
use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PortalRegistrationNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PortalUser $user,
        public Company $company
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Neue Business Portal Registrierung - ' . $this->company->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.portal-registration-notification',
        );
    }
}