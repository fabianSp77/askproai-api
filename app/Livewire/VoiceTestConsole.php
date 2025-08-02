<?php

namespace App\Livewire;

use App\Models\Company;
use App\Models\Call;
use App\Services\MCP\RetellAIBridgeMCPServer;
use Livewire\Component;
use Livewire\Attributes\On;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

class VoiceTestConsole extends Component implements HasForms
{
    use InteractsWithForms;

    public ?string $testPhoneNumber = null;
    public ?string $agentId = null;
    public ?string $testScenario = 'greeting';
    public ?string $customPrompt = null;
    public bool $autoRefresh = true;
    public ?string $activeCallId = null;
    public ?array $callDetails = null;
    public array $testHistory = [];
    public bool $showTranscript = false;

    public function mount(): void
    {
        // Set default test number (user's phone if available)
        $this->testPhoneNumber = auth()->user()->phone ?? '';
        
        // Set default agent
        $company = Company::find(auth()->user()->company_id);
        if ($company && $company->retell_agent_id) {
            $this->agentId = $company->retell_agent_id;
        }
        
        // Load test history
        $this->loadTestHistory();
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Test Configuration')
                ->description('Configure and initiate a test call to validate your AI agent settings')
                ->schema([
                    TextInput::make('testPhoneNumber')
                        ->label('Test Phone Number')
                        ->tel()
                        ->required()
                        ->placeholder('+49 123 456789')
                        ->helperText('Phone number that will receive the test call'),
                    
                    Select::make('agentId')
                        ->label('AI Agent')
                        ->options($this->getAvailableAgents())
                        ->required()
                        ->searchable()
                        ->helperText('Select the agent configuration to test'),
                    
                    Select::make('testScenario')
                        ->label('Test Scenario')
                        ->options([
                            'greeting' => 'Basic Greeting Test',
                            'appointment_booking' => 'Appointment Booking Flow',
                            'information_request' => 'Information Request',
                            'objection_handling' => 'Objection Handling',
                            'multilingual' => 'Language Detection & Switch',
                            'error_recovery' => 'Error Recovery Test',
                            'custom' => 'Custom Scenario',
                        ])
                        ->reactive()
                        ->required()
                        ->helperText('Pre-configured test scenarios'),
                    
                    Textarea::make('customPrompt')
                        ->label('Custom Test Prompt')
                        ->visible(fn () => $this->testScenario === 'custom')
                        ->rows(3)
                        ->placeholder('Describe the specific scenario you want to test...')
                        ->helperText('Provide instructions for the test scenario'),
                    
                    Toggle::make('autoRefresh')
                        ->label('Auto-refresh call status')
                        ->default(true)
                        ->helperText('Automatically update call status every 2 seconds'),
                ]),
        ];
    }

    public function initiateTestCall(): void
    {
        $this->validate([
            'testPhoneNumber' => 'required',
            'agentId' => 'required',
            'testScenario' => 'required',
            'customPrompt' => $this->testScenario === 'custom' ? 'required' : 'nullable',
        ]);

        try {
            $bridgeServer = app(RetellAIBridgeMCPServer::class);
            
            // Build test parameters based on scenario
            $testParams = $this->buildTestParameters();
            
            $result = $bridgeServer->testVoiceConfiguration([
                'company_id' => auth()->user()->company_id,
                'agent_id' => $this->agentId,
                'test_number' => $this->testPhoneNumber,
                'test_scenario' => $this->testScenario,
                'dynamic_variables' => $testParams,
            ]);
            
            // Store active call ID
            $this->activeCallId = $result['call_id'];
            
            // Add to test history
            $this->addToTestHistory([
                'call_id' => $result['call_id'],
                'scenario' => $this->testScenario,
                'started_at' => now()->toISOString(),
                'phone_number' => $this->testPhoneNumber,
            ]);
            
            Notification::make()
                ->title('Test Call Initiated')
                ->body('Test call to ' . $this->testPhoneNumber . ' has been started. Monitor the progress below.')
                ->success()
                ->send();
            
            // Start monitoring the call
            if ($this->autoRefresh) {
                $this->dispatch('start-call-monitoring');
            }
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Test Call Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    #[On('refresh-call-status')]
    public function refreshCallStatus(): void
    {
        if (!$this->activeCallId) {
            return;
        }

        $call = Call::find($this->activeCallId);
        
        if ($call) {
            $this->callDetails = [
                'id' => $call->id,
                'status' => $call->status,
                'duration' => $call->duration_sec,
                'from_number' => $call->from_number,
                'to_number' => $call->to_number,
                'started_at' => $call->created_at,
                'transcript' => $call->transcript,
                'recording_url' => $call->recording_url,
                'analysis' => $call->metadata['analysis'] ?? null,
            ];
            
            // Stop auto-refresh if call is completed
            if (in_array($call->status, ['completed', 'failed', 'no-answer'])) {
                $this->autoRefresh = false;
                $this->dispatch('stop-call-monitoring');
                
                // Analyze test results
                $this->analyzeTestResults();
            }
        }
    }

    public function stopTest(): void
    {
        $this->autoRefresh = false;
        $this->dispatch('stop-call-monitoring');
        
        Notification::make()
            ->title('Test Stopped')
            ->body('Call monitoring has been stopped.')
            ->warning()
            ->send();
    }

    public function clearTest(): void
    {
        $this->activeCallId = null;
        $this->callDetails = null;
        $this->showTranscript = false;
    }

    protected function buildTestParameters(): array
    {
        $params = [
            'test_mode' => true,
            'test_scenario' => $this->testScenario,
            'tester_name' => auth()->user()->name,
            'timestamp' => now()->toISOString(),
        ];
        
        // Add scenario-specific parameters
        switch ($this->testScenario) {
            case 'appointment_booking':
                $params['test_service'] = 'Haircut';
                $params['test_date'] = 'next Monday';
                $params['test_time'] = '2 PM';
                break;
                
            case 'information_request':
                $params['test_questions'] = [
                    'What are your opening hours?',
                    'Do you offer weekend appointments?',
                    'What services do you provide?',
                ];
                break;
                
            case 'objection_handling':
                $params['test_objections'] = [
                    'I\'m too busy right now',
                    'Your prices are too high',
                    'I need to think about it',
                ];
                break;
                
            case 'multilingual':
                $params['test_languages'] = ['English', 'German', 'Spanish'];
                break;
                
            case 'custom':
                $params['custom_prompt'] = $this->customPrompt;
                break;
        }
        
        return $params;
    }

    protected function analyzeTestResults(): void
    {
        if (!$this->callDetails || !$this->callDetails['transcript']) {
            return;
        }
        
        $analysis = [
            'duration' => $this->callDetails['duration'],
            'completed' => $this->callDetails['status'] === 'completed',
            'scenario_success' => false,
            'issues_found' => [],
            'recommendations' => [],
        ];
        
        // Analyze based on scenario
        switch ($this->testScenario) {
            case 'greeting':
                $analysis['scenario_success'] = str_contains(
                    strtolower($this->callDetails['transcript']), 
                    strtolower(auth()->user()->company->name)
                );
                if (!$analysis['scenario_success']) {
                    $analysis['issues_found'][] = 'Company name not mentioned in greeting';
                    $analysis['recommendations'][] = 'Update agent prompt to include company name';
                }
                break;
                
            case 'appointment_booking':
                $requiredElements = ['date', 'time', 'service'];
                foreach ($requiredElements as $element) {
                    if (!str_contains(strtolower($this->callDetails['transcript']), $element)) {
                        $analysis['issues_found'][] = "Missing {$element} confirmation";
                    }
                }
                $analysis['scenario_success'] = empty($analysis['issues_found']);
                break;
        }
        
        // Store analysis
        $this->callDetails['test_analysis'] = $analysis;
        
        // Cache the analysis for the test history
        Cache::put("voice_test_analysis:{$this->activeCallId}", $analysis, 86400); // 24 hours
    }

    protected function loadTestHistory(): void
    {
        $this->testHistory = Cache::get(
            "voice_test_history:" . auth()->user()->company_id, 
            []
        );
        
        // Keep only last 10 tests
        $this->testHistory = array_slice($this->testHistory, -10);
    }

    protected function addToTestHistory(array $test): void
    {
        $this->testHistory[] = $test;
        
        // Keep only last 10 tests
        $this->testHistory = array_slice($this->testHistory, -10);
        
        Cache::put(
            "voice_test_history:" . auth()->user()->company_id, 
            $this->testHistory, 
            86400 // 24 hours
        );
    }

    protected function getAvailableAgents(): array
    {
        $company = Company::find(auth()->user()->company_id);
        
        if (!$company || !$company->retell_agent_id) {
            return [];
        }
        
        // TODO: Fetch multiple agents from Retell API
        return [
            $company->retell_agent_id => 'Default Agent',
        ];
    }

    public function render()
    {
        return view('livewire.voice-test-console');
    }
}