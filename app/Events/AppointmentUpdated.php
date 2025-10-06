<?php

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $appointment;
    public $action;

    public function __construct(Appointment $appointment, string $action = 'updated')
    {
        $this->appointment = $appointment->load(['customer', 'staff', 'service', 'branch']);
        $this->action = $action;
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
        return 'appointment.' . $this->action;
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->appointment->id,
            'customer_id' => $this->appointment->customer_id,
            'customer_name' => $this->appointment->customer->name,
            'service_id' => $this->appointment->service_id,
            'service_name' => $this->appointment->service->name,
            'staff_id' => $this->appointment->staff_id,
            'staff_name' => $this->appointment->staff->name,
            'branch_id' => $this->appointment->branch_id,
            'branch_name' => $this->appointment->branch->name,
            'start_at' => $this->appointment->start_at->toIso8601String(),
            'end_at' => $this->appointment->end_at->toIso8601String(),
            'status' => $this->appointment->status,
            'total_price' => $this->appointment->total_price,
            'action' => $this->action,
        ];
    }
}