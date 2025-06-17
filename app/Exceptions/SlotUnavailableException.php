<?php

namespace App\Exceptions;

class SlotUnavailableException extends BookingException
{
    public function __construct(string $message = 'The selected time slot is no longer available')
    {
        parent::__construct($message, 'slot_unavailable');
    }
}