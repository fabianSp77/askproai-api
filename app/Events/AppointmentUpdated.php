<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Appointment;

class AppointmentUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $appointmentId;
    public $data;
    private $appointment;

    /**
     * Create a new event instance.
     */
    public function __construct($appointmentId, array $data = [])
    {
        $this->appointmentId = $appointmentId;
        $this->data = $data;
        $this->appointment = Appointment::withoutGlobalScopes()
            ->with(['staff', 'customer', 'service'])
            ->find($appointmentId);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];
        
        if ($this->appointment) {
            // Broadcast to company channel
            $channels[] = new PrivateChannel('company.' . $this->appointment->company_id);
            
            // Broadcast to branch channel if available
            if ($this->appointment->branch_id) {
                $channels[] = new PrivateChannel('branch.' . $this->appointment->branch_id);
            }
            
            // Broadcast to staff member's channel
            if ($this->appointment->staff_id) {
                $channels[] = new PrivateChannel('staff.' . $this->appointment->staff_id);
            }
            
            // Broadcast to appointments channel
            $channels[] = new PrivateChannel('appointments');
        }
        
        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'appointment.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $appointmentData = $this->appointment ? [
            'id' => $this->appointment->id,
            'status' => $this->appointment->status,
            'start_time' => $this->appointment->start_time,
            'end_time' => $this->appointment->end_time,
            'customer' => $this->appointment->customer ? [
                'id' => $this->appointment->customer->id,
                'name' => $this->appointment->customer->name,
                'phone' => $this->appointment->customer->phone
            ] : null,
            'staff' => $this->appointment->staff ? [
                'id' => $this->appointment->staff->id,
                'name' => $this->appointment->staff->name
            ] : null,
            'service' => $this->appointment->service ? [
                'id' => $this->appointment->service->id,
                'name' => $this->appointment->service->name,
                'duration' => $this->appointment->service->duration
            ] : null
        ] : [];
        
        return array_merge([
            'appointment_id' => $this->appointmentId,
            'company_id' => $this->appointment->company_id ?? null,
            'branch_id' => $this->appointment->branch_id ?? null,
            'updated_at' => now()->toIso8601String(),
        ], $appointmentData, $this->data);
    }
}