<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use App\Services\MCP\MCPGateway;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

class RetellWebhookConfiguration extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-link';
    protected static ?string $navigationGroup = 'Integrationen';
    protected static ?string $navigationLabel = 'Retell.ai Webhook';
    protected static ?int $navigationSort = 2;
    
    protected static string $view = 'filament.admin.pages.retell-webhook-configuration';
    
    public ?array $webhookData = [];
    public ?array $customFunctions = [];
    public ?array $testResults = null;
    public bool $isLoading = false;
    
    protected MCPGateway $mcpGateway;
    
    public function boot(): void
    {
        $this->mcpGateway = app(MCPGateway::class);
    }
    
    public function mount(): void
    {
        $this->loadConfiguration();
    }
    
    protected function loadConfiguration(): void
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
            
            // Get custom functions via MCP
            $functionsResponse = $this->mcpGateway->process([
                'jsonrpc' => '2.0',
                'method' => 'retell.config.getCustomFunctions',
                'params' => ['company_id' => $company->id],
                'id' => Str::uuid()->toString(),
            ]);
            
            if (isset($functionsResponse['result']['custom_functions'])) {
                $this->customFunctions = $functionsResponse['result']['custom_functions'];
            }
            
            $this->form->fill([
                'webhook_url' => $this->webhookData['webhook_url'] ?? '',
                'webhook_secret' => $this->webhookData['webhook_secret'] ?? '',
                'webhook_events' => $this->webhookData['webhook_events'] ?? [],
                'custom_functions' => $this->customFunctions,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to load Retell configuration', [
                'error' => $e->getMessage(),
            ]);
            
            Notification::make()
                ->title('Fehler beim Laden der Konfiguration')
                ->danger()
                ->send();
        }
    }
    
    protected function getFormSchema(): array
    {
        return [
            Section::make('Webhook-Konfiguration')
                ->description('Diese Einstellungen müssen in Retell.ai konfiguriert werden')
                ->schema([
                    TextInput::make('webhook_url')
                        ->label('Webhook URL')
                        ->readOnly()
                        ->copyable()
                        ->helperText('Diese URL in Retell.ai als Webhook-Endpunkt eintragen'),
                    
                    TextInput::make('webhook_secret')
                        ->label('Webhook Secret')
                        ->password()
                        ->revealable()
                        ->copyable()
                        ->readOnly()
                        ->helperText('Dieses Secret in Retell.ai konfigurieren')
                        ->suffixAction(
                            Action::make('regenerate')
                                ->icon('heroicon-m-arrow-path')
                                ->requiresConfirmation()
                                ->modalHeading('Secret neu generieren?')
                                ->modalDescription('Das alte Secret wird ungültig. Sie müssen das neue Secret in Retell.ai aktualisieren.')
                                ->action(fn () => $this->regenerateSecret())
                        ),
                    
                    Select::make('webhook_events')
                        ->label('Aktivierte Events')
                        ->multiple()
                        ->options([
                            'call_started' => 'Call Started',
                            'call_ended' => 'Call Ended',
                            'call_analyzed' => 'Call Analyzed',
                        ])
                        ->default(['call_ended'])
                        ->helperText('Welche Events soll Retell.ai senden?'),
                ]),
            
            Section::make('Custom Functions')
                ->description('Funktionen, die während eines Anrufs aufgerufen werden können')
                ->schema([
                    Repeater::make('custom_functions')
                        ->label('')
                        ->schema([
                            TextInput::make('name')
                                ->label('Funktionsname')
                                ->required()
                                ->readOnly()
                                ->columnSpan(2),
                            
                            Toggle::make('enabled')
                                ->label('Aktiviert')
                                ->default(true)
                                ->columnSpan(1),
                            
                            Textarea::make('description')
                                ->label('Beschreibung')
                                ->rows(2)
                                ->columnSpan(3),
                            
                            TextInput::make('url')
                                ->label('URL')
                                ->readOnly()
                                ->copyable()
                                ->default(fn ($get) => url("/api/mcp/gateway/retell/functions/{$get('name')}"))
                                ->columnSpan(3),
                        ])
                        ->columns(3)
                        ->defaultItems(4)
                        ->reorderable(false)
                        ->deletable(false)
                        ->addable(false),
                ]),
        ];
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema($this->getFormSchema())
            ->statePath('data');
    }
    
    public function save(): void
    {
        try {
            $data = $this->form->getState();
            $company = auth()->user()->company;
            
            // Update webhook configuration
            $webhookResponse = $this->mcpGateway->process([
                'jsonrpc' => '2.0',
                'method' => 'retell.config.updateWebhook',
                'params' => [
                    'company_id' => $company->id,
                    'webhook_events' => $data['webhook_events'],
                ],
                'id' => Str::uuid()->toString(),
            ]);
            
            if (isset($webhookResponse['error'])) {
                throw new \Exception($webhookResponse['error']['message'] ?? 'Unknown error');
            }
            
            // Update each custom function
            foreach ($data['custom_functions'] as $function) {
                $functionResponse = $this->mcpGateway->process([
                    'jsonrpc' => '2.0',
                    'method' => 'retell.config.updateCustomFunction',
                    'params' => [
                        'company_id' => $company->id,
                        'function_name' => $function['name'],
                        'enabled' => $function['enabled'],
                        'description' => $function['description'],
                    ],
                    'id' => Str::uuid()->toString(),
                ]);
                
                if (isset($functionResponse['error'])) {
                    Log::warning('Failed to update custom function', [
                        'function' => $function['name'],
                        'error' => $functionResponse['error'],
                    ]);
                }
            }
            
            Notification::make()
                ->title('Konfiguration gespeichert')
                ->success()
                ->send();
            
            $this->loadConfiguration();
            
        } catch (\Exception $e) {
            Log::error('Failed to save Retell configuration', [
                'error' => $e->getMessage(),
            ]);
            
            Notification::make()
                ->title('Fehler beim Speichern')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function regenerateSecret(): void
    {
        try {
            $company = auth()->user()->company;
            
            $response = $this->mcpGateway->process([
                'jsonrpc' => '2.0',
                'method' => 'retell.config.updateWebhook',
                'params' => [
                    'company_id' => $company->id,
                    'regenerate_secret' => true,
                ],
                'id' => Str::uuid()->toString(),
            ]);
            
            if (isset($response['error'])) {
                throw new \Exception($response['error']['message'] ?? 'Unknown error');
            }
            
            Notification::make()
                ->title('Secret neu generiert')
                ->body('Bitte aktualisieren Sie das Secret in Retell.ai')
                ->warning()
                ->send();
            
            $this->loadConfiguration();
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function testWebhook(): void
    {
        try {
            $this->isLoading = true;
            $company = auth()->user()->company;
            
            $response = $this->mcpGateway->process([
                'jsonrpc' => '2.0',
                'method' => 'retell.config.testWebhook',
                'params' => ['company_id' => $company->id],
                'id' => Str::uuid()->toString(),
            ]);
            
            if (isset($response['error'])) {
                throw new \Exception($response['error']['message'] ?? 'Test fehlgeschlagen');
            }
            
            $this->testResults = $response['result'];
            
            if ($this->testResults['success']) {
                Notification::make()
                    ->title('Webhook-Test erfolgreich')
                    ->body("Response Zeit: {$this->testResults['response_time_ms']}ms")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Webhook-Test fehlgeschlagen')
                    ->body($this->testResults['message'] ?? 'Unbekannter Fehler')
                    ->danger()
                    ->send();
            }
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Test fehlgeschlagen')
                ->body($e->getMessage())
                ->danger()
                ->send();
                
            $this->testResults = [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        } finally {
            $this->isLoading = false;
        }
    }
    
    public function deployToRetell(): void
    {
        try {
            $company = auth()->user()->company;
            
            if (!$company->retell_api_key) {
                throw new \Exception('Retell.ai API Key nicht konfiguriert');
            }
            
            $response = $this->mcpGateway->process([
                'jsonrpc' => '2.0',
                'method' => 'retell.config.deployCustomFunctions',
                'params' => ['company_id' => $company->id],
                'id' => Str::uuid()->toString(),
            ]);
            
            if (isset($response['error'])) {
                throw new \Exception($response['error']['message'] ?? 'Deployment fehlgeschlagen');
            }
            
            Notification::make()
                ->title('Erfolgreich deployed')
                ->body('Custom Functions wurden zu Retell.ai übertragen')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Deployment fehlgeschlagen')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function getAgentPromptTemplate(): void
    {
        try {
            $company = auth()->user()->company;
            
            $response = $this->mcpGateway->process([
                'jsonrpc' => '2.0',
                'method' => 'retell.config.getAgentPromptTemplate',
                'params' => ['company_id' => $company->id],
                'id' => Str::uuid()->toString(),
            ]);
            
            if (isset($response['error'])) {
                throw new \Exception($response['error']['message'] ?? 'Fehler beim Abrufen');
            }
            
            // Show prompt in modal
            $this->dispatch('open-modal', id: 'agent-prompt-modal', data: [
                'prompt' => $response['result']['prompt_template'],
                'variables' => $response['result']['variables'],
            ]);
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    #[Computed]
    public function isConfiguredInRetell(): bool
    {
        return $this->webhookData['is_configured_in_retell'] ?? false;
    }
    
    #[Computed]
    public function lastTestStatus(): ?string
    {
        return $this->webhookData['test_status'] ?? null;
    }
    
    #[Computed]
    public function lastTestTime(): ?string
    {
        if (!isset($this->webhookData['last_tested_at'])) {
            return null;
        }
        
        return \Carbon\Carbon::parse($this->webhookData['last_tested_at'])
            ->diffForHumans();
    }
}