<?php

// This script will backup and replace the EventTypeSetupWizard with a working version

$sourceFile = '/var/www/api-gateway/app/Filament/Admin/Pages/EventTypeSetupWizard.php';
$backupFile = '/var/www/api-gateway/app/Filament/Admin/Pages/EventTypeSetupWizard.php.backup';

// Create backup
copy($sourceFile, $backupFile);
echo "✅ Backup created at: $backupFile\n";

// New working content
$newContent = <<<'PHP'
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

class EventTypeSetupWizard extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Event-Type Konfiguration';
    protected static ?string $navigationGroup = 'Setup & Onboarding';
    protected static ?int $navigationSort = 15;
    protected static ?string $title = 'Event-Type Konfiguration';
    
    protected static string $view = 'filament.admin.pages.event-type-setup-wizard';
    
    #[Url]
    public ?int $eventTypeId = null;
    
    public ?CalcomEventType $eventType = null;
    
    // Form state - properly managed for Livewire reactivity
    public ?array $data = [];
    
    public array $checklist = [];
    public array $calcomLinks = [];
    
    protected CalcomMCPServer $calcomMCP;
    
    public function boot()
    {
        $this->calcomMCP = app(CalcomMCPServer::class);
    }
    
    public function mount(): void
    {
        // Initialize with user's company if available
        $user = auth()->user();
        if ($user && $user->company_id && !$this->eventTypeId) {
            $this->data['company_id'] = $user->company_id;
        }
        
        if ($this->eventTypeId) {
            $this->loadEventType();
        }
        
        $this->form->fill($this->data);
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
                                        if ($user->company_id) {
                                            return Company::where('id', $user->company_id)
                                                ->pluck('name', 'id');
                                        }
                                        if ($user->hasRole('super_admin')) {
                                            return Company::pluck('name', 'id');
                                        }
                                        return [];
                                    })
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, callable $set) => $this->resetSelections($set))
                                    ->disabled(fn() => auth()->user()->company_id !== null)
                                    ->searchable(),
                                    
                                Select::make('branch_id')
                                    ->label('Filiale (Optional)')
                                    ->options(function (callable $get) {
                                        $companyId = $get('company_id');
                                        if (!$companyId) return [];
                                        
                                        return Branch::withoutGlobalScopes()
                                            ->where('company_id', $companyId)
                                            ->where('is_active', true)
                                            ->pluck('name', 'id');
                                    })
                                    ->visible(fn (callable $get) => filled($get('company_id')))
                                    ->reactive()
                                    ->searchable()
                                    ->placeholder('Alle Filialen'),
                                    
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
    
    protected function getHeaderWidgets(): array
    {
        return $this->eventType ? [
            \App\Filament\Admin\Widgets\EventTypeSyncStatus::class,
        ] : [];
    }
}
PHP;

// Write new content
file_put_contents($sourceFile, $newContent);
echo "✅ EventTypeSetupWizard updated with working version\n";
echo "\nChanges made:\n";
echo "- Added HasForms interface and InteractsWithForms trait\n";
echo "- Fixed Livewire state management with proper form handling\n";
echo "- Removed formData array in favor of proper Filament form state\n";
echo "- Fixed reactive field updates with proper callbacks\n";
echo "- Added security checks for company access\n";
echo "- Simplified the entire component structure\n";
echo "\n⚠️  Original file backed up to: $backupFile\n";