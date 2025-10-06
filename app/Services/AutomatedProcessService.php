<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\Service;
use App\Models\RecurringAppointment;
use App\Models\StaffSchedule;
use App\Models\WorkingHour;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutomatedProcessService
{
    protected AppointmentService $appointmentService;
    protected NotificationWorkflowService $notificationService;

    public function __construct(
        AppointmentService $appointmentService,
        NotificationWorkflowService $notificationService
    ) {
        $this->appointmentService = $appointmentService;
        $this->notificationService = $notificationService;
    }

    /**
     * Automatically assign staff to appointments based on availability and skills
     */
    public function autoAssignStaff(Appointment $appointment): ?Staff
    {
        if ($appointment->staff_id) {
            return $appointment->staff;
        }

        $service = $appointment->service;
        $dateTime = Carbon::parse($appointment->appointment_date . ' ' . $appointment->appointment_time);

        // Find available staff with required skills
        $availableStaff = $this->findAvailableStaff(
            $service,
            $dateTime,
            $appointment->duration,
            $appointment->branch_id
        );

        if ($availableStaff->isEmpty()) {
            Log::warning('No available staff for automatic assignment', [
                'appointment_id' => $appointment->id,
                'service_id' => $service->id,
                'datetime' => $dateTime
            ]);
            return null;
        }

        // Select best staff based on criteria
        $selectedStaff = $this->selectBestStaff($availableStaff, $appointment);

        // Assign staff to appointment
        $appointment->update(['staff_id' => $selectedStaff->id]);

        // Send notification to staff
        $this->notifyStaffAssignment($selectedStaff, $appointment);

        Log::info('Staff auto-assigned to appointment', [
            'appointment_id' => $appointment->id,
            'staff_id' => $selectedStaff->id
        ]);

        return $selectedStaff;
    }

    /**
     * Process and create recurring appointments
     */
    public function processRecurringAppointments(): int
    {
        $created = 0;
        $recurringAppointments = RecurringAppointment::active()
            ->whereDate('next_occurrence', '<=', now()->addDays(30))
            ->get();

        foreach ($recurringAppointments as $recurring) {
            $created += $this->createRecurringInstances($recurring);
        }

        return $created;
    }

    /**
     * Create instances of recurring appointment
     */
    protected function createRecurringInstances(RecurringAppointment $recurring): int
    {
        $created = 0;
        $template = $recurring->template_appointment;
        $endDate = min(
            $recurring->end_date ?? Carbon::now()->addMonths(3),
            Carbon::now()->addDays(30)
        );

        $currentDate = Carbon::parse($recurring->next_occurrence);

        while ($currentDate <= $endDate) {
            // Check if appointment already exists
            $exists = Appointment::where('recurring_appointment_id', $recurring->id)
                ->whereDate('appointment_date', $currentDate)
                ->exists();

            if (!$exists) {
                // Check staff availability
                $isAvailable = $this->checkAvailability(
                    $template->staff_id,
                    $currentDate->setTimeFromTimeString($template->appointment_time),
                    $template->duration
                );

                if ($isAvailable) {
                    $appointment = $this->createAppointmentFromRecurring($recurring, $currentDate);
                    $created++;

                    // Send confirmation
                    $this->notificationService->sendAppointmentConfirmation($appointment);
                } else {
                    // Try to find alternative slot
                    $this->handleRecurringConflict($recurring, $currentDate);
                }
            }

            // Calculate next occurrence
            $currentDate = $this->calculateNextOccurrence($currentDate, $recurring);
        }

        // Update next occurrence
        $recurring->update(['next_occurrence' => $currentDate]);

        return $created;
    }

    /**
     * Automatically optimize staff schedules
     */
    public function optimizeStaffSchedules(Carbon $date): array
    {
        $optimizations = [
            'shifts_balanced' => 0,
            'breaks_added' => 0,
            'conflicts_resolved' => 0
        ];

        $branches = \App\Models\Branch::active()->get();

        foreach ($branches as $branch) {
            // Balance workload across staff
            $optimizations['shifts_balanced'] += $this->balanceStaffWorkload($branch, $date);

            // Ensure mandatory breaks
            $optimizations['breaks_added'] += $this->ensureStaffBreaks($branch, $date);

            // Resolve scheduling conflicts
            $optimizations['conflicts_resolved'] += $this->resolveScheduleConflicts($branch, $date);
        }

        return $optimizations;
    }

    /**
     * Auto-confirm appointments based on rules
     */
    public function autoConfirmAppointments(): int
    {
        $confirmed = 0;

        $pendingAppointments = Appointment::where('status', 'pending')
            ->where('created_at', '<', now()->subHours(24))
            ->whereHas('customer', function ($query) {
                $query->where('auto_confirm_enabled', true)
                    ->orWhere('trust_level', 'high');
            })
            ->get();

        foreach ($pendingAppointments as $appointment) {
            if ($this->shouldAutoConfirm($appointment)) {
                $appointment->update(['status' => 'scheduled']);
                $this->notificationService->sendAppointmentConfirmation($appointment);
                $confirmed++;
            }
        }

        return $confirmed;
    }

    /**
     * Automatically follow up on incomplete appointments
     */
    public function processIncompleteAppointments(): int
    {
        $processed = 0;

        $incompleteAppointments = Appointment::whereIn('status', ['pending', 'rescheduled'])
            ->where('appointment_date', '<', now())
            ->where('follow_up_count', '<', 3)
            ->get();

        foreach ($incompleteAppointments as $appointment) {
            $this->sendFollowUp($appointment);
            $appointment->increment('follow_up_count');
            $processed++;

            // Auto-cancel after 3 follow-ups
            if ($appointment->follow_up_count >= 3) {
                $appointment->update(['status' => 'cancelled']);
                Log::info('Appointment auto-cancelled after 3 follow-ups', [
                    'appointment_id' => $appointment->id
                ]);
            }
        }

        return $processed;
    }

    /**
     * Intelligent appointment rescheduling suggestions
     */
    public function suggestReschedule(Appointment $appointment): array
    {
        $suggestions = [];
        $preferredTimes = $this->analyzeCustomerPreferences($appointment->customer);

        // Find similar time slots
        for ($i = 1; $i <= 7; $i++) {
            $date = Carbon::parse($appointment->appointment_date)->addDays($i);

            foreach ($preferredTimes as $time) {
                $slot = $this->findAvailableSlot(
                    $appointment->service_id,
                    $appointment->staff_id,
                    $date,
                    $time,
                    $appointment->duration
                );

                if ($slot) {
                    $suggestions[] = [
                        'date' => $slot['date'],
                        'time' => $slot['time'],
                        'staff_id' => $slot['staff_id'],
                        'confidence' => $this->calculateConfidenceScore($slot, $appointment)
                    ];
                }

                if (count($suggestions) >= 5) {
                    break 2;
                }
            }
        }

        return array_slice($suggestions, 0, 5);
    }

    /**
     * Automatic waitlist management
     */
    public function processWaitlist(): int
    {
        $processed = 0;

        $cancelledAppointments = Appointment::where('status', 'cancelled')
            ->where('updated_at', '>', now()->subHour())
            ->get();

        foreach ($cancelledAppointments as $cancelled) {
            $waitlistEntries = \App\Models\WaitlistEntry::where('service_id', $cancelled->service_id)
                ->where('preferred_date', $cancelled->appointment_date)
                ->where('status', 'waiting')
                ->orderBy('created_at')
                ->get();

            foreach ($waitlistEntries as $entry) {
                if ($this->offerWaitlistSlot($entry, $cancelled)) {
                    $processed++;
                    break; // Move to next cancelled appointment
                }
            }
        }

        return $processed;
    }

    /**
     * Find available staff for service
     */
    protected function findAvailableStaff(Service $service, Carbon $dateTime, int $duration, ?int $branchId = null)
    {
        $query = Staff::active()
            ->whereHas('services', function ($q) use ($service) {
                $q->where('services.id', $service->id);
            });

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $staff = $query->get();

        return $staff->filter(function ($member) use ($dateTime, $duration) {
            return $this->checkAvailability($member->id, $dateTime, $duration);
        });
    }

    /**
     * Select best staff based on criteria
     */
    protected function selectBestStaff($availableStaff, Appointment $appointment): Staff
    {
        $scores = [];

        foreach ($availableStaff as $staff) {
            $score = 0;

            // Workload balance (prefer less busy staff)
            $todayAppointments = $staff->appointments()
                ->whereDate('appointment_date', $appointment->appointment_date)
                ->count();
            $score += (10 - min($todayAppointments, 10)) * 10;

            // Customer preference
            if ($appointment->customer->preferred_staff_id == $staff->id) {
                $score += 50;
            }

            // Previous appointments with customer
            $previousAppointments = Appointment::where('customer_id', $appointment->customer_id)
                ->where('staff_id', $staff->id)
                ->where('status', 'completed')
                ->count();
            $score += $previousAppointments * 5;

            // Staff rating
            $score += $staff->rating * 10;

            // Specialization match
            if ($staff->specializations->contains($appointment->service->category)) {
                $score += 20;
            }

            $scores[$staff->id] = $score;
        }

        arsort($scores);
        $bestStaffId = array_key_first($scores);

        return $availableStaff->find($bestStaffId);
    }

    /**
     * Check staff availability
     */
    protected function checkAvailability(int $staffId, Carbon $dateTime, int $duration): bool
    {
        // Check working hours
        $workingHour = WorkingHour::where('staff_id', $staffId)
            ->where('day_of_week', $dateTime->dayOfWeek)
            ->where('is_available', true)
            ->first();

        if (!$workingHour) {
            return false;
        }

        $startTime = Carbon::parse($dateTime->format('Y-m-d') . ' ' . $workingHour->start_time);
        $endTime = Carbon::parse($dateTime->format('Y-m-d') . ' ' . $workingHour->end_time);

        if ($dateTime < $startTime || $dateTime->copy()->addMinutes($duration) > $endTime) {
            return false;
        }

        // Check for conflicts
        $hasConflict = Appointment::where('staff_id', $staffId)
            ->whereDate('appointment_date', $dateTime->format('Y-m-d'))
            ->where(function ($query) use ($dateTime, $duration) {
                $endTime = $dateTime->copy()->addMinutes($duration);
                $query->whereBetween('appointment_time', [
                    $dateTime->format('H:i:s'),
                    $endTime->format('H:i:s')
                ]);
            })
            ->exists();

        return !$hasConflict;
    }

    /**
     * Create appointment from recurring template
     */
    protected function createAppointmentFromRecurring(RecurringAppointment $recurring, Carbon $date): Appointment
    {
        $template = $recurring->template_appointment;

        return Appointment::create([
            'company_id' => $template->company_id,
            'branch_id' => $template->branch_id,
            'customer_id' => $template->customer_id,
            'staff_id' => $template->staff_id,
            'service_id' => $template->service_id,
            'appointment_date' => $date->format('Y-m-d'),
            'appointment_time' => $template->appointment_time,
            'duration' => $template->duration,
            'price' => $template->price,
            'status' => 'scheduled',
            'recurring_appointment_id' => $recurring->id,
            'notes' => 'Automatisch erstellt (Wiederkehrender Termin)'
        ]);
    }

    /**
     * Calculate next occurrence based on pattern
     */
    protected function calculateNextOccurrence(Carbon $current, RecurringAppointment $recurring): Carbon
    {
        switch ($recurring->pattern) {
            case 'daily':
                return $current->addDays($recurring->interval);

            case 'weekly':
                return $current->addWeeks($recurring->interval);

            case 'monthly':
                return $current->addMonths($recurring->interval);

            case 'custom':
                return $this->calculateCustomOccurrence($current, $recurring);

            default:
                return $current->addWeeks(1);
        }
    }

    /**
     * Balance staff workload
     */
    protected function balanceStaffWorkload($branch, Carbon $date): int
    {
        $balanced = 0;

        $staff = Staff::where('branch_id', $branch->id)->active()->get();
        $appointments = Appointment::whereDate('appointment_date', $date)
            ->whereIn('staff_id', $staff->pluck('id'))
            ->get()
            ->groupBy('staff_id');

        $avgAppointments = $appointments->map->count()->avg();

        foreach ($staff as $member) {
            $memberAppointments = $appointments->get($member->id, collect())->count();

            if ($memberAppointments > $avgAppointments * 1.5) {
                // Reassign some appointments
                $toReassign = $memberAppointments - ceil($avgAppointments);
                $balanced += $this->reassignAppointments($member, $toReassign, $date);
            }
        }

        return $balanced;
    }

    /**
     * Send follow-up for incomplete appointment
     */
    protected function sendFollowUp(Appointment $appointment): void
    {
        $template = match ($appointment->follow_up_count) {
            0 => 'first_follow_up',
            1 => 'second_follow_up',
            default => 'final_follow_up'
        };

        // Send notification via workflow service
        $this->notificationService->sendAppointmentReminders();

        Log::info('Follow-up sent for appointment', [
            'appointment_id' => $appointment->id,
            'follow_up_number' => $appointment->follow_up_count + 1
        ]);
    }

    /**
     * Analyze customer preferences for scheduling
     */
    protected function analyzeCustomerPreferences(Customer $customer): array
    {
        $pastAppointments = $customer->appointments()
            ->where('status', 'completed')
            ->orderBy('appointment_date', 'desc')
            ->limit(10)
            ->get();

        if ($pastAppointments->isEmpty()) {
            // Default preferred times
            return ['10:00', '14:00', '16:00'];
        }

        $times = $pastAppointments->pluck('appointment_time')
            ->map(fn($time) => Carbon::parse($time)->format('H:00'))
            ->countBy()
            ->sortDesc()
            ->keys()
            ->take(3)
            ->toArray();

        return $times;
    }
}