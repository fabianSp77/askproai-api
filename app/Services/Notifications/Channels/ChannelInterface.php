<?php

namespace App\Services\Notifications\Channels;

interface ChannelInterface
{
    /**
     * Send notification through this channel
     *
     * @param array $recipient Recipient information (email, phone, etc.)
     * @param array $content Notification content
     * @param array $options Additional options
     * @return array Result with success status and metadata
     */
    public function send(array $recipient, array $content, array $options = []): array;
}