<?php

namespace App\Services\Booking;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Branch;
use App\Models\Staff;
use App\Services\CalcomV2Service;
use App\Services\Calcom\CalcomAvailabilityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

class RecurringAppointmentService
{
    protected CalcomAvailabilityService $availabilityService;
    protected CalcomV2Service $calcomService;
    
    public function __construct(
        CalcomAvailabilityService $availabilityService = null,
        CalcomV2Service $calcomService = null
    ) {
        $this->availabilityService = $availabilityService ?? app(CalcomAvailabilityService::class);
        $this->calcomService = $calcomService ?? app(CalcomV2Service::class);
    }
    
    /**
     * Erstelle eine Serie von wiederkehrenden Terminen
     */
    public function createRecurringSeries(array $data): array
    {
        try {
            DB::beginTransaction();
            
            $seriesId = Str::uuid();
            $appointments = [];
            $failedSlots = [];
            
            // Validiere Eingabedaten
            $validated = $this->validateRecurringData($data);
            
            // Generiere alle Termine basierend auf dem Muster
            $slots = $this->generateRecurringSlots($validated);
            
            // Prüfe Verfügbarkeit für alle Slots
            $availabilityCheck = $this->checkBulkAvailability($slots, $validated);
            
            if ($availabilityCheck['all_available'] === false && !$validated['allow_partial']) {
                DB::rollback();
                return [
                    'success' => false,
                    'message' => 'Nicht alle Termine sind verfügbar',
                    'unavailable_slots' => $availabilityCheck['unavailable_slots'],
                    'available_slots' => $availabilityCheck['available_slots']
                ];
            }
            
            // Erstelle Serie-Eintrag
            $series = DB::table('appointment_series')->insertGetId([
                'company_id' => $validated['company_id'],
                'customer_id' => $validated['customer_id'],
                'branch_id' => $validated['branch_id'],
                'staff_id' => $validated['staff_id'] ?? null,
                'series_id' => $seriesId,
                'title' => $validated['title'] ?? 'Terminserie',
                'description' => $validated['description'] ?? null,
                'recurrence_pattern' => json_encode($validated['recurrence_pattern']),
                'start_date' => $slots[0]['datetime'],
                'end_date' => $slots[count($slots) - 1]['datetime'] ?? null,
                'total_appointments' => count($availabilityCheck['available_slots']),
                'status' => 'active',
                'metadata' => json_encode($validated['metadata'] ?? []),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Erstelle einzelne Termine
            $parentId = null;
            foreach ($availabilityCheck['available_slots'] as $index => $slot) {
                $appointment = $this->createSingleAppointment(
                    $slot,
                    $validated,
                    $seriesId,
                    $parentId,
                    $index + 1
                );
                
                if ($appointment) {
                    $appointments[] = $appointment;
                    if ($index === 0) {
                        $parentId = $appointment->id;
                    }
                } else {
                    $failedSlots[] = $slot;
                }
            }
            
            // Aktualisiere Customer Stats
            $this->updateCustomerStats($validated['customer_id'], count($appointments));
            
            DB::commit();
            
            // Log erfolgreiche Erstellung
            Log::info('Recurring appointment series created', [
                'series_id' => $seriesId,
                'total_appointments' => count($appointments),
                'failed_slots' => count($failedSlots)
            ]);
            
            return [
                'success' => true,
                'series_id' => $seriesId,
                'appointments' => $appointments,
                'total_created' => count($appointments),
                'failed_slots' => $failedSlots,
                'message' => count($appointments) . ' Termine erfolgreich erstellt'
            ];
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to create recurring appointments', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            
            return [
                'success' => false,
                'message' => 'Fehler beim Erstellen der Terminserie: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generiere wiederkehrende Zeitslots basierend auf Muster
     */
    protected function generateRecurringSlots(array $data): array
    {
        $slots = [];
        $pattern = $data['recurrence_pattern'];
        $startDate = Carbon::parse($data['start_date']);
        $endDate = isset($data['end_date']) ? Carbon::parse($data['end_date']) : null;
        $maxCount = $pattern['count'] ?? 52; // Default: 1 Jahr
        
        $currentDate = $startDate->copy();
        $count = 0;
        
        while ($count < $maxCount && (!$endDate || $currentDate->lte($endDate))) {
            switch ($pattern['type']) {
                case 'daily':
                    if ($this->shouldIncludeDate($currentDate, $pattern)) {
                        $slots[] = $this->createSlot($currentDate, $data);
                        $count++;
                    }
                    $currentDate->addDays($pattern['interval'] ?? 1);
                    break;
                    
                case 'weekly':
                    $weekdays = $pattern['days'] ?? [$startDate->dayOfWeek];
                    foreach ($weekdays as $weekday) {
                        $nextDate = $currentDate->copy()->next($weekday);
                        if ($nextDate->gte($startDate) && (!$endDate || $nextDate->lte($endDate))) {
                            $slots[] = $this->createSlot($nextDate, $data);
                            $count++;
                        }
                    }
                    $currentDate->addWeeks($pattern['interval'] ?? 1);
                    break;
                    
                case 'monthly':
                    if ($pattern['day_of_month'] ?? false) {
                        // Specific day of month (e.g., 15th)
                        $nextDate = $currentDate->copy()->day($pattern['day_of_month']);
                        if ($nextDate->gte($startDate)) {
                            $slots[] = $this->createSlot($nextDate, $data);
                            $count++;
                        }
                    } else {
                        // Relative day (e.g., 2nd Tuesday)
                        $weekday = $pattern['weekday'] ?? $startDate->dayOfWeek;
                        $occurrence = $pattern['occurrence'] ?? 1;
                        $nextDate = $this->getNthWeekdayOfMonth($currentDate, $weekday, $occurrence);
                        if ($nextDate && $nextDate->gte($startDate)) {
                            $slots[] = $this->createSlot($nextDate, $data);
                            $count++;
                        }
                    }
                    $currentDate->addMonths($pattern['interval'] ?? 1);
                    break;
            }
            
            // Sicherheitslimit
            if ($count > 365) {
                Log::warning('Recurring appointment generation hit safety limit', [
                    'pattern' => $pattern,
                    'generated' => $count
                ]);
                break;
            }
        }
        
        return $slots;
    }
    
    /**
     * Prüfe Verfügbarkeit für mehrere Slots
     */
    protected function checkBulkAvailability(array $slots, array $data): array
    {
        $availableSlots = [];
        $unavailableSlots = [];
        
        // Gruppiere Slots nach Datum für effiziente Prüfung
        $slotsByDate = [];
        foreach ($slots as $slot) {
            $date = Carbon::parse($slot['datetime'])->format('Y-m-d');
            $slotsByDate[$date][] = $slot;
        }
        
        // Prüfe Verfügbarkeit pro Tag
        foreach ($slotsByDate as $date => $daySlots) {
            $availability = $this->availabilityService->checkAvailability(
                $data['event_type_id'],
                $date
            );
            
            if ($availability['available']) {
                $availableTimes = array_column($availability['slots'], 'time');
                
                foreach ($daySlots as $slot) {
                    $slotTime = Carbon::parse($slot['datetime'])->format('H:i');
                    if (in_array($slotTime, $availableTimes)) {
                        $availableSlots[] = $slot;
                    } else {
                        $unavailableSlots[] = $slot;
                    }
                }
            } else {
                $unavailableSlots = array_merge($unavailableSlots, $daySlots);
            }
        }
        
        return [
            'all_available' => empty($unavailableSlots),
            'available_slots' => $availableSlots,
            'unavailable_slots' => $unavailableSlots,
            'availability_rate' => count($availableSlots) / count($slots)
        ];
    }
    
    /**
     * Erstelle einzelnen Termin als Teil einer Serie
     */
    protected function createSingleAppointment(
        array $slot,
        array $data,
        string $seriesId,
        ?int $parentId,
        int $sequenceNumber
    ): ?Appointment {
        try {
            // Buche bei Cal.com
            $calcomBooking = $this->calcomService->createBooking([
                'eventTypeId' => $data['event_type_id'],
                'start' => $slot['datetime'],
                'attendee' => [
                    'name' => $data['customer_name'],
                    'email' => $data['customer_email'],
                    'phone' => $data['customer_phone']
                ],
                'metadata' => [
                    'series_id' => $seriesId,
                    'sequence_number' => $sequenceNumber,
                    'is_recurring' => true
                ]
            ]);
            
            if (!$calcomBooking['success']) {
                Log::warning('Cal.com booking failed for recurring appointment', [
                    'slot' => $slot,
                    'error' => $calcomBooking['message'] ?? 'Unknown error'
                ]);
                return null;
            }
            
            // Erstelle lokalen Termin
            $appointment = Appointment::create([
                'company_id' => $data['company_id'],
                'branch_id' => $data['branch_id'],
                'customer_id' => $data['customer_id'],
                'staff_id' => $data['staff_id'] ?? null,
                'service_id' => $data['service_id'] ?? null,
                'parent_appointment_id' => $parentId,
                'series_id' => $seriesId,
                'booking_type' => 'recurring',
                'recurrence_count' => $sequenceNumber,
                'start_time' => $slot['datetime'],
                'end_time' => Carbon::parse($slot['datetime'])->addMinutes($data['duration'] ?? 60),
                'status' => 'scheduled',
                'calcom_booking_id' => $calcomBooking['data']['id'] ?? null,
                'calcom_event_type_id' => $data['event_type_id'],
                'metadata' => array_merge($data['metadata'] ?? [], [
                    'series_id' => $seriesId,
                    'sequence_number' => $sequenceNumber,
                    'recurrence_pattern' => $data['recurrence_pattern']
                ])
            ]);
            
            return $appointment;
            
        } catch (\Exception $e) {
            Log::error('Failed to create single recurring appointment', [
                'slot' => $slot,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Storniere eine komplette Terminserie
     */
    public function cancelSeries(string $seriesId, array $options = []): array
    {
        try {
            DB::beginTransaction();
            
            $cancelFuture = $options['cancel_future_only'] ?? true;
            $reason = $options['reason'] ?? 'Serie storniert';
            
            // Hole alle Termine der Serie
            $appointments = Appointment::where('series_id', $seriesId)
                ->when($cancelFuture, function ($query) {
                    $query->where('start_time', '>=', now());
                })
                ->get();
            
            $cancelledCount = 0;
            $failedCancellations = [];
            
            foreach ($appointments as $appointment) {
                try {
                    // Storniere bei Cal.com
                    if ($appointment->calcom_booking_id) {
                        $this->calcomService->cancelBooking($appointment->calcom_booking_id, $reason);
                    }
                    
                    // Update lokalen Status
                    $appointment->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                        'cancellation_reason' => $reason
                    ]);
                    
                    $cancelledCount++;
                    
                } catch (\Exception $e) {
                    $failedCancellations[] = [
                        'appointment_id' => $appointment->id,
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            // Update Serie-Status
            DB::table('appointment_series')
                ->where('series_id', $seriesId)
                ->update([
                    'status' => 'cancelled',
                    'cancelled_appointments' => $cancelledCount,
                    'updated_at' => now()
                ]);
            
            DB::commit();
            
            return [
                'success' => true,
                'cancelled_count' => $cancelledCount,
                'failed_cancellations' => $failedCancellations,
                'message' => "$cancelledCount Termine wurden storniert"
            ];
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to cancel appointment series', [
                'series_id' => $seriesId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Fehler beim Stornieren der Terminserie'
            ];
        }
    }
    
    /**
     * Ändere einen einzelnen Termin in einer Serie
     */
    public function modifySeriesAppointment(int $appointmentId, array $changes): array
    {
        try {
            $appointment = Appointment::findOrFail($appointmentId);
            
            if (!$appointment->series_id) {
                return [
                    'success' => false,
                    'message' => 'Termin ist nicht Teil einer Serie'
                ];
            }
            
            // Prüfe neue Verfügbarkeit falls Zeitänderung
            if (isset($changes['new_datetime'])) {
                $newDate = Carbon::parse($changes['new_datetime'])->format('Y-m-d');
                $newTime = Carbon::parse($changes['new_datetime'])->format('H:i');
                
                $isAvailable = $this->availabilityService->isTimeSlotAvailable(
                    $appointment->calcom_event_type_id,
                    $newDate,
                    $newTime
                );
                
                if (!$isAvailable) {
                    return [
                        'success' => false,
                        'message' => 'Der neue Termin ist nicht verfügbar'
                    ];
                }
                
                // Verschiebe bei Cal.com
                if ($appointment->calcom_booking_id) {
                    $rescheduleResult = $this->calcomService->rescheduleBooking(
                        $appointment->calcom_booking_id,
                        $changes['new_datetime']
                    );
                    
                    if (!$rescheduleResult['success']) {
                        return [
                            'success' => false,
                            'message' => 'Fehler beim Verschieben des Termins'
                        ];
                    }
                }
                
                // Update lokalen Termin
                $appointment->update([
                    'start_time' => $changes['new_datetime'],
                    'end_time' => Carbon::parse($changes['new_datetime'])
                        ->addMinutes($appointment->duration_minutes ?? 60),
                    'metadata' => array_merge($appointment->metadata ?? [], [
                        'modified_from_series' => true,
                        'original_datetime' => $appointment->start_time
                    ])
                ]);
            }
            
            // Andere Änderungen
            if (isset($changes['staff_id'])) {
                $appointment->update(['staff_id' => $changes['staff_id']]);
            }
            
            if (isset($changes['notes'])) {
                $appointment->update([
                    'notes' => $changes['notes'],
                    'metadata' => array_merge($appointment->metadata ?? [], [
                        'last_modified' => now()
                    ])
                ]);
            }
            
            return [
                'success' => true,
                'appointment' => $appointment->fresh(),
                'message' => 'Termin erfolgreich geändert'
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to modify series appointment', [
                'appointment_id' => $appointmentId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Fehler beim Ändern des Termins'
            ];
        }
    }
    
    /**
     * Hilfsmethoden
     */
    
    protected function validateRecurringData(array $data): array
    {
        // Basis-Validierung
        $required = ['company_id', 'customer_id', 'branch_id', 'event_type_id', 'start_date', 'recurrence_pattern'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("Pflichtfeld '$field' fehlt");
            }
        }
        
        // Validiere Recurrence Pattern
        $pattern = $data['recurrence_pattern'];
        if (!in_array($pattern['type'], ['daily', 'weekly', 'monthly'])) {
            throw new \InvalidArgumentException("Ungültiger Wiederholungstyp: " . $pattern['type']);
        }
        
        return $data;
    }
    
    protected function createSlot(Carbon $date, array $data): array
    {
        $time = $data['time'] ?? '09:00';
        $datetime = $date->copy()->setTimeFromTimeString($time);
        
        return [
            'date' => $date->format('Y-m-d'),
            'time' => $time,
            'datetime' => $datetime->toIso8601String(),
            'duration' => $data['duration'] ?? 60
        ];
    }
    
    protected function shouldIncludeDate(Carbon $date, array $pattern): bool
    {
        // Überspringe Wochenenden wenn gewünscht
        if ($pattern['skip_weekends'] ?? false) {
            if ($date->isWeekend()) {
                return false;
            }
        }
        
        // Überspringe Feiertage wenn gewünscht
        if ($pattern['skip_holidays'] ?? false) {
            // TODO: Feiertags-Check implementieren
        }
        
        return true;
    }
    
    protected function getNthWeekdayOfMonth(Carbon $date, int $weekday, int $occurrence): ?Carbon
    {
        $firstDay = $date->copy()->startOfMonth();
        $targetDay = $firstDay->copy();
        
        // Finde ersten gewünschten Wochentag
        while ($targetDay->dayOfWeek !== $weekday) {
            $targetDay->addDay();
        }
        
        // Addiere Wochen für gewünschte Occurrence
        $targetDay->addWeeks($occurrence - 1);
        
        // Prüfe ob noch im selben Monat
        if ($targetDay->month !== $date->month) {
            return null;
        }
        
        return $targetDay;
    }
    
    protected function updateCustomerStats(int $customerId, int $appointmentCount): void
    {
        Customer::where('id', $customerId)->increment('total_appointments', $appointmentCount);
    }
}