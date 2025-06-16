<?php

namespace App\Filament\Components;

use App\Models\Appointment;
use App\Models\Staff;
use App\Models\WorkingHour;
use Carbon\Carbon;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Concerns\HasName;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Collection;

class LiveAvailabilityChecker extends Component
{
    protected string $view = 'filament.components.live-availability-checker';
    
    protected ?int $staffId = null;
    protected ?string $date = null;
    protected ?int $serviceId = null;
    protected int $slotDuration = 30;
    protected bool $showAlternatives = true;
    
    public function staffId(int $staffId): static
    {
        $this->staffId = $staffId;
        return $this;
    }
    
    public function date(string $date): static
    {
        $this->date = $date;
        return $this;
    }
    
    public function serviceId(int $serviceId): static
    {
        $this->serviceId = $serviceId;
        return $this;
    }
    
    public function slotDuration(int $minutes): static
    {
        $this->slotDuration = $minutes;
        return $this;
    }
    
    public function showAlternatives(bool $show = true): static
    {
        $this->showAlternatives = $show;
        return $this;
    }
    
    public function getAvailableSlots(): Collection
    {
        if (!$this->staffId || !$this->date) {
            return collect();
        }
        
        $date = Carbon::parse($this->date);
        $staff = Staff::find($this->staffId);
        
        if (!$staff) {
            return collect();
        }
        
        // Get working hours for the day
        $workingHour = $staff->workingHours()
            ->where('day_of_week', $date->dayOfWeek)
            ->where('is_available', true)
            ->first();
            
        if (!$workingHour) {
            return collect();
        }
        
        // Get all appointments for the day
        $appointments = Appointment::where('staff_id', $this->staffId)
            ->whereDate('starts_at', $date)
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('starts_at')
            ->get();
        
        // Generate available slots
        $slots = collect();
        $start = Carbon::parse($date->format('Y-m-d') . ' ' . $workingHour->start_time);
        $end = Carbon::parse($date->format('Y-m-d') . ' ' . $workingHour->end_time);
        
        $current = $start->copy();
        
        while ($current < $end) {
            $slotEnd = $current->copy()->addMinutes($this->slotDuration);
            
            // Check if slot is available
            $isAvailable = !$appointments->some(function ($appointment) use ($current, $slotEnd) {
                return $current < $appointment->ends_at && $slotEnd > $appointment->starts_at;
            });
            
            if ($isAvailable && $slotEnd <= $end) {
                $slots->push([
                    'start' => $current->copy(),
                    'end' => $slotEnd->copy(),
                    'display' => $current->format('H:i'),
                    'available' => true,
                ]);
            }
            
            $current->addMinutes($this->slotDuration);
        }
        
        return $slots;
    }
    
    public function getSuggestedSlots(): Collection
    {
        if (!$this->showAlternatives) {
            return collect();
        }
        
        $suggestions = collect();
        $baseDate = Carbon::parse($this->date ?? now());
        
        // Check next 7 days
        for ($i = 0; $i < 7; $i++) {
            $checkDate = $baseDate->copy()->addDays($i);
            $this->date = $checkDate->format('Y-m-d');
            
            $slots = $this->getAvailableSlots();
            
            if ($slots->isNotEmpty()) {
                $suggestions->push([
                    'date' => $checkDate,
                    'slots' => $slots->take(3),
                ]);
            }
            
            if ($suggestions->count() >= 3) {
                break;
            }
        }
        
        return $suggestions;
    }
    
    public function getNextAvailable(): ?array
    {
        $suggestions = $this->getSuggestedSlots();
        
        if ($suggestions->isEmpty()) {
            return null;
        }
        
        $firstSuggestion = $suggestions->first();
        $firstSlot = $firstSuggestion['slots']->first();
        
        return [
            'date' => $firstSuggestion['date'],
            'time' => $firstSlot['display'],
            'start' => $firstSlot['start'],
            'end' => $firstSlot['end'],
        ];
    }
}