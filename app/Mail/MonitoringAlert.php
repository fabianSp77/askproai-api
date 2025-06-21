<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MonitoringAlert extends Mailable
{
    use Queueable, SerializesModels;

    public array $alert;

    /**
     * Create a new message instance.
     */
    public function __construct(array $alert)
    {
        $this->alert = $alert;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $severity = strtoupper($this->alert['severity'] ?? 'MEDIUM');
        
        return new Envelope(
            subject: "[$severity] AskProAI Alert: {$this->alert['rule']}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.monitoring-alert',
            with: [
                'alert' => $this->alert,
                'severityColor' => $this->getSeverityColor(),
                'actionUrl' => config('app.url') . '/admin/monitoring',
            ],
        );
    }

    /**
     * Get the severity color
     */
    private function getSeverityColor(): string
    {
        return match ($this->alert['severity'] ?? 'medium') {
            'critical' => '#FF0000',
            'high' => '#FF8C00',
            'medium' => '#FFD700',
            'low' => '#90EE90',
            default => '#808080',
        };
    }
}