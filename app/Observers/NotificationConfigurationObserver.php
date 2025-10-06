<?php

namespace App\Observers;

use App\Models\NotificationConfiguration;
use App\Models\NotificationEventMapping;
use Illuminate\Validation\ValidationException;

class NotificationConfigurationObserver
{
    /**
     * Handle the NotificationConfiguration "creating" event.
     */
    public function creating(NotificationConfiguration $notificationConfiguration): void
    {
        $this->validateEventType($notificationConfiguration);
        $this->validateChannel($notificationConfiguration);
        $this->sanitizeTemplateContent($notificationConfiguration);
    }

    /**
     * Handle the NotificationConfiguration "updating" event.
     */
    public function updating(NotificationConfiguration $notificationConfiguration): void
    {
        if ($notificationConfiguration->isDirty('event_type')) {
            $this->validateEventType($notificationConfiguration);
        }

        if ($notificationConfiguration->isDirty('channel')) {
            $this->validateChannel($notificationConfiguration);
        }

        if ($notificationConfiguration->isDirty(['template_content', 'metadata'])) {
            $this->sanitizeTemplateContent($notificationConfiguration);
        }
    }

    /**
     * Validate that event_type exists in NotificationEventMapping.
     */
    protected function validateEventType(NotificationConfiguration $notificationConfiguration): void
    {
        $eventType = $notificationConfiguration->event_type;

        if (!$eventType) {
            throw ValidationException::withMessages([
                'event_type' => 'Event type is required.',
            ]);
        }

        // Check if event type exists and is active
        $eventMapping = NotificationEventMapping::where('event_type', $eventType)
            ->where('is_active', true)
            ->first();

        if (!$eventMapping) {
            throw ValidationException::withMessages([
                'event_type' => "Invalid or inactive event type: {$eventType}",
            ]);
        }

        // Check if the selected channel is allowed for this event type
        $allowedChannels = $eventMapping->default_channels ?? [];
        if (!empty($allowedChannels) && !in_array($notificationConfiguration->channel, $allowedChannels)) {
            $allowedList = implode(', ', $allowedChannels);
            throw ValidationException::withMessages([
                'channel' => "Channel '{$notificationConfiguration->channel}' is not allowed for event '{$eventType}'. Allowed channels: {$allowedList}",
            ]);
        }
    }

    /**
     * Validate notification channel.
     */
    protected function validateChannel(NotificationConfiguration $notificationConfiguration): void
    {
        $validChannels = ['email', 'sms', 'whatsapp', 'push', 'in_app'];

        if (!in_array($notificationConfiguration->channel, $validChannels)) {
            $validList = implode(', ', $validChannels);
            throw ValidationException::withMessages([
                'channel' => "Invalid channel. Valid channels are: {$validList}",
            ]);
        }
    }

    /**
     * Sanitize template content to prevent XSS.
     */
    protected function sanitizeTemplateContent(NotificationConfiguration $notificationConfiguration): void
    {
        // Sanitize template_content (JSON or text)
        if ($notificationConfiguration->template_content) {
            if (is_array($notificationConfiguration->template_content)) {
                array_walk_recursive($notificationConfiguration->template_content, function (&$value) {
                    if (is_string($value)) {
                        $value = $this->sanitizeString($value);
                    }
                });
            } else {
                $notificationConfiguration->template_content = $this->sanitizeString(
                    $notificationConfiguration->template_content
                );
            }
        }

        // Sanitize metadata
        if ($notificationConfiguration->metadata && is_array($notificationConfiguration->metadata)) {
            array_walk_recursive($notificationConfiguration->metadata, function (&$value) {
                if (is_string($value)) {
                    $value = $this->sanitizeString($value);
                }
            });
        }
    }

    /**
     * Sanitize a string value.
     */
    protected function sanitizeString(string $value): string
    {
        // Allow template variables like {{customer_name}}
        // Remove script tags but preserve template syntax
        $value = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i', '', $value);
        $value = preg_replace('/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/i', '', $value);

        // Remove dangerous attributes
        $value = preg_replace('/\bon\w+\s*=\s*["\'][^"\']*["\']/i', '', $value);

        return trim($value);
    }

    /**
     * Handle the NotificationConfiguration "saving" event.
     */
    public function saving(NotificationConfiguration $notificationConfiguration): void
    {
        // Auto-enable new configurations unless explicitly disabled
        if (!$notificationConfiguration->exists && $notificationConfiguration->is_enabled === null) {
            $notificationConfiguration->is_enabled = true;
        }
    }
}
