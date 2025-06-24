@php
    $appointment = $getRecord();
    $events = [];
    
    // Build timeline events
    if ($appointment->created_at) {
        $events[] = [
            'time' => $appointment->created_at,
            'label' => 'Appointment Created',
            'icon' => 'plus-circle',
            'color' => 'primary',
        ];
    }
    
    if ($appointment->confirmed_at) {
        $events[] = [
            'time' => $appointment->confirmed_at,
            'label' => 'Confirmed',
            'icon' => 'check',
            'color' => 'success',
        ];
    }
    
    if ($appointment->rescheduled_at) {
        $events[] = [
            'time' => $appointment->rescheduled_at,
            'label' => 'Rescheduled',
            'icon' => 'calendar',
            'color' => 'warning',
        ];
    }
    
    if ($appointment->reminder_sent_at) {
        $events[] = [
            'time' => $appointment->reminder_sent_at,
            'label' => 'Reminder Sent',
            'icon' => 'bell',
            'color' => 'info',
        ];
    }
    
    if ($appointment->checked_in_at) {
        $events[] = [
            'time' => $appointment->checked_in_at,
            'label' => 'Checked In',
            'icon' => 'user-check',
            'color' => 'success',
        ];
    }
    
    if ($appointment->completed_at) {
        $events[] = [
            'time' => $appointment->completed_at,
            'label' => 'Completed',
            'icon' => 'check-circle',
            'color' => 'success',
        ];
    }
    
    if ($appointment->cancelled_at) {
        $events[] = [
            'time' => $appointment->cancelled_at,
            'label' => 'Cancelled',
            'icon' => 'x-circle',
            'color' => 'danger',
        ];
    }
    
    // Sort events by time
    usort($events, fn($a, $b) => $a['time']->timestamp - $b['time']->timestamp);
@endphp

<div class="appointment-timeline-container">
    <div class="relative">
        @foreach($events as $index => $event)
            <div class="flex gap-4 {{ $index < count($events) - 1 ? 'pb-8' : '' }}">
                <!-- Timeline Line -->
                @if($index < count($events) - 1)
                    <div class="absolute left-5 top-10 bottom-0 w-0.5 bg-gray-300 dark:bg-gray-600"></div>
                @endif
                
                <!-- Icon -->
                <div class="relative z-10 flex items-center justify-center w-10 h-10 rounded-full 
                    {{ match($event['color']) {
                        'primary' => 'bg-primary-100 dark:bg-primary-900/30',
                        'success' => 'bg-success-100 dark:bg-success-900/30',
                        'warning' => 'bg-warning-100 dark:bg-warning-900/30',
                        'danger' => 'bg-danger-100 dark:bg-danger-900/30',
                        'info' => 'bg-info-100 dark:bg-info-900/30',
                        default => 'bg-gray-100 dark:bg-gray-800'
                    } }}">
                    <x-dynamic-component 
                        :component="'heroicon-o-' . $event['icon']" 
                        class="w-5 h-5 {{ match($event['color']) {
                            'primary' => 'text-primary-600 dark:text-primary-400',
                            'success' => 'text-success-600 dark:text-success-400',
                            'warning' => 'text-warning-600 dark:text-warning-400',
                            'danger' => 'text-danger-600 dark:text-danger-400',
                            'info' => 'text-info-600 dark:text-info-400',
                            default => 'text-gray-600 dark:text-gray-400'
                        } }}"
                    />
                </div>
                
                <!-- Content -->
                <div class="flex-1 pt-1">
                    <p class="font-medium text-gray-900 dark:text-white">
                        {{ $event['label'] }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $event['time']->format('M j, Y - g:i A') }}
                        <span class="text-xs">({{ $event['time']->diffForHumans() }})</span>
                    </p>
                </div>
            </div>
        @endforeach
    </div>
    
    @if(empty($events))
        <p class="text-center text-gray-500 dark:text-gray-400 py-4">
            No timeline events recorded yet.
        </p>
    @endif
</div>

<style>
    .appointment-timeline-container {
        max-height: 400px;
        overflow-y: auto;
        padding-right: 1rem;
    }
    
    .appointment-timeline-container::-webkit-scrollbar {
        width: 6px;
    }
    
    .appointment-timeline-container::-webkit-scrollbar-track {
        background: transparent;
    }
    
    .appointment-timeline-container::-webkit-scrollbar-thumb {
        background: rgba(0,0,0,0.2);
        border-radius: 3px;
    }
    
    .dark .appointment-timeline-container::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,0.2);
    }
</style>