<x-filament-panels::page>
    @php
        $sections = [
            [
                'title' => 'Call Details',
                'description' => 'Basic information about the enhanced call',
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
                        'name' => 'agent_id',
                        'label' => 'Agent',
                        'placeholder' => 'Select an agent',
                        'options' => \App\Models\Staff::pluck('name', 'id')->map(fn($name, $id) => ['value' => $id, 'label' => $name])->values()->toArray(),
                    ],
                    [
                        'type' => 'tel',
                        'name' => 'from_number',
                        'label' => 'From Number',
                        'placeholder' => '+49 123 456 789',
                        'required' => true,
                    ],
                    [
                        'type' => 'tel',
                        'name' => 'to_number',
                        'label' => 'To Number',
                        'placeholder' => '+49 123 456 789',
                        'required' => true,
                    ],
                ],
            ],
            [
                'title' => 'Call Information',
                'description' => 'Call type, status and enhanced details',
                'fields' => [
                    [
                        'type' => 'select',
                        'name' => 'call_type',
                        'label' => 'Call Type',
                        'required' => true,
                        'options' => [
                            ['value' => 'inbound', 'label' => 'Inbound'],
                            ['value' => 'outbound', 'label' => 'Outbound'],
                        ],
                    ],
                    [
                        'type' => 'select',
                        'name' => 'status',
                        'label' => 'Status',
                        'required' => true,
                        'value' => 'initiated',
                        'options' => [
                            ['value' => 'initiated', 'label' => 'Initiated'],
                            ['value' => 'in_progress', 'label' => 'In Progress'],
                            ['value' => 'completed', 'label' => 'Completed'],
                            ['value' => 'failed', 'label' => 'Failed'],
                            ['value' => 'abandoned', 'label' => 'Abandoned'],
                        ],
                    ],
                    [
                        'type' => 'select',
                        'name' => 'priority',
                        'label' => 'Priority',
                        'value' => 'normal',
                        'options' => [
                            ['value' => 'low', 'label' => 'Low'],
                            ['value' => 'normal', 'label' => 'Normal'],
                            ['value' => 'high', 'label' => 'High'],
                            ['value' => 'urgent', 'label' => 'Urgent'],
                        ],
                    ],
                    [
                        'type' => 'datetime-local',
                        'name' => 'started_at',
                        'label' => 'Started At',
                        'value' => now()->format('Y-m-d\TH:i'),
                    ],
                ],
            ],
            [
                'title' => 'AI Analysis & Content',
                'description' => 'AI-generated content and analysis',
                'fields' => [
                    [
                        'type' => 'textarea',
                        'name' => 'transcript',
                        'label' => 'Call Transcript',
                        'placeholder' => 'AI-generated or manual call transcript...',
                        'rows' => 6,
                    ],
                    [
                        'type' => 'textarea',
                        'name' => 'ai_summary',
                        'label' => 'AI Summary',
                        'placeholder' => 'AI-generated summary of the call...',
                        'rows' => 4,
                    ],
                    [
                        'type' => 'select',
                        'name' => 'sentiment',
                        'label' => 'Call Sentiment',
                        'options' => [
                            ['value' => 'positive', 'label' => 'Positive'],
                            ['value' => 'neutral', 'label' => 'Neutral'],
                            ['value' => 'negative', 'label' => 'Negative'],
                        ],
                    ],
                    [
                        'type' => 'textarea',
                        'name' => 'keywords',
                        'label' => 'Keywords/Tags',
                        'placeholder' => 'AI-extracted keywords, comma separated...',
                        'rows' => 2,
                        'help' => 'Comma-separated keywords extracted by AI',
                    ],
                ],
            ],
            [
                'title' => 'Quality & Follow-up',
                'description' => 'Call quality metrics and follow-up actions',
                'fields' => [
                    [
                        'type' => 'number',
                        'name' => 'quality_score',
                        'label' => 'Quality Score',
                        'placeholder' => '85',
                        'min' => '0',
                        'max' => '100',
                        'help' => 'AI-generated quality score (0-100)',
                    ],
                    [
                        'type' => 'toggle',
                        'name' => 'requires_followup',
                        'label' => 'Requires Follow-up',
                        'value' => false,
                    ],
                    [
                        'type' => 'textarea',
                        'name' => 'action_items',
                        'label' => 'Action Items',
                        'placeholder' => 'AI-extracted or manual action items...',
                        'rows' => 3,
                    ],
                    [
                        'type' => 'textarea',
                        'name' => 'notes',
                        'label' => 'Additional Notes',
                        'placeholder' => 'Any additional notes about this call...',
                        'rows' => 3,
                    ],
                ],
            ],
        ];
    @endphp
    
    <x-admin.flowbite-form
        title="Create Enhanced Call"
        description="Record a new enhanced call with AI analysis"
        action="{{ url('/admin/enhanced-calls') }}"
        method="POST"
        :sections="$sections"
        submitLabel="Create Enhanced Call"
        cancelUrl="/admin/enhanced-calls"
        layout="single"
    />
</x-filament-panels::page>