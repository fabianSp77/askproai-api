<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use App\Services\MCP\MCPOrchestrator;
use App\Services\MCP\MCPRequest;

class MCPTestPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationLabel = 'MCP Test Console';
    protected static ?string $navigationGroup = 'System & Überwachung';
    protected static ?int $navigationSort = 101;
    protected static ?string $slug = 'mcp-test';
    protected static string $view = 'filament.admin.pages.mcp-test';
    
    public ?array $data = [];
    public ?string $response = null;
    public array $availableServices = [
        'webhook' => 'Webhook Service',
        'calcom' => 'Cal.com Service', 
        'database' => 'Database Service',
        'queue' => 'Queue Service',
        'retell' => 'Retell Service',
        'stripe' => 'Stripe Service',
    ];
    
    public function mount(): void
    {
        $this->form->fill();
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('service')
                    ->label('MCP Service')
                    ->options($this->availableServices)
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn () => $this->updateOperations()),
                    
                Select::make('operation')
                    ->label('Operation')
                    ->options(fn () => $this->getOperationsForService($this->data['service'] ?? null))
                    ->required()
                    ->visible(fn () => !empty($this->data['service'])),
                    
                Textarea::make('params')
                    ->label('Parameters (JSON)')
                    ->default('{}')
                    ->helperText('Enter parameters as JSON object')
                    ->visible(fn () => !empty($this->data['operation'])),
            ])
            ->statePath('data');
    }
    
    protected function getOperationsForService(?string $service): array
    {
        return match ($service) {
            'webhook' => [
                'getWebhookStats' => 'Get Webhook Statistics',
                'processRetellWebhook' => 'Process Test Webhook',
            ],
            'calcom' => [
                'getEventTypes' => 'Get Event Types',
                'testConnection' => 'Test Connection',
                'getBookings' => 'Get Recent Bookings',
            ],
            'database' => [
                'getSchema' => 'Get Database Schema',
                'getCallStats' => 'Get Call Statistics',
                'getTenantStats' => 'Get Tenant Statistics',
            ],
            'queue' => [
                'getOverview' => 'Get Queue Overview',
                'getFailedJobs' => 'Get Failed Jobs',
                'getWorkers' => 'Get Active Workers',
            ],
            'retell' => [
                'healthCheck' => 'Health Check',
                'getCallStats' => 'Get Call Statistics',
                'testConnection' => 'Test Connection',
            ],
            'stripe' => [
                'getPaymentOverview' => 'Get Payment Overview',
                'generateReport' => 'Generate Financial Report',
            ],
            default => [],
        };
    }
    
    public function updateOperations(): void
    {
        $this->data['operation'] = null;
        $this->data['params'] = '{}';
    }
    
    public function executeTest(): void
    {
        try {
            $params = json_decode($this->data['params'] ?? '{}', true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON in parameters');
            }
            
            // Add default company_id if not provided
            if (!isset($params['company_id']) && auth()->user()->company_id) {
                $params['company_id'] = auth()->user()->company_id;
            }
            
            $orchestrator = app(MCPOrchestrator::class);
            
            $request = new MCPRequest(
                service: $this->data['service'],
                operation: $this->data['operation'],
                params: $params,
                tenantId: auth()->user()->company_id ?? 1
            );
            
            $response = $orchestrator->route($request);
            
            $this->response = json_encode([
                'success' => $response->isSuccess(),
                'data' => $response->getData(),
                'error' => $response->getError(),
                'metadata' => $response->getMetadata(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            
            Notification::make()
                ->title('MCP Test Executed')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            $this->response = json_encode([
                'error' => $e->getMessage(),
                'trace' => app()->hasDebugModeEnabled() ? $e->getTraceAsString() : null,
            ], JSON_PRETTY_PRINT);
            
            Notification::make()
                ->title('MCP Test Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function clearResponse(): void
    {
        $this->response = null;
    }
}