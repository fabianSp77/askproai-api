@php
    $appointment = $getRecord();
    $staff = $appointment->staff;
    $date = $appointment->starts_at->startOf('day');
    
    $dayAppointments = $staff ? $staff->appointments()
        ->whereDate('starts_at', $date)
        ->orderBy('starts_at')
        ->get() : collect();
    
    // Generate time slots from 8 AM to 6 PM
    $timeSlots = [];
    for ($hour = 8; $hour <= 18; $hour++) {
        $timeSlots[] = sprintf('%02d:00', $hour);
        if ($hour < 18) {
            $timeSlots[] = sprintf('%02d:30', $hour);
        }
    }
@endphp

<div class="staff-day-schedule">
    @if($staff)
        <div class="mb-4">
            <h4 class="font-medium text-sm text-gray-700 dark:text-gray-300">
                {{ $staff->name }}'s Schedule
            </h4>
            <p class="text-xs text-gray-500">
                {{ $date->format('l, F j, Y') }}
            </p>
        </div>

        <div class="schedule-grid">
            @foreach($timeSlots as $timeSlot)
                @php
                    $slotTime = $date->copy()->setTimeFromTimeString($timeSlot);
                    $hasAppointment = null;
                    
                    foreach ($dayAppointments as $apt) {
                        if ($apt->starts_at <= $slotTime && $apt->ends_at > $slotTime) {
                            $hasAppointment = $apt;
                            break;
                        }
                    }
                    
                    $isCurrentAppointment = $hasAppointment && $hasAppointment->id === $appointment->id;
                    $isPast = $slotTime->isPast();
                @endphp
                
                <div class="time-slot {{ $hasAppointment ? ($isCurrentAppointment ? 'current' : 'booked') : ($isPast ? 'past' : 'available') }}">
                    <div class="time-label">{{ $timeSlot }}</div>
                    @if($hasAppointment)
                        <div class="appointment-info">
                            @if($isCurrentAppointment)
                                <div class="text-xs font-medium">Current Appointment</div>
                            @else
                                <div class="text-xs">{{ $hasAppointment->customer->name }}</div>
                                <div class="text-xs opacity-75">{{ $hasAppointment->service->name ?? 'Service' }}</div>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- Summary -->
        <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div>
                    <span class="text-gray-500">Total Appointments:</span>
                    <span class="font-medium ml-1">{{ $dayAppointments->count() }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Hours Booked:</span>
                    <span class="font-medium ml-1">
                        {{ round($dayAppointments->sum(fn($apt) => $apt->starts_at->diffInMinutes($apt->ends_at)) / 60, 1) }}h
                    </span>
                </div>
            </div>
        </div>
    @else
        <div class="text-center py-8">
            <x-heroicon-o-user-circle class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-2" />
            <p class="text-sm text-gray-500">No staff member assigned</p>
        </div>
    @endif
</div>

<style>
    .schedule-grid {
        display: grid;
        gap: 0.5rem;
        max-height: 400px;
        overflow-y: auto;
        padding-right: 0.5rem;
    }
    
    .time-slot {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem;
        border-radius: 0.375rem;
        font-size: 0.75rem;
        transition: all 0.2s;
    }
    
    .time-label {
        font-weight: 500;
        width: 3rem;
        flex-shrink: 0;
    }
    
    .appointment-info {
        flex: 1;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
    }
    
    .time-slot.available {
        background: var(--filament-gray-50, #f9fafb);
        color: var(--filament-gray-600, #4b5563);
    }
    
    .dark .time-slot.available {
        background: var(--filament-gray-900, #111827);
        color: var(--filament-gray-400, #9ca3af);
    }
    
    .time-slot.available:hover {
        background: var(--filament-gray-100, #f3f4f6);
    }
    
    .dark .time-slot.available:hover {
        background: var(--filament-gray-800, #1f2937);
    }
    
    .time-slot.past {
        opacity: 0.5;
    }
    
    .time-slot.booked {
        background: var(--filament-danger-50, #fef2f2);
        color: var(--filament-danger-700, #b91c1c);
    }
    
    .dark .time-slot.booked {
        background: var(--filament-danger-900/20);
        color: var(--filament-danger-400, #f87171);
    }
    
    .time-slot.booked .appointment-info {
        background: var(--filament-danger-100, #fee2e2);
    }
    
    .dark .time-slot.booked .appointment-info {
        background: var(--filament-danger-900/30);
    }
    
    .time-slot.current {
        background: var(--filament-primary-100, #dbeafe);
        color: var(--filament-primary-700, #1d4ed8);
        font-weight: 500;
    }
    
    .dark .time-slot.current {
        background: var(--filament-primary-900/30);
        color: var(--filament-primary-400, #60a5fa);
    }
    
    .time-slot.current .appointment-info {
        background: var(--filament-primary-200, #bfdbfe);
    }
    
    .dark .time-slot.current .appointment-info {
        background: var(--filament-primary-900/50);
    }
    
    .schedule-grid::-webkit-scrollbar {
        width: 6px;
    }
    
    .schedule-grid::-webkit-scrollbar-track {
        background: transparent;
    }
    
    .schedule-grid::-webkit-scrollbar-thumb {
        background: rgba(0,0,0,0.2);
        border-radius: 3px;
    }
    
    .dark .schedule-grid::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,0.2);
    }
</style>