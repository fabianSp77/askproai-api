<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Models\CalcomEventType;
use App\Models\Company;
use App\Models\Branch;
use App\Services\MCP\CalcomMCPServer;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;
use Illuminate\Support\Collection;

class EventTypeSetupWizard extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Event-Type Konfiguration';
    protected static ?string $navigationGroup = 'Einstellungen';
    protected static ?int $navigationSort = 15;
    protected static ?string $title = 'Event-Type Konfiguration';
    
    protected static string $view = 'filament.admin.pages.event-type-setup-wizard-simple';
    
    #[Url]
    public ?int $eventTypeId = null;
    
    public ?CalcomEventType $eventType = null;
    
    // Form state - properly managed for Livewire reactivity
    public ?array $data = [];
    
    // Track company changes to force refresh
    public ?int $currentCompanyId = null;
    
    public array $checklist = [];
    public array $calcomLinks = [];
    
    protected CalcomMCPServer $calcomMCP;
    
    public function boot()
    {
        $this->calcomMCP = app(CalcomMCPServer::class);
    }
    
    public function mount(): void
    {
        // Initialize form data array
        $this->data = [
            'company_id' => null,
            'branch_id' => null,
            'event_type_id' => null,
        ];
        
        // Initialize with user's company if available
        $user = auth()->user();
        if ($user && $user->company_id && !$this->eventTypeId) {
            $this->data['company_id'] = $user->company_id;
            
            // Pre-load branches for the user's company
            try {
                $branches = Branch::withoutGlobalScopes()
                    ->where('company_id', $user->company_id)
                    ->where('is_active', true)
                    ->pluck('name', 'id')
                    ->toArray();
                    
                Log::info('EventTypeSetupWizard: Pre-loaded branches on mount', [
                    'company_id' => $user->company_id,
                    'branch_count' => count($branches),
                    'branches' => array_keys($branches)
                ]);
            } catch (\Exception $e) {
                Log::error('EventTypeSetupWizard: Failed to pre-load branches', [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        if ($this->eventTypeId) {
            $this->loadEventType();
        }
        
        $this->form->fill($this->data);
        
        Log::info('EventTypeSetupWizard: Mount completed', [
            'eventTypeId' => $this->eventTypeId,
            'data' => $this->data
        ]);
    }
    
    protected function loadEventType(): void
    {
        $this->eventType = CalcomEventType::withoutGlobalScopes()
            ->with(['company', 'branch', 'assignedStaff'])
            ->find($this->eventTypeId);
            
        if (!$this->eventType) {
            Notification::make()
                ->title('Event-Type nicht gefunden')
                ->danger()
                ->send();
            $this->redirect(static::getUrl());
            return;
        }
        
        // Security check
        $user = auth()->user();
        if (!$user->hasRole('super_admin') && $this->eventType->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access');
        }
        
        // Initialize checklist if needed
        if (empty($this->eventType->setup_checklist)) {
            $this->eventType->initializeChecklist();
        }
        
        // Load form data
        $this->data = [
            'name' => $this->eventType->name,
            'description' => $this->eventType->description,
            'duration_minutes' => $this->eventType->duration_minutes,
            'price' => $this->eventType->price,
            'minimum_booking_notice' => $this->eventType->minimum_booking_notice ?? 60,
            'booking_future_limit' => $this->eventType->booking_future_limit ?? 60,
            'time_slot_interval' => $this->eventType->time_slot_interval ?? 30,
            'buffer_before' => $this->eventType->buffer_before ?? 0,
            'buffer_after' => $this->eventType->buffer_after ?? 0,
            'max_bookings_per_day' => $this->eventType->max_bookings_per_day,
            'requires_confirmation' => $this->eventType->requires_confirmation ?? false,
        ];
        
        $this->checklist = $this->eventType->getSetupChecklist();
        $this->generateCalcomLinks();
        
        $this->form->fill($this->data);
    }
    
    protected function generateCalcomLinks(): void
    {
        if (!$this->eventType || !$this->eventType->calcom_numeric_event_type_id) {
            return;
        }
        
        try {
            $sections = ['availability', 'advanced', 'workflows', 'webhooks'];
            
            foreach ($sections as $section) {
                $result = $this->calcomMCP->generateCalcomDirectLink([
                    'event_type_id' => $this->eventType->calcom_numeric_event_type_id,
                    'section' => $section
                ]);
                
                if ($result['success'] ?? false) {
                    $this->calcomLinks[$section] = $result;
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to generate Cal.com links', [
                'error' => $e->getMessage(),
                'event_type_id' => $this->eventType->id
            ]);
        }
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Selection form when no event type selected
                Section::make('Event-Type auswählen')
                    ->description('Wählen Sie den Event-Type aus, den Sie konfigurieren möchten.')
                    ->visible(!$this->eventTypeId)
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                Select::make('company_id')
                                    ->label('Unternehmen')
                                    ->options(function () {
                                        $user = auth()->user();
                                        
                                        // Super admins can see all companies
                                        if ($user->hasRole('super_admin')) {
                                            return Company::pluck('name', 'id');
                                        }
                                        
                                        // Regular users only see their own company
                                        if ($user->company_id) {
                                            return Company::where('id', $user->company_id)
                                                ->pluck('name', 'id');
                                        }
                                        
                                        return [];
                                    })
                                    ->required()
                                    ->live(onBlur: false) // Important: Make it live immediately
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        // Reset dependent fields when company changes
                                        $set('branch_id', null);
                                        $set('event_type_id', null);
                                        
                                        // Update tracking variable
                                        $this->currentCompanyId = $state;
                                        
                                        Log::info('EventTypeSetupWizard: Company selection changed', [
                                            'new_company_id' => $state,
                                            'user_company_id' => auth()->user()->company_id
                                        ]);
                                        
                                        // Force full component refresh
                                        $this->dispatch('$refresh');
                                    })
                                    ->disabled(fn() => !auth()->user()->hasRole('super_admin') && auth()->user()->company_id !== null)
                                    ->searchable()
                                    ->helperText(function () {
                                        $user = auth()->user();
                                        if (!$user->hasRole('super_admin') && $user->company_id) {
                                            return 'Sie können nur Event-Types für Ihr eigenes Unternehmen konfigurieren.';
                                        }
                                        return 'Wählen Sie das Unternehmen aus, für das Sie Event-Types konfigurieren möchten.';
                                    }),
                                    
                                Select::make('branch_id')
                                    ->label('Filiale (Optional)')
                                    ->options(function (callable $get) {
                                        $companyId = $get('company_id');
                                        
                                        if (!$companyId) {
                                            return [];
                                        }
                                        
                                        return Branch::withoutGlobalScopes()
                                            ->where('company_id', $companyId)
                                            ->where('is_active', true)
                                            ->orderBy('name')
                                            ->pluck('name', 'id');
                                    })
                                    ->placeholder('Alle Filialen')
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->visible(fn (callable $get) => filled($get('company_id')))
                                    ->helperText('Optional: Filtern Sie Event-Types nach Filiale.')
                                    ->afterStateUpdated(fn (callable $set) => $set('event_type_id', null)),
                                    
                                Select::make('event_type_id')
                                    ->label('Event-Type')
                                    ->options(function (callable $get) {
                                        $companyId = $get('company_id');
                                        if (!$companyId) return [];
                                        
                                        $query = CalcomEventType::withoutGlobalScopes()
                                            ->where('company_id', $companyId)
                                            ->with(['branch']);
                                        
                                        $branchId = $get('branch_id');
                                        if ($branchId) {
                                            $query->where('branch_id', $branchId);
                                        }
                                        
                                        $eventTypes = $query->get();
                                        
                                        $options = [];
                                        foreach ($eventTypes as $et) {
                                            $status = match($et->setup_status) {
                                                'complete' => '✅',
                                                'partial' => '⚠️',
                                                default => '❌'
                                            };
                                            
                                            $branchName = $et->branch ? " ({$et->branch->name})" : '';
                                            $progress = " [{$et->getSetupProgress()}%]";
                                            
                                            $options[$et->id] = "{$status} {$et->name}{$branchName}{$progress}";
                                        }
                                        
                                        return $options;
                                    })
                                    ->required()
                                    ->visible(fn (callable $get) => filled($get('company_id')))
                                    ->searchable()
                                    ->placeholder('Bitte wählen...'),
                                    
                                Actions::make([
                                    Action::make('select')
                                        ->label('Event-Type konfigurieren')
                                        ->action(function (array $data) {
                                            if ($data['event_type_id'] ?? null) {
                                                $this->redirect(static::getUrl(['eventTypeId' => $data['event_type_id']]));
                                            }
                                        })
                                        ->disabled(fn (callable $get) => !$get('event_type_id'))
                                        ->icon('heroicon-o-arrow-right')
                                ]),
                            ]),
                            
                        Placeholder::make('info')
                            ->content(fn () => view('filament.components.compact-data-flow-info')),
                    ]),
                    
                // Configuration form when event type selected
                ...($this->eventType ? $this->getConfigurationSchema() : []),
            ])
            ->statePath('data');
    }
    
    protected function resetSelections(callable $set): void
    {
        $set('branch_id', null);
        $set('event_type_id', null);
        
        // Force Livewire to refresh the component
        $this->dispatch('$refresh');
    }
    
    public function updated($propertyName): void
    {
        if ($propertyName === 'data.company_id') {
            Log::info('EventTypeSetupWizard: Company changed via updated hook', [
                'new_company' => $this->data['company_id'] ?? null,
                'old_company' => $this->currentCompanyId
            ]);
            
            $this->currentCompanyId = $this->data['company_id'] ?? null;
            
            // Force re-render of the entire form
            $this->fillForm();
        }
    }
    
    protected function fillForm(): void 
    {
        $this->form->fill($this->data);
    }
    
    protected function getConfigurationSchema(): array
    {
        return [
            Section::make('Basis-Informationen')
                ->description('Grundlegende Einstellungen für den Event-Type')
                ->schema([
                    TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->maxLength(255),
                        
                    Textarea::make('description')
                        ->label('Beschreibung')
                        ->rows(3)
                        ->maxLength(500),
                        
                    Grid::make(2)
                        ->schema([
                            TextInput::make('duration_minutes')
                                ->label('Dauer (Minuten)')
                                ->numeric()
                                ->required()
                                ->minValue(5)
                                ->maxValue(480),
                                
                            TextInput::make('price')
                                ->label('Preis (€)')
                                ->numeric()
                                ->prefix('€')
                                ->minValue(0),
                        ]),
                ]),
                
            Section::make('Buchungseinstellungen')
                ->description('Regeln für die Terminbuchung')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('minimum_booking_notice')
                                ->label('Vorlaufzeit (Minuten)')
                                ->numeric()
                                ->default(60)
                                ->helperText('Mindestzeit vor Terminbuchung'),
                                
                            TextInput::make('booking_future_limit')
                                ->label('Buchbar bis (Tage)')
                                ->numeric()
                                ->default(60)
                                ->helperText('Wie weit im Voraus buchbar'),
                        ]),
                        
                    Toggle::make('requires_confirmation')
                        ->label('Bestätigung erforderlich')
                        ->helperText('Termine müssen manuell bestätigt werden'),
                        
                    Actions::make([
                        Action::make('save')
                            ->label('Einstellungen speichern')
                            ->action('saveSettings')
                            ->icon('heroicon-o-check')
                    ]),
                ]),
        ];
    }
    
    public function saveSettings(): void
    {
        if (!$this->eventType) {
            return;
        }
        
        $data = $this->form->getState();
        
        try {
            $this->eventType->update([
                'name' => $data['name'],
                'description' => $data['description'],
                'duration_minutes' => $data['duration_minutes'],
                'price' => $data['price'],
                'minimum_booking_notice' => $data['minimum_booking_notice'],
                'booking_future_limit' => $data['booking_future_limit'],
                'requires_confirmation' => $data['requires_confirmation'],
            ]);
            
            // Update checklist
            $this->eventType->updateChecklistItem('basic_info', true);
            $this->eventType->updateChecklistItem('booking_settings', true);
            
            Notification::make()
                ->title('Einstellungen gespeichert')
                ->success()
                ->send();
                
            // Reload
            $this->loadEventType();
            
        } catch (\Exception $e) {
            Log::error('Failed to save event type', [
                'error' => $e->getMessage(),
                'event_type_id' => $this->eventType->id
            ]);
            
            Notification::make()
                ->title('Fehler beim Speichern')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function getHeading(): string
    {
        return $this->eventType 
            ? "Event-Type konfigurieren: {$this->eventType->name}"
            : 'Event-Type Konfiguration';
    }
    
    public function getSubheading(): ?string
    {
        return $this->eventType
            ? 'Verwalten Sie die Einstellungen für diesen Event-Type.'
            : 'Wählen Sie einen Event-Type aus, um die Konfiguration zu starten.';
    }
    
    protected function getBranchOptionsForCompany(?int $companyId): array
    {
        if (!$companyId) {
            return [];
        }
        
        try {
            $branches = Branch::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray();
            
            Log::info('EventTypeSetupWizard: Branch options loaded', [
                'company_id' => $companyId,
                'count' => count($branches)
            ]);
            
            return $branches;
        } catch (\Exception $e) {
            Log::error('EventTypeSetupWizard: Failed to load branch options', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    protected function getHeaderWidgets(): array
    {
        return $this->eventType ? [
            \App\Filament\Admin\Widgets\EventTypeSyncStatus::class,
        ] : [];
    }
}