<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Appointment;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class AppointmentTimelineWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.appointment-timeline-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 3;
    
    // Update every minute for timeline accuracy
    protected static ?string $pollingInterval = '60s';
    
    public Collection $appointments;
    public array $timeSlots = [];
    public int $currentHour;
    public int $currentMinute;
    public array $stats = [];
    
    public function mount(): void
    {
        $this->loadAppointments();
    }
    
    public function loadAppointments(): void
    {
        $now = Carbon::now();
        $this->currentHour = $now->hour;
        $this->currentMinute = $now->minute;
        
        // Get today's appointments
        $this->appointments = Appointment::whereDate('starts_at', Carbon::today())
            ->with(['customer', 'staff', 'service', 'branch'])
            ->orderBy('starts_at')
            ->get()
            ->map(function ($appointment) {
                $startTime = Carbon::parse($appointment->starts_at);
                $endTime = Carbon::parse($appointment->ends_at ?? $appointment->starts_at)->addMinutes($appointment->duration ?? 30);
                
                return [
                    'id' => $appointment->id,
                    'customer_name' => $appointment->customer?->name ?? 'Unbekannt',
                    'service_name' => $appointment->service?->name ?? 'Allgemein',
                    'staff_name' => $appointment->staff?->name ?? 'Nicht zugewiesen',
                    'branch_name' => $appointment->branch?->name ?? 'Hauptfiliale',
                    'start_time' => $startTime->format('H:i'),
                    'end_time' => $endTime->format('H:i'),
                    'start_hour' => $startTime->hour,
                    'start_minute' => $startTime->minute,
                    'duration' => $startTime->diffInMinutes($endTime),
                    'status' => $this->getAppointmentStatus($appointment, $startTime, $endTime),
                    'status_color' => $this->getStatusColor($appointment, $startTime, $endTime),
                    'is_current' => $now->between($startTime, $endTime),
                ];
            });
        
        // Generate time slots for the timeline (8 AM to 8 PM)
        $this->timeSlots = [];
        for ($hour = 8; $hour <= 20; $hour++) {
            $this->timeSlots[] = [
                'hour' => $hour,
                'label' => sprintf('%02d:00', $hour),
                'is_current' => $hour === $this->currentHour,
                'is_past' => $hour < $this->currentHour,
            ];
        }
        
        // Calculate statistics
        $this->calculateStats();
    }
    
    private function getAppointmentStatus($appointment, $startTime, $endTime): string
    {
        $now = Carbon::now();
        
        if ($appointment->status === 'cancelled') {
            return 'Abgesagt';
        } elseif ($appointment->status === 'completed') {
            return 'Abgeschlossen';
        } elseif ($appointment->status === 'no_show') {
            return 'Nicht erschienen';
        } elseif ($now->between($startTime, $endTime)) {
            return 'Läuft';
        } elseif ($startTime->isFuture()) {
            return 'Geplant';
        } else {
            return 'Beendet';
        }
    }
    
    private function getStatusColor($appointment, $startTime, $endTime): string
    {
        $now = Carbon::now();
        $status = $appointment->status ?? '';
        
        if ($status === 'cancelled') {
            return 'danger';
        } elseif ($status === 'completed') {
            return 'success';
        } elseif ($status === 'no_show') {
            return 'warning';
        } elseif ($now->between($startTime, $endTime)) {
            return 'primary';
        } elseif ($startTime->isFuture()) {
            return 'info';
        } else {
            return 'gray';
        }
    }
    
    private function calculateStats(): void
    {
        $total = $this->appointments->count();
        $completed = $this->appointments->filter(fn($a) => in_array($a['status'], ['Abgeschlossen', 'Beendet']))->count();
        $upcoming = $this->appointments->filter(fn($a) => $a['status'] === 'Geplant')->count();
        $inProgress = $this->appointments->filter(fn($a) => $a['is_current'])->count();
        $cancelled = $this->appointments->filter(fn($a) => $a['status'] === 'Abgesagt')->count();
        
        $this->stats = [
            'total' => $total,
            'completed' => $completed,
            'upcoming' => $upcoming,
            'in_progress' => $inProgress,
            'cancelled' => $cancelled,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100) : 0,
        ];
    }
    
    public function poll(): void
    {
        $this->loadAppointments();
    }
    
    public function getAppointmentsForHour(int $hour): Collection
    {
        return $this->appointments->filter(function ($appointment) use ($hour) {
            return $appointment['start_hour'] === $hour;
        });
    }
}