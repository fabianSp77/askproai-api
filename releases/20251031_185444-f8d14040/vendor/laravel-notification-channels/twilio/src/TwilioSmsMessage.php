<?php

namespace NotificationChannels\Twilio;

class TwilioSmsMessage extends TwilioMessage
{
    public ?string $alphaNumSender = null;

    public ?string $messagingServiceSid = null;

    public ?string $applicationSid = null;

    public ?bool $forceDelivery = null;

    public ?float $maxPrice = null;

    public ?bool $provideFeedback = null;

    public ?int $validityPeriod = null;

    /**
     * Get the from address of this message.
     */
    public function getFrom(): ?string
    {
        if ($this->from) {
            return $this->from;
        }

        if ($this->alphaNumSender !== null && $this->alphaNumSender !== '') {
            return $this->alphaNumSender;
        }

        return null;
    }

    /**
     * Set the messaging service SID.
     */
    public function messagingServiceSid(string $messagingServiceSid): self
    {
        $this->messagingServiceSid = $messagingServiceSid;

        return $this;
    }

    /**
     * Get the messaging service SID of this message.
     */
    public function getMessagingServiceSid(): ?string
    {
        return $this->messagingServiceSid;
    }

    /**
     * Set the alphanumeric sender.
     */
    public function sender(string $sender): self
    {
        $this->alphaNumSender = $sender;

        return $this;
    }

    /**
     * Set application SID for the message status callback.
     */
    public function applicationSid(string $applicationSid): self
    {
        $this->applicationSid = $applicationSid;

        return $this;
    }

    /**
     * Set force delivery (Deliver message without validation).
     */
    public function forceDelivery(bool $forceDelivery): self
    {
        $this->forceDelivery = $forceDelivery;

        return $this;
    }

    /**
     * Set the max price (in USD dollars).
     */
    public function maxPrice(float $maxPrice): self
    {
        $this->maxPrice = $maxPrice;

        return $this;
    }

    /**
     * Set the provide feedback option.
     */
    public function provideFeedback(bool $provideFeedback): self
    {
        $this->provideFeedback = $provideFeedback;

        return $this;
    }

    /**
     * Set the validity period (in seconds).
     */
    public function validityPeriod(int $validityPeriodSeconds): self
    {
        $this->validityPeriod = $validityPeriodSeconds;

        return $this;
    }
}
