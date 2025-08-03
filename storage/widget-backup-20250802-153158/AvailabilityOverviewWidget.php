<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use App\Models\CalcomEventType;
use App\Models\Company;
use App\Services\AvailabilityChecker;
use Carbon\Carbon;

class AvailabilityOverviewWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.availability-overview';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 3;
    
    public ?int $companyId = null;
    public array $availabilityData = [];
    public bool $isLoading = false;
    
    public function mount(): void
    {
        // Hole erste Company als Standard
        $firstCompany = Company::first();
        if ($firstCompany) {
            $this->companyId = $firstCompany->id;
            $this->loadAvailability();
        }
    }
    
    public function loadAvailability(): void
    {
        if (!$this->companyId) {
            return;
        }
        
        $this->isLoading = true;
        $this->availabilityData = [];
        
        try {
            // Hole aktive Event-Types für das Unternehmen
            $eventTypes = CalcomEventType::where('company_id', $this->companyId)
                ->where('is_active', true)
                ->limit(5) // Zeige nur die ersten 5
                ->get();
            
            $availabilityChecker = app(AvailabilityChecker::class);
            
            foreach ($eventTypes as $eventType) {
                // Prüfe Verfügbarkeit für die nächsten 7 Tage
                $availability = $availabilityChecker->checkAvailability(
                    $eventType->id,
                    Carbon::now()->toIso8601String(),
                    Carbon::now()->addDays(7)->toIso8601String()
                );
                
                $this->availabilityData[] = [
                    'event_type' => $eventType->name,
                    'duration' => $eventType->duration_minutes,
                    'available' => $availability['available'] ?? false,
                    'next_slot' => $this->formatNextSlot($availability['slots'] ?? []),
                    'total_slots' => count($availability['slots'] ?? []),
                    'staff_count' => $availability['available_staff_count'] ?? 0
                ];
            }
        } catch (\Exception $e) {
            \Log::error('Availability widget error', ['error' => $e->getMessage()]);
        }
        
        $this->isLoading = false;
    }
    
    private function formatNextSlot(array $slots): ?string
    {
        if (empty($slots)) {
            return null;
        }
        
        $nextSlot = $slots[0];
        $start = Carbon::parse($nextSlot['start']);
        
        return $start->format('d.m.Y H:i');
    }
    
    public function updatedCompanyId(): void
    {
        $this->loadAvailability();
    }
}