<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Http\Request;

class SecurityAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private array $threats;
    private Request $request;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $threats, Request $request)
    {
        $this->threats = $threats;
        $this->request = $request;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        // Send via mail and Slack if configured
        $channels = ['mail', 'database'];
        
        if (config('services.slack.webhook_url')) {
            $channels[] = 'slack';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $threatTypes = [];
        foreach ($this->threats as $threat) {
            if (isset($threat['threats'])) {
                $threatTypes = array_merge($threatTypes, $threat['threats']);
            }
        }
        $threatTypes = array_unique($threatTypes);

        return (new MailMessage)
            ->error()
            ->subject('🚨 Security Alert: Potential Attack Detected')
            ->line('A potential security threat has been detected on the AskProAI platform.')
            ->line('**Threat Types:** ' . implode(', ', $threatTypes))
            ->line('**IP Address:** ' . $this->request->ip())
            ->line('**URL:** ' . $this->request->fullUrl())
            ->line('**Method:** ' . $this->request->method())
            ->line('**Time:** ' . now()->format('Y-m-d H:i:s'))
            ->action('View Security Dashboard', url('/admin/security'))
            ->line('Please investigate this incident immediately.');
    }

    /**
     * Get the Slack representation of the notification.
     */
    public function toSlack(object $notifiable): SlackMessage
    {
        $threatTypes = [];
        foreach ($this->threats as $threat) {
            if (isset($threat['threats'])) {
                $threatTypes = array_merge($threatTypes, $threat['threats']);
            }
        }
        $threatTypes = array_unique($threatTypes);

        return (new SlackMessage)
            ->error()
            ->content('🚨 Security Alert: Potential Attack Detected')
            ->attachment(function ($attachment) use ($threatTypes) {
                $attachment->title('Security Threat Details')
                    ->fields([
                        'Threat Types' => implode(', ', $threatTypes),
                        'IP Address' => $this->request->ip(),
                        'URL' => $this->request->fullUrl(),
                        'Method' => $this->request->method(),
                        'Time' => now()->format('Y-m-d H:i:s'),
                    ])
                    ->color('danger');
            });
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'security_alert',
            'threats' => $this->threats,
            'request' => [
                'ip' => $this->request->ip(),
                'url' => $this->request->fullUrl(),
                'method' => $this->request->method(),
                'user_agent' => $this->request->userAgent(),
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }
}