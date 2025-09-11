<x-filament-panels::page>
    @php
        $sections = [
            [
                'title' => 'Integration Details',
                'description' => 'Basic integration information',
                'fields' => [
                    [
                        'type' => 'text',
                        'name' => 'name',
                        'label' => 'Integration Name',
                        'placeholder' => 'Enter integration name',
                        'required' => true,
                        'value' => $record->name ?? '',
                    ],
                    [
                        'type' => 'select',
                        'name' => 'type',
                        'label' => 'Integration Type',
                        'required' => true,
                        'value' => $record->type ?? '',
                        'options' => [
                            ['value' => 'calcom', 'label' => 'Cal.com'],
                            ['value' => 'retell', 'label' => 'Retell AI'],
                            ['value' => 'stripe', 'label' => 'Stripe'],
                            ['value' => 'twilio', 'label' => 'Twilio'],
                            ['value' => 'webhook', 'label' => 'Generic Webhook'],
                        ],
                    ],
                    [
                        'type' => 'toggle',
                        'name' => 'is_active',
                        'label' => 'Active Integration',
                        'value' => $record->is_active ?? true,
                    ],
                ],
            ],
            [
                'title' => 'API Configuration',
                'description' => 'API keys and endpoint configuration',
                'fields' => [
                    [
                        'type' => 'password',
                        'name' => 'api_key',
                        'label' => 'API Key',
                        'placeholder' => 'Leave blank to keep current key',
                        'help' => 'Leave blank to keep current API key, API key will be encrypted when stored',
                    ],
                    [
                        'type' => 'url',
                        'name' => 'webhook_url',
                        'label' => 'Webhook URL',
                        'placeholder' => 'https://api.service.com/webhook',
                        'value' => $record->webhook_url ?? '',
                        'help' => 'URL for receiving webhook notifications',
                    ],
                    [
                        'type' => 'url',
                        'name' => 'base_url',
                        'label' => 'Base URL',
                        'placeholder' => 'https://api.service.com',
                        'value' => $record->base_url ?? '',
                        'help' => 'Base API URL (optional)',
                    ],
                ],
            ],
            [
                'title' => 'Settings & Configuration',
                'description' => 'Additional configuration and settings',
                'fields' => [
                    [
                        'type' => 'textarea',
                        'name' => 'settings',
                        'label' => 'Configuration Settings (JSON)',
                        'placeholder' => '{"key": "value", "timeout": 30}',
                        'rows' => 6,
                        'value' => $record->settings ? json_encode($record->settings, JSON_PRETTY_PRINT) : '',
                        'help' => 'Additional settings in JSON format',
                    ],
                    [
                        'type' => 'textarea',
                        'name' => 'description',
                        'label' => 'Description',
                        'placeholder' => 'Brief description of this integration...',
                        'rows' => 3,
                        'value' => $record->description ?? '',
                    ],
                    [
                        'type' => 'toggle',
                        'name' => 'log_requests',
                        'label' => 'Log API Requests',
                        'value' => $record->log_requests ?? false,
                        'help' => 'Enable request logging for debugging',
                    ],
                ],
            ],
        ];
    @endphp
    
    <x-admin.flowbite-form
        title="Edit Integration"
        description="Update integration configuration"
        action="{{ url('/admin/integrations/' . $record->id, $record) }}"
        method="PUT"
        :sections="$sections"
        submitLabel="Update Integration"
        cancelUrl="/admin/integrations"
        layout="single"
        :showDelete="true"
    />
    
    <script>
        // JSON validation for settings field
        document.addEventListener('DOMContentLoaded', function() {
            const settingsField = document.getElementById('settings');
            
            settingsField?.addEventListener('blur', function() {
                if (this.value.trim()) {
                    try {
                        JSON.parse(this.value);
                        this.setCustomValidity('');
                        this.classList.remove('border-red-500');
                        this.classList.add('border-green-500');
                    } catch (e) {
                        this.setCustomValidity('Invalid JSON format');
                        this.classList.remove('border-green-500');
                        this.classList.add('border-red-500');
                    }
                } else {
                    this.setCustomValidity('');
                    this.classList.remove('border-red-500', 'border-green-500');
                }
            });
        });
    </script>
</x-filament-panels::page>