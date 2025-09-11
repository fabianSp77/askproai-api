<x-filament-panels::page>
    @php
        $sections = [
            [
                'title' => 'Service Details',
                'description' => 'Basic service information',
                'fields' => [
                    [
                        'type' => 'text',
                        'name' => 'name',
                        'label' => 'Service Name',
                        'placeholder' => 'Enter service name',
                        'required' => true,
                    ],
                    [
                        'type' => 'select',
                        'name' => 'category',
                        'label' => 'Category',
                        'placeholder' => 'Select category',
                        'options' => [
                            ['value' => 'consultation', 'label' => 'Consultation'],
                            ['value' => 'treatment', 'label' => 'Treatment'],
                            ['value' => 'procedure', 'label' => 'Procedure'],
                            ['value' => 'therapy', 'label' => 'Therapy'],
                            ['value' => 'assessment', 'label' => 'Assessment'],
                            ['value' => 'follow_up', 'label' => 'Follow-up'],
                            ['value' => 'other', 'label' => 'Other'],
                        ],
                    ],
                    [
                        'type' => 'textarea',
                        'name' => 'description',
                        'label' => 'Description',
                        'placeholder' => 'Describe what this service includes...',
                        'rows' => 4,
                    ],
                    [
                        'type' => 'toggle',
                        'name' => 'is_active',
                        'label' => 'Active Service',
                        'value' => true,
                    ],
                ],
            ],
            [
                'title' => 'Pricing & Duration',
                'description' => 'Service timing and cost details',
                'fields' => [
                    [
                        'type' => 'number',
                        'name' => 'duration_minutes',
                        'label' => 'Duration (minutes)',
                        'placeholder' => '60',
                        'required' => true,
                        'min' => '15',
                        'max' => '480',
                    ],
                    [
                        'type' => 'number',
                        'name' => 'price_cents',
                        'label' => 'Price (cents)',
                        'placeholder' => '5000',
                        'required' => true,
                        'min' => '0',
                        'help' => 'Price in cents (e.g., 5000 = €50.00)',
                    ],
                    [
                        'type' => 'number',
                        'name' => 'buffer_time',
                        'label' => 'Buffer Time (minutes)',
                        'placeholder' => '15',
                        'min' => '0',
                        'max' => '60',
                        'help' => 'Buffer time between appointments',
                    ],
                ],
            ],
            [
                'title' => 'Booking Settings',
                'description' => 'Availability and booking constraints',
                'fields' => [
                    [
                        'type' => 'number',
                        'name' => 'max_bookings_per_day',
                        'label' => 'Max Bookings Per Day',
                        'placeholder' => '8',
                        'min' => '1',
                        'max' => '50',
                        'help' => 'Maximum number of bookings allowed per day',
                    ],
                    [
                        'type' => 'number',
                        'name' => 'advance_booking_hours',
                        'label' => 'Advance Booking (hours)',
                        'placeholder' => '24',
                        'min' => '1',
                        'help' => 'Minimum hours in advance required for booking',
                    ],
                ],
            ],
        ];
    @endphp
    
    <x-admin.flowbite-form
        title="Create Service"
        description="Add a new service to the system"
        action="{{ url('/admin/services') }}"
        method="POST"
        :sections="$sections"
        submitLabel="Create Service"
        cancelUrl="/admin/services"
        layout="single"
    />
</x-filament-panels::page>