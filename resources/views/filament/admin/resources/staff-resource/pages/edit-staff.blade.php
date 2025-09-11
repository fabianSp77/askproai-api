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
                        'value' => $record->name ?? '',
                    ],
                    [
                        'type' => 'email',
                        'name' => 'email',
                        'label' => 'Email Address',
                        'placeholder' => 'staff@company.com',
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
                        'type' => 'select',
                        'name' => 'home_branch_id',
                        'label' => 'Home Branch',
                        'placeholder' => 'Select home branch',
                        'required' => true,
                        'value' => $record->home_branch_id ?? '',
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
                        'value' => $record->specialties ?? '',
                    ],
                    [
                        'type' => 'textarea',
                        'name' => 'bio',
                        'label' => 'Biography',
                        'placeholder' => 'Brief professional biography...',
                        'rows' => 4,
                        'value' => $record->bio ?? '',
                    ],
                    [
                        'type' => 'number',
                        'name' => 'hourly_rate',
                        'label' => 'Hourly Rate (€)',
                        'placeholder' => '50.00',
                        'min' => '0',
                        'step' => '0.01',
                        'value' => $record->hourly_rate ?? '',
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
                        'value' => $record->is_active ?? true,
                    ],
                    [
                        'type' => 'toggle',
                        'name' => 'can_book_appointments',
                        'label' => 'Can Book Appointments',
                        'value' => $record->can_book_appointments ?? true,
                    ],
                    [
                        'type' => 'toggle',
                        'name' => 'receives_notifications',
                        'label' => 'Receives Notifications',
                        'value' => $record->receives_notifications ?? true,
                    ],
                ],
            ],
        ];
    @endphp
    
    <x-admin.flowbite-form
        title="Edit Staff Member"
        description="Update staff member information"
        action="{{ url('/admin/staff/' . $record->id) }}"
        method="PUT"
        :sections="$sections"
        submitLabel="Update Staff Member"
        cancelUrl="/admin/staff"
        layout="single"
        :showDelete="true"
    />
</x-filament-panels::page>