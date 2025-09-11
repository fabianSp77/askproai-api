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
                        'value' => $record->name ?? '',
                    ],
                    [
                        'type' => 'select',
                        'name' => 'company_id',
                        'label' => 'Company',
                        'placeholder' => 'Select company',
                        'required' => true,
                        'value' => $record->company_id ?? '',
                        'options' => \App\Models\Company::pluck('name', 'id')->map(fn($name, $id) => ['value' => $id, 'label' => $name])->values()->toArray(),
                    ],
                    [
                        'type' => 'toggle',
                        'name' => 'is_main',
                        'label' => 'Main Branch',
                        'value' => $record->is_main ?? false,
                    ],
                    [
                        'type' => 'toggle',
                        'name' => 'is_active',
                        'label' => 'Active',
                        'value' => $record->is_active ?? true,
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
                        'value' => $record->address ?? '',
                    ],
                    [
                        'type' => 'text',
                        'name' => 'city',
                        'label' => 'City',
                        'placeholder' => 'Enter city',
                        'required' => true,
                        'value' => $record->city ?? '',
                    ],
                    [
                        'type' => 'text',
                        'name' => 'postal_code',
                        'label' => 'Postal Code',
                        'placeholder' => 'Enter postal code',
                        'required' => true,
                        'value' => $record->postal_code ?? '',
                    ],
                    [
                        'type' => 'text',
                        'name' => 'country',
                        'label' => 'Country',
                        'placeholder' => 'Enter country',
                        'required' => true,
                        'value' => $record->country ?? 'Germany',
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
                        'value' => $record->phone ?? '',
                    ],
                    [
                        'type' => 'email',
                        'name' => 'email',
                        'label' => 'Email Address',
                        'placeholder' => 'branch@company.com',
                        'value' => $record->email ?? '',
                    ],
                    [
                        'type' => 'textarea',
                        'name' => 'operating_hours',
                        'label' => 'Operating Hours',
                        'placeholder' => 'Mon-Fri: 9:00-18:00, Sat: 9:00-16:00',
                        'rows' => 3,
                        'value' => $record->operating_hours ?? '',
                    ],
                ],
            ],
        ];
    @endphp
    
    <x-admin.flowbite-form
        title="Edit Branch"
        description="Update branch information"
        action="{{ url('/admin/branches/' . $record->id, $record) }}"
        method="PUT"
        :sections="$sections"
        submitLabel="Update Branch"
        cancelUrl="/admin/branches"
        layout="single"
        :showDelete="true"
    />
</x-filament-panels::page>