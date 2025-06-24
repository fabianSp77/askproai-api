<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Services\RetellV2Service;
use App\Models\Company;
use Illuminate\Support\Facades\Cache;

class RetellUltimateDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';
    protected static ?string $navigationGroup = 'System';
    protected static ?string $navigationLabel = 'Retell Ultimate Control';
    protected static ?int $navigationSort = 7;
    
    protected static string $view = 'filament.admin.pages.retell-ultimate-dashboard';
    
    protected function getViewData(): array
    {
        return [
            'forceModernStyles' => true,
        ];
    }
    
    public $agents = [];
    public $selectedAgent = null;
    public $selectedLLM = null;
    public $llmData = null;
    public $phoneNumbers = [];
    public $error = null;
    public $successMessage = null;
    
    // Editing states
    public $editingPrompt = false;
    public $editingFunction = null;
    public $newPrompt = '';
    public $functionDetails = [];
    
    // Phone number editing
    public $editingPhoneNumber = null;
    public $phoneNumberConfig = [];
    
    // Agent settings editing
    public $editingAgentSettings = false;
    public $agentSettings = [];
    
    // LLM settings editing
    public $editingLLMSettings = false;
    public $llmSettings = [];
    
    // Webhook configuration
    public $webhookConfig = null;
    public $editingWebhook = false;
    
    // Function editing
    public $functionEditor = [
        'name' => '',
        'description' => '',
        'type' => 'custom',
        'url' => '',
        'method' => 'POST',
        'headers' => [],
        'parameters' => [],
        'speak_during_execution' => false,
        'speak_after_execution' => true,
        'execution_message' => 'One moment while I process that...'
    ];
    public $addingNewFunction = false;
    public $functionTemplates = [];
    
    // Test states
    public $testingFunction = null;
    public $testInputs = [];
    public $testResult = null;
    
    protected $service = null;
    
    public function mount(): void
    {
        try {
            $this->initializeService();
            
            if ($this->service) {
                // Load initial data
                $this->loadAgents();
                $this->loadPhoneNumbers();
            }
            
            // Initialize function templates
            $this->loadFunctionTemplates();
            
        } catch (\Exception $e) {
            $this->error = 'Error: ' . $e->getMessage();
        }
    }
    
    protected function initializeService(): void
    {
        $user = auth()->user();
        if (!$user || !$user->company_id) {
            $this->error = 'No company found for user';
            return;
        }
        
        $company = Company::find($user->company_id);
        if (!$company || !$company->retell_api_key) {
            $this->error = 'No Retell API key configured for your company';
            return;
        }
        
        $apiKey = $company->retell_api_key;
        if (strlen($apiKey) > 50) {
            $apiKey = decrypt($apiKey);
        }
        
        $this->service = new RetellV2Service($apiKey);
    }
    
    public function loadAgents(): void
    {
        if (!$this->service) {
            $this->error = 'Retell service not initialized';
            return;
        }
        
        try {
            $agentsResult = $this->service->listAgents();
            $this->agents = collect($agentsResult['agents'] ?? [])
                ->values()
                ->toArray();
        } catch (\Exception $e) {
            $this->error = 'Failed to load agents: ' . $e->getMessage();
            $this->agents = [];
        }
    }
    
    public function loadPhoneNumbers(): void
    {
        if (!$this->service) {
            $this->error = 'Retell service not initialized';
            return;
        }
        
        try {
            $phonesResult = $this->service->listPhoneNumbers();
            $this->phoneNumbers = $phonesResult['phone_numbers'] ?? [];
        } catch (\Exception $e) {
            $this->error = 'Failed to load phone numbers: ' . $e->getMessage();
            $this->phoneNumbers = [];
        }
    }
    
    public function selectAgent($agentId): void
    {
        try {
            $this->reset(['llmData', 'editingPrompt', 'editingFunction', 'testingFunction', 'testResult', 'error']);
            
            // Ensure service is initialized
            if (!$this->service) {
                $this->initializeService();
                if (!$this->service) {
                    $this->error = 'Failed to initialize Retell service';
                    return;
                }
            }
            
            // Load all agents first to ensure we have the full list
            $agentsResult = $this->service->listAgents();
            $allAgents = collect($agentsResult['agents'] ?? []);
            
            // Find the specific agent
            $agent = $allAgents->firstWhere('agent_id', $agentId);
            if (!$agent) {
                $this->error = 'Agent not found';
                return;
            }
            
            $this->selectedAgent = $agent;
            
            // Load LLM data if agent uses retell-llm
            if (isset($agent['response_engine']['type']) && 
                $agent['response_engine']['type'] === 'retell-llm' &&
                isset($agent['response_engine']['llm_id'])) {
                
                $this->selectedLLM = $agent['response_engine']['llm_id'];
                $this->loadLLMData();
            }
        } catch (\Exception $e) {
            $this->error = 'Error loading agent: ' . $e->getMessage();
        }
    }
    
    public function loadLLMData(): void
    {
        if (!$this->selectedLLM) return;
        
        // Ensure service is initialized
        if (!$this->service) {
            $this->initializeService();
            if (!$this->service) {
                $this->error = 'Failed to initialize Retell service';
                return;
            }
        }
        
        try {
            $this->llmData = Cache::remember(
                "retell_llm_details_{$this->selectedLLM}", 
                60, // Cache for 1 minute during editing
                fn() => $this->service->getRetellLLM($this->selectedLLM)
            );
        } catch (\Exception $e) {
            $this->error = 'Failed to load LLM data: ' . $e->getMessage();
            return;
        }
        
        // Parse function details
        if (isset($this->llmData['general_tools'])) {
            foreach ($this->llmData['general_tools'] as $tool) {
                $this->functionDetails[$tool['name']] = $this->parseFunctionDetails($tool);
            }
        }
    }
    
    private function parseFunctionDetails($tool): array
    {
        $details = [
            'name' => $tool['name'] ?? '',
            'type' => $tool['type'] ?? 'custom',
            'description' => $tool['description'] ?? '',
            'url' => $tool['url'] ?? '',
            'method' => $tool['method'] ?? 'GET',
            'speak_during_execution' => $tool['speak_during_execution'] ?? false,
            'speak_after_execution' => $tool['speak_after_execution'] ?? false,
            'headers' => $tool['headers'] ?? [],
            'body' => $tool['body'] ?? [],
            'parameters' => []
        ];
        
        // Parse parameters based on function type
        $functionType = $tool['type'] ?? 'custom';
        if ($functionType === 'check_availability_cal') {
            $details['parameters'] = [
                [
                    'name' => 'service',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'Die gewünschte Dienstleistung',
                    'example' => 'Herrenhaarschnitt'
                ],
                [
                    'name' => 'date',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'Das gewünschte Datum im Format YYYY-MM-DD',
                    'example' => '2024-01-15'
                ],
                [
                    'name' => 'time',
                    'type' => 'string',
                    'required' => false,
                    'description' => 'Die gewünschte Uhrzeit im Format HH:MM',
                    'example' => '14:00'
                ]
            ];
        } elseif ($functionType === 'book_appointment_cal') {
            $details['parameters'] = [
                [
                    'name' => 'customer_name',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'Der vollständige Name des Kunden',
                    'example' => 'Max Mustermann'
                ],
                [
                    'name' => 'customer_phone',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'Die Telefonnummer des Kunden',
                    'example' => '+49 30 12345678'
                ],
                [
                    'name' => 'customer_email',
                    'type' => 'string',
                    'required' => false,
                    'description' => 'Die E-Mail-Adresse des Kunden',
                    'example' => 'max@example.com'
                ],
                [
                    'name' => 'service',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'Die gewünschte Dienstleistung',
                    'example' => 'Herrenhaarschnitt'
                ],
                [
                    'name' => 'date',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'Das gewünschte Datum im Format YYYY-MM-DD',
                    'example' => '2024-01-15'
                ],
                [
                    'name' => 'time',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'Die gewünschte Uhrzeit im Format HH:MM',
                    'example' => '14:00'
                ],
                [
                    'name' => 'notes',
                    'type' => 'string',
                    'required' => false,
                    'description' => 'Zusätzliche Notizen oder spezielle Wünsche',
                    'example' => 'Bitte kurze Wartezeit'
                ]
            ];
        } elseif ($tool['type'] === 'custom' && isset($tool['body'])) {
            // For custom functions, try to parse parameters from body structure
            foreach ($tool['body'] as $param) {
                if (isset($param['name'])) {
                    $details['parameters'][] = [
                        'name' => $param['name'],
                        'type' => $param['type'] ?? 'string',
                        'required' => $param['required'] ?? false,
                        'description' => $param['description'] ?? '',
                        'example' => $param['example'] ?? ''
                    ];
                }
            }
        }
        
        return $details;
    }
    
    public function startEditingPrompt(): void
    {
        $this->editingPrompt = true;
        $this->newPrompt = $this->llmData['general_prompt'] ?? '';
    }
    
    public function cancelEditingPrompt(): void
    {
        $this->editingPrompt = false;
        $this->newPrompt = '';
    }
    
    public function savePrompt(): void
    {
        if (!$this->selectedLLM || !$this->newPrompt) return;
        
        // Ensure service is initialized
        if (!$this->service) {
            $this->initializeService();
            if (!$this->service) {
                $this->error = 'Failed to initialize Retell service';
                return;
            }
        }
        
        try {
            $result = $this->service->updateRetellLLM($this->selectedLLM, [
                'general_prompt' => $this->newPrompt
            ]);
            
            if ($result) {
                $this->successMessage = 'Prompt successfully updated!';
                $this->editingPrompt = false;
                Cache::forget("retell_llm_details_{$this->selectedLLM}");
                $this->loadLLMData();
            }
        } catch (\Exception $e) {
            $this->error = 'Failed to update prompt: ' . $e->getMessage();
        }
    }
    
    public function startEditingFunction($functionName): void
    {
        if (!$this->llmData || !isset($this->llmData['general_tools'])) return;
        
        // Find the function to edit
        $functionToEdit = null;
        foreach ($this->llmData['general_tools'] as $tool) {
            if ($tool['name'] === $functionName) {
                $functionToEdit = $tool;
                break;
            }
        }
        
        if (!$functionToEdit) {
            $this->error = 'Function not found';
            return;
        }
        
        $this->editingFunction = $functionName;
        $this->addingNewFunction = false;
        
        // Load function data into editor
        $this->functionEditor = [
            'name' => $functionToEdit['name'] ?? '',
            'description' => $functionToEdit['description'] ?? '',
            'type' => $functionToEdit['type'] ?? 'custom',
            'url' => $functionToEdit['url'] ?? '',
            'method' => $functionToEdit['method'] ?? 'POST',
            'headers' => $functionToEdit['headers'] ?? [],
            'parameters' => [],
            'speak_during_execution' => $functionToEdit['speak_during_execution'] ?? false,
            'speak_after_execution' => $functionToEdit['speak_after_execution'] ?? true,
            'execution_message' => $functionToEdit['execution_message'] ?? 'Processing your request...'
        ];
        
        // Convert headers to key-value format if needed
        if (!empty($this->functionEditor['headers']) && !isset($this->functionEditor['headers'][0]['key'])) {
            $headers = [];
            foreach ($this->functionEditor['headers'] as $key => $value) {
                $headers[] = ['key' => $key, 'value' => $value];
            }
            $this->functionEditor['headers'] = $headers;
        }
        
        // Parse parameters from body
        if (isset($functionToEdit['body']) && is_array($functionToEdit['body'])) {
            foreach ($functionToEdit['body'] as $param) {
                if (isset($param['name'])) {
                    $this->functionEditor['parameters'][] = [
                        'name' => $param['name'],
                        'type' => $param['type'] ?? 'string',
                        'required' => $param['required'] ?? false,
                        'description' => $param['description'] ?? '',
                        'example' => $param['example'] ?? ''
                    ];
                }
            }
        }
    }
    
    public function cancelEditingFunction(): void
    {
        $this->editingFunction = null;
    }
    
    public function saveFunction(): void
    {
        if (!$this->selectedLLM || !$this->editingFunction) return;
        
        // Ensure service is initialized
        if (!$this->service) {
            $this->initializeService();
            if (!$this->service) {
                $this->error = 'Failed to initialize Retell service';
                return;
            }
        }
        
        try {
            // Get current LLM data
            $llmData = $this->llmData;
            
            // Find and update the function
            $tools = $llmData['general_tools'] ?? [];
            $updatedTools = [];
            $functionUpdated = false;
            
            foreach ($tools as $tool) {
                if ($tool['name'] === $this->editingFunction) {
                    // Update this function with new data
                    $updatedTool = array_merge($tool, $this->functionEditor);
                    
                    // Convert parameters to body format for custom functions
                    if ($this->functionEditor['type'] === 'custom' && isset($this->functionEditor['parameters'])) {
                        $updatedTool['body'] = [];
                        foreach ($this->functionEditor['parameters'] as $param) {
                            $updatedTool['body'][] = [
                                'name' => $param['name'],
                                'type' => $param['type'] ?? 'string',
                                'required' => $param['required'] ?? false,
                                'description' => $param['description'] ?? '',
                                'example' => $param['example'] ?? ''
                            ];
                        }
                    }
                    
                    $updatedTools[] = $updatedTool;
                    $functionUpdated = true;
                } else {
                    $updatedTools[] = $tool;
                }
            }
            
            if (!$functionUpdated && $this->addingNewFunction) {
                // Add new function
                $newTool = $this->functionEditor;
                
                // Convert parameters to body format for custom functions
                if ($newTool['type'] === 'custom' && isset($newTool['parameters'])) {
                    $newTool['body'] = [];
                    foreach ($newTool['parameters'] as $param) {
                        $newTool['body'][] = [
                            'name' => $param['name'],
                            'type' => $param['type'] ?? 'string',
                            'required' => $param['required'] ?? false,
                            'description' => $param['description'] ?? '',
                            'example' => $param['example'] ?? ''
                        ];
                    }
                }
                
                $updatedTools[] = $newTool;
            }
            
            // Update LLM with new tools
            $result = $this->service->updateRetellLLM($this->selectedLLM, [
                'general_tools' => $updatedTools
            ]);
            
            if ($result) {
                $this->successMessage = 'Function saved successfully!';
                $this->editingFunction = null;
                $this->addingNewFunction = false;
                Cache::forget("retell_llm_details_{$this->selectedLLM}");
                $this->loadLLMData();
            }
        } catch (\Exception $e) {
            $this->error = 'Failed to save function: ' . $e->getMessage();
        }
    }
    
    public function deleteFunction($functionName): void
    {
        if (!$this->selectedLLM || !$functionName) return;
        
        // Ensure service is initialized
        if (!$this->service) {
            $this->initializeService();
            if (!$this->service) {
                $this->error = 'Failed to initialize Retell service';
                return;
            }
        }
        
        try {
            // Get current LLM data
            $llmData = $this->llmData;
            
            // Remove the function
            $tools = $llmData['general_tools'] ?? [];
            $updatedTools = array_filter($tools, function($tool) use ($functionName) {
                return $tool['name'] !== $functionName;
            });
            
            // Update LLM with new tools
            $result = $this->service->updateRetellLLM($this->selectedLLM, [
                'general_tools' => array_values($updatedTools)
            ]);
            
            if ($result) {
                $this->successMessage = 'Function deleted successfully!';
                Cache::forget("retell_llm_details_{$this->selectedLLM}");
                $this->loadLLMData();
            }
        } catch (\Exception $e) {
            $this->error = 'Failed to delete function: ' . $e->getMessage();
        }
    }
    
    public function duplicateFunction($functionName): void
    {
        if (!$this->selectedLLM || !$functionName) return;
        
        // Find the function to duplicate
        $tools = $this->llmData['general_tools'] ?? [];
        $functionToDuplicate = null;
        
        foreach ($tools as $tool) {
            if ($tool['name'] === $functionName) {
                $functionToDuplicate = $tool;
                break;
            }
        }
        
        if (!$functionToDuplicate) {
            $this->error = 'Function not found';
            return;
        }
        
        // Create a copy with a new name
        $this->addingNewFunction = true;
        $this->editingFunction = 'new_function';
        $this->functionEditor = array_merge($functionToDuplicate, [
            'name' => $functionToDuplicate['name'] . '_copy',
            'description' => $functionToDuplicate['description'] . ' (Copy)',
        ]);
        
        // Parse parameters if they exist
        if (isset($functionToDuplicate['body']) && is_array($functionToDuplicate['body'])) {
            $this->functionEditor['parameters'] = [];
            foreach ($functionToDuplicate['body'] as $param) {
                if (isset($param['name'])) {
                    $this->functionEditor['parameters'][] = [
                        'name' => $param['name'],
                        'type' => $param['type'] ?? 'string',
                        'required' => $param['required'] ?? false,
                        'description' => $param['description'] ?? '',
                        'example' => $param['example'] ?? ''
                    ];
                }
            }
        }
    }
    
    public function startAddingFunction(): void
    {
        $this->addingNewFunction = true;
        $this->editingFunction = 'new_function';
        $this->functionEditor = [
            'name' => '',
            'description' => '',
            'type' => 'custom',
            'url' => '',
            'method' => 'POST',
            'headers' => [
                ['key' => 'Content-Type', 'value' => 'application/json'],
                ['key' => 'Authorization', 'value' => 'Bearer YOUR_API_KEY']
            ],
            'parameters' => [],
            'speak_during_execution' => false,
            'speak_after_execution' => true,
            'execution_message' => 'Processing your request...'
        ];
    }
    
    public function addFunctionParameter(): void
    {
        $this->functionEditor['parameters'][] = [
            'name' => '',
            'type' => 'string',
            'description' => '',
            'required' => true,
            'example' => ''
        ];
    }
    
    public function removeFunctionParameter($index): void
    {
        unset($this->functionEditor['parameters'][$index]);
        $this->functionEditor['parameters'] = array_values($this->functionEditor['parameters']);
    }
    
    public function addFunctionHeader(): void
    {
        $this->functionEditor['headers'][] = [
            'key' => '',
            'value' => ''
        ];
    }
    
    public function removeFunctionHeader($index): void
    {
        unset($this->functionEditor['headers'][$index]);
        $this->functionEditor['headers'] = array_values($this->functionEditor['headers']);
    }
    
    public function loadFunctionTemplates(): void
    {
        $this->functionTemplates = [
            [
                'name' => 'Weather API',
                'description' => 'Get weather information for a location',
                'config' => [
                    'type' => 'custom',
                    'url' => 'https://api.openweathermap.org/data/2.5/weather',
                    'method' => 'GET',
                    'parameters' => [
                        ['name' => 'city', 'type' => 'string', 'description' => 'City name', 'required' => true],
                        ['name' => 'units', 'type' => 'string', 'description' => 'Temperature units (metric/imperial)', 'required' => false]
                    ]
                ]
            ],
            [
                'name' => 'Database Query',
                'description' => 'Query your database',
                'config' => [
                    'type' => 'custom',
                    'url' => 'https://api.yourcompany.com/query',
                    'method' => 'POST',
                    'parameters' => [
                        ['name' => 'query', 'type' => 'string', 'description' => 'Search query', 'required' => true],
                        ['name' => 'limit', 'type' => 'number', 'description' => 'Result limit', 'required' => false]
                    ]
                ]
            ],
            [
                'name' => 'Send Email',
                'description' => 'Send an email notification',
                'config' => [
                    'type' => 'custom',
                    'url' => 'https://api.yourcompany.com/send-email',
                    'method' => 'POST',
                    'parameters' => [
                        ['name' => 'to', 'type' => 'string', 'description' => 'Recipient email', 'required' => true],
                        ['name' => 'subject', 'type' => 'string', 'description' => 'Email subject', 'required' => true],
                        ['name' => 'body', 'type' => 'string', 'description' => 'Email body', 'required' => true]
                    ]
                ]
            ]
        ];
    }
    
    public function applyFunctionTemplate($templateIndex): void
    {
        if (isset($this->functionTemplates[$templateIndex])) {
            $template = $this->functionTemplates[$templateIndex];
            $this->functionEditor = array_merge($this->functionEditor, [
                'name' => str_replace(' ', '_', strtolower($template['name'])),
                'description' => $template['description'],
                ...$template['config']
            ]);
        }
    }
    
    public function startTestingFunction($functionName): void
    {
        $this->testingFunction = $functionName;
        $this->testResult = null;
        $this->testInputs = [];
        
        // Initialize test inputs with empty values
        if (isset($this->functionDetails[$functionName]['parameters'])) {
            foreach ($this->functionDetails[$functionName]['parameters'] as $param) {
                $this->testInputs[$param['name']] = $param['example'] ?? '';
            }
        }
    }
    
    public function executeTest(): void
    {
        if (!$this->testingFunction) return;
        
        $function = collect($this->llmData['general_tools'] ?? [])
            ->firstWhere('name', $this->testingFunction);
        
        if (!$function) {
            $this->error = 'Function not found';
            return;
        }
        
        try {
            $functionType = $function['type'] ?? 'custom';
            
            // Built-in functions can't be tested directly
            if (in_array($functionType, ['check_availability_cal', 'book_appointment_cal', 'end_call'])) {
                $this->testResult = [
                    'success' => false,
                    'error' => "This is a built-in Retell function (type: $functionType) and cannot be tested directly from this interface. It will be called automatically by Retell during conversations."
                ];
                return;
            }
            
            // Test custom functions
            if ($functionType === 'custom' && isset($function['url'])) {
                $client = new \GuzzleHttp\Client();
                
                // Build request options
                $options = [
                    'headers' => array_merge([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ], $function['headers'] ?? []),
                    'timeout' => 10
                ];
                
                // Add body if method supports it
                $method = strtoupper($function['method'] ?? 'GET');
                if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
                    $options['json'] = $this->testInputs;
                } else {
                    // For GET requests, add as query parameters
                    $options['query'] = $this->testInputs;
                }
                
                $response = $client->request($method, $function['url'], $options);
                
                $this->testResult = [
                    'success' => true,
                    'status' => $response->getStatusCode(),
                    'body' => json_decode($response->getBody()->getContents(), true)
                ];
            } else {
                $this->testResult = [
                    'success' => false,
                    'error' => 'Invalid function configuration - missing URL for custom function'
                ];
            }
        } catch (\Exception $e) {
            $this->testResult = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function refreshData(): void
    {
        Cache::flush();
        $this->mount();
        $this->successMessage = 'Data refreshed successfully!';
    }
    
    public function createNewVersion(): void
    {
        // Implementation for creating a new agent version
        // This would clone the current agent with a new version number
    }
    
    // Phone Number Management
    public function startEditingPhoneNumber($phoneNumberId): void
    {
        // Try to find by phone_number_id first, then by phone_number
        $phoneNumber = collect($this->phoneNumbers)->firstWhere('phone_number_id', $phoneNumberId) 
                    ?? collect($this->phoneNumbers)->firstWhere('phone_number', $phoneNumberId);
        
        if ($phoneNumber) {
            $this->editingPhoneNumber = $phoneNumberId;
            $this->phoneNumberConfig = [
                'nickname' => $phoneNumber['nickname'] ?? '',
                'inbound_agent_id' => $phoneNumber['inbound_agent_id'] ?? '',
                'outbound_agent_id' => $phoneNumber['outbound_agent_id'] ?? '',
                'inbound_webhook_url' => $phoneNumber['inbound_webhook_url'] ?? ''
            ];
        }
    }
    
    public function cancelEditingPhoneNumber(): void
    {
        $this->editingPhoneNumber = null;
        $this->phoneNumberConfig = [];
    }
    
    public function savePhoneNumber(): void
    {
        if (!$this->editingPhoneNumber) return;
        
        // Ensure service is initialized
        if (!$this->service) {
            $this->initializeService();
            if (!$this->service) {
                $this->error = 'Failed to initialize Retell service';
                return;
            }
        }
        
        try {
            $result = $this->service->updatePhoneNumber($this->editingPhoneNumber, $this->phoneNumberConfig);
            
            if ($result) {
                $this->successMessage = 'Phone number updated successfully!';
                $this->editingPhoneNumber = null;
                $this->phoneNumberConfig = [];
                $this->loadPhoneNumbers();
            }
        } catch (\Exception $e) {
            $this->error = 'Failed to update phone number: ' . $e->getMessage();
        }
    }
    
    // Agent Settings Management
    public function startEditingAgentSettings(): void
    {
        if (!$this->selectedAgent) return;
        
        $this->editingAgentSettings = true;
        $this->agentSettings = [
            'agent_name' => $this->selectedAgent['agent_name'] ?? '',
            'voice_id' => $this->selectedAgent['voice_id'] ?? '',
            'language' => $this->selectedAgent['language'] ?? 'en-US',
            'voice_temperature' => $this->selectedAgent['voice_temperature'] ?? 1,
            'voice_speed' => $this->selectedAgent['voice_speed'] ?? 1,
            'interruption_sensitivity' => $this->selectedAgent['interruption_sensitivity'] ?? 1,
            'enable_backchannel' => $this->selectedAgent['enable_backchannel'] ?? true,
            'ambient_sound' => $this->selectedAgent['ambient_sound'] ?? 'off',
            'responsiveness' => $this->selectedAgent['responsiveness'] ?? 1,
            'reminder_trigger_ms' => $this->selectedAgent['reminder_trigger_ms'] ?? 10000,
            'normalize_for_speech' => $this->selectedAgent['normalize_for_speech'] ?? true,
            'end_call_after_silence_ms' => $this->selectedAgent['end_call_after_silence_ms'] ?? 600000,
            'pronunciation_guide' => $this->selectedAgent['pronunciation_guide'] ?? []
        ];
    }
    
    public function cancelEditingAgentSettings(): void
    {
        $this->editingAgentSettings = false;
        $this->agentSettings = [];
    }
    
    public function saveAgentSettings(): void
    {
        if (!$this->selectedAgent || !$this->editingAgentSettings) return;
        
        // Ensure service is initialized
        if (!$this->service) {
            $this->initializeService();
            if (!$this->service) {
                $this->error = 'Failed to initialize Retell service';
                return;
            }
        }
        
        try {
            $result = $this->service->updateAgent($this->selectedAgent['agent_id'], $this->agentSettings);
            
            if ($result) {
                $this->successMessage = 'Agent settings updated successfully!';
                $this->editingAgentSettings = false;
                // Reload agent
                $this->selectAgent($this->selectedAgent['agent_id']);
            }
        } catch (\Exception $e) {
            $this->error = 'Failed to update agent: ' . $e->getMessage();
        }
    }
    
    // LLM Settings Management
    public function startEditingLLMSettings(): void
    {
        if (!$this->llmData) return;
        
        $this->editingLLMSettings = true;
        $this->llmSettings = [
            'model' => $this->llmData['model'] ?? 'gpt-4',
            'temperature' => $this->llmData['temperature'] ?? 0.7,
            'max_tokens' => $this->llmData['max_tokens'] ?? 250,
            'top_p' => $this->llmData['top_p'] ?? 1,
            'frequency_penalty' => $this->llmData['frequency_penalty'] ?? 0,
            'presence_penalty' => $this->llmData['presence_penalty'] ?? 0,
            'response_type' => $this->llmData['response_type'] ?? 'text'
        ];
    }
    
    public function cancelEditingLLMSettings(): void
    {
        $this->editingLLMSettings = false;
        $this->llmSettings = [];
    }
    
    public function saveLLMSettings(): void
    {
        if (!$this->selectedLLM || !$this->editingLLMSettings) return;
        
        // Ensure service is initialized
        if (!$this->service) {
            $this->initializeService();
            if (!$this->service) {
                $this->error = 'Failed to initialize Retell service';
                return;
            }
        }
        
        try {
            $result = $this->service->updateRetellLLM($this->selectedLLM, $this->llmSettings);
            
            if ($result) {
                $this->successMessage = 'LLM settings updated successfully!';
                $this->editingLLMSettings = false;
                Cache::forget("retell_llm_details_{$this->selectedLLM}");
                $this->loadLLMData();
            }
        } catch (\Exception $e) {
            $this->error = 'Failed to update LLM settings: ' . $e->getMessage();
        }
    }
    
    // Webhook Configuration
    public function loadWebhookConfig(): void
    {
        // This would need a new API method to get webhook config
        // For now, we'll use company settings
        $user = auth()->user();
        if ($user && $user->company_id) {
            $company = Company::find($user->company_id);
            if ($company) {
                $this->webhookConfig = [
                    'webhook_url' => $company->retell_webhook_url ?? '',
                    'webhook_secret' => $company->retell_webhook_secret ?? '',
                    'events' => [
                        'call_started' => true,
                        'call_ended' => true,
                        'call_analyzed' => true
                    ]
                ];
            }
        }
    }
    
    public function startEditingWebhook(): void
    {
        $this->loadWebhookConfig();
        $this->editingWebhook = true;
    }
    
    public function cancelEditingWebhook(): void
    {
        $this->editingWebhook = false;
    }
    
    public function saveWebhookConfig(): void
    {
        if (!$this->webhookConfig) return;
        
        $user = auth()->user();
        if ($user && $user->company_id) {
            $company = Company::find($user->company_id);
            if ($company) {
                $company->retell_webhook_url = $this->webhookConfig['webhook_url'];
                $company->retell_webhook_secret = $this->webhookConfig['webhook_secret'];
                $company->save();
                
                $this->successMessage = 'Webhook configuration saved successfully!';
                $this->editingWebhook = false;
            }
        }
    }
}