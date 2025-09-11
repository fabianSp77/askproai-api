<x-filament-panels::page>
    @php
        $sections = [
            [
                'title' => 'Working Hour Details',
                'description' => 'Basic working hour information',
                'fields' => [
                    [
                        'type' => 'select',
                        'name' => 'staff_id',
                        'label' => 'Staff Member',
                        'placeholder' => 'Select staff member',
                        'required' => true,
                        'options' => \App\Models\Staff::where('is_active', true)->pluck('name', 'id')->map(fn($name, $id) => ['value' => $id, 'label' => $name])->values()->toArray(),
                    ],
                    [
                        'type' => 'select',
                        'name' => 'day_of_week',
                        'label' => 'Day of Week',
                        'required' => true,
                        'options' => [
                            ['value' => '0', 'label' => 'Sunday'],
                            ['value' => '1', 'label' => 'Monday'],
                            ['value' => '2', 'label' => 'Tuesday'],
                            ['value' => '3', 'label' => 'Wednesday'],
                            ['value' => '4', 'label' => 'Thursday'],
                            ['value' => '5', 'label' => 'Friday'],
                            ['value' => '6', 'label' => 'Saturday'],
                        ],
                    ],
                    [
                        'type' => 'toggle',
                        'name' => 'is_active',
                        'label' => 'Active Schedule',
                        'value' => true,
                    ],
                ],
            ],
            [
                'title' => 'Time Schedule',
                'description' => 'Working hours and break times',
                'fields' => [
                    [
                        'type' => 'time',
                        'name' => 'start_time',
                        'label' => 'Start Time',
                        'required' => true,
                        'value' => '09:00',
                    ],
                    [
                        'type' => 'time',
                        'name' => 'end_time',
                        'label' => 'End Time',
                        'required' => true,
                        'value' => '17:00',
                    ],
                    [
                        'type' => 'number',
                        'name' => 'break_duration_minutes',
                        'label' => 'Break Duration (minutes)',
                        'placeholder' => '60',
                        'min' => '0',
                        'max' => '480',
                        'value' => '60',
                        'help' => 'Total break time during the work day',
                    ],
                ],
            ],
            [
                'title' => 'Additional Settings',
                'description' => 'Break times and availability settings',
                'fields' => [
                    [
                        'type' => 'time',
                        'name' => 'break_start_time',
                        'label' => 'Break Start Time',
                        'value' => '12:00',
                        'help' => 'Optional: When break starts (leave empty for flexible break)',
                    ],
                    [
                        'type' => 'time',
                        'name' => 'break_end_time',
                        'label' => 'Break End Time',
                        'value' => '13:00',
                        'help' => 'Optional: When break ends (leave empty for flexible break)',
                    ],
                    [
                        'type' => 'toggle',
                        'name' => 'available_for_appointments',
                        'label' => 'Available for Appointments',
                        'value' => true,
                        'help' => 'Can appointments be booked during these hours',
                    ],
                ],
            ],
        ];
    @endphp
    
    <x-admin.flowbite-form
        title="Create Working Hours"
        description="Add working hours for a staff member"
        action="{{ url('/admin/working-hours') }}"
        method="POST"
        :sections="$sections"
        submitLabel="Create Working Hours"
        cancelUrl="/admin/working-hours"
        layout="single"
    />
    
    <script>
        // Auto-calculate break end time based on start time and duration
        document.addEventListener('DOMContentLoaded', function() {
            const breakStart = document.getElementById('break_start_time');
            const breakDuration = document.getElementById('break_duration_minutes');
            const breakEnd = document.getElementById('break_end_time');
            
            function updateBreakEndTime() {
                if (breakStart?.value && breakDuration?.value) {
                    const startTime = breakStart.value.split(':');
                    const startMinutes = parseInt(startTime[0]) * 60 + parseInt(startTime[1]);
                    const endMinutes = startMinutes + parseInt(breakDuration.value);
                    
                    const endHour = Math.floor(endMinutes / 60);
                    const endMin = endMinutes % 60;
                    
                    if (endHour < 24) {
                        breakEnd.value = String(endHour).padStart(2, '0') + ':' + String(endMin).padStart(2, '0');
                    }
                }
            }
            
            breakStart?.addEventListener('change', updateBreakEndTime);
            breakDuration?.addEventListener('change', updateBreakEndTime);
        });
    </script>
</x-filament-panels::page>