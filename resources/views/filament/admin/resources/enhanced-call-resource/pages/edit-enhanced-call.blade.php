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
                        'value' => $record->customer_id ?? '',
                        'options' => \App\Models\Customer::pluck('name', 'id')->map(fn($name, $id) => ['value' => $id, 'label' => $name])->values()->toArray(),
                    ],
                    [
                        'type' => 'select',
                        'name' => 'agent_id',
                        'label' => 'Agent',
                        'placeholder' => 'Select an agent',
                        'value' => $record->agent_id ?? '',
                        'options' => \App\Models\Staff::pluck('name', 'id')->map(fn($name, $id) => ['value' => $id, 'label' => $name])->values()->toArray(),
                    ],
                    [
                        'type' => 'tel',
                        'name' => 'from_number',
                        'label' => 'From Number',
                        'placeholder' => '+49 123 456 789',
                        'required' => true,
                        'value' => $record->from_number ?? '',
                    ],
                    [
                        'type' => 'tel',
                        'name' => 'to_number',
                        'label' => 'To Number',
                        'placeholder' => '+49 123 456 789',
                        'required' => true,
                        'value' => $record->to_number ?? '',
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
                        'value' => $record->call_type ?? 'inbound',
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
                        'value' => $record->status ?? 'initiated',
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
                        'value' => $record->priority ?? 'normal',
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
                        'value' => $record->started_at ? $record->started_at->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i'),
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
                        'value' => $record->transcript ?? '',
                    ],
                    [
                        'type' => 'textarea',
                        'name' => 'ai_summary',
                        'label' => 'AI Summary',
                        'placeholder' => 'AI-generated summary of the call...',
                        'rows' => 4,
                        'value' => $record->ai_summary ?? '',
                    ],
                    [
                        'type' => 'select',
                        'name' => 'sentiment',
                        'label' => 'Call Sentiment',
                        'value' => $record->sentiment ?? '',
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
                        'value' => $record->keywords ?? '',
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
                        'value' => $record->quality_score ?? '',
                        'help' => 'AI-generated quality score (0-100)',
                    ],
                    [
                        'type' => 'toggle',
                        'name' => 'requires_followup',
                        'label' => 'Requires Follow-up',
                        'value' => $record->requires_followup ?? false,
                    ],
                    [
                        'type' => 'textarea',
                        'name' => 'action_items',
                        'label' => 'Action Items',
                        'placeholder' => 'AI-extracted or manual action items...',
                        'rows' => 3,
                        'value' => $record->action_items ?? '',
                    ],
                    [
                        'type' => 'textarea',
                        'name' => 'notes',
                        'label' => 'Additional Notes',
                        'placeholder' => 'Any additional notes about this call...',
                        'rows' => 3,
                        'value' => $record->notes ?? '',
                    ],
                ],
            ],
        ];
    @endphp
    
    <x-admin.flowbite-form
        title="Edit Enhanced Call"
        description="Update enhanced call information and AI analysis"
        action="{{ url('/admin/enhanced-calls/' . $record->id, $record) }}"
        method="PUT"
        :sections="$sections"
        submitLabel="Update Enhanced Call"
        cancelUrl="/admin/enhanced-calls"
        layout="single"
        :showDelete="true"
    />
</x-filament-panels::page>