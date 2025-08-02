<?php

namespace App\Livewire;

use App\Models\Company;
use App\Models\Customer;
use App\Services\MCP\RetellAIBridgeMCPServer;
use Livewire\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;

class CallInitiatorWidget extends Component implements HasForms
{
    use InteractsWithForms;

    public ?string $phoneNumber = null;
    public ?string $agentId = null;
    public ?string $purpose = 'custom';
    public ?string $customMessage = null;
    public array $variables = [];
    public ?int $customerId = null;
    public bool $isCompact = false;
    public ?string $defaultPurpose = null;

    public function mount(
        ?int $customerId = null, 
        bool $isCompact = false, 
        ?string $defaultPurpose = null
    ): void
    {
        $this->customerId = $customerId;
        $this->isCompact = $isCompact;
        $this->defaultPurpose = $defaultPurpose;
        
        if ($this->customerId) {
            $customer = Customer::find($this->customerId);
            if ($customer) {
                $this->phoneNumber = $customer->phone;
                $this->variables['customer_name'] = $customer->full_name;
                $this->variables['customer_email'] = $customer->email;
            }
        }
        
        if ($this->defaultPurpose) {
            $this->purpose = $this->defaultPurpose;
        }
        
        // Set default agent
        $company = Company::find(auth()->user()->company_id);
        if ($company && $company->retell_agent_id) {
            $this->agentId = $company->retell_agent_id;
        }
    }

    protected function getFormSchema(): array
    {
        $schema = [];
        
        if (!$this->customerId) {
            $schema[] = TextInput::make('phoneNumber')
                ->label('Phone Number')
                ->tel()
                ->required()
                ->placeholder('+49 123 456789');
        }
        
        if (!$this->isCompact) {
            $schema[] = Select::make('agentId')
                ->label('AI Agent')
                ->options($this->getAvailableAgents())
                ->required()
                ->default($this->agentId);
        }
        
        $schema[] = Select::make('purpose')
            ->label('Call Purpose')
            ->options([
                'follow_up' => 'Follow-up Call',
                'appointment_reminder' => 'Appointment Reminder',
                'feedback_collection' => 'Feedback Collection',
                'no_show_follow_up' => 'No-Show Follow-up',
                'birthday_wishes' => 'Birthday Wishes',
                'custom' => 'Custom Message',
            ])
            ->reactive()
            ->required()
            ->default($this->purpose);
        
        if (!$this->isCompact || $this->purpose === 'custom') {
            $schema[] = Textarea::make('customMessage')
                ->label('Message/Instructions')
                ->placeholder('Provide specific instructions for the AI agent...')
                ->rows($this->isCompact ? 2 : 3)
                ->visible(fn () => $this->purpose === 'custom')
                ->required(fn () => $this->purpose === 'custom');
        }
        
        return $schema;
    }

    public function initiateCall(): void
    {
        $this->validate([
            'phoneNumber' => $this->customerId ? 'nullable' : 'required',
            'agentId' => $this->isCompact ? 'nullable' : 'required',
            'purpose' => 'required',
            'customMessage' => $this->purpose === 'custom' ? 'required' : 'nullable',
        ]);

        try {
            $bridgeServer = app(RetellAIBridgeMCPServer::class);
            
            // Prepare call parameters
            $params = [
                'company_id' => auth()->user()->company_id,
                'to_number' => $this->phoneNumber,
                'agent_id' => $this->agentId,
                'purpose' => $this->purpose,
                'dynamic_variables' => $this->buildDynamicVariables(),
            ];
            
            if ($this->customerId) {
                $params['customer_id'] = $this->customerId;
            }
            
            // Create the call
            $result = $bridgeServer->createOutboundCall($params);
            
            // Show success notification
            Notification::make()
                ->title('Call Initiated Successfully')
                ->body($this->getSuccessMessage())
                ->success()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->button()
                        ->url('/admin/calls/' . $result['call_id'])
                        ->openUrlInNewTab(),
                ])
                ->send();
            
            // Emit event for parent components
            $this->dispatch('call-initiated', [
                'callId' => $result['call_id'],
                'retellCallId' => $result['retell_call_id'],
                'toNumber' => $this->phoneNumber,
            ]);
            
            // Reset form if not in compact mode
            if (!$this->isCompact && !$this->customerId) {
                $this->reset(['phoneNumber', 'customMessage']);
                $this->purpose = 'custom';
            }
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to Initiate Call')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    protected function buildDynamicVariables(): array
    {
        $variables = $this->variables;
        
        // Add purpose-specific variables
        switch ($this->purpose) {
            case 'appointment_reminder':
                // Fetch next appointment if customer exists
                if ($this->customerId) {
                    $customer = Customer::find($this->customerId);
                    $nextAppointment = $customer->appointments()
                        ->where('scheduled_at', '>', now())
                        ->orderBy('scheduled_at')
                        ->first();
                    
                    if ($nextAppointment) {
                        $variables['appointment_date'] = $nextAppointment->scheduled_at->format('l, F j');
                        $variables['appointment_time'] = $nextAppointment->scheduled_at->format('g:i A');
                        $variables['service_name'] = $nextAppointment->service->name ?? 'your appointment';
                    }
                }
                break;
                
            case 'no_show_follow_up':
                if ($this->customerId) {
                    $customer = Customer::find($this->customerId);
                    $lastNoShow = $customer->appointments()
                        ->where('status', 'no_show')
                        ->orderBy('scheduled_at', 'desc')
                        ->first();
                    
                    if ($lastNoShow) {
                        $variables['missed_date'] = $lastNoShow->scheduled_at->format('F j');
                        $variables['service_name'] = $lastNoShow->service->name ?? 'your appointment';
                    }
                }
                break;
                
            case 'custom':
                $variables['custom_message'] = $this->customMessage;
                break;
        }
        
        // Add general context
        $variables['company_name'] = auth()->user()->company->name;
        $variables['current_date'] = now()->format('l, F j');
        $variables['current_time'] = now()->format('g:i A');
        
        return $variables;
    }

    protected function getAvailableAgents(): array
    {
        $company = Company::find(auth()->user()->company_id);
        
        if (!$company || !$company->retell_agent_id) {
            return [];
        }
        
        // For now, return the default agent
        // TODO: Fetch multiple agents from Retell API
        return [
            $company->retell_agent_id => 'Default Agent',
        ];
    }

    protected function getSuccessMessage(): string
    {
        $messages = [
            'follow_up' => 'Follow-up call initiated to ' . $this->phoneNumber,
            'appointment_reminder' => 'Appointment reminder call initiated',
            'feedback_collection' => 'Feedback collection call initiated',
            'no_show_follow_up' => 'No-show follow-up call initiated',
            'birthday_wishes' => 'Birthday wishes call initiated',
            'custom' => 'Custom call initiated to ' . $this->phoneNumber,
        ];
        
        return $messages[$this->purpose] ?? 'Call initiated successfully';
    }

    public function render()
    {
        return view('livewire.call-initiator-widget');
    }
}