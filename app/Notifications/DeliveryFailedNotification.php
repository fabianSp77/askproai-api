<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ServiceCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Throwable;

/**
 * Delivery Failed Notification
 *
 * Sent to administrators when a service case output delivery
 * permanently fails after all retry attempts.
 *
 * This is a critical alert requiring immediate attention.
 */
class DeliveryFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ServiceCase $case,
        public Throwable $exception,
        public int $attempts
    ) {
        $this->queue = 'notifications';
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $caseName = $this->case->formatted_id ?? "Case #{$this->case->id}";
        $companyName = $this->case->company?->name ?? 'Unknown Company';
        $categoryName = $this->case->category?->name ?? 'Uncategorized';

        return (new MailMessage)
            ->error()
            ->subject("[ALERT] Delivery Failed: {$caseName}")
            ->greeting("Service Case Delivery Failed")
            ->line("A service case output delivery has permanently failed after {$this->attempts} attempts.")
            ->line("**Case Details:**")
            ->line("- **Case ID:** {$caseName}")
            ->line("- **Company:** {$companyName}")
            ->line("- **Category:** {$categoryName}")
            ->line("- **Subject:** {$this->case->subject}")
            ->line("- **Priority:** {$this->case->priority}")
            ->line("**Error:**")
            ->line("```")
            ->line($this->exception->getMessage())
            ->line("```")
            ->action('View Case in Admin', $this->getCaseUrl())
            ->line("Please investigate and resolve this issue promptly.");
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'case_id' => $this->case->id,
            'formatted_id' => $this->case->formatted_id,
            'company_id' => $this->case->company_id,
            'category_id' => $this->case->category_id,
            'exception' => $this->exception->getMessage(),
            'attempts' => $this->attempts,
        ];
    }

    /**
     * Get the URL to view the case in Filament admin.
     */
    private function getCaseUrl(): string
    {
        return url("/admin/service-cases/{$this->case->id}");
    }
}
