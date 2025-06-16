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

class AppointmentRescheduled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Appointment $appointment;

    /**
     * Create a new event instance.
     */
    public function __construct(Appointment $appointment)
    {
        $this->appointment = $appointment->load(['customer', 'staff', 'service', 'branch']);
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('company.' . $this->appointment->company_id),
            new PrivateChannel('branch.' . $this->appointment->branch_id),
            new PrivateChannel('staff.' . $this->appointment->staff_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'appointment.rescheduled';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'appointment' => [
                'id' => $this->appointment->id,
                'starts_at' => $this->appointment->starts_at->toIso8601String(),
                'ends_at' => $this->appointment->ends_at->toIso8601String(),
                'customer_name' => $this->appointment->customer->name,
                'staff_name' => $this->appointment->staff->name,
                'service_name' => $this->appointment->service?->name,
                'branch_name' => $this->appointment->branch->name,
                'status' => $this->appointment->status,
            ],
        ];
    }
}