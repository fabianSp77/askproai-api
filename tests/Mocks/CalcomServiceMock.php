<?php

namespace Tests\Mocks;

class CalcomServiceMock
{
    public function getAvailability($date = null, $eventTypeId = null)
    {
        return [
            'slots' => [
                ['time' => '09:00', 'available' => true],
                ['time' => '10:00', 'available' => true],
                ['time' => '11:00', 'available' => false],
                ['time' => '14:00', 'available' => true],
                ['time' => '15:00', 'available' => true],
            ]
        ];
    }
    
    public function createBooking($data)
    {
        return [
            'id' => 'booking_' . uniqid(),
            'uid' => 'uid_' . uniqid(),
            'title' => $data['title'] ?? 'Test Booking',
            'startTime' => $data['start'],
            'endTime' => $data['end'],
            'status' => 'ACCEPTED'
        ];
    }
    
    public function getEventTypes()
    {
        return [
            ['id' => 1, 'title' => 'Consultation', 'length' => 30],
            ['id' => 2, 'title' => 'Follow-up', 'length' => 15],
        ];
    }
}