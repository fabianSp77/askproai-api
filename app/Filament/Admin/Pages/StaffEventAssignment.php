<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\Staff;
use App\Models\CalcomEventType;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Livewire\Attributes\Reactive;

class StaffEventAssignment extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    
    protected static ?string $navigationGroup = 'Kalender & Events';
    
    protected static ?string $navigationLabel = 'Mitarbeiter-Zuordnung';
    
    protected static ?int $navigationSort = 20;
    
    protected static string $view = 'filament.admin.pages.staff-event-assignment';
    
    protected static ?string $title = 'Mitarbeiter zu Event-Types zuordnen';
    
    public ?int $company_id = null;
    public array $assignments = [];
    public array $staff = [];
    public array $eventTypes = [];
    
    public function mount(): void
    {
        // Setze Standard-Company wenn nur eine vorhanden
        $companies = Company::all();
        if ($companies->count() === 1) {
            $this->company_id = $companies->first()->id;
            $this->loadData();
        }
    }
    
    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form
            ->schema([
                Select::make('company_id')
                    ->label('Unternehmen auswählen')
                    ->options(Company::pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(fn () => $this->loadData()),
            ]);
    }
    
    public function loadData(): void
    {
        if (!$this->company_id) {
            $this->staff = [];
            $this->eventTypes = [];
            $this->assignments = [];
            return;
        }
        
        // Lade Mitarbeiter
        $this->staff = Staff::where('company_id', $this->company_id)
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'email' => $s->email,
                    'branch' => $s->branch ? $s->branch->name : 'Keine Filiale'
                ];
            })
            ->toArray();
        
        // Lade Event-Types
        $this->eventTypes = CalcomEventType::where('company_id', $this->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($et) {
                return [
                    'id' => (int)$et->id, // Ensure ID is integer
                    'name' => $et->name,
                    'duration' => $et->duration_minutes,
                    'price' => $et->price
                ];
            })
            ->toArray();
        
        // Lade bestehende Zuordnungen
        $existingAssignments = DB::table('staff_event_types')
            ->whereIn('staff_id', collect($this->staff)->pluck('id'))
            ->whereIn('event_type_id', collect($this->eventTypes)->pluck('id'))
            ->get()
            ->keyBy(function ($item) {
                return $item->staff_id . '::' . $item->event_type_id;
            });
        
        // Initialisiere assignments Array
        $this->assignments = [];
        foreach ($this->staff as $staff) {
            foreach ($this->eventTypes as $eventType) {
                $key = $staff['id'] . '::' . $eventType['id'];
                $assignment = $existingAssignments->get($key);
                
                $this->assignments[$key] = [
                    'assigned' => !is_null($assignment)
                ];
            }
        }
    }
    
    public function toggleAssignment($staffId, $eventTypeId): void
    {
        $key = $staffId . '::' . $eventTypeId;
        $this->assignments[$key]['assigned'] = !$this->assignments[$key]['assigned'];
    }
    
    public function saveAssignments(): void
    {
        DB::beginTransaction();
        
        try {
            // Lösche alle bestehenden Zuordnungen für diese Company
            $staffIds = collect($this->staff)->pluck('id');
            $eventTypeIds = collect($this->eventTypes)->pluck('id');
            
            DB::table('staff_event_types')
                ->whereIn('staff_id', $staffIds)
                ->whereIn('event_type_id', $eventTypeIds)
                ->delete();
            
            // Füge neue Zuordnungen hinzu
            $inserts = [];
            foreach ($this->assignments as $key => $assignment) {
                if ($assignment['assigned']) {
                    // Split using our custom separator
                    $parts = explode('::', $key);
                    
                    // Debug logging
                    Log::info('Parsing assignment key', [
                        'key' => $key,
                        'parts' => $parts,
                        'parts_count' => count($parts)
                    ]);
                    
                    if (count($parts) !== 2) {
                        Log::error('Invalid assignment key format', [
                            'key' => $key,
                            'parts' => $parts
                        ]);
                        continue;
                    }
                    
                    [$staffId, $eventTypeId] = $parts;
                    
                    // Validate IDs
                    if (!is_numeric($eventTypeId)) {
                        Log::error('Event type ID is not numeric', [
                            'eventTypeId' => $eventTypeId,
                            'staffId' => $staffId,
                            'key' => $key
                        ]);
                        continue;
                    }
                    
                    $inserts[] = [
                        'staff_id' => $staffId,
                        'event_type_id' => (int)$eventTypeId,
                        'is_primary' => false,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
            }
            
            if (!empty($inserts)) {
                DB::table('staff_event_types')->insert($inserts);
            }
            
            DB::commit();
            
            Notification::make()
                ->title('Zuordnungen gespeichert')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Notification::make()
                ->title('Fehler beim Speichern')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function selectAll(): void
    {
        foreach ($this->assignments as $key => &$assignment) {
            $assignment['assigned'] = true;
        }
    }
    
    public function deselectAll(): void
    {
        foreach ($this->assignments as $key => &$assignment) {
            $assignment['assigned'] = false;
        }
    }
    
    public function selectAllForStaff($staffId): void
    {
        foreach ($this->eventTypes as $eventType) {
            $key = $staffId . '::' . $eventType['id'];
            $this->assignments[$key]['assigned'] = true;
        }
    }
    
    public function selectAllForEventType($eventTypeId): void
    {
        foreach ($this->staff as $staff) {
            $key = $staff['id'] . '::' . $eventTypeId;
            $this->assignments[$key]['assigned'] = true;
        }
    }
}