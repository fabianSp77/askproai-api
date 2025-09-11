<x-filament-panels::page>
    @php
        $sections = [
            [
                'title' => 'Appointment Details',
                'description' => 'Basic information about the appointment',
                'fields' => [
                    [
                        'type' => 'select',
                        'name' => 'customer_id',
                        'label' => 'Customer',
                        'placeholder' => 'Select a customer',
                        'required' => true,
                        'options' => \App\Models\Customer::pluck('name', 'id')->map(fn($name, $id) => ['value' => $id, 'label' => $name])->values()->toArray(),
                    ],
                    [
                        'type' => 'select',
                        'name' => 'service_id',
                        'label' => 'Service',
                        'placeholder' => 'Select a service',
                        'required' => true,
                        'options' => \App\Models\Service::all()->map(fn($s) => [
                            'value' => $s->id,
                            'label' => $s->name . ' (' . $s->duration_minutes . ' min - €' . number_format($s->price_cents / 100, 2) . ')'
                        ])->toArray(),
                    ],
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
                        'name' => 'branch_id',
                        'label' => 'Branch',
                        'placeholder' => 'Select a branch',
                        'required' => true,
                        'options' => \App\Models\Branch::where('is_active', true)->pluck('name', 'id')->map(fn($name, $id) => ['value' => $id, 'label' => $name])->values()->toArray(),
                    ],
                ],
            ],
            [
                'title' => 'Schedule',
                'description' => 'Set the appointment date and time',
                'fields' => [
                    [
                        'type' => 'datetime-local',
                        'name' => 'starts_at',
                        'label' => 'Start Date & Time',
                        'required' => true,
                        'value' => now()->addDay()->format('Y-m-d\TH:00'),
                    ],
                    [
                        'type' => 'datetime-local',
                        'name' => 'ends_at',
                        'label' => 'End Date & Time',
                        'required' => true,
                        'value' => now()->addDay()->addHour()->format('Y-m-d\TH:00'),
                    ],
                    [
                        'type' => 'select',
                        'name' => 'status',
                        'label' => 'Status',
                        'required' => true,
                        'value' => 'scheduled',
                        'options' => [
                            ['value' => 'scheduled', 'label' => 'Scheduled'],
                            ['value' => 'confirmed', 'label' => 'Confirmed'],
                            ['value' => 'completed', 'label' => 'Completed'],
                            ['value' => 'cancelled', 'label' => 'Cancelled'],
                            ['value' => 'no_show', 'label' => 'No Show'],
                        ],
                    ],
                    [
                        'type' => 'toggle',
                        'name' => 'send_reminder',
                        'label' => 'Send reminder to customer',
                        'value' => true,
                    ],
                ],
            ],
            [
                'title' => 'Additional Information',
                'description' => 'Optional notes and special requirements',
                'fields' => [
                    [
                        'type' => 'textarea',
                        'name' => 'notes',
                        'label' => 'Internal Notes',
                        'placeholder' => 'Add any internal notes about this appointment...',
                        'rows' => 4,
                    ],
                    [
                        'type' => 'textarea',
                        'name' => 'customer_notes',
                        'label' => 'Customer Notes',
                        'placeholder' => 'Special requests or requirements from the customer...',
                        'rows' => 3,
                    ],
                    [
                        'type' => 'select',
                        'name' => 'payment_status',
                        'label' => 'Payment Status',
                        'value' => 'pending',
                        'options' => [
                            ['value' => 'pending', 'label' => 'Pending'],
                            ['value' => 'paid', 'label' => 'Paid'],
                            ['value' => 'partially_paid', 'label' => 'Partially Paid'],
                            ['value' => 'refunded', 'label' => 'Refunded'],
                        ],
                    ],
                ],
            ],
        ];
    @endphp
    
    <x-admin.flowbite-form
        title="Create Appointment"
        description="Schedule a new appointment for a customer"
        action="{{ url('/admin/appointments') }}"
        method="POST"
        :sections="$sections"
        submitLabel="Create Appointment"
        cancelUrl="/admin/appointments"
        layout="single"
    />
    
    <script>
        // Auto-calculate end time based on service duration
        document.getElementById('service_id')?.addEventListener('change', function(e) {
            const startInput = document.getElementById('starts_at');
            const endInput = document.getElementById('ends_at');
            
            if (startInput && endInput && e.target.value) {
                // Get service duration from the option text
                const selectedOption = e.target.options[e.target.selectedIndex];
                const durationMatch = selectedOption.text.match(/(\d+)\s*min/);
                
                if (durationMatch) {
                    const duration = parseInt(durationMatch[1]);
                    const startDate = new Date(startInput.value);
                    const endDate = new Date(startDate.getTime() + duration * 60000);
                    
                    // Format for datetime-local input
                    const year = endDate.getFullYear();
                    const month = String(endDate.getMonth() + 1).padStart(2, '0');
                    const day = String(endDate.getDate()).padStart(2, '0');
                    const hours = String(endDate.getHours()).padStart(2, '0');
                    const minutes = String(endDate.getMinutes()).padStart(2, '0');
                    
                    endInput.value = `${year}-${month}-${day}T${hours}:${minutes}`;
                }
            }
        });
    </script>
</x-filament-panels::page>