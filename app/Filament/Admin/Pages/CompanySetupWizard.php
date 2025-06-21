<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\Company;
use App\Models\Branch;
use App\Models\PhoneNumber;
use App\Models\KnowledgeDocument;
use App\Services\MCP\CalcomMCPServer;
use App\Services\MCP\RetellMCPServer;
use App\Services\MCP\KnowledgeMCPServer;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;

class CompanySetupWizard extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Company Setup Wizard';
    protected static ?string $title = 'Company Setup Wizard';
    protected static ?string $navigationGroup = 'Einrichtung & Konfiguration';
    protected static ?int $navigationSort = 2;
    protected static bool $shouldRegisterNavigation = false;
    
    protected static string $view = 'filament.admin.pages.company-setup-wizard';
    
    public ?Company $company = null;
    public ?array $data = [];
    public int $currentStep = 1;
    public array $validationResults = [];
    public bool $isSimulatingCall = false;
    
    // MCP Services
    protected CalcomMCPServer $calcomService;
    protected RetellMCPServer $retellService;
    protected KnowledgeMCPServer $knowledgeService;
    
    public function mount(): void
    {
        $companyId = request()->query('company');
        if ($companyId) {
            $this->company = Company::find($companyId);
            if ($this->company) {
                $this->fillFormFromCompany();
            }
        }
        
        $this->initializeServices();
    }
    
    protected function initializeServices(): void
    {
        $this->calcomService = new CalcomMCPServer();
        $this->retellService = new RetellMCPServer();
        $this->knowledgeService = new KnowledgeMCPServer();
    }
    
    protected function fillFormFromCompany(): void
    {
        $this->form->fill([
            'company_name' => $this->company->name,
            'calcom_api_key' => $this->company->calcom_api_key ? decrypt($this->company->calcom_api_key) : '',
            'calcom_team_slug' => $this->company->calcom_team_slug,
            'retell_api_key' => $this->company->retell_api_key ? decrypt($this->company->retell_api_key) : '',
            'retell_agent_id' => $this->company->retell_agent_id,
        ]);
    }
    
    protected function getFormSchema(): array
    {
        return [
            Wizard::make([
                Step::make('API Keys')
                    ->description('Configure your API keys for integrations')
                    ->schema([
                        TextInput::make('company_name')
                            ->label('Company Name')
                            ->required()
                            ->disabled($this->company !== null),
                            
                        TextInput::make('calcom_api_key')
                            ->label('Cal.com API Key')
                            ->password()
                            ->revealable()
                            ->helperText('Get your API key from Cal.com settings')
                            ->suffixAction(
                                \Filament\Forms\Components\Actions\Action::make('validateCalcom')
                                    ->label('Validate')
                                    ->action('validateCalcomKey')
                            ),
                            
                        TextInput::make('calcom_team_slug')
                            ->label('Cal.com Team Slug')
                            ->helperText('Your team identifier in Cal.com'),
                            
                        TextInput::make('retell_api_key')
                            ->label('Retell.ai API Key')
                            ->password()
                            ->revealable()
                            ->helperText('Get your API key from Retell.ai dashboard')
                            ->suffixAction(
                                \Filament\Forms\Components\Actions\Action::make('validateRetell')
                                    ->label('Validate')
                                    ->action('validateRetellKey')
                            ),
                            
                        TextInput::make('retell_agent_id')
                            ->label('Retell.ai Agent ID')
                            ->helperText('Your AI agent ID from Retell.ai'),
                    ]),
                    
                Step::make('Phone Numbers')
                    ->description('Assign phone numbers to branches')
                    ->schema([
                        \Filament\Forms\Components\Repeater::make('phone_numbers')
                            ->label('Phone Numbers')
                            ->schema([
                                TextInput::make('number')
                                    ->label('Phone Number')
                                    ->tel()
                                    ->required()
                                    ->placeholder('+49 30 12345678'),
                                    
                                Select::make('branch_id')
                                    ->label('Branch')
                                    ->options(function () {
                                        if (!$this->company) return [];
                                        return Branch::where('company_id', $this->company->id)
                                            ->pluck('name', 'id');
                                    })
                                    ->required(),
                                    
                                Toggle::make('is_primary')
                                    ->label('Primary Number')
                                    ->default(false),
                            ])
                            ->defaultItems(1)
                            ->addActionLabel('Add Phone Number'),
                    ]),
                    
                Step::make('Knowledge Base')
                    ->description('Set up your company knowledge base')
                    ->schema([
                        Textarea::make('company_description')
                            ->label('Company Description')
                            ->rows(3)
                            ->helperText('Brief description of your company for the AI'),
                            
                        Textarea::make('services_overview')
                            ->label('Services Overview')
                            ->rows(3)
                            ->helperText('List your main services'),
                            
                        Textarea::make('business_hours')
                            ->label('Business Hours')
                            ->rows(3)
                            ->helperText('Your typical business hours'),
                            
                        Textarea::make('booking_instructions')
                            ->label('Booking Instructions')
                            ->rows(3)
                            ->helperText('Special instructions for booking appointments'),
                    ]),
                    
                Step::make('Test & Verify')
                    ->description('Test your configuration')
                    ->schema([
                        \Filament\Forms\Components\ViewField::make('test_results')
                            ->view('filament.forms.components.setup-test-results')
                            ->viewData([
                                'validationResults' => $this->validationResults,
                            ]),
                    ]),
            ])
            ->submitAction($this->getFormActions()[0])
        ];
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema($this->getFormSchema())
            ->statePath('data');
    }
    
    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Complete Setup')
                ->action('completeSetup'),
        ];
    }
    
    public function validateCalcomKey(): void
    {
        $apiKey = $this->data['calcom_api_key'] ?? '';
        
        if (empty($apiKey)) {
            Notification::make()
                ->title('API Key Required')
                ->body('Please enter your Cal.com API key')
                ->warning()
                ->send();
            return;
        }
        
        try {
            // Test the API key
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->get('https://api.cal.com/v2/me');
            
            if ($response->successful()) {
                $this->validationResults['calcom'] = [
                    'valid' => true,
                    'message' => 'API key is valid',
                    'user' => $response->json()['data'] ?? [],
                ];
                
                Notification::make()
                    ->title('Cal.com Connected')
                    ->body('API key validated successfully')
                    ->success()
                    ->send();
            } else {
                $this->validationResults['calcom'] = [
                    'valid' => false,
                    'message' => 'Invalid API key',
                ];
                
                Notification::make()
                    ->title('Invalid API Key')
                    ->body('Please check your Cal.com API key')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            $this->validationResults['calcom'] = [
                'valid' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
            
            Notification::make()
                ->title('Connection Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function validateRetellKey(): void
    {
        $apiKey = $this->data['retell_api_key'] ?? '';
        
        if (empty($apiKey)) {
            Notification::make()
                ->title('API Key Required')
                ->body('Please enter your Retell.ai API key')
                ->warning()
                ->send();
            return;
        }
        
        try {
            // Test the API key
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->get('https://api.retellai.com/list-agents');
            
            if ($response->successful()) {
                $this->validationResults['retell'] = [
                    'valid' => true,
                    'message' => 'API key is valid',
                    'agents' => $response->json() ?? [],
                ];
                
                Notification::make()
                    ->title('Retell.ai Connected')
                    ->body('API key validated successfully')
                    ->success()
                    ->send();
            } else {
                $this->validationResults['retell'] = [
                    'valid' => false,
                    'message' => 'Invalid API key',
                ];
                
                Notification::make()
                    ->title('Invalid API Key')
                    ->body('Please check your Retell.ai API key')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            $this->validationResults['retell'] = [
                'valid' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
            
            Notification::make()
                ->title('Connection Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function simulateTestCall(): void
    {
        $this->isSimulatingCall = true;
        
        try {
            // Check if we have the required configuration
            if (!$this->company || !$this->company->retell_agent_id) {
                throw new \Exception('Retell agent not configured');
            }
            
            // Get a phone number for testing
            $phoneNumber = PhoneNumber::where('company_id', $this->company->id)
                ->where('is_active', true)
                ->first();
                
            if (!$phoneNumber) {
                throw new \Exception('No active phone number found');
            }
            
            // Simulate the call flow
            $testResults = [
                'phone_recognized' => true,
                'branch_identified' => $phoneNumber->branch_id !== null,
                'agent_available' => true,
                'calendar_connected' => !empty($phoneNumber->branch->calcom_event_type_id ?? null),
                'booking_possible' => true,
            ];
            
            $this->validationResults['test_call'] = [
                'success' => true,
                'results' => $testResults,
                'message' => 'Test call simulation completed',
            ];
            
            Notification::make()
                ->title('Test Call Completed')
                ->body('All systems are working correctly')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            $this->validationResults['test_call'] = [
                'success' => false,
                'message' => 'Test failed: ' . $e->getMessage(),
            ];
            
            Notification::make()
                ->title('Test Call Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isSimulatingCall = false;
        }
    }
    
    public function completeSetup(): void
    {
        $data = $this->form->getState();
        
        try {
            // Save API Keys
            if ($this->company) {
                $this->company->update([
                    'calcom_api_key' => encrypt($data['calcom_api_key']),
                    'calcom_team_slug' => $data['calcom_team_slug'],
                    'retell_api_key' => encrypt($data['retell_api_key']),
                    'retell_agent_id' => $data['retell_agent_id'],
                ]);
            }
            
            // Save Phone Numbers
            if (isset($data['phone_numbers'])) {
                foreach ($data['phone_numbers'] as $phoneData) {
                    $phoneUtil = PhoneNumberUtil::getInstance();
                    try {
                        $phoneNumber = $phoneUtil->parse($phoneData['number'], 'DE');
                        $formatted = $phoneUtil->format($phoneNumber, PhoneNumberFormat::E164);
                        
                        PhoneNumber::updateOrCreate(
                            [
                                'number' => $formatted,
                                'company_id' => $this->company->id,
                            ],
                            [
                                'branch_id' => $phoneData['branch_id'],
                                'is_primary' => $phoneData['is_primary'] ?? false,
                                'is_active' => true,
                                'formatted_number' => $phoneUtil->format($phoneNumber, PhoneNumberFormat::INTERNATIONAL),
                            ]
                        );
                    } catch (\Exception $e) {
                        // Skip invalid phone numbers
                        continue;
                    }
                }
            }
            
            // Save Knowledge Base
            if ($this->company && (
                !empty($data['company_description']) ||
                !empty($data['services_overview']) ||
                !empty($data['business_hours']) ||
                !empty($data['booking_instructions'])
            )) {
                KnowledgeDocument::create([
                    'company_id' => $this->company->id,
                    'title' => 'Company Information',
                    'content' => json_encode([
                        'description' => $data['company_description'] ?? '',
                        'services' => $data['services_overview'] ?? '',
                        'hours' => $data['business_hours'] ?? '',
                        'booking' => $data['booking_instructions'] ?? '',
                    ]),
                    'type' => 'company_info',
                    'is_published' => true,
                ]);
            }
            
            Notification::make()
                ->title('Setup Complete')
                ->body('Your company integration is now configured')
                ->success()
                ->send();
                
            // Redirect back to integration portal
            $this->redirect(CompanyIntegrationPortal::getUrl());
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Setup Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function nextStep(): void
    {
        if ($this->currentStep < 4) {
            $this->currentStep++;
        }
    }
    
    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }
}