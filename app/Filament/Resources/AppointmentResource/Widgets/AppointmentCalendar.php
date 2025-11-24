<?php

namespace App\Filament\Resources\AppointmentResource\Widgets;

use App\Models\Appointment;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AppointmentCalendar extends Widget
{
    protected static string $view = 'filament.resources.appointment-resource.widgets.appointment-calendar';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    public ?string $viewMode = 'week';
    public ?string $selectedDate = null;

    protected function getViewData(): array
    {
        $this->selectedDate = $this->selectedDate ?? now()->format('Y-m-d');

        return [
            'appointments' => $this->getAppointments(),
            'viewMode' => $this->viewMode,
            'selectedDate' => $this->selectedDate,
            'staff' => $this->getStaff(),
            'timeSlots' => $this->generateTimeSlots(),
        ];
    }

    protected function getAppointments()
    {
        // Align cache to 5-minute granularity for consistency
        $cacheMinute = floor(now()->minute / 5) * 5;
        $cacheKey = 'calendar-appointments-' . $this->selectedDate . '-' . $this->viewMode . '-' . now()->format('H') . '-' . str_pad($cacheMinute, 2, '0', STR_PAD_LEFT);

        return Cache::remember($cacheKey, 300, function () {
            $query = Appointment::with(['customer', 'service', 'staff', 'branch']);

            switch ($this->viewMode) {
                case 'day':
                    $query->whereDate('starts_at', $this->selectedDate);
                    break;
                case 'week':
                    $startOfWeek = Carbon::parse($this->selectedDate)->startOfWeek();
                    $endOfWeek = Carbon::parse($this->selectedDate)->endOfWeek();
                    $query->whereBetween('starts_at', [$startOfWeek, $endOfWeek]);
                    break;
                case 'month':
                    $startOfMonth = Carbon::parse($this->selectedDate)->startOfMonth();
                    $endOfMonth = Carbon::parse($this->selectedDate)->endOfMonth();
                    $query->whereBetween('starts_at', [$startOfMonth, $endOfMonth]);
                    break;
            }

            return $query->orderBy('starts_at')->get()->map(function ($appointment) {
                // Base appointment data
                $data = [
                    'id' => $appointment->id,
                    'title' => $appointment->customer->name . ' - ' . $appointment->service->name,
                    'start' => $appointment->starts_at->toIso8601String(),
                    'end' => $appointment->ends_at->toIso8601String(),
                    'color' => $this->getStatusColor($appointment->status),
                    'staff_id' => $appointment->staff_id,
                    'customer_name' => $appointment->customer->name,
                    'service_name' => $appointment->service->name,
                    'staff_name' => $appointment->staff->name ?? 'Nicht zugewiesen',
                    'status' => $appointment->status,
                    'price' => $appointment->price,
                    'is_composite' => $appointment->is_composite,
                ];

                // Add composite appointment details
                if ($appointment->is_composite) {
                    $data['composite_group_uid'] = $appointment->composite_group_uid;
                    $data['segments'] = $appointment->segments;
                    $data['color'] = '#9333EA'; // Purple for composite appointments
                }

                return $data;
            });
        });
    }

    protected function getStaff()
    {
        return Cache::remember('calendar-staff', 300, function () {
            return \App\Models\Staff::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'color_code']);
        });
    }

    protected function generateTimeSlots(): array
    {
        $slots = [];
        $start = Carbon::createFromTime(7, 0);
        $end = Carbon::createFromTime(20, 0);

        while ($start <= $end) {
            $slots[] = $start->format('H:i');
            $start->addMinutes(30);
        }

        return $slots;
    }

    protected function getStatusColor(string $status): string
    {
        return match ($status) {
            'pending' => '#FFA500',
            'confirmed' => '#10B981',
            'in_progress' => '#3B82F6',
            'completed' => '#6B7280',
            'cancelled' => '#EF4444',
            'no_show' => '#991B1B',
            default => '#9CA3AF',
        };
    }

    public function switchView(string $view): void
    {
        $this->viewMode = $view;
    }

    public function navigateDate(string $direction): void
    {
        $date = Carbon::parse($this->selectedDate);

        switch ($this->viewMode) {
            case 'day':
                $this->selectedDate = $direction === 'next'
                    ? $date->addDay()->format('Y-m-d')
                    : $date->subDay()->format('Y-m-d');
                break;
            case 'week':
                $this->selectedDate = $direction === 'next'
                    ? $date->addWeek()->format('Y-m-d')
                    : $date->subWeek()->format('Y-m-d');
                break;
            case 'month':
                $this->selectedDate = $direction === 'next'
                    ? $date->addMonth()->format('Y-m-d')
                    : $date->subMonth()->format('Y-m-d');
                break;
        }
    }

    public function goToToday(): void
    {
        $this->selectedDate = now()->format('Y-m-d');
    }
}