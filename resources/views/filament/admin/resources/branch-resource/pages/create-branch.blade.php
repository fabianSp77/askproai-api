<x-filament-panels::page>
    @php
        $sections = [
            [
                'title' => 'Branch Details',
                'description' => 'Basic information about the branch',
                'fields' => [
                    [
                        'type' => 'text',
                        'name' => 'name',
                        'label' => 'Branch Name',
                        'placeholder' => 'Enter branch name',
                        'required' => true,
                    ],
                    [
                        'type' => 'select',
                        'name' => 'company_id',
                        'label' => 'Company',
                        'placeholder' => 'Select company',
                        'required' => true,
                        'options' => \App\Models\Company::pluck('name', 'id')->map(fn($name, $id) => ['value' => $id, 'label' => $name])->values()->toArray(),
                    ],
                    [
                        'type' => 'toggle',
                        'name' => 'is_main',
                        'label' => 'Main Branch',
                        'value' => false,
                    ],
                    [
                        'type' => 'toggle',
                        'name' => 'is_active',
                        'label' => 'Active',
                        'value' => true,
                    ],
                ],
            ],
            [
                'title' => 'Address Information',
                'description' => 'Physical location details',
                'fields' => [
                    [
                        'type' => 'textarea',
                        'name' => 'address',
                        'label' => 'Street Address',
                        'placeholder' => 'Enter full street address',
                        'required' => true,
                        'rows' => 3,
                    ],
                    [
                        'type' => 'text',
                        'name' => 'city',
                        'label' => 'City',
                        'placeholder' => 'Enter city',
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'name' => 'postal_code',
                        'label' => 'Postal Code',
                        'placeholder' => 'Enter postal code',
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'name' => 'country',
                        'label' => 'Country',
                        'placeholder' => 'Enter country',
                        'value' => 'Germany',
                        'required' => true,
                    ],
                ],
            ],
            [
                'title' => 'Contact Information',
                'description' => 'Phone and email details',
                'fields' => [
                    [
                        'type' => 'tel',
                        'name' => 'phone',
                        'label' => 'Phone Number',
                        'placeholder' => '+49 123 456 789',
                        'required' => true,
                    ],
                    [
                        'type' => 'email',
                        'name' => 'email',
                        'label' => 'Email Address',
                        'placeholder' => 'branch@company.com',
                    ],
                    [
                        'type' => 'textarea',
                        'name' => 'operating_hours',
                        'label' => 'Operating Hours',
                        'placeholder' => 'Mon-Fri: 9:00-18:00, Sat: 9:00-16:00',
                        'rows' => 3,
                    ],
                ],
            ],
        ];
    @endphp
    
    <x-admin.flowbite-form
        title="Create Branch"
        description="Add a new branch location"
        action="{{ url('/admin/branches') }}"
        method="POST"
        :sections="$sections"
        submitLabel="Create Branch"
        cancelUrl="/admin/branches"
        layout="single"
    />
</x-filament-panels::page>