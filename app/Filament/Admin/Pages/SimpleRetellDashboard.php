<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Services\RetellV2Service;
use App\Models\Company;
use Illuminate\Support\Facades\Cache;

class SimpleRetellDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-phone';
    protected static ?string $navigationGroup = 'System';
    protected static ?string $navigationLabel = 'Retell Configuration';
    protected static ?int $navigationSort = 10;
    
    protected static string $view = 'filament.admin.pages.simple-retell-dashboard';
    
    public $agents = [];
    public $phoneNumbers = [];
    public $selectedAgent = null;
    public $selectedPhone = null;
    public $loading = false;
    public $error = null;
    
    public function mount(): void
    {
        $this->loadData();
    }
    
    public function loadData(): void
    {
        try {
            $this->loading = true;
            $this->error = null;
            
            $company = Company::find(1);
            if (!$company || !$company->retell_api_key) {
                $this->error = 'No Retell API key configured';
                return;
            }
            
            $service = new RetellV2Service(decrypt($company->retell_api_key));
            
            // Load agents
            $agentsResult = $service->listAgents();
            $this->agents = $agentsResult['agents'] ?? [];
            
            // Load phone numbers
            $phonesResult = $service->listPhoneNumbers();
            $this->phoneNumbers = $phonesResult['phone_numbers'] ?? [];
            
        } catch (\Exception $e) {
            $this->error = 'Error loading data: ' . $e->getMessage();
        } finally {
            $this->loading = false;
        }
    }
    
    public function updatePhoneAgent($phoneNumber, $agentId): void
    {
        try {
            $company = Company::find(1);
            $service = new RetellV2Service(decrypt($company->retell_api_key));
            
            $service->updatePhoneNumber($phoneNumber, [
                'agent_id' => $agentId,
                'inbound_agent_id' => $agentId
            ]);
            
            $this->loadData();
            
            \Filament\Notifications\Notification::make()
                ->title('Phone number updated')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error updating phone')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function updateAgentWebhook($agentId, $webhookUrl, $events): void
    {
        try {
            $company = Company::find(1);
            $service = new RetellV2Service(decrypt($company->retell_api_key));
            
            $updateData = [
                'webhook_url' => $webhookUrl,
                'webhook_events' => $events
            ];
            
            // Add custom functions
            $updateData['custom_functions'] = [
                [
                    'name' => 'collect_appointment_data',
                    'url' => 'https://api.askproai.de/api/retell/collect-appointment',
                    'description' => 'Sammelt alle Terminbuchungsdaten vom Anrufer',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'telefonnummer' => [
                                'type' => 'string',
                                'description' => 'Telefonnummer des Anrufers'
                            ],
                            'dienstleistung' => [
                                'type' => 'string',
                                'description' => 'Gewünschte Dienstleistung'
                            ],
                            'wunschtermin_datum' => [
                                'type' => 'string',
                                'description' => 'Gewünschtes Datum (Format: YYYY-MM-DD)'
                            ],
                            'wunschtermin_uhrzeit' => [
                                'type' => 'string',
                                'description' => 'Gewünschte Uhrzeit (Format: HH:MM)'
                            ]
                        ],
                        'required' => ['dienstleistung', 'wunschtermin_datum', 'wunschtermin_uhrzeit']
                    ]
                ],
                [
                    'name' => 'current_time_berlin',
                    'url' => 'https://api.askproai.de/api/zeitinfo',
                    'description' => 'Gibt die aktuelle Zeit in Berlin zurück',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [],
                        'required' => []
                    ]
                ]
            ];
            
            $service->updateAgent($agentId, $updateData);
            
            $this->loadData();
            
            \Filament\Notifications\Notification::make()
                ->title('Agent updated')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error updating agent')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}