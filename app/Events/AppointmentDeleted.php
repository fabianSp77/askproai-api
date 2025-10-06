<?php

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $appointment;

    public function __construct(Appointment $appointment)
    {
        $this->appointment = $appointment;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('appointments'),
            new PrivateChannel('branch.' . $this->appointment->branch_id),
            new PrivateChannel('staff.' . $this->appointment->staff_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'appointment.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->appointment->id,
            'branch_id' => $this->appointment->branch_id,
            'staff_id' => $this->appointment->staff_id,
            'customer_id' => $this->appointment->customer_id,
            'service_id' => $this->appointment->service_id,
        ];
    }
}