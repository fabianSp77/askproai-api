<?php

namespace App\Filament\Admin\Resources\BranchResource\Widgets;

use Filament\Widgets\Widget;
use App\Models\Branch;
use App\Services\Provisioning\RetellAgentProvisioner;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class RetellAgentProvisioningWidget extends Widget
{
    protected static string $view = 'filament.widgets.retell-agent-provisioning';
    
    public ?Branch $record = null;
    
    public function mount(): void
    {
        $this->record = request()->route('record');
    }
    
    public function getAgentStatus(): array
    {
        if (!$this->record) {
            return [
                'hasAgent' => false,
                'status' => 'unknown',
                'message' => 'Keine Filiale ausgewählt',
            ];
        }
        
        if ($this->record->hasRetellAgent()) {
            return [
                'hasAgent' => true,
                'status' => 'active',
                'agentId' => $this->record->retell_agent_id,
                'createdAt' => $this->record->retell_agent_created_at?->format('d.m.Y H:i'),
                'message' => 'Agent ist aktiv und einsatzbereit',
            ];
        }
        
        if ($this->record->retell_agent_id && $this->record->retell_agent_status !== 'active') {
            return [
                'hasAgent' => true,
                'status' => $this->record->retell_agent_status ?? 'inactive',
                'agentId' => $this->record->retell_agent_id,
                'message' => 'Agent existiert, ist aber nicht aktiv',
            ];
        }
        
        return [
            'hasAgent' => false,
            'status' => 'not_provisioned',
            'message' => 'Kein Agent vorhanden',
        ];
    }
    
    public function provisionAgent(): void
    {
        try {
            $provisioner = new RetellAgentProvisioner();
            $result = $provisioner->createAgentForBranch($this->record);
            
            if ($result['success']) {
                Notification::make()
                    ->title('Agent erfolgreich erstellt')
                    ->body("Agent ID: {$result['agent_id']}")
                    ->success()
                    ->send();
                    
                // Refresh the page to show updated status
                $this->redirect(request()->url());
            } else {
                Notification::make()
                    ->title('Agent-Erstellung fehlgeschlagen')
                    ->body($result['error'])
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Log::error('Agent provisioning failed', [
                'branch_id' => $this->record->id,
                'error' => $e->getMessage(),
            ]);
            
            Notification::make()
                ->title('Fehler beim Erstellen des Agents')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function updateAgent(): void
    {
        try {
            $provisioner = new RetellAgentProvisioner();
            $result = $provisioner->updateAgentForBranch($this->record);
            
            if ($result['success']) {
                Notification::make()
                    ->title('Agent erfolgreich aktualisiert')
                    ->success()
                    ->send();
                    
                $this->redirect(request()->url());
            } else {
                Notification::make()
                    ->title('Agent-Aktualisierung fehlgeschlagen')
                    ->body($result['error'])
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler beim Aktualisieren')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function testAgent(): void
    {
        if (!$this->record->hasRetellAgent()) {
            Notification::make()
                ->title('Kein Agent vorhanden')
                ->body('Bitte erstellen Sie zuerst einen Agent')
                ->warning()
                ->send();
            return;
        }
        
        // Store branch ID in session for test call page
        session(['test_call_branch' => $this->record->id]);
        
        // Redirect to test call page
        $this->redirect(route('filament.admin.pages.test-call'));
    }
    
    public function canProvision(): bool
    {
        // Check if branch has required data
        return $this->record 
            && $this->record->company 
            && $this->record->services->count() > 0
            && !$this->record->hasRetellAgent();
    }
    
    public function getProvisioningChecks(): array
    {
        return [
            'company' => [
                'label' => 'Unternehmen zugeordnet',
                'passed' => !is_null($this->record->company),
            ],
            'phone' => [
                'label' => 'Telefonnummer hinterlegt',
                'passed' => !empty($this->record->phone_number),
            ],
            'services' => [
                'label' => 'Services definiert',
                'passed' => $this->record->services->count() > 0,
            ],
            'hours' => [
                'label' => 'Öffnungszeiten eingestellt',
                'passed' => !empty($this->record->business_hours),
            ],
        ];
    }
}