<?php

namespace App\Mail;

use App\Models\RetellAICallCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CampaignCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public RetellAICallCampaign $campaign;
    public array $report;

    /**
     * Create a new message instance.
     */
    public function __construct(RetellAICallCampaign $campaign, array $report)
    {
        $this->campaign = $campaign;
        $this->report = $report;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Campaign Completed: ' . $this->campaign->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.campaign-completed',
            with: [
                'campaign' => $this->campaign,
                'report' => $this->report,
                'dashboardUrl' => url('/admin/ai-call-center?campaign=' . $this->campaign->id),
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