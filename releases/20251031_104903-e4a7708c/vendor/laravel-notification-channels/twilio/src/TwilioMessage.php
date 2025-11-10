<?php

namespace NotificationChannels\Twilio;

abstract class TwilioMessage
{
    /**
     * The phone number the message should be sent from.
     */
    public ?string $from = null;

    public ?string $statusCallback = null;

    public ?string $statusCallbackMethod = null;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $content = ''
    ) {}

    /**
     * Create a message object.
     */
    public static function create(string $content = ''): self
    {
        return new static($content);
    }

    /**
     * Set the message content.
     */
    public function content(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Set the phone number the message should be sent from.
     */
    public function from(string $from): self
    {
        $this->from = $from;

        return $this;
    }

    /**
     * Get the from address.
     */
    public function getFrom(): ?string
    {
        return $this->from;
    }

    /**
     * Set the status callback.
     */
    public function statusCallback(string $statusCallback): self
    {
        $this->statusCallback = $statusCallback;

        return $this;
    }

    /**
     * Set the status callback request method.
     */
    public function statusCallbackMethod(string $statusCallbackMethod): self
    {
        $this->statusCallbackMethod = $statusCallbackMethod;

        return $this;
    }
}
