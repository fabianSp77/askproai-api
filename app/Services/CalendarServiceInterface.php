<?php

namespace App\Services;

interface CalendarServiceInterface
{
    public function checkAvailability(array $data);

    public function bookAppointment(array $data);

    public function getAvailableSlots(array $data);
}
