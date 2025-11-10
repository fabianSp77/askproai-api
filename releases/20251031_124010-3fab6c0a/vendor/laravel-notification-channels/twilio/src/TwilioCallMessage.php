<?php

namespace NotificationChannels\Twilio;

class TwilioCallMessage extends TwilioMessage
{
    public const STATUS_CANCELED = 'canceled';

    public const STATUS_COMPLETED = 'completed';

    public ?string $method = null;

    public ?string $status = null;

    public ?string $fallbackUrl = null;

    public ?string $fallbackMethod = null;

    /**
     * Set the message url.
     */
    public function url(string $url): self
    {
        $this->content = $url;

        return $this;
    }

    /**
     * Set the message url request method.
     */
    public function method(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Set the status for the current calls.
     */
    public function status(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Set the fallback url.
     */
    public function fallbackUrl(string $fallbackUrl): self
    {
        $this->fallbackUrl = $fallbackUrl;

        return $this;
    }

    /**
     * Set the fallback url request method.
     */
    public function fallbackMethod(string $fallbackMethod): self
    {
        $this->fallbackMethod = $fallbackMethod;

        return $this;
    }
}
