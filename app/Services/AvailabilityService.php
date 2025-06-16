<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Staff;
use App\Models\WorkingHour;
use App\Models\Appointment;
use App\Models\CalcomEventType;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\CacheService;

class AvailabilityService
{
    protected int $slotDuration; // Minuten
    protected int $bufferTime; // Puffer zwischen Terminen
    protected CacheService $cacheService;
    
    public function __construct(CacheService $cacheService)
    {
        $this->slotDuration = config('booking.slot_duration', 15);
        $this->bufferTime = config('booking.buffer_time', 5);
        $this->cacheService = $cacheService;
    }
    
    /**
     * Prüfe Echtzeit-Verfügbarkeit für einen Mitarbeiter
     */
    public function checkRealTimeAvailability(string $staffId, int $eventTypeId, Carbon $date): array
    {
        return $this->cacheService->getAvailability((int)$staffId, $date->format('Y-m-d'), function() use ($staffId, $eventTypeId, $date) {
            $staff = Staff::with(['workingHours', 'appointments'])->find($staffId);
            
            if (!$staff || !$staff->active) {
                return ['available' => false, 'slots' => []];
            }
            
            // Hole Arbeitszeiten für den Wochentag
            $dayOfWeek = $date->dayOfWeek;
            $workingHours = $this->getWorkingHoursForDay($staff, $dayOfWeek);
            
            if (empty($workingHours)) {
                return ['available' => false, 'slots' => []];
            }
            
            // Generiere alle möglichen Zeitslots
            $allSlots = $this->generateTimeSlots($workingHours, $date);
            
            // Filtere bereits gebuchte Slots
            $bookedSlots = $this->getBookedSlots($staffId, $date);
            
            // Berechne verfügbare Slots
            $availableSlots = $this->filterAvailableSlots($allSlots, $bookedSlots, $eventTypeId);
            
            return [
                'available' => count($availableSlots) > 0,
                'slots' => $availableSlots,
                'total_slots' => count($allSlots),
                'booked_slots' => count($bookedSlots),
                'utilization' => count($allSlots) > 0 ? 
                    round((count($bookedSlots) / count($allSlots)) * 100, 1) : 0
            ];
        });
    }
    
    /**
     * Hole Arbeitszeiten für einen bestimmten Wochentag
     */
    protected function getWorkingHoursForDay(Staff $staff, int $dayOfWeek): array
    {
        return $staff->workingHours
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->map(function ($wh) {
                return [
                    'start' => Carbon::parse($wh->start_time),
                    'end' => Carbon::parse($wh->end_time),
                    'break_start' => $wh->break_start ? Carbon::parse($wh->break_start) : null,
                    'break_end' => $wh->break_end ? Carbon::parse($wh->break_end) : null,
                ];
            })
            ->toArray();
    }
    
    /**
     * Generiere alle möglichen Zeitslots
     */
    protected function generateTimeSlots(array $workingHours, Carbon $date): array
    {
        $slots = [];
        
        foreach ($workingHours as $period) {
            $current = $date->copy()->setTime($period['start']->hour, $period['start']->minute);
            $end = $date->copy()->setTime($period['end']->hour, $period['end']->minute);
            
            while ($current->lt($end)) {
                $slotEnd = $current->copy()->addMinutes($this->slotDuration);
                
                // Überspringe Pausenzeiten
                if ($period['break_start'] && $period['break_end']) {
                    $breakStart = $date->copy()->setTime($period['break_start']->hour, $period['break_start']->minute);
                    $breakEnd = $date->copy()->setTime($period['break_end']->hour, $period['break_end']->minute);
                    
                    if ($current->gte($breakStart) && $current->lt($breakEnd)) {
                        $current = $breakEnd;
                        continue;
                    }
                }
                
                $slots[] = [
                    'start' => $current->format('H:i'),
                    'end' => $slotEnd->format('H:i'),
                    'datetime' => $current->toIso8601String(),
                ];
                
                $current->addMinutes($this->slotDuration);
            }
        }
        
        return $slots;
    }
    
    /**
     * Hole bereits gebuchte Slots
     */
    protected function getBookedSlots(string $staffId, Carbon $date): array
    {
        return Appointment::where('staff_id', $staffId)
            ->whereDate('starts_at', $date)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->get()
            ->map(function ($appointment) {
                return [
                    'start' => Carbon::parse($appointment->starts_at)->format('H:i'),
                    'end' => Carbon::parse($appointment->ends_at)->format('H:i'),
                    'buffer_before' => 5, // 5 Minuten Puffer vor Termin
                    'buffer_after' => 5,  // 5 Minuten Puffer nach Termin
                ];
            })
            ->toArray();
    }
    
    /**
     * Filtere verfügbare Slots basierend auf Buchungen
     */
    protected function filterAvailableSlots(array $allSlots, array $bookedSlots, int $eventTypeId): array
    {
        // Hole Event-Type Dauer
        $eventType = DB::table('calcom_event_types')->find($eventTypeId);
        $eventDuration = $eventType ? $eventType->duration_minutes : 30;
        
        return array_values(array_filter($allSlots, function($slot) use ($bookedSlots, $eventDuration) {
            $slotStart = Carbon::parse($slot['datetime']);
            $slotEnd = $slotStart->copy()->addMinutes($eventDuration);
            
            // Prüfe ob genug Zeit für das Event vorhanden ist
            foreach ($bookedSlots as $booked) {
                $bookedStart = Carbon::parse($booked['start'])->subMinutes($booked['buffer_before']);
                $bookedEnd = Carbon::parse($booked['end'])->addMinutes($booked['buffer_after']);
                
                // Überlappung prüfen
                if ($slotStart->lt($bookedEnd) && $slotEnd->gt($bookedStart)) {
                    return false;
                }
            }
            
            // Prüfe ob Slot in der Zukunft liegt
            if ($slotStart->lte(now())) {
                return false;
            }
            
            return true;
        }));
    }
    
    /**
     * Prüfe Verfügbarkeit für mehrere Mitarbeiter gleichzeitig
     */
    public function checkMultipleStaffAvailability(array $staffIds, int $eventTypeId, Carbon $date): Collection
    {
        return collect($staffIds)->map(function($staffId) use ($eventTypeId, $date) {
            $availability = $this->checkRealTimeAvailability($staffId, $eventTypeId, $date);
            
            return [
                'staff_id' => $staffId,
                'date' => $date->format('Y-m-d'),
                'available' => $availability['available'],
                'slots_count' => count($availability['slots']),
                'utilization' => $availability['utilization'] ?? 0,
                'next_available' => $this->getNextAvailableSlot($staffId, $eventTypeId, $date),
            ];
        });
    }
    
    /**
     * Finde nächsten verfügbaren Slot
     */
    public function getNextAvailableSlot(string $staffId, int $eventTypeId, Carbon $fromDate): ?array
    {
        $maxDays = 30; // Maximal 30 Tage in die Zukunft schauen
        
        for ($i = 0; $i < $maxDays; $i++) {
            $checkDate = $fromDate->copy()->addDays($i);
            $availability = $this->checkRealTimeAvailability($staffId, $eventTypeId, $checkDate);
            
            if ($availability['available'] && count($availability['slots']) > 0) {
                return [
                    'date' => $checkDate->format('Y-m-d'),
                    'slot' => $availability['slots'][0],
                ];
            }
        }
        
        return null;
    }
}