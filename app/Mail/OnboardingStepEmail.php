<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OnboardingStepEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Company $company,
        public User $user,
        public string $milestone
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match($this->milestone) {
            '25% abgeschlossen' => 'Großartig! Sie sind auf dem richtigen Weg',
            '50% abgeschlossen' => 'Halbzeit! Ihre Einrichtung nimmt Form an',
            '75% abgeschlossen' => 'Fast geschafft! Nur noch wenige Schritte',
            'completed' => 'Herzlichen Glückwunsch! Ihr System ist einsatzbereit',
            default => 'Ihr Fortschritt bei AskProAI',
        };

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $view = $this->milestone === 'completed' 
            ? 'emails.onboarding-completed' 
            : 'emails.onboarding-progress';

        return new Content(
            view: $view,
            with: [
                'userName' => $this->user->name,
                'companyName' => $this->company->name,
                'milestone' => $this->milestone,
                'dashboardUrl' => url('/admin'),
                'onboardingUrl' => url('/admin/onboarding'),
            ],
        );
    }
}