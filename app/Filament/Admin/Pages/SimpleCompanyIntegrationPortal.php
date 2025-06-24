<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use App\Models\Company;
use App\Models\Branch;
use App\Models\PhoneNumber;
use App\Models\CalcomEventType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SimpleCompanyIntegrationPortal extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Simple Integration Portal';
    protected static ?string $title = 'Simple Company Integration Portal';
    protected static ?string $navigationGroup = 'Einrichtung & Konfiguration';
    protected static ?int $navigationSort = 2;
    
    protected static string $view = 'filament.admin.pages.simple-company-integration-portal';
    
    // Form data
    public ?array $companyData = [];
    public ?array $calcomData = [];
    public ?array $retellData = [];
    
    // State
    public ?int $selectedCompanyId = null;
    public ?Company $selectedCompany = null;
    
    public static function canAccess(): bool
    {
        return auth()->check();
    }
    
    public function mount(): void
    {
        $user = auth()->user();
        
        // Auto-select company for non-super-admins
        if (!$user->hasRole('super_admin') && $user->company_id) {
            $this->selectedCompanyId = $user->company_id;
            $this->loadCompanyData();
        }
    }
    
    protected function getForms(): array
    {
        return [
            'companyForm',
            'calcomForm',
            'retellForm',
        ];
    }
    
    public function companyForm(Form $form): Form
    {
        $user = auth()->user();
        
        $companies = Company::query();
        if (!$user->hasRole('super_admin')) {
            $companies->where('id', $user->company_id);
        }
        
        return $form
            ->schema([
                Forms\Components\Section::make('Unternehmen auswählen')
                    ->description('Wählen Sie das Unternehmen aus, das Sie konfigurieren möchten.')
                    ->schema([
                        Forms\Components\Select::make('selectedCompanyId')
                            ->label('Unternehmen')
                            ->options($companies->pluck('name', 'id'))
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn () => $this->loadCompanyData())
                            ->disabled(!$user->hasRole('super_admin')),
                            
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Placeholder::make('branches')
                                    ->label('Filialen')
                                    ->content(fn () => $this->selectedCompany ? $this->selectedCompany->branches()->count() : '-'),
                                    
                                Forms\Components\Placeholder::make('phones')
                                    ->label('Telefonnummern')
                                    ->content(fn () => $this->selectedCompany ? $this->selectedCompany->phoneNumbers()->count() : '-'),
                                    
                                Forms\Components\Placeholder::make('status')
                                    ->label('Status')
                                    ->content(fn () => $this->selectedCompany && $this->selectedCompany->is_active ? 'Aktiv' : 'Inaktiv'),
                            ])
                            ->columns(3)
                            ->visible(fn () => $this->selectedCompany !== null),
                    ]),
            ])
            ->statePath('companyData');
    }
    
    public function calcomForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Cal.com Integration')
                    ->description('Konfigurieren Sie die Verbindung zu Cal.com für Kalenderbuchungen.')
                    ->schema([
                        Forms\Components\TextInput::make('calcom_api_key')
                            ->label('API Key')
                            ->password()
                            ->placeholder('cal_live_xxxxxxxxxxxxxxxxx')
                            ->helperText('Erstellen Sie einen API Key in Cal.com unter Settings > Developer'),
                            
                        Forms\Components\TextInput::make('calcom_team_slug')
                            ->label('Team Slug (optional)')
                            ->placeholder('mein-team')
                            ->helperText('Nur erforderlich, wenn Sie Teams in Cal.com verwenden'),
                            
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('saveCalcom')
                                ->label('Speichern')
                                ->action('saveCalcomSettings'),
                                
                            Forms\Components\Actions\Action::make('testCalcom')
                                ->label('Verbindung testen')
                                ->action('testCalcomConnection')
                                ->color('gray'),
                                
                            Forms\Components\Actions\Action::make('syncEventTypes')
                                ->label('Event Types synchronisieren')
                                ->action('syncCalcomEventTypes')
                                ->color('gray')
                                ->visible(fn () => !empty($this->calcomData['calcom_api_key'])),
                        ]),
                        
                        Forms\Components\Placeholder::make('event_types_count')
                            ->label('Event Types')
                            ->content(function () {
                                if (!$this->selectedCompany) return '-';
                                $count = $this->selectedCompany->eventTypes()->count();
                                return $count . ' Event Types gefunden';
                            }),
                    ])
                    ->collapsible()
                    ->visible(fn () => $this->selectedCompany !== null),
            ])
            ->statePath('calcomData');
    }
    
    public function retellForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Retell.ai Integration')
                    ->description('Konfigurieren Sie die Verbindung zu Retell.ai für KI-Telefonie.')
                    ->schema([
                        Forms\Components\TextInput::make('retell_api_key')
                            ->label('API Key')
                            ->password()
                            ->placeholder('key_xxxxxxxxxxxxxxxxx')
                            ->helperText('Erstellen Sie einen API Key in Retell.ai unter Settings > API Keys'),
                            
                        Forms\Components\TextInput::make('retell_agent_id')
                            ->label('Default Agent ID')
                            ->placeholder('agent_xxxxxxxxxxxxxxxxx')
                            ->helperText('Die ID Ihres konfigurierten Agents in Retell.ai'),
                            
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('saveRetell')
                                ->label('Speichern')
                                ->action('saveRetellSettings'),
                                
                            Forms\Components\Actions\Action::make('testRetell')
                                ->label('Verbindung testen')
                                ->action('testRetellConnection')
                                ->color('gray'),
                                
                            Forms\Components\Actions\Action::make('importCalls')
                                ->label('Anrufe importieren')
                                ->action('importRetellCalls')
                                ->color('gray')
                                ->visible(fn () => !empty($this->retellData['retell_api_key'])),
                        ]),
                        
                        Forms\Components\View::make('retell-webhook-info')
                            ->view('filament.admin.components.webhook-info', [
                                'url' => 'https://api.askproai.de/api/retell/webhook',
                            ]),
                    ])
                    ->collapsible()
                    ->visible(fn () => $this->selectedCompany !== null),
            ])
            ->statePath('retellData');
    }
    
    public function loadCompanyData(): void
    {
        if (!$this->selectedCompanyId) {
            $this->selectedCompany = null;
            return;
        }
        
        try {
            $this->selectedCompany = Company::find($this->selectedCompanyId);
            
            if ($this->selectedCompany) {
                // Load Cal.com data
                $this->calcomData = [
                    'calcom_api_key' => $this->selectedCompany->calcom_api_key,
                    'calcom_team_slug' => $this->selectedCompany->calcom_team_slug,
                ];
                
                // Load Retell data
                $this->retellData = [
                    'retell_api_key' => $this->selectedCompany->retell_api_key,
                    'retell_agent_id' => $this->selectedCompany->retell_agent_id,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error loading company data: ' . $e->getMessage());
            Notification::make()
                ->title('Fehler beim Laden der Daten')
                ->danger()
                ->send();
        }
    }
    
    public function saveCalcomSettings(): void
    {
        try {
            $this->selectedCompany->update([
                'calcom_api_key' => $this->calcomData['calcom_api_key'],
                'calcom_team_slug' => $this->calcomData['calcom_team_slug'],
            ]);
            
            Notification::make()
                ->title('Cal.com Einstellungen gespeichert')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Log::error('Error saving Cal.com settings: ' . $e->getMessage());
            Notification::make()
                ->title('Fehler beim Speichern')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function saveRetellSettings(): void
    {
        try {
            $this->selectedCompany->update([
                'retell_api_key' => $this->retellData['retell_api_key'],
                'retell_agent_id' => $this->retellData['retell_agent_id'],
            ]);
            
            Notification::make()
                ->title('Retell.ai Einstellungen gespeichert')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Log::error('Error saving Retell settings: ' . $e->getMessage());
            Notification::make()
                ->title('Fehler beim Speichern')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function testCalcomConnection(): void
    {
        if (empty($this->calcomData['calcom_api_key'])) {
            Notification::make()
                ->title('Kein API Key')
                ->body('Bitte geben Sie einen Cal.com API Key ein.')
                ->warning()
                ->send();
            return;
        }
        
        try {
            // Simple test - try to get user info
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->calcomData['calcom_api_key'],
                'Content-Type' => 'application/json',
            ])->get('https://api.cal.com/v2/me');
            
            if ($response->successful()) {
                Notification::make()
                    ->title('Verbindung erfolgreich')
                    ->body('Die Verbindung zu Cal.com wurde erfolgreich getestet.')
                    ->success()
                    ->send();
            } else {
                throw new \Exception('API returned: ' . $response->status());
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Verbindungstest fehlgeschlagen')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function testRetellConnection(): void
    {
        if (empty($this->retellData['retell_api_key'])) {
            Notification::make()
                ->title('Kein API Key')
                ->body('Bitte geben Sie einen Retell.ai API Key ein.')
                ->warning()
                ->send();
            return;
        }
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->retellData['retell_api_key'],
            ])->get('https://api.retellai.com/list-agents');
            
            if ($response->successful()) {
                $agents = $response->json();
                $count = count($agents);
                
                Notification::make()
                    ->title('Verbindung erfolgreich')
                    ->body("Die Verbindung zu Retell.ai wurde erfolgreich getestet. {$count} Agents gefunden.")
                    ->success()
                    ->send();
            } else {
                throw new \Exception('API returned: ' . $response->status());
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Verbindungstest fehlgeschlagen')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function syncCalcomEventTypes(): void
    {
        try {
            // Simple sync - fetch event types directly
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->selectedCompany->calcom_api_key,
                'Content-Type' => 'application/json',
            ])->get('https://api.cal.com/v1/event-types');
            
            if (!$response->successful()) {
                throw new \Exception('Failed to fetch event types');
            }
            
            $eventTypes = $response->json()['event_types'] ?? [];
            $count = 0;
            
            foreach ($eventTypes as $eventType) {
                CalcomEventType::updateOrCreate(
                    [
                        'calcom_id' => $eventType['id'],
                        'company_id' => $this->selectedCompany->id,
                    ],
                    [
                        'name' => $eventType['title'],
                        'slug' => $eventType['slug'],
                        'description' => $eventType['description'] ?? null,
                        'duration' => $eventType['length'],
                        'is_active' => !($eventType['hidden'] ?? false),
                    ]
                );
                $count++;
            }
            
            Notification::make()
                ->title('Synchronisation erfolgreich')
                ->body("{$count} Event Types wurden synchronisiert.")
                ->success()
                ->send();
                
            // Refresh the form to show updated count
            $this->loadCompanyData();
        } catch (\Exception $e) {
            Log::error('Error syncing event types: ' . $e->getMessage());
            Notification::make()
                ->title('Synchronisation fehlgeschlagen')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function importRetellCalls(): void
    {
        try {
            // Simple import - fetch recent calls directly
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->selectedCompany->retell_api_key,
            ])->get('https://api.retellai.com/list-calls', [
                'limit' => 50,
                'sort_order' => 'descending',
            ]);
            
            if (!$response->successful()) {
                throw new \Exception('Failed to fetch calls');
            }
            
            $calls = $response->json() ?? [];
            $count = 0;
            
            foreach ($calls as $callData) {
                \App\Models\Call::updateOrCreate(
                    [
                        'retell_call_id' => $callData['call_id'],
                        'company_id' => $this->selectedCompany->id,
                    ],
                    [
                        'from_number' => $callData['from_number'] ?? null,
                        'to_number' => $callData['to_number'] ?? null,
                        'status' => $callData['status'] ?? 'unknown',
                        'duration' => $callData['duration'] ?? 0,
                        'started_at' => isset($callData['start_timestamp']) ? 
                            \Carbon\Carbon::createFromTimestamp($callData['start_timestamp'] / 1000) : null,
                        'ended_at' => isset($callData['end_timestamp']) ? 
                            \Carbon\Carbon::createFromTimestamp($callData['end_timestamp'] / 1000) : null,
                        'retell_agent_id' => $callData['agent_id'] ?? null,
                        'transcript' => $callData['transcript'] ?? null,
                        'recording_url' => $callData['recording_url'] ?? null,
                    ]
                );
                $count++;
            }
            
            Notification::make()
                ->title('Import erfolgreich')
                ->body("{$count} Anrufe wurden importiert.")
                ->success()
                ->send();
        } catch (\Exception $e) {
            Log::error('Error importing calls: ' . $e->getMessage());
            Notification::make()
                ->title('Import fehlgeschlagen')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}