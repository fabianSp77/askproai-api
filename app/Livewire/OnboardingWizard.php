<?php

namespace App\Livewire;

use App\Models\Company;
use App\Models\IndustryTemplate;
use App\Models\OnboardingState;
use App\Services\RetellService;
use App\Services\CalcomService;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class OnboardingWizard extends Component
{
    // Current state
    public int $currentStep = 1;
    public int $timeElapsed = 0;
    public bool $isCompleted = false;
    
    // Form data
    public array $companyData = [];
    public array $apiKeys = [];
    public array $aiConfig = [];
    public array $services = [];
    public array $workingHours = [];
    public ?string $selectedTemplate = null;
    
    // UI state
    public bool $showVideo = false;
    public bool $isValidating = false;
    public array $validationStatus = [
        'retell' => null,
        'calcom' => null,
    ];
    
    // Templates and options
    public $industryTemplates;
    public $selectedIndustryTemplate;
    
    protected $rules = [
        'companyData.name' => 'required|min:3',
        'companyData.email' => 'required|email',
        'companyData.phone' => 'required',
        'companyData.address' => 'required',
        'companyData.city' => 'required',
        'companyData.postal_code' => 'required',
        'selectedTemplate' => 'required',
        'apiKeys.retell' => 'required|string',
        'apiKeys.calcom' => 'required|string',
    ];

    protected $messages = [
        'companyData.name.required' => 'Bitte geben Sie Ihren Firmennamen ein.',
        'selectedTemplate.required' => 'Bitte wählen Sie eine Branche aus.',
        'apiKeys.retell.required' => 'Bitte geben Sie Ihren Retell.ai API Key ein.',
        'apiKeys.calcom.required' => 'Bitte geben Sie Ihren Cal.com API Key ein.',
    ];

    public function mount()
    {
        $this->industryTemplates = IndustryTemplate::active()->popular()->get();
        
        // Check if company already has onboarding state
        $company = auth()->user()->company;
        if ($company) {
            $state = $company->onboardingState;
            if ($state && !$state->is_completed) {
                $this->loadFromState($state);
            }
        }
        
        // Start timer
        $this->dispatch('start-timer');
    }

    public function loadFromState(OnboardingState $state)
    {
        $this->currentStep = $state->current_step;
        $this->timeElapsed = $state->time_elapsed;
        $this->selectedTemplate = $state->industry_template;
        
        // Load saved data
        if ($stateData = $state->state_data) {
            $this->companyData = $stateData['companyData'] ?? [];
            $this->apiKeys = $stateData['apiKeys'] ?? [];
            $this->aiConfig = $stateData['aiConfig'] ?? [];
            $this->services = $stateData['services'] ?? [];
            $this->workingHours = $stateData['workingHours'] ?? [];
        }
    }

    public function selectTemplate(string $templateSlug)
    {
        $this->selectedTemplate = $templateSlug;
        $this->selectedIndustryTemplate = IndustryTemplate::where('slug', $templateSlug)->first();
        
        if ($this->selectedIndustryTemplate) {
            // Pre-fill services and hours from template
            $this->services = $this->selectedIndustryTemplate->default_services;
            $this->workingHours = $this->selectedIndustryTemplate->default_hours;
            $this->aiConfig = $this->selectedIndustryTemplate->ai_personality;
        }
    }

    public function validateApiKey(string $type)
    {
        $this->isValidating = true;
        
        try {
            if ($type === 'retell' && !empty($this->apiKeys['retell'])) {
                // Test Retell API
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKeys['retell'],
                ])->get('https://api.retellai.com/v2/agents');
                
                $this->validationStatus['retell'] = $response->successful() ? 'valid' : 'invalid';
            }
            
            if ($type === 'calcom' && !empty($this->apiKeys['calcom'])) {
                // Test Cal.com API
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKeys['calcom'],
                ])->get('https://api.cal.com/v1/me');
                
                $this->validationStatus['calcom'] = $response->successful() ? 'valid' : 'invalid';
            }
        } catch (\Exception $e) {
            $this->validationStatus[$type] = 'invalid';
        }
        
        $this->isValidating = false;
    }

    public function nextStep()
    {
        // Validate current step
        $this->validateStep();
        
        if ($this->currentStep < 7) {
            $this->currentStep++;
            $this->saveProgress();
            $this->dispatch('step-changed', $this->currentStep);
        }
    }

    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
            $this->dispatch('step-changed', $this->currentStep);
        }
    }

    public function validateStep()
    {
        switch ($this->currentStep) {
            case 2: // Company data
                $this->validate([
                    'companyData.name' => 'required|min:3',
                    'companyData.email' => 'required|email',
                    'companyData.phone' => 'required',
                ]);
                break;
            case 3: // API Keys
                $this->validate([
                    'apiKeys.retell' => 'required',
                    'apiKeys.calcom' => 'required',
                ]);
                break;
        }
    }

    public function saveProgress()
    {
        $company = auth()->user()->company;
        if (!$company) return;
        
        $state = $company->onboardingState ?? new OnboardingState(['company_id' => $company->id]);
        
        $state->current_step = $this->currentStep;
        $state->time_elapsed = $this->timeElapsed;
        $state->industry_template = $this->selectedTemplate;
        $state->state_data = [
            'companyData' => $this->companyData,
            'apiKeys' => $this->apiKeys,
            'aiConfig' => $this->aiConfig,
            'services' => $this->services,
            'workingHours' => $this->workingHours,
        ];
        
        $state->completeStep($this->currentStep - 1);
        $state->save();
    }

    public function testCall()
    {
        $this->dispatch('start-test-call');
        
        // Simulate test call
        sleep(2);
        
        $this->dispatch('test-call-complete', [
            'transcript' => 'Agent: Guten Tag, Sie haben die Praxis ' . $this->companyData['name'] . ' erreicht. Wie kann ich Ihnen helfen?',
            'duration' => '0:45',
        ]);
    }

    public function completeOnboarding()
    {
        DB::transaction(function () {
            $company = auth()->user()->company;
            
            // Save API keys
            $company->retell_api_key = encrypt($this->apiKeys['retell']);
            $company->calcom_api_key = encrypt($this->apiKeys['calcom']);
            $company->save();
            
            // Apply template
            if ($this->selectedIndustryTemplate) {
                $this->selectedIndustryTemplate->applyToCompany($company);
            }
            
            // Mark onboarding as complete
            $state = $company->onboardingState;
            $state->markAsCompleted();
            
            // Dispatch background jobs
            // SetupRetellAgentJob::dispatch($company, $this->aiConfig);
            // SyncCalcomEventsJob::dispatch($company);
            
            $this->isCompleted = true;
        });
        
        // Show confetti
        $this->dispatch('show-confetti');
        
        // Redirect after 3 seconds
        $this->dispatch('redirect-to-dashboard')->delay(3000);
    }

    public function updateTimer($seconds)
    {
        $this->timeElapsed = $seconds;
    }

    public function render()
    {
        return view('livewire.onboarding-wizard', [
            'industryTemplates' => $this->industryTemplates,
            'timeRemaining' => max(0, 300 - $this->timeElapsed),
            'progressPercentage' => round(($this->currentStep / 7) * 100),
        ]);
    }
}