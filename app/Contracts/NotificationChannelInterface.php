<?php

namespace App\Contracts;

use App\Models\NotificationQueue;

/**
 * Interface for notification delivery channels
 *
 * Channels handle the actual delivery of notifications through
 * various mediums (email, SMS, WhatsApp, push, etc.)
 */
interface NotificationChannelInterface
{
    /**
     * Send a notification through this channel
     *
     * @param NotificationQueue $notification The queued notification to send
     * @return array Result with success status, message_id, and metadata
     *               ['success' => bool, 'message_id' => string|null, 'error' => string|null, ...]
     */
    public function send(NotificationQueue $notification): array;

    /**
     * Check if this channel can deliver to the given recipient
     *
     * Validates that the recipient has the necessary contact information
     * and that the channel is properly configured
     *
     * @param array $recipient Recipient data (email, phone, etc.)
     * @return bool True if delivery is possible
     */
    public function canDeliver(array $recipient): bool;

    /**
     * Get the channel identifier
     *
     * @return string Channel name (email, sms, whatsapp, etc.)
     */
    public function getChannelName(): string;

    /**
     * Validate channel configuration
     *
     * @return bool True if channel is properly configured
     */
    public function validateConfig(): bool;
}
