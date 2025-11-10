<?php

namespace NotificationChannels\Twilio;

class TwilioMmsMessage extends TwilioSmsMessage
{
    public ?string $mediaUrl = null;

    /**
     * Set the message media url.
     */
    public function mediaUrl(string $url): self
    {
        $this->mediaUrl = $url;

        return $this;
    }
}
