<?php

namespace App\Livewire;

use App\Models\Appointment;
use Livewire\Component;
use App\Helpers\GermanFormatter;

class AppointmentViewer extends Component
{
    public $appointmentId;
    public Appointment $appointment;
    
    // UI States
    public $activeTab = 'overview';
    
    // Formatted data
    public $formattedData = [];
    
    public function mount($appointmentId)
    {
        $this->appointmentId = $appointmentId;
        $this->loadAppointment();
    }
    
    public function loadAppointment()
    {
        $this->appointment = Appointment::with([
            'customer',
            'staff',
            'service',
            'branch',
            'call',
            'calcomEventType'
        ])->findOrFail($this->appointmentId);
        
        $this->prepareFormattedData();
    }
    
    protected function prepareFormattedData()
    {
        $formatter = new GermanFormatter();
        
        $this->formattedData = [
            // Basic Information
            'booking_id' => $this->appointment->calcom_v2_booking_id ?? '-',
            'status' => $this->appointment->status ?? 'pending',
            'source' => $this->appointment->source ?? 'manual',
            
            // Schedule
            'starts_at' => $this->appointment->starts_at ? 
                $formatter->formatDateTime($this->appointment->starts_at) : '-',
            'ends_at' => $this->appointment->ends_at ? 
                $formatter->formatDateTime($this->appointment->ends_at) : '-',
            'duration' => $this->calculateDuration(),
            'time_until' => $this->calculateTimeUntil(),
            
            // Customer
            'customer_name' => $this->appointment->customer->name ?? '-',
            'customer_email' => $this->appointment->customer->email ?? '-',
            'customer_phone' => $this->appointment->customer->phone ?? '-',
            
            // Staff & Service
            'staff_name' => $this->appointment->staff->name ?? 'Not assigned',
            'service_name' => $this->appointment->service->name ?? 'General appointment',
            'branch_name' => $this->appointment->branch->name ?? '-',
            
            // Cal.com Integration
            'calcom_uid' => $this->appointment->calcom_booking_uid ?? '-',
            'event_type' => $this->appointment->calcomEventType->name ?? 'Manual booking',
            'meeting_url' => $this->appointment->meeting_url ?? null,
            'location_type' => $this->getLocationDisplay(),
            'location_value' => $this->appointment->location_value ?? '-',
            'reschedule_uid' => $this->appointment->reschedule_uid ?? '-',
            
            // Additional Data
            'attendees' => $this->appointment->attendees ?? [],
            'responses' => $this->appointment->responses ?? [],
            'hosts' => $this->getHosts(),
            'is_recurring' => $this->appointment->is_recurring ?? false,
            'recurring_event_id' => $this->appointment->recurring_event_id ?? null,
            'cancellation_reason' => $this->appointment->cancellation_reason ?? null,
            'rejected_reason' => $this->appointment->rejected_reason ?? null,
            
            // Metadata
            'notes' => $this->appointment->notes ?? '',
            'created_at' => $this->appointment->created_at ? 
                $formatter->formatDateTime($this->appointment->created_at) : '-',
            'updated_at' => $this->appointment->updated_at ? 
                $formatter->formatDateTime($this->appointment->updated_at) : '-',
        ];
    }
    
    protected function calculateDuration()
    {
        if ($this->appointment->starts_at && $this->appointment->ends_at) {
            $minutes = $this->appointment->starts_at->diffInMinutes($this->appointment->ends_at);
            return $minutes . ' Minuten';
        }
        return '-';
    }
    
    protected function calculateTimeUntil()
    {
        if ($this->appointment->starts_at && $this->appointment->starts_at->isFuture()) {
            return $this->appointment->starts_at->diffForHumans();
        }
        return 'Vergangener Termin';
    }
    
    protected function getLocationDisplay()
    {
        return match($this->appointment->location_type) {
            'video' => '📹 Video Call',
            'phone' => '📞 Phone Call',
            'inPerson' => '🏢 In Person',
            'email' => '✉️ Email',
            default => '📍 Location TBD'
        };
    }
    
    protected function getHosts()
    {
        if ($this->appointment->booking_metadata && 
            isset($this->appointment->booking_metadata['hosts'])) {
            return $this->appointment->booking_metadata['hosts'];
        }
        return [];
    }
    
    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }
    
    public function render()
    {
        return view('livewire.appointment-viewer');
    }
}