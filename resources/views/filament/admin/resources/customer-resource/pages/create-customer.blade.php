<x-filament-panels::page>
    @php
        $sections = [
            [
                'title' => 'Customer Details',
                'description' => 'Basic customer information',
                'fields' => [
                    [
                        'type' => 'text',
                        'name' => 'name',
                        'label' => 'Full Name',
                        'placeholder' => 'Enter customer full name',
                        'required' => true,
                    ],
                    [
                        'type' => 'email',
                        'name' => 'email',
                        'label' => 'Email Address',
                        'placeholder' => 'customer@email.com',
                        'required' => true,
                    ],
                    [
                        'type' => 'tel',
                        'name' => 'phone',
                        'label' => 'Phone Number',
                        'placeholder' => '+49 123 456 789',
                        'required' => true,
                    ],
                    [
                        'type' => 'date',
                        'name' => 'birthdate',
                        'label' => 'Birth Date',
                    ],
                ],
            ],
            [
                'title' => 'Address Information',
                'description' => 'Customer address details',
                'fields' => [
                    [
                        'type' => 'textarea',
                        'name' => 'address',
                        'label' => 'Full Address',
                        'placeholder' => 'Enter customer address',
                        'rows' => 3,
                    ],
                ],
            ],
            [
                'title' => 'Preferences & Settings',
                'description' => 'Customer preferences and status',
                'fields' => [
                    [
                        'type' => 'select',
                        'name' => 'preferred_contact_method',
                        'label' => 'Preferred Contact Method',
                        'value' => 'phone',
                        'options' => [
                            ['value' => 'phone', 'label' => 'Phone'],
                            ['value' => 'email', 'label' => 'Email'],
                            ['value' => 'sms', 'label' => 'SMS'],
                        ],
                    ],
                    [
                        'type' => 'toggle',
                        'name' => 'is_active',
                        'label' => 'Active Customer',
                        'value' => true,
                    ],
                    [
                        'type' => 'textarea',
                        'name' => 'notes',
                        'label' => 'Customer Notes',
                        'placeholder' => 'Any special notes about this customer...',
                        'rows' => 4,
                    ],
                ],
            ],
        ];
    @endphp
    
    <x-admin.flowbite-form
        title="Create Customer"
        description="Add a new customer to the system"
        action="{{ url('/admin/customers') }}"
        method="POST"
        :sections="$sections"
        submitLabel="Create Customer"
        cancelUrl="/admin/customers"
        layout="single"
    />
</x-filament-panels::page>