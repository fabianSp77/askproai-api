<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\RecurringAppointmentPattern;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecurringAppointmentService
{
    protected $availabilityService;

    public function __construct(AvailabilityService $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }

    public function createRecurringAppointments(
        Appointment $parentAppointment,
        array $recurrenceData
    ): Collection {
        try {
            DB::beginTransaction();

            // Create recurring pattern record
            $pattern = RecurringAppointmentPattern::create([
                'appointment_id' => $parentAppointment->id,
                'frequency' => $recurrenceData['frequency'],
                'interval' => $recurrenceData['interval'] ?? 1,
                'days_of_week' => $recurrenceData['days_of_week'] ?? null,
                'day_of_month' => $recurrenceData['day_of_month'] ?? null,
                'start_date' => $recurrenceData['start_date'] ?? $parentAppointment->start_at->toDateString(),
                'end_date' => $recurrenceData['end_date'] ?? null,
                'occurrences' => $recurrenceData['occurrences'] ?? null,
                'exceptions' => $recurrenceData['exceptions'] ?? [],
            ]);

            // Mark parent appointment as recurring
            $parentAppointment->update([
                'is_recurring' => true,
                'recurring_pattern' => $recurrenceData
            ]);

            // Generate occurrence dates
            $occurrences = $this->generateOccurrences($parentAppointment, $pattern);

            // Create appointments for each occurrence
            $createdAppointments = collect();
            foreach ($occurrences as $occurrence) {
                if ($this->shouldSkipDate($occurrence, $pattern->exceptions)) {
                    continue;
                }

                $newAppointment = $this->createOccurrence($parentAppointment, $occurrence);
                if ($newAppointment) {
                    $createdAppointments->push($newAppointment);
                }
            }

            DB::commit();
            return $createdAppointments;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create recurring appointments: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function generateOccurrences(Appointment $appointment, RecurringAppointmentPattern $pattern): array
    {
        $occurrences = [];
        $startDate = Carbon::parse($pattern->start_date);
        $endDate = $pattern->end_date ? Carbon::parse($pattern->end_date) : $startDate->copy()->addYear();

        switch ($pattern->frequency) {
            case 'daily':
                $period = CarbonPeriod::create($startDate, "{$pattern->interval} days", $endDate);
                foreach ($period as $date) {
                    $occurrences[] = $date;
                    if ($pattern->occurrences && count($occurrences) >= $pattern->occurrences) {
                        break;
                    }
                }
                break;

            case 'weekly':
                if ($pattern->days_of_week) {
                    $currentDate = $startDate->copy();
                    while ($currentDate <= $endDate) {
                        foreach ($pattern->days_of_week as $dayOfWeek) {
                            $nextOccurrence = $currentDate->copy()->next($dayOfWeek);
                            if ($nextOccurrence <= $endDate && $nextOccurrence >= $startDate) {
                                $occurrences[] = $nextOccurrence;
                                if ($pattern->occurrences && count($occurrences) >= $pattern->occurrences) {
                                    break 2;
                                }
                            }
                        }
                        $currentDate->addWeeks($pattern->interval);
                    }
                } else {
                    $period = CarbonPeriod::create($startDate, "{$pattern->interval} weeks", $endDate);
                    foreach ($period as $date) {
                        $occurrences[] = $date;
                        if ($pattern->occurrences && count($occurrences) >= $pattern->occurrences) {
                            break;
                        }
                    }
                }
                break;

            case 'monthly':
                $currentDate = $startDate->copy();
                while ($currentDate <= $endDate) {
                    if ($pattern->day_of_month) {
                        // Specific day of month (e.g., 15th)
                        $occurrence = $currentDate->copy()->day($pattern->day_of_month);
                    } else {
                        // Same day of week in month (e.g., 2nd Tuesday)
                        $weekOfMonth = ceil($startDate->day / 7);
                        $dayOfWeek = $startDate->dayOfWeek;
                        $occurrence = $currentDate->copy()
                            ->startOfMonth()
                            ->next($dayOfWeek)
                            ->addWeeks($weekOfMonth - 1);
                    }

                    if ($occurrence <= $endDate && $occurrence >= $startDate) {
                        $occurrences[] = $occurrence;
                        if ($pattern->occurrences && count($occurrences) >= $pattern->occurrences) {
                            break;
                        }
                    }

                    $currentDate->addMonths($pattern->interval);
                }
                break;

            case 'yearly':
                $period = CarbonPeriod::create($startDate, "{$pattern->interval} years", $endDate);
                foreach ($period as $date) {
                    $occurrences[] = $date;
                    if ($pattern->occurrences && count($occurrences) >= $pattern->occurrences) {
                        break;
                    }
                }
                break;
        }

        // Sort occurrences chronologically
        usort($occurrences, function ($a, $b) {
            return $a->timestamp - $b->timestamp;
        });

        return $occurrences;
    }

    protected function createOccurrence(Appointment $parentAppointment, Carbon $date): ?Appointment
    {
        $startTime = $date->copy()
            ->setTime($parentAppointment->start_at->hour, $parentAppointment->start_at->minute);
        $endTime = $date->copy()
            ->setTime($parentAppointment->end_at->hour, $parentAppointment->end_at->minute);

        // Check availability
        if (!$this->availabilityService->isSlotAvailable(
            $parentAppointment->service_id,
            $parentAppointment->branch_id,
            $startTime,
            $parentAppointment->staff_id
        )) {
            Log::info("Slot not available for recurring appointment on {$startTime}");
            return null;
        }

        try {
            $appointment = Appointment::create([
                'customer_id' => $parentAppointment->customer_id,
                'service_id' => $parentAppointment->service_id,
                'staff_id' => $parentAppointment->staff_id,
                'branch_id' => $parentAppointment->branch_id,
                'company_id' => $parentAppointment->company_id,
                'start_at' => $startTime,
                'end_at' => $endTime,
                'status' => 'pending',
                'total_price' => $parentAppointment->total_price,
                'notes' => $parentAppointment->notes,
                'parent_appointment_id' => $parentAppointment->id,
                'is_recurring' => false, // Individual occurrences are not marked as recurring
            ]);

            return $appointment;

        } catch (\Exception $e) {
            Log::error("Failed to create recurring appointment occurrence: " . $e->getMessage());
            return null;
        }
    }

    protected function shouldSkipDate(Carbon $date, ?array $exceptions): bool
    {
        if (!$exceptions || empty($exceptions)) {
            return false;
        }

        $dateString = $date->toDateString();
        return in_array($dateString, $exceptions);
    }

    public function updateRecurringAppointments(
        Appointment $appointment,
        string $updateScope = 'single',
        array $updates = []
    ): bool {
        try {
            DB::beginTransaction();

            switch ($updateScope) {
                case 'single':
                    // Update only this occurrence
                    $appointment->update($updates);
                    // Mark as exception in parent pattern if it has one
                    if ($appointment->parent_appointment_id) {
                        $this->markAsException($appointment);
                    }
                    break;

                case 'future':
                    // Update this and all future occurrences
                    $futureAppointments = Appointment::where('parent_appointment_id', $appointment->parent_appointment_id ?? $appointment->id)
                        ->where('start_at', '>=', $appointment->start_at)
                        ->get();

                    foreach ($futureAppointments as $futureAppointment) {
                        $futureAppointment->update($updates);
                    }
                    break;

                case 'all':
                    // Update all occurrences
                    $parentId = $appointment->parent_appointment_id ?? $appointment->id;
                    Appointment::where('parent_appointment_id', $parentId)
                        ->orWhere('id', $parentId)
                        ->update($updates);
                    break;
            }

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update recurring appointments: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteRecurringAppointments(
        Appointment $appointment,
        string $deleteScope = 'single'
    ): bool {
        try {
            DB::beginTransaction();

            switch ($deleteScope) {
                case 'single':
                    // Delete only this occurrence
                    $appointment->delete();
                    // Mark date as exception in pattern
                    if ($appointment->parent_appointment_id) {
                        $this->markAsException($appointment);
                    }
                    break;

                case 'future':
                    // Delete this and all future occurrences
                    $futureAppointments = Appointment::where('parent_appointment_id', $appointment->parent_appointment_id ?? $appointment->id)
                        ->where('start_at', '>=', $appointment->start_at)
                        ->get();

                    foreach ($futureAppointments as $futureAppointment) {
                        $futureAppointment->delete();
                    }

                    // Update pattern end date
                    if ($pattern = $this->getPattern($appointment)) {
                        $pattern->update(['end_date' => $appointment->start_at->subDay()]);
                    }
                    break;

                case 'all':
                    // Delete all occurrences and pattern
                    $parentId = $appointment->parent_appointment_id ?? $appointment->id;

                    // Delete pattern
                    RecurringAppointmentPattern::where('appointment_id', $parentId)->delete();

                    // Delete all appointments
                    Appointment::where('parent_appointment_id', $parentId)->delete();
                    Appointment::find($parentId)?->delete();
                    break;
            }

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete recurring appointments: ' . $e->getMessage());
            return false;
        }
    }

    protected function markAsException(Appointment $appointment): void
    {
        $parentId = $appointment->parent_appointment_id ?? $appointment->id;
        $pattern = RecurringAppointmentPattern::where('appointment_id', $parentId)->first();

        if ($pattern) {
            $exceptions = $pattern->exceptions ?? [];
            $exceptions[] = $appointment->start_at->toDateString();
            $pattern->update(['exceptions' => array_unique($exceptions)]);
        }
    }

    protected function getPattern(Appointment $appointment): ?RecurringAppointmentPattern
    {
        $parentId = $appointment->parent_appointment_id ?? $appointment->id;
        return RecurringAppointmentPattern::where('appointment_id', $parentId)->first();
    }

    public function getNextOccurrences(Appointment $appointment, int $count = 5): Collection
    {
        if (!$appointment->is_recurring && !$appointment->parent_appointment_id) {
            return collect();
        }

        $parentId = $appointment->parent_appointment_id ?? $appointment->id;

        return Appointment::where('parent_appointment_id', $parentId)
            ->where('start_at', '>', now())
            ->orderBy('start_at')
            ->limit($count)
            ->get();
    }

    public function hasConflicts(Appointment $appointment, array $occurrences): array
    {
        $conflicts = [];

        foreach ($occurrences as $occurrence) {
            if (!$this->availabilityService->isSlotAvailable(
                $appointment->service_id,
                $appointment->branch_id,
                $occurrence,
                $appointment->staff_id
            )) {
                $conflicts[] = $occurrence->toDateTimeString();
            }
        }

        return $conflicts;
    }
}