<?php

namespace App\Notifications;

use App\Models\CallbackRequest;
use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when callback request is assigned to staff
 */
class CallbackAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly CallbackRequest $callbackRequest,
        public readonly ?Customer $customer = null,
        public readonly string $priority = 'normal'
    ) {
        $this->onQueue('notifications');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('New Callback Request Assigned')
                    ->greeting('Hello ' . $notifiable->name)
                    ->line('A new callback request has been assigned to you.')
                    ->line('Customer: ' . $this->callbackRequest->customer_name)
                    ->line('Phone: ' . $this->callbackRequest->phone_number)
                    ->line('Priority: ' . $this->priority)
                    ->action('View Callback', url('/admin/callbacks/' . $this->callbackRequest->id))
                    ->line('Please contact the customer as soon as possible.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'callback_request_id' => $this->callbackRequest->id,
            'customer_id' => $this->customer?->id,
            'customer_name' => $this->callbackRequest->customer_name,
            'phone_number' => $this->callbackRequest->phone_number,
            'priority' => $this->priority,
            'expires_at' => $this->callbackRequest->expires_at?->toIso8601String(),
        ];
    }
}
