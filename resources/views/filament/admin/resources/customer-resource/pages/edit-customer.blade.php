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
                        'value' => $record->name ?? '',
                    ],
                    [
                        'type' => 'email',
                        'name' => 'email',
                        'label' => 'Email Address',
                        'placeholder' => 'customer@email.com',
                        'required' => true,
                        'value' => $record->email ?? '',
                    ],
                    [
                        'type' => 'tel',
                        'name' => 'phone',
                        'label' => 'Phone Number',
                        'placeholder' => '+49 123 456 789',
                        'required' => true,
                        'value' => $record->phone ?? '',
                    ],
                    [
                        'type' => 'date',
                        'name' => 'birthdate',
                        'label' => 'Birth Date',
                        'value' => $record->birthdate ? $record->birthdate->format('Y-m-d') : '',
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
                        'value' => $record->address ?? '',
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
                        'value' => $record->preferred_contact_method ?? 'phone',
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
                        'value' => $record->is_active ?? true,
                    ],
                    [
                        'type' => 'textarea',
                        'name' => 'notes',
                        'label' => 'Customer Notes',
                        'placeholder' => 'Any special notes about this customer...',
                        'rows' => 4,
                        'value' => $record->notes ?? '',
                    ],
                ],
            ],
        ];
    @endphp
    
    <x-admin.flowbite-form
        title="Edit Customer"
        description="Update customer information"
        action="{{ url('/admin/customers/' . $record->id, $record) }}"
        method="PUT"
        :sections="$sections"
        submitLabel="Update Customer"
        cancelUrl="/admin/customers"
        layout="single"
        :showDelete="true"
    />
</x-filament-panels::page>