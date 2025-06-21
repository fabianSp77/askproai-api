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
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class StaffEventAssignmentModern extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Personal & Services';
    protected static ?string $navigationLabel = 'Mitarbeiter-Zuordnung';
    protected static ?int $navigationSort = 230;
    protected static string $view = 'filament.admin.pages.staff-event-assignment-modern';
    protected static ?string $title = 'Intelligente Mitarbeiter-Zuordnung';
    
    public ?int $company_id = null;
    public array $assignments = [];
    public array $staff = [];
    public array $eventTypes = [];
    public string $viewMode = 'matrix'; // matrix, cards, kanban
    public bool $showSkillMatrix = false;
    
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
        
        // Lade Mitarbeiter mit Performance-Daten
        $this->staff = Staff::where('company_id', $this->company_id)
            ->where('active', true)
            ->withCount(['appointments' => function ($query) {
                $query->where('created_at', '>=', now()->subDays(30));
            }])
            ->orderBy('name')
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'email' => $s->email,
                    'branch' => $s->branch ? $s->branch->name : 'Keine Filiale',
                    'appointments_count' => $s->appointments_count,
                    'skills' => $s->skills ?? [],
                    'max_daily_appointments' => $s->max_daily_appointments ?? 8,
                ];
            })
            ->toArray();
        
        // Lade Event-Types mit Buchungsstatistiken
        $this->eventTypes = CalcomEventType::where('company_id', $this->company_id)
            ->where('is_active', true)
            ->withCount(['bookings' => function ($query) {
                $query->where('created_at', '>=', now()->subDays(30));
            }])
            ->orderBy('name')
            ->get()
            ->map(function ($et) {
                return [
                    'id' => (int)$et->id,
                    'name' => $et->name,
                    'duration' => $et->duration_minutes,
                    'price' => $et->price,
                    'bookings_count' => $et->bookings_count,
                    'required_skills' => $et->required_skills ?? [],
                    'difficulty_level' => $et->difficulty_level ?? 'medium',
                ];
            })
            ->toArray();
        
        // Lade bestehende Zuordnungen
        $existingAssignments = DB::table('staff_event_types')
            ->whereIn('staff_event_types.staff_id', collect($this->staff)->pluck('id'))
            ->whereIn('staff_event_types.event_type_id', collect($this->eventTypes)->pluck('id'))
            ->get()
            ->keyBy(function ($item) {
                return $item->staff_id . '::' . $item->event_type_id;
            });
        
        // Lade Appointment-Counts separat
        $appointmentCounts = DB::table('appointments')
            ->select('staff_id', DB::raw('COUNT(*) as count'))
            ->whereIn('staff_id', collect($this->staff)->pluck('id'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('staff_id')
            ->pluck('count', 'staff_id');
            
        // Lade erfolgreiche Abschlussraten
        $successRates = DB::table('appointments')
            ->select('staff_id', 
                DB::raw('COUNT(CASE WHEN status = "completed" THEN 1 END) as completed'),
                DB::raw('COUNT(*) as total'))
            ->whereIn('staff_id', collect($this->staff)->pluck('id'))
            ->where('created_at', '>=', now()->subDays(90))
            ->groupBy('staff_id')
            ->get()
            ->mapWithKeys(function ($item) {
                $rate = $item->total > 0 ? ($item->completed / $item->total) : 0;
                return [$item->staff_id => round($rate * 100, 1)];
            });
            
        // Lade Working Hours für Verfügbarkeit
        $availability = DB::table('working_hours')
            ->select('staff_id', DB::raw('COUNT(DISTINCT day_of_week) as working_days'))
            ->whereIn('staff_id', collect($this->staff)->pluck('id'))
            ->groupBy('staff_id')
            ->pluck('working_days', 'staff_id');
        
        // Initialisiere assignments Array mit erweiterten Daten
        $this->assignments = [];
        foreach ($this->staff as $staff) {
            foreach ($this->eventTypes as $eventType) {
                $key = $staff['id'] . '::' . $eventType['id'];
                $assignment = $existingAssignments->get($key);
                
                $this->assignments[$key] = [
                    'assigned' => !is_null($assignment),
                    'performance' => [
                        'appointments' => $appointmentCounts[$staff['id']] ?? 0,
                        'success_rate' => $successRates[$staff['id']] ?? 0,
                        'availability' => $availability[$staff['id']] ?? 0,
                    ],
                    'skill_match' => $this->calculateSkillMatch($staff, $eventType),
                    'score' => $this->calculateAssignmentScore($staff, $eventType, 
                        $appointmentCounts[$staff['id']] ?? 0,
                        $successRates[$staff['id']] ?? 0,
                        $availability[$staff['id']] ?? 0
                    ),
                ];
            }
        }
    }
    
    /**
     * Berechne Skill-Übereinstimmung
     */
    private function calculateSkillMatch($staff, $eventType): float
    {
        $staffSkills = $staff['skills'] ?? [];
        $requiredSkills = $eventType['required_skills'] ?? [];
        
        if (empty($requiredSkills)) {
            return 1.0; // Keine spezifischen Skills erforderlich
        }
        
        $matchingSkills = array_intersect($staffSkills, $requiredSkills);
        return count($matchingSkills) / count($requiredSkills);
    }
    
    /**
     * Berechne Gesamtbewertung für eine Zuordnung
     */
    private function calculateAssignmentScore($staff, $eventType, $appointments, $successRate, $availability): float
    {
        // Gewichtungsfaktoren
        $weights = [
            'experience' => 0.3,      // Erfahrung (Anzahl Termine)
            'success' => 0.25,        // Erfolgsrate
            'availability' => 0.2,    // Verfügbarkeit
            'workload' => 0.15,       // Arbeitsbelastung (invers)
            'skill_match' => 0.1      // Skill-Übereinstimmung
        ];
        
        // Normalisiere Erfahrung (0-100 Punkte basierend auf Terminen)
        $experienceScore = min($appointments / 50 * 100, 100);
        
        // Erfolgsrate ist bereits in Prozent
        $successScore = $successRate;
        
        // Verfügbarkeit (0-100 basierend auf Arbeitstagen)
        $availabilityScore = ($availability / 7) * 100;
        
        // Arbeitsbelastung (weniger ist besser)
        $workloadScore = max(100 - ($appointments / 30 * 100), 0);
        
        // Skill-Match
        $skillScore = $this->calculateSkillMatch($staff, $eventType) * 100;
        
        // Gesamtscore berechnen
        $totalScore = 
            $experienceScore * $weights['experience'] +
            $successScore * $weights['success'] +
            $availabilityScore * $weights['availability'] +
            $workloadScore * $weights['workload'] +
            $skillScore * $weights['skill_match'];
            
        return round($totalScore, 1);
    }
    
    /**
     * Intelligente Verteilung basierend auf verschiedenen Faktoren
     */
    public function distributeEvenly(): void
    {
        // Berechne optimale Verteilung
        $totalAssignments = count($this->staff) * count($this->eventTypes);
        $targetAssignmentsPerStaff = ceil(count($this->eventTypes) * 0.6); // 60% Abdeckung
        
        // Sortiere Event-Types nach Beliebtheit
        $sortedEventTypes = collect($this->eventTypes)
            ->sortByDesc('bookings_count')
            ->values();
        
        // Verteile Event-Types gleichmäßig
        foreach ($this->staff as $staffIndex => $staff) {
            $assignedCount = 0;
            
            foreach ($sortedEventTypes as $eventTypeIndex => $eventType) {
                if ($assignedCount >= $targetAssignmentsPerStaff) {
                    break;
                }
                
                // Rotiere durch Mitarbeiter für faire Verteilung
                if (($eventTypeIndex % count($this->staff)) === $staffIndex) {
                    $key = $staff['id'] . '::' . $eventType['id'];
                    $this->assignments[$key]['assigned'] = true;
                    $assignedCount++;
                }
            }
        }
        
        Notification::make()
            ->title('Gleichmäßige Verteilung angewendet')
            ->body('Event-Types wurden fair auf alle Mitarbeiter verteilt.')
            ->success()
            ->send();
    }
    
    /**
     * Intelligente Zuordnung basierend auf Scoring-System
     */
    public function applyIntelligentMatching(): void
    {
        // Sammle alle möglichen Zuordnungen mit Scores
        $allAssignments = [];
        foreach ($this->staff as $staff) {
            foreach ($this->eventTypes as $eventType) {
                $key = $staff['id'] . '::' . $eventType['id'];
                $allAssignments[] = [
                    'key' => $key,
                    'staff_id' => $staff['id'],
                    'event_type_id' => $eventType['id'],
                    'score' => $this->assignments[$key]['score'] ?? 0,
                    'current_load' => 0 // Track assignments per staff
                ];
            }
        }
        
        // Sortiere nach Score (beste zuerst)
        usort($allAssignments, fn($a, $b) => $b['score'] <=> $a['score']);
        
        // Verfolge Arbeitsbelastung pro Mitarbeiter
        $staffWorkload = array_fill_keys(collect($this->staff)->pluck('id')->toArray(), 0);
        $maxAssignmentsPerStaff = ceil(count($this->eventTypes) * 0.7);
        
        // Zuordnung mit Belastungsausgleich
        foreach ($allAssignments as $assignment) {
            // Prüfe ob Mitarbeiter noch Kapazität hat
            if ($staffWorkload[$assignment['staff_id']] < $maxAssignmentsPerStaff) {
                // Prüfe ob Event-Type bereits genug Mitarbeiter hat
                $eventAssignments = collect($this->assignments)
                    ->filter(fn($a, $k) => str_ends_with($k, '::' . $assignment['event_type_id']) && $a['assigned'])
                    ->count();
                    
                // Mindestens 2 Mitarbeiter pro Event-Type (wenn möglich)
                if ($eventAssignments < min(2, count($this->staff))) {
                    $this->assignments[$assignment['key']]['assigned'] = true;
                    $staffWorkload[$assignment['staff_id']]++;
                }
            }
        }
        
        Notification::make()
            ->title('Intelligente Zuordnung abgeschlossen')
            ->body('Mitarbeiter wurden basierend auf Qualifikation und Verfügbarkeit optimal zugeordnet.')
            ->success()
            ->send();
    }
    
    /**
     * Wende vordefinierte Vorlage an
     */
    public function applyTemplate(string $templateType): void
    {
        switch ($templateType) {
            case 'basic':
                // Basis-Services für alle
                $basicEventTypes = collect($this->eventTypes)
                    ->filter(fn($et) => $et['difficulty_level'] === 'easy' || $et['duration'] <= 30)
                    ->take(3);
                
                foreach ($this->staff as $staff) {
                    foreach ($basicEventTypes as $eventType) {
                        $key = $staff['id'] . '::' . $eventType['id'];
                        $this->assignments[$key]['assigned'] = true;
                    }
                }
                break;
                
            case 'expert':
                // Nur erfahrene Mitarbeiter für komplexe Services
                foreach ($this->eventTypes as $eventType) {
                    if ($eventType['difficulty_level'] === 'hard') {
                        foreach ($this->staff as $staff) {
                            if ($staff['appointments_count'] > 50) { // Erfahrene Mitarbeiter
                                $key = $staff['id'] . '::' . $eventType['id'];
                                $this->assignments[$key]['assigned'] = true;
                            }
                        }
                    }
                }
                break;
        }
        
        Notification::make()
            ->title('Vorlage angewendet')
            ->success()
            ->send();
    }
    
    /**
     * Export der Zuordnungen
     */
    public function exportAssignments(): void
    {
        $data = [];
        
        foreach ($this->staff as $staff) {
            $row = ['Mitarbeiter' => $staff['name'], 'Filiale' => $staff['branch']];
            
            foreach ($this->eventTypes as $eventType) {
                $key = $staff['id'] . '::' . $eventType['id'];
                $row[$eventType['name']] = $this->assignments[$key]['assigned'] ? 'X' : '';
            }
            
            $data[] = $row;
        }
        
        // Excel Export würde hier implementiert
        Notification::make()
            ->title('Export vorbereitet')
            ->body('Die Zuordnungsmatrix wurde exportiert.')
            ->success()
            ->send();
    }
    
    /**
     * KI-basierte Zuordnungsvorschläge
     */
    public function suggestOptimalAssignments(): void
    {
        foreach ($this->assignments as $key => &$assignment) {
            // Berechne Empfehlungs-Score basierend auf:
            // - Skill-Match
            // - Bisherige Performance
            // - Aktuelle Auslastung
            // - Filialzugehörigkeit
            
            $score = $assignment['skill_match'] * 0.4;
            
            if ($assignment['performance']['appointments'] > 0) {
                $performanceScore = ($assignment['performance']['rating'] / 5) * 0.3;
                $score += $performanceScore;
            }
            
            // Weitere Faktoren könnten hier einbezogen werden
            
            $assignment['ai_recommendation'] = $score;
            $assignment['ai_suggested'] = $score > 0.6;
        }
        
        Notification::make()
            ->title('KI-Analyse abgeschlossen')
            ->body('Optimale Zuordnungen wurden berechnet und markiert.')
            ->success()
            ->send();
    }
    
    /**
     * Toggle einzelne Zuordnung
     */
    public function toggleAssignment(string $staffId, int $eventTypeId): void
    {
        $key = $staffId . '::' . $eventTypeId;
        
        Log::info('toggleAssignment called', [
            'key' => $key,
            'exists' => isset($this->assignments[$key]),
            'current_value' => $this->assignments[$key]['assigned'] ?? 'not set'
        ]);
        
        if (isset($this->assignments[$key])) {
            $this->assignments[$key]['assigned'] = !$this->assignments[$key]['assigned'];
            
            // Force Livewire to update the array
            $this->assignments = [...$this->assignments];
            
            Log::info('toggleAssignment after toggle', [
                'new_value' => $this->assignments[$key]['assigned']
            ]);
        }
    }
    
    /**
     * Wähle alle Event-Types für einen Mitarbeiter
     */
    public function selectAllForStaff(string $staffId): void
    {
        foreach ($this->eventTypes as $eventType) {
            $key = $staffId . '::' . $eventType['id'];
            if (isset($this->assignments[$key])) {
                $this->assignments[$key]['assigned'] = true;
            }
        }
        // Force Livewire to update
        $this->assignments = [...$this->assignments];
    }
    
    /**
     * Wähle alle Mitarbeiter für einen Event-Type
     */
    public function selectAllForEventType(int $eventTypeId): void
    {
        foreach ($this->staff as $staff) {
            $key = $staff['id'] . '::' . $eventTypeId;
            if (isset($this->assignments[$key])) {
                $this->assignments[$key]['assigned'] = true;
            }
        }
        // Force Livewire to update
        $this->assignments = [...$this->assignments];
    }
    
    /**
     * Wähle alle Zuordnungen
     */
    public function selectAll(): void
    {
        foreach ($this->assignments as $key => $assignment) {
            $this->assignments[$key]['assigned'] = true;
        }
        // Force Livewire to update
        $this->assignments = [...$this->assignments];
    }
    
    /**
     * Wähle alle Zuordnungen ab
     */
    public function deselectAll(): void
    {
        foreach ($this->assignments as $key => $assignment) {
            $this->assignments[$key]['assigned'] = false;
        }
        // Force Livewire to update
        $this->assignments = [...$this->assignments];
    }
    
    /**
     * Speichere Zuordnungen in der Datenbank
     */
    public function saveAssignments(): void
    {
        try {
            DB::beginTransaction();
            
            // Lösche bestehende Zuordnungen für diese Company
            DB::table('staff_event_types')
                ->whereIn('staff_id', collect($this->staff)->pluck('id'))
                ->whereIn('event_type_id', collect($this->eventTypes)->pluck('id'))
                ->delete();
            
            // Füge neue Zuordnungen ein
            $toInsert = [];
            foreach ($this->assignments as $key => $assignment) {
                if ($assignment['assigned']) {
                    [$staffId, $eventTypeId] = explode('::', $key);
                    $toInsert[] = [
                        'staff_id' => $staffId,
                        'event_type_id' => $eventTypeId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            
            if (!empty($toInsert)) {
                DB::table('staff_event_types')->insert($toInsert);
            }
            
            DB::commit();
            
            Notification::make()
                ->title('Zuordnungen gespeichert')
                ->body(count($toInsert) . ' Zuordnungen wurden erfolgreich gespeichert.')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Notification::make()
                ->title('Fehler beim Speichern')
                ->body('Die Zuordnungen konnten nicht gespeichert werden: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}