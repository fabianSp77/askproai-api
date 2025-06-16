<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Staff;
use App\Models\CalcomEventType;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ConflictDetectionService
{
    /**
     * Erkenne Konflikte für eine neue Buchung
     */
    public function detectConflicts(string $staffId, Carbon $startTime, Carbon $endTime, ?int $excludeAppointmentId = null): array
    {
        $conflicts = [
            'has_conflicts' => false,
            'conflicts' => [],
            'warnings' => [],
        ];
        
        // 1. Doppelbuchungen prüfen
        $overlappingAppointments = $this->checkDoubleBookings($staffId, $startTime, $endTime, $excludeAppointmentId);
        if ($overlappingAppointments->isNotEmpty()) {
            $conflicts['has_conflicts'] = true;
            $conflicts['conflicts']['double_bookings'] = $overlappingAppointments->toArray();
        }
        
        // 2. Arbeitszeiten prüfen
        if (!$this->isWithinWorkingHours($staffId, $startTime, $endTime)) {
            $conflicts['has_conflicts'] = true;
            $conflicts['conflicts']['outside_working_hours'] = [
                'message' => 'Der Termin liegt außerhalb der Arbeitszeiten',
                'start' => $startTime->format('H:i'),
                'end' => $endTime->format('H:i'),
            ];
        }
        
        // 3. Kapazitätsgrenzen prüfen
        $capacityCheck = $this->checkDailyCapacity($staffId, $startTime);
        if ($capacityCheck['exceeded']) {
            $conflicts['warnings']['capacity'] = $capacityCheck;
        }
        
        // 4. Pausenzeiten prüfen
        if ($this->conflictsWithBreak($staffId, $startTime, $endTime)) {
            $conflicts['warnings']['break_time'] = [
                'message' => 'Der Termin überschneidet sich mit einer Pausenzeit',
            ];
        }
        
        // 5. Back-to-Back Termine prüfen
        $backToBackCheck = $this->checkBackToBackAppointments($staffId, $startTime, $endTime);
        if ($backToBackCheck['has_issues']) {
            $conflicts['warnings']['back_to_back'] = $backToBackCheck;
        }
        
        return $conflicts;
    }
    
    /**
     * Prüfe auf Doppelbuchungen
     */
    protected function checkDoubleBookings(string $staffId, Carbon $startTime, Carbon $endTime, ?int $excludeId): Collection
    {
        $query = Appointment::where('staff_id', $staffId)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->where(function($q) use ($startTime, $endTime) {
                // Überlappung: Neuer Termin startet während eines bestehenden
                $q->where(function($q2) use ($startTime, $endTime) {
                    $q2->where('starts_at', '<=', $startTime)
                       ->where('ends_at', '>', $startTime);
                })
                // Oder: Neuer Termin endet während eines bestehenden
                ->orWhere(function($q2) use ($startTime, $endTime) {
                    $q2->where('starts_at', '<', $endTime)
                       ->where('ends_at', '>=', $endTime);
                })
                // Oder: Neuer Termin umschließt einen bestehenden
                ->orWhere(function($q2) use ($startTime, $endTime) {
                    $q2->where('starts_at', '>=', $startTime)
                       ->where('ends_at', '<=', $endTime);
                });
            });
            
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->get()->map(function($appointment) {
            return [
                'id' => $appointment->id,
                'customer' => $appointment->customer->name ?? 'Unbekannt',
                'start' => $appointment->starts_at->format('H:i'),
                'end' => $appointment->ends_at->format('H:i'),
                'status' => $appointment->status,
            ];
        });
    }
    
    /**
     * Prüfe ob Termin innerhalb der Arbeitszeiten liegt
     */
    protected function isWithinWorkingHours(string $staffId, Carbon $startTime, Carbon $endTime): bool
    {
        $staff = Staff::find($staffId);
        $dayOfWeek = $startTime->dayOfWeek;
        
        $workingHours = $staff->workingHours()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();
            
        if (!$workingHours) {
            return false;
        }
        
        $workStart = Carbon::parse($workingHours->start_time);
        $workEnd = Carbon::parse($workingHours->end_time);
        
        return $startTime->format('H:i') >= $workStart->format('H:i') 
            && $endTime->format('H:i') <= $workEnd->format('H:i');
    }
    
    /**
     * Prüfe tägliche Kapazitätsgrenzen
     */
    protected function checkDailyCapacity(string $staffId, Carbon $date): array
    {
        $staff = Staff::find($staffId);
        $maxDaily = $staff->max_daily_appointments ?? 8;
        
        $currentCount = Appointment::where('staff_id', $staffId)
            ->whereDate('starts_at', $date)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->count();
            
        return [
            'exceeded' => $currentCount >= $maxDaily,
            'current' => $currentCount,
            'max' => $maxDaily,
            'percentage' => round(($currentCount / $maxDaily) * 100),
            'message' => $currentCount >= $maxDaily 
                ? "Maximale Tageskapazität erreicht ({$currentCount}/{$maxDaily})"
                : "Auslastung: {$currentCount}/{$maxDaily} Termine",
        ];
    }
    
    /**
     * Prüfe ob Termin mit Pausenzeiten kollidiert
     */
    protected function conflictsWithBreak(string $staffId, Carbon $startTime, Carbon $endTime): bool
    {
        $staff = Staff::find($staffId);
        $dayOfWeek = $startTime->dayOfWeek;
        
        $workingHours = $staff->workingHours()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->whereNotNull('break_start')
            ->first();
            
        if (!$workingHours || !$workingHours->break_start) {
            return false;
        }
        
        $breakStart = Carbon::parse($workingHours->break_start);
        $breakEnd = Carbon::parse($workingHours->break_end);
        
        // Prüfe Überlappung mit Pause
        return !($endTime->format('H:i') <= $breakStart->format('H:i') 
            || $startTime->format('H:i') >= $breakEnd->format('H:i'));
    }
    
    /**
     * Prüfe Back-to-Back Termine
     */
    protected function checkBackToBackAppointments(string $staffId, Carbon $startTime, Carbon $endTime): array
    {
        $bufferMinutes = 15; // Mindestens 15 Minuten zwischen Terminen
        
        // Termin direkt davor
        $previousAppointment = Appointment::where('staff_id', $staffId)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->where('ends_at', '<=', $startTime)
            ->where('ends_at', '>', $startTime->copy()->subMinutes($bufferMinutes))
            ->first();
            
        // Termin direkt danach
        $nextAppointment = Appointment::where('staff_id', $staffId)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->where('starts_at', '>=', $endTime)
            ->where('starts_at', '<', $endTime->copy()->addMinutes($bufferMinutes))
            ->first();
            
        $issues = [];
        
        if ($previousAppointment) {
            $gap = $startTime->diffInMinutes($previousAppointment->ends_at);
            $issues[] = [
                'type' => 'previous',
                'gap_minutes' => $gap,
                'appointment' => [
                    'id' => $previousAppointment->id,
                    'ends_at' => $previousAppointment->ends_at->format('H:i'),
                ],
                'message' => "Nur {$gap} Minuten Pause nach vorherigem Termin",
            ];
        }
        
        if ($nextAppointment) {
            $gap = $nextAppointment->starts_at->diffInMinutes($endTime);
            $issues[] = [
                'type' => 'next',
                'gap_minutes' => $gap,
                'appointment' => [
                    'id' => $nextAppointment->id,
                    'starts_at' => $nextAppointment->starts_at->format('H:i'),
                ],
                'message' => "Nur {$gap} Minuten Pause vor nächstem Termin",
            ];
        }
        
        return [
            'has_issues' => count($issues) > 0,
            'issues' => $issues,
        ];
    }
    
    /**
     * Batch-Konfliktprüfung für mehrere Termine
     */
    public function batchConflictCheck(array $appointments): array
    {
        $results = [];
        
        foreach ($appointments as $appointment) {
            $conflicts = $this->detectConflicts(
                $appointment['staff_id'],
                Carbon::parse($appointment['start_time']),
                Carbon::parse($appointment['end_time']),
                $appointment['exclude_id'] ?? null
            );
            
            $results[] = [
                'appointment' => $appointment,
                'conflicts' => $conflicts,
            ];
        }
        
        return $results;
    }
}