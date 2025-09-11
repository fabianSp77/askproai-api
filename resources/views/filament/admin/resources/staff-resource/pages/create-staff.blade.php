<x-filament-panels::page>
    @php
        $sections = [
            [
                'title' => 'Staff Details',
                'description' => 'Basic staff member information',
                'fields' => [
                    [
                        'type' => 'text',
                        'name' => 'name',
                        'label' => 'Full Name',
                        'placeholder' => 'Enter staff member full name',
                        'required' => true,
                    ],
                    [
                        'type' => 'email',
                        'name' => 'email',
                        'label' => 'Email Address',
                        'placeholder' => 'staff@company.com',
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
                        'type' => 'select',
                        'name' => 'home_branch_id',
                        'label' => 'Home Branch',
                        'placeholder' => 'Select home branch',
                        'required' => true,
                        'options' => \App\Models\Branch::where('is_active', true)->pluck('name', 'id')->map(fn($name, $id) => ['value' => $id, 'label' => $name])->values()->toArray(),
                    ],
                ],
            ],
            [
                'title' => 'Professional Information',
                'description' => 'Skills, specialties and bio',
                'fields' => [
                    [
                        'type' => 'textarea',
                        'name' => 'specialties',
                        'label' => 'Specialties',
                        'placeholder' => 'List staff member specialties and skills...',
                        'rows' => 3,
                    ],
                    [
                        'type' => 'textarea',
                        'name' => 'bio',
                        'label' => 'Biography',
                        'placeholder' => 'Brief professional biography...',
                        'rows' => 4,
                    ],
                    [
                        'type' => 'number',
                        'name' => 'hourly_rate',
                        'label' => 'Hourly Rate (€)',
                        'placeholder' => '50.00',
                        'min' => '0',
                        'step' => '0.01',
                    ],
                ],
            ],
            [
                'title' => 'Status & Settings',
                'description' => 'Staff member status and availability',
                'fields' => [
                    [
                        'type' => 'toggle',
                        'name' => 'is_active',
                        'label' => 'Active Staff Member',
                        'value' => true,
                    ],
                    [
                        'type' => 'toggle',
                        'name' => 'can_book_appointments',
                        'label' => 'Can Book Appointments',
                        'value' => true,
                    ],
                    [
                        'type' => 'toggle',
                        'name' => 'receives_notifications',
                        'label' => 'Receives Notifications',
                        'value' => true,
                    ],
                ],
            ],
        ];
    @endphp
    
    <x-admin.flowbite-form
        title="Create Staff Member"
        description="Add a new staff member to the system"
        action="{{ url('/admin/staff') }}"
        method="POST"
        :sections="$sections"
        submitLabel="Create Staff Member"
        cancelUrl="/admin/staff"
        layout="single"
    />
</x-filament-panels::page>