<?php

namespace App\Filament\Admin\Resources\BranchResource\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use App\Models\Branch;
use App\Services\Provisioning\RetellAgentProvisioner;
use App\Services\RetellV2Service;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class RetellAgentProvisioningWidget extends Widget
{
    protected static string $view = 'filament.widgets.retell-agent-provisioning';
    
    public ?Model $record = null;
    
    protected function getViewData(): array
    {
        return [
            'record' => $this->record,
            'agentDetails' => $this->getAgentDetails(),
        ];
    }
    
    public function getAgentDetails(): ?array
    {
        if (!$this->record || !$this->record->retell_agent_id) {
            return null;
        }
        
        // Cache agent details for 5 minutes
        return Cache::remember(
            "retell_agent_details_{$this->record->retell_agent_id}",
            300,
            function () {
                try {
                    $apiKey = $this->getRetellApiKey();
                    if (!$apiKey) {
                        return null;
                    }
                    
                    $retellService = new RetellV2Service($apiKey);
                    $agent = $retellService->getAgent($this->record->retell_agent_id);
                    
                    if (!$agent) {
                        return null;
                    }
                    
                    return $this->parseAgentData($agent);
                    
                } catch (\Exception $e) {
                    Log::error('Failed to fetch agent details', [
                        'agent_id' => $this->record->retell_agent_id,
                        'error' => $e->getMessage(),
                    ]);
                    
                    // Return error info for debugging
                    return [
                        'error' => true,
                        'message' => $e->getMessage(),
                    ];
                }
            }
        );
    }
    
    private function parseAgentData(array $agent): array
    {
        return [
            'basic' => [
                'agent_id' => $agent['agent_id'] ?? '',
                'agent_name' => $agent['agent_name'] ?? 'Unnamed Agent',
                'created_at' => isset($agent['created_at']) ? \Carbon\Carbon::parse($agent['created_at'])->format('d.m.Y H:i') : null,
                'last_modified' => isset($agent['last_modified']) ? \Carbon\Carbon::parse($agent['last_modified'])->format('d.m.Y H:i') : null,
            ],
            'language' => [
                'language' => $this->getLanguageName($agent['language'] ?? 'de-DE'),
                'voice_id' => $agent['voice_id'] ?? 'default',
                'voice_speed' => $agent['voice_speed'] ?? 1.0,
                'voice_temperature' => $agent['voice_temperature'] ?? 0.5,
            ],
            'model' => [
                'llm_type' => $agent['response_engine']['type'] ?? 'retell-llm',
                'llm_id' => $agent['response_engine']['llm_id'] ?? 'gpt-4',
                'system_prompt' => $agent['response_engine']['system_prompt'] ?? '',
            ],
            'behavior' => [
                'end_call_after_silence_ms' => $agent['end_call_after_silence_ms'] ?? 10000,
                'max_call_duration_ms' => $agent['max_call_duration_ms'] ?? 1800000,
            ],
        ];
    }
    
    private function getLanguageName(string $code): string
    {
        $languages = [
            'de-DE' => 'Deutsch (Deutschland)',
            'de-AT' => 'Deutsch (Österreich)',
            'de-CH' => 'Deutsch (Schweiz)',
            'en-US' => 'English (US)',
            'en-GB' => 'English (UK)',
        ];
        
        return $languages[$code] ?? $code;
    }
    
    private function getRetellApiKey(): ?string
    {
        $company = $this->record->company;
        if ($company->retell_api_key) {
            try {
                return decrypt($company->retell_api_key);
            } catch (\Exception $e) {
                return $company->retell_api_key;
            }
        }
        
        return config('services.retell.api_key');
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
                'createdAt' => $this->record->retell_agent_created_at ? $this->record->retell_agent_created_at->format('d.m.Y H:i') : null,
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
        $this->redirect('/admin');
    }
    
    public function canProvision(): bool
    {
        // Check if branch has required data
        if (!$this->record) {
            return false;
        }
        
        return $this->record->company 
            && $this->record->services->count() > 0
            && !$this->record->hasRetellAgent();
    }
    
    public function getProvisioningChecks(): array
    {
        if (!$this->record) {
            return [];
        }
        
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