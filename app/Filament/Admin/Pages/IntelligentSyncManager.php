<?php

namespace App\Filament\Admin\Pages;

use App\Services\MCP\SyncMCPService;
use Filament\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Filament\Pages\Concerns\InteractsWithFormActions;

class IntelligentSyncManager extends Page implements HasForms
{
    use InteractsWithForms;
    use InteractsWithFormActions;
    
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationLabel = 'Intelligente Synchronisation';
    protected static ?string $navigationGroup = 'System & Monitoring';
    protected static ?int $navigationSort = 100;
    
    public static function getNavigationBadge(): ?string
    {
        try {
            $unsynced = \App\Models\Call::where('created_at', '>=', now()->subHours(2))
                ->whereNull('synced_at')
                ->count();
                
            return $unsynced > 0 ? (string) $unsynced : null;
        } catch (\Exception $e) {
            // Fallback wenn Spalte noch nicht existiert
            return null;
        }
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
    protected static string $view = 'filament.admin.pages.intelligent-sync-manager';
    
    // Filter für Anrufe
    public ?string $callDateFrom = null;
    public ?string $callDateTo = null;
    public ?int $callLimit = 100;
    public ?int $callMinDuration = 30;
    public ?string $callStatus = null;
    public ?bool $callHasAppointment = null;
    public bool $callSkipExisting = true;
    
    // Filter für Termine
    public ?string $appointmentDateFrom = null;
    public ?string $appointmentDateTo = null;
    public ?int $appointmentLimit = 200;
    public ?string $appointmentStatus = null;
    public ?string $appointmentEventTypeId = null;
    public bool $appointmentSkipExisting = true;
    public bool $appointmentIncludeCancelled = false;
    
    // UI State
    public bool $showCallPreview = false;
    public bool $showAppointmentPreview = false;
    public array $callPreviewData = [];
    public array $appointmentPreviewData = [];
    
    protected ?SyncMCPService $syncService = null;
    
    public function mount(): void
    {
        $this->syncService = app(SyncMCPService::class);
        
        // Setze sinnvolle Defaults
        $this->callDateFrom = now()->subDays(7)->format('Y-m-d');
        $this->callDateTo = now()->format('Y-m-d');
        
        $this->appointmentDateFrom = now()->subDays(30)->format('Y-m-d');
        $this->appointmentDateTo = now()->addDays(90)->format('Y-m-d');
        
        // Lade Empfehlungen
        $this->loadRecommendations();
    }
    
    #[Computed]
    public function recommendations(): array
    {
        return Cache::remember('sync_recommendations', 300, function() {
            return $this->syncService->getSyncRecommendations();
        });
    }
    
    #[Computed]
    public function lastSyncInfo(): array
    {
        return [
            'calls' => Cache::get('last_call_sync_info', [
                'time' => null,
                'stats' => ['total' => 0, 'new' => 0, 'updated' => 0]
            ]),
            'appointments' => Cache::get('last_appointment_sync_info', [
                'time' => null,
                'stats' => ['total' => 0, 'new' => 0, 'updated' => 0]
            ])
        ];
    }
    
    public function previewCalls(): void
    {
        try {
            $filters = [
                'date_from' => Carbon::parse($this->callDateFrom),
                'date_to' => Carbon::parse($this->callDateTo),
                'limit' => min($this->callLimit, 20), // Preview max 20
                'min_duration' => $this->callMinDuration,
                'status' => $this->callStatus,
                'skip_existing' => $this->callSkipExisting,
            ];
            
            $this->callPreviewData = $this->syncService->previewSync('calls', $filters);
            $this->showCallPreview = true;
            
            Notification::make()
                ->title('Vorschau generiert')
                ->body("Es würden {$this->callPreviewData['would_sync']} Anrufe synchronisiert werden.")
                ->info()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler bei Vorschau')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function syncCalls(): void
    {
        try {
            $filters = [
                'date_from' => Carbon::parse($this->callDateFrom),
                'date_to' => Carbon::parse($this->callDateTo),
                'limit' => $this->callLimit,
                'min_duration' => $this->callMinDuration,
                'status' => $this->callStatus,
                'has_appointment' => $this->callHasAppointment,
                'skip_existing' => $this->callSkipExisting,
            ];
            
            Notification::make()
                ->title('Synchronisation gestartet')
                ->body('Die Anrufe werden im Hintergrund synchronisiert.')
                ->info()
                ->send();
            
            // Dispatch Job für Background Processing
            dispatch(function() use ($filters) {
                $stats = app(SyncMCPService::class)->syncCalls($filters);
                
                Cache::put('last_call_sync', now());
                Cache::put('last_call_sync_info', [
                    'time' => now(),
                    'stats' => $stats
                ]);
                
                Notification::make()
                    ->title('Anruf-Synchronisation abgeschlossen')
                    ->body("Gesamt: {$stats['total']}, Neu: {$stats['new']}, Aktualisiert: {$stats['updated']}, Fehler: {$stats['errors']}")
                    ->success()
                    ->sendToDatabase(auth()->user());
            })->afterResponse();
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Synchronisation fehlgeschlagen')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function syncAppointments(): void
    {
        try {
            $filters = [
                'date_from' => Carbon::parse($this->appointmentDateFrom),
                'date_to' => Carbon::parse($this->appointmentDateTo),
                'limit' => $this->appointmentLimit,
                'status' => $this->appointmentStatus,
                'event_type_id' => $this->appointmentEventTypeId,
                'skip_existing' => $this->appointmentSkipExisting,
                'include_cancelled' => $this->appointmentIncludeCancelled,
            ];
            
            Notification::make()
                ->title('Synchronisation gestartet')
                ->body('Die Termine werden im Hintergrund synchronisiert.')
                ->info()
                ->send();
            
            // Dispatch Job für Background Processing
            dispatch(function() use ($filters) {
                $stats = app(SyncMCPService::class)->syncAppointments($filters);
                
                Cache::put('last_appointment_sync', now());
                Cache::put('last_appointment_sync_info', [
                    'time' => now(),
                    'stats' => $stats
                ]);
                
                Notification::make()
                    ->title('Termin-Synchronisation abgeschlossen')
                    ->body("Gesamt: {$stats['total']}, Neu: {$stats['new']}, Aktualisiert: {$stats['updated']}, Fehler: {$stats['errors']}")
                    ->success()
                    ->sendToDatabase(auth()->user());
            })->afterResponse();
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Synchronisation fehlgeschlagen')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function applyRecommendation(string $type): void
    {
        $recommendation = collect($this->recommendations)->firstWhere('type', $type);
        
        if (!$recommendation) {
            return;
        }
        
        if ($type === 'calls' && isset($recommendation['suggested_filters'])) {
            $filters = $recommendation['suggested_filters'];
            
            if (isset($filters['date_from'])) {
                $this->callDateFrom = $filters['date_from']->format('Y-m-d');
            }
            if (isset($filters['limit'])) {
                $this->callLimit = $filters['limit'];
            }
            if (isset($filters['min_duration'])) {
                $this->callMinDuration = $filters['min_duration'];
            }
            
            Notification::make()
                ->title('Filter angewendet')
                ->body('Die empfohlenen Filter wurden übernommen.')
                ->success()
                ->send();
        }
        
        // Ähnlich für appointments...
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
            Section::make('Anruf-Synchronisation')
                ->description('Konfigurieren Sie, welche Anrufe von Retell.ai abgerufen werden sollen.')
                ->schema([
                    Grid::make(3)->schema([
                        DatePicker::make('callDateFrom')
                            ->label('Von Datum')
                            ->required()
                            ->maxDate(now()),
                            
                        DatePicker::make('callDateTo')
                            ->label('Bis Datum')
                            ->required()
                            ->maxDate(now())
                            ->afterOrEqual('callDateFrom'),
                            
                        TextInput::make('callLimit')
                            ->label('Maximale Anzahl')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(1000)
                            ->required(),
                    ]),
                    
                    Grid::make(3)->schema([
                        TextInput::make('callMinDuration')
                            ->label('Mindestdauer (Sek.)')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Nur Anrufe länger als X Sekunden'),
                            
                        Select::make('callStatus')
                            ->label('Status')
                            ->options([
                                'ended' => 'Beendet',
                                'ongoing' => 'Laufend',
                                'error' => 'Fehler',
                            ])
                            ->placeholder('Alle Status'),
                            
                        Select::make('callHasAppointment')
                            ->label('Mit Termin')
                            ->options([
                                '1' => 'Nur mit Termin',
                                '0' => 'Nur ohne Termin',
                            ])
                            ->placeholder('Alle Anrufe'),
                    ]),
                    
                    Toggle::make('callSkipExisting')
                        ->label('Existierende überspringen')
                        ->helperText('Bereits importierte Anrufe nicht erneut verarbeiten')
                        ->default(true),
                ])
                ->footerActions([
                    Action::make('previewCalls')
                        ->label('Vorschau')
                        ->icon('heroicon-o-eye')
                        ->action('previewCalls'),
                        
                    Action::make('syncCalls')
                        ->label('Anrufe synchronisieren')
                        ->icon('heroicon-o-arrow-path')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->action('syncCalls'),
                ]),
                
            Section::make('Termin-Synchronisation')
                ->description('Konfigurieren Sie, welche Termine von Cal.com abgerufen werden sollen.')
                ->schema([
                    Grid::make(3)->schema([
                        DatePicker::make('appointmentDateFrom')
                            ->label('Von Datum')
                            ->required(),
                            
                        DatePicker::make('appointmentDateTo')
                            ->label('Bis Datum')
                            ->required()
                            ->afterOrEqual('appointmentDateFrom'),
                            
                        TextInput::make('appointmentLimit')
                            ->label('Maximale Anzahl')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5000)
                            ->required(),
                    ]),
                    
                    Grid::make(2)->schema([
                        Select::make('appointmentStatus')
                            ->label('Status')
                            ->options([
                                'upcoming' => 'Zukünftig',
                                'past' => 'Vergangen',
                                'cancelled' => 'Storniert',
                            ])
                            ->placeholder('Alle Status'),
                            
                        Select::make('appointmentEventTypeId')
                            ->label('Event-Typ')
                            ->options(fn() => \App\Models\CalcomEventType::query()
                                ->where('company_id', auth()->user()->company_id)
                                ->pluck('name', 'id')
                            )
                            ->placeholder('Alle Event-Typen')
                            ->searchable(),
                    ]),
                    
                    Grid::make(2)->schema([
                        Toggle::make('appointmentSkipExisting')
                            ->label('Existierende überspringen')
                            ->default(true),
                            
                        Toggle::make('appointmentIncludeCancelled')
                            ->label('Stornierte einschließen')
                            ->default(false),
                    ]),
                ])
                ->footerActions([
                    Action::make('previewAppointments')
                        ->label('Vorschau')
                        ->icon('heroicon-o-eye')
                        ->action('previewAppointments'),
                        
                    Action::make('syncAppointments')
                        ->label('Termine synchronisieren')
                        ->icon('heroicon-o-arrow-path')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->action('syncAppointments'),
                ]),
        ]);
    }
    
    private function loadRecommendations(): void
    {
        // Trigger computed property
        $this->recommendations;
    }
}