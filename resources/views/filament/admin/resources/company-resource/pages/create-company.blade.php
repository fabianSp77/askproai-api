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
                    ],
                    [
                        'type' => 'select',
                        'name' => 'industry',
                        'label' => 'Industry',
                        'placeholder' => 'Select industry',
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
                'title' => 'Contact Information',
                'description' => 'Phone, email and address details',
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
                        'placeholder' => 'info@company.com',
                        'required' => true,
                    ],
                    [
                        'type' => 'textarea',
                        'name' => 'address',
                        'label' => 'Address',
                        'placeholder' => 'Enter full company address',
                        'rows' => 3,
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
                    ],
                ],
            ],
        ];
    @endphp
    
    <x-admin.flowbite-form
        title="Create Company"
        description="Add a new company to the system"
        action="{{ url('/admin/companies') }}"
        method="POST"
        :sections="$sections"
        submitLabel="Create Company"
        cancelUrl="/admin/companies"
        layout="single"
    />
</x-filament-panels::page>