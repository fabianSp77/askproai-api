<x-filament-panels::page>
    @php
        $sections = [
            [
                'title' => 'Company Details',
                'description' => 'Basic company information',
                'fields' => [
                    [
                        'type' => 'text',
                        'name' => 'name',
                        'label' => 'Company Name',
                        'placeholder' => 'Enter company name',
                        'required' => true,
                        'value' => $record->name ?? '',
                    ],
                    [
                        'type' => 'select',
                        'name' => 'industry',
                        'label' => 'Industry',
                        'placeholder' => 'Select industry',
                        'value' => $record->industry ?? '',
                        'options' => [
                            ['value' => 'healthcare', 'label' => 'Healthcare'],
                            ['value' => 'beauty', 'label' => 'Beauty & Wellness'],
                            ['value' => 'consulting', 'label' => 'Consulting'],
                            ['value' => 'education', 'label' => 'Education'],
                            ['value' => 'finance', 'label' => 'Finance'],
                            ['value' => 'legal', 'label' => 'Legal'],
                            ['value' => 'other', 'label' => 'Other'],
                        ],
                    ],
                    [
                        'type' => 'url',
                        'name' => 'website',
                        'label' => 'Website',
                        'placeholder' => 'https://www.company.com',
                        'value' => $record->website ?? '',
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
                'title' => 'Contact Information',
                'description' => 'Phone, email and address details',
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
                        'placeholder' => 'info@company.com',
                        'required' => true,
                        'value' => $record->email ?? '',
                    ],
                    [
                        'type' => 'textarea',
                        'name' => 'address',
                        'label' => 'Address',
                        'placeholder' => 'Enter full company address',
                        'rows' => 3,
                        'value' => $record->address ?? '',
                    ],
                ],
            ],
            [
                'title' => 'Additional Information',
                'description' => 'Company description and notes',
                'fields' => [
                    [
                        'type' => 'textarea',
                        'name' => 'description',
                        'label' => 'Description',
                        'placeholder' => 'Brief description of the company...',
                        'rows' => 4,
                        'value' => $record->description ?? '',
                    ],
                ],
            ],
        ];
    @endphp
    
    <x-admin.flowbite-form
        title="Edit Company"
        description="Update company information"
        action="{{ url('/admin/companies/' . $record->id, $record) }}"
        method="PUT"
        :sections="$sections"
        submitLabel="Update Company"
        cancelUrl="/admin/companies"
        layout="single"
        :showDelete="true"
    />
</x-filament-panels::page>