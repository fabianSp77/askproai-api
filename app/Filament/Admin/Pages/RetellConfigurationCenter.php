<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action as NotificationAction;
use App\Models\RetellAgent;
use App\Models\PhoneNumber;
use App\Models\RetellWebhook;
use App\Models\Call;
use App\Services\MCP\MCPGateway;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class RetellConfigurationCenter extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;
    
    protected static ?string $navigationIcon = 'heroicon-o-phone-arrow-up-right';
    protected static ?string $navigationGroup = 'Einstellungen';
    protected static ?string $navigationLabel = 'Retell Configuration Center';
    protected static ?int $navigationSort = 2;
    
    public static function shouldRegisterNavigation(): bool
    {
        return false; // Deaktiviert - Use RetellUltimateControlCenter instead
    }
    protected static ?string $title = 'Retell Configuration Center';
    
    protected static string $view = 'filament.admin.pages.retell-configuration-center';
    
    // Dashboard metrics
    public array $metrics = [
        'total_agents' => 0,
        'active_agents' => 0,
        'total_phone_numbers' => 0,
        'connected_phone_numbers' => 0,
        'webhook_health' => 'unknown',
        'last_webhook_time' => null,
        'recent_calls_count' => 0,
        'failed_webhooks_count' => 0,
    ];
    
    // Form data
    public ?array $agentData = [];
    public ?array $webhookData = [];
    public ?array $customFunctions = [];
    
    // UI state
    public bool $showAgentModal = false;
    public bool $showWebhookModal = false;
    public bool $showTestModal = false;
    public ?string $selectedAgentId = null;
    public ?array $testResults = null;
    
    protected MCPGateway $mcpGateway;
    
    public function boot(): void
    {
        $this->mcpGateway = app(MCPGateway::class);
    }
    
    public function mount(): void
    {
        $this->loadDashboardMetrics();
        $this->loadWebhookConfiguration();
    }
    
    protected function loadDashboardMetrics(): void
    {
        try {
            $company = auth()->user()->company;
            
            // Get agent statistics
            $this->metrics['total_agents'] = RetellAgent::where('company_id', $company->id)->count();
            $this->metrics['active_agents'] = RetellAgent::where('company_id', $company->id)
                ->where('active', true)
                ->count();
            
            // Get phone number statistics
            $this->metrics['total_phone_numbers'] = PhoneNumber::where('company_id', $company->id)->count();
            $this->metrics['connected_phone_numbers'] = PhoneNumber::where('company_id', $company->id)
                ->whereNotNull('retell_agent_id')
                ->count();
            
            // Get recent webhook activity
            $latestWebhook = RetellWebhook::where('company_id', $company->id)
                ->latest()
                ->first();
                
            if ($latestWebhook) {
                $this->metrics['last_webhook_time'] = $latestWebhook->created_at;
                $this->metrics['webhook_health'] = $latestWebhook->created_at->diffInMinutes(now()) < 60 ? 'healthy' : 'warning';
            }
            
            // Get recent calls count (last 24 hours)
            $this->metrics['recent_calls_count'] = Call::where('company_id', $company->id)
                ->where('created_at', '>=', now()->subDay())
                ->count();
            
            // Get failed webhooks count (last 24 hours)
            $this->metrics['failed_webhooks_count'] = RetellWebhook::where('company_id', $company->id)
                ->where('created_at', '>=', now()->subDay())
                ->where('status', 'failed')
                ->count();
                
        } catch (\Exception $e) {
            Log::error('Failed to load dashboard metrics', [
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    protected function loadWebhookConfiguration(): void
    {
        try {
            $company = auth()->user()->company;
            
            // Get webhook configuration via MCP
            $webhookResponse = $this->mcpGateway->process([
                'jsonrpc' => '2.0',
                'method' => 'retell.config.getWebhook',
                'params' => ['company_id' => $company->id],
                'id' => Str::uuid()->toString(),
            ]);
            
            if (isset($webhookResponse['result'])) {
                $this->webhookData = $webhookResponse['result'];
            }
            
            // Get custom functions
            $functionsResponse = $this->mcpGateway->process([
                'jsonrpc' => '2.0',
                'method' => 'retell.config.getCustomFunctions',
                'params' => ['company_id' => $company->id],
                'id' => Str::uuid()->toString(),
            ]);
            
            if (isset($functionsResponse['result']['custom_functions'])) {
                $this->customFunctions = $functionsResponse['result']['custom_functions'];
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to load webhook configuration', [
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    // Agent Management Methods
    public function openAgentModal(?string $agentId = null): void
    {
        $this->selectedAgentId = $agentId;
        
        if ($agentId) {
            $agent = RetellAgent::find($agentId);
            if ($agent) {
                $this->agentForm->fill([
                    'name' => $agent->name,
                    'agent_id' => $agent->agent_id,
                    'webhook_url' => $agent->settings['webhook_url'] ?? '',
                    'webhook_events' => $agent->settings['webhook_events'] ?? [],
                    'active' => $agent->active,
                ]);
            }
        } else {
            $this->agentForm->fill([
                'name' => '',
                'agent_id' => '',
                'webhook_url' => url('/api/retell/webhook'),
                'webhook_events' => ['call_ended'],
                'active' => true,
            ]);
        }
        
        $this->showAgentModal = true;
    }
    
    public function saveAgent(): void
    {
        try {
            $data = $this->agentForm->getState();
            $company = auth()->user()->company;
            
            if ($this->selectedAgentId) {
                $agent = RetellAgent::find($this->selectedAgentId);
                $agent->update([
                    'name' => $data['name'],
                    'agent_id' => $data['agent_id'],
                    'settings' => [
                        'webhook_url' => $data['webhook_url'],
                        'webhook_events' => $data['webhook_events'],
                    ],
                    'active' => $data['active'],
                ]);
            } else {
                RetellAgent::create([
                    'company_id' => $company->id,
                    'name' => $data['name'],
                    'agent_id' => $data['agent_id'],
                    'settings' => [
                        'webhook_url' => $data['webhook_url'],
                        'webhook_events' => $data['webhook_events'],
                    ],
                    'active' => $data['active'],
                ]);
            }
            
            Notification::make()
                ->title('Agent saved successfully')
                ->success()
                ->send();
                
            $this->showAgentModal = false;
            $this->loadDashboardMetrics();
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error saving agent')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function testAgent(string $agentId): void
    {
        try {
            $agent = RetellAgent::find($agentId);
            if (!$agent) {
                throw new \Exception('Agent not found');
            }
            
            // Test agent configuration via MCP
            $response = $this->mcpGateway->process([
                'jsonrpc' => '2.0',
                'method' => 'retell.agent.test',
                'params' => [
                    'agent_id' => $agent->agent_id,
                    'company_id' => $agent->company_id,
                ],
                'id' => Str::uuid()->toString(),
            ]);
            
            if (isset($response['error'])) {
                throw new \Exception($response['error']['message'] ?? 'Test failed');
            }
            
            $this->testResults = $response['result'];
            
            Notification::make()
                ->title('Agent test completed')
                ->body($this->testResults['message'] ?? 'Test successful')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Test failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    // Webhook Testing Methods
    public function testWebhook(): void
    {
        try {
            $company = auth()->user()->company;
            
            $response = $this->mcpGateway->process([
                'jsonrpc' => '2.0',
                'method' => 'retell.config.testWebhook',
                'params' => ['company_id' => $company->id],
                'id' => Str::uuid()->toString(),
            ]);
            
            if (isset($response['error'])) {
                throw new \Exception($response['error']['message'] ?? 'Test failed');
            }
            
            $this->testResults = $response['result'];
            
            Notification::make()
                ->title('Webhook test completed')
                ->body("Response time: {$this->testResults['response_time_ms']}ms")
                ->success()
                ->actions([
                    NotificationAction::make('view')
                        ->label('View Details')
                        ->button()
                        ->dispatch('openTestResultsModal'),
                ])
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Webhook test failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function simulateCall(): void
    {
        try {
            $company = auth()->user()->company;
            
            // Create a test call simulation
            $response = $this->mcpGateway->process([
                'jsonrpc' => '2.0',
                'method' => 'retell.test.simulateCall',
                'params' => [
                    'company_id' => $company->id,
                    'from_number' => '+4930123456789',
                    'to_number' => PhoneNumber::where('company_id', $company->id)->first()?->number,
                    'duration' => 120,
                    'test_scenario' => 'appointment_booking',
                ],
                'id' => Str::uuid()->toString(),
            ]);
            
            if (isset($response['error'])) {
                throw new \Exception($response['error']['message'] ?? 'Simulation failed');
            }
            
            Notification::make()
                ->title('Call simulation started')
                ->body('Check the Recent Calls section for the test call')
                ->success()
                ->send();
                
            // Refresh metrics after a delay
            $this->dispatch('refresh-metrics')->delay(2);
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Simulation failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function testCustomFunction(string $functionName): void
    {
        try {
            $company = auth()->user()->company;
            
            $response = $this->mcpGateway->process([
                'jsonrpc' => '2.0',
                'method' => 'retell.customFunction.test',
                'params' => [
                    'company_id' => $company->id,
                    'function_name' => $functionName,
                    'test_params' => $this->getTestParamsForFunction($functionName),
                ],
                'id' => Str::uuid()->toString(),
            ]);
            
            if (isset($response['error'])) {
                throw new \Exception($response['error']['message'] ?? 'Test failed');
            }
            
            Notification::make()
                ->title('Function test completed')
                ->body('Function executed successfully')
                ->success()
                ->actions([
                    NotificationAction::make('view')
                        ->label('View Response')
                        ->button()
                        ->dispatch('showFunctionTestResults', [
                            'function' => $functionName,
                            'results' => $response['result'],
                        ]),
                ])
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Function test failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    // Form Schemas
    protected function getAgentFormSchema(): array
    {
        return [
            Grid::make(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Agent Name')
                        ->required()
                        ->maxLength(255),
                        
                    TextInput::make('agent_id')
                        ->label('Retell Agent ID')
                        ->required()
                        ->placeholder('agent_xxxxxxxxxxxx'),
                ]),
                
            TextInput::make('webhook_url')
                ->label('Webhook URL')
                ->required()
                ->url()
                ->default(url('/api/retell/webhook'))
                ->hint('Click to copy')
                ->extraAttributes(['readonly' => true]),
                
            Select::make('webhook_events')
                ->label('Webhook Events')
                ->multiple()
                ->options([
                    'call_started' => 'Call Started',
                    'call_ended' => 'Call Ended',
                    'call_analyzed' => 'Call Analyzed',
                ])
                ->default(['call_ended']),
                
            Toggle::make('active')
                ->label('Active')
                ->default(true),
        ];
    }
    
    protected function getForms(): array
    {
        return [
            'agentForm' => $this->makeForm()
                ->schema($this->getAgentFormSchema())
                ->statePath('agentData'),
        ];
    }
    
    // Table Configuration
    public function table(Table $table): Table
    {
        return $table
            ->query(RetellWebhook::query()->where('company_id', auth()->user()->company_id)->latest())
            ->columns([
                TextColumn::make('event_type')
                    ->label('Event')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'call_started' => 'info',
                        'call_ended' => 'success',
                        'call_analyzed' => 'warning',
                        default => 'gray',
                    }),
                    
                TextColumn::make('call_id')
                    ->label('Call ID')
                    ->searchable()
                    ->limit(20),
                    
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'failed' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    }),
                    
                TextColumn::make('response_time')
                    ->label('Response Time')
                    ->suffix(' ms')
                    ->numeric()
                    ->color(fn (int $state): string => match (true) {
                        $state < 100 => 'success',
                        $state < 500 => 'warning',
                        default => 'danger',
                    }),
                    
                TextColumn::make('created_at')
                    ->label('Received At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Action::make('view')
                    ->icon('heroicon-m-eye')
                    ->action(fn (RetellWebhook $record) => $this->viewWebhookDetails($record)),
                    
                Action::make('retry')
                    ->icon('heroicon-m-arrow-path')
                    ->visible(fn (RetellWebhook $record) => $record->status === 'failed')
                    ->requiresConfirmation()
                    ->action(fn (RetellWebhook $record) => $this->retryWebhook($record)),
            ])
            ->poll('10s');
    }
    
    // Helper Methods
    protected function getTestParamsForFunction(string $functionName): array
    {
        return match ($functionName) {
            'check_availability' => [
                'date' => now()->addDay()->format('Y-m-d'),
                'time' => '14:00',
                'service' => 'consultation',
            ],
            'book_appointment' => [
                'date' => now()->addDay()->format('Y-m-d'),
                'time' => '14:00',
                'service' => 'consultation',
                'customer_name' => 'Test Customer',
                'customer_phone' => '+4930123456789',
            ],
            'get_business_hours' => [],
            'list_services' => [],
            default => [],
        };
    }
    
    protected function viewWebhookDetails(RetellWebhook $webhook): void
    {
        $this->dispatch('openWebhookDetailsModal', [
            'webhook' => $webhook->toArray(),
            'payload' => $webhook->payload,
            'response' => $webhook->response,
        ]);
    }
    
    protected function retryWebhook(RetellWebhook $webhook): void
    {
        try {
            // Dispatch retry job
            \App\Jobs\ProcessRetellWebhookJob::dispatch($webhook->payload);
            
            $webhook->update([
                'status' => 'pending',
                'retries' => $webhook->retries + 1,
            ]);
            
            Notification::make()
                ->title('Webhook retry initiated')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Retry failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    #[On('refresh-metrics')]
    public function refreshMetrics(): void
    {
        $this->loadDashboardMetrics();
    }
    
    #[Computed]
    public function webhookHealthColor(): string
    {
        return match ($this->metrics['webhook_health']) {
            'healthy' => 'success',
            'warning' => 'warning',
            'error' => 'danger',
            default => 'gray',
        };
    }
    
    #[Computed]
    public function webhookHealthText(): string
    {
        return match ($this->metrics['webhook_health']) {
            'healthy' => 'Healthy',
            'warning' => 'Warning',
            'error' => 'Error',
            default => 'Unknown',
        };
    }
    
    #[Computed]
    public function activeAgents()
    {
        return RetellAgent::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->with('phoneNumber')
            ->get();
    }
    
    #[Computed]
    public function recentCalls()
    {
        return Call::where('company_id', auth()->user()->company_id)
            ->with(['customer', 'appointment'])
            ->latest()
            ->limit(5)
            ->get();
    }
}