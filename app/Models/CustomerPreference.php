<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Scopes\TenantScope;

class CustomerPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'company_id',
        'preferred_days_of_week',
        'preferred_time_slots',
        'earliest_booking_time',
        'latest_booking_time',
        'preferred_duration_minutes',
        'advance_booking_days',
        'preferred_services',
        'avoided_services',
        'preferred_staff_ids',
        'avoided_staff_ids',
        'preferred_branch_id',
        'reminder_24h',
        'reminder_2h',
        'reminder_sms',
        'reminder_whatsapp',
        'marketing_consent',
        'birthday_greetings',
        'communication_blackout_times',
        'accessibility_needs',
        'health_conditions',
        'allergies',
        'special_instructions',
        'booking_patterns',
        'cancellation_patterns',
        'punctuality_score',
        'reliability_score',
        'service_history',
        'price_sensitive',
        'average_spend',
        'preferred_payment_methods',
        'auto_charge_enabled'
    ];

    protected $casts = [
        'preferred_days_of_week' => 'array',
        'preferred_time_slots' => 'array',
        'earliest_booking_time' => 'datetime:H:i',
        'latest_booking_time' => 'datetime:H:i',
        'preferred_services' => 'array',
        'avoided_services' => 'array',
        'preferred_staff_ids' => 'array',
        'avoided_staff_ids' => 'array',
        'reminder_24h' => 'boolean',
        'reminder_2h' => 'boolean',
        'reminder_sms' => 'boolean',
        'reminder_whatsapp' => 'boolean',
        'marketing_consent' => 'boolean',
        'birthday_greetings' => 'boolean',
        'communication_blackout_times' => 'array',
        'accessibility_needs' => 'array',
        'health_conditions' => 'array',
        'allergies' => 'array',
        'booking_patterns' => 'array',
        'cancellation_patterns' => 'array',
        'punctuality_score' => 'float',
        'reliability_score' => 'float',
        'service_history' => 'array',
        'price_sensitive' => 'boolean',
        'average_spend' => 'decimal:2',
        'preferred_payment_methods' => 'array',
        'auto_charge_enabled' => 'boolean'
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    /**
     * Get the customer
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the preferred branch
     */
    public function preferredBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'preferred_branch_id');
    }

    /**
     * Get preferred staff members
     */
    public function preferredStaff()
    {
        return Staff::whereIn('id', $this->preferred_staff_ids ?? [])->get();
    }

    /**
     * Get avoided staff members
     */
    public function avoidedStaff()
    {
        return Staff::whereIn('id', $this->avoided_staff_ids ?? [])->get();
    }

    /**
     * Get preferred services
     */
    public function preferredServices()
    {
        return Service::whereIn('id', $this->preferred_services ?? [])->get();
    }

    /**
     * Get avoided services
     */
    public function avoidedServices()
    {
        return Service::whereIn('id', $this->avoided_services ?? [])->get();
    }

    /**
     * Check if a day of week is preferred
     */
    public function isDayPreferred(int $dayOfWeek): bool
    {
        return in_array($dayOfWeek, $this->preferred_days_of_week ?? []);
    }

    /**
     * Check if a time slot is preferred
     */
    public function isTimeSlotPreferred(string $timeSlot): bool
    {
        return in_array($timeSlot, $this->preferred_time_slots ?? []);
    }

    /**
     * Check if a time is within preferred hours
     */
    public function isTimeWithinPreferredHours(\Carbon\Carbon $time): bool
    {
        if (!$this->earliest_booking_time || !$this->latest_booking_time) {
            return true;
        }

        $timeOnly = $time->format('H:i');
        $earliest = $this->earliest_booking_time->format('H:i');
        $latest = $this->latest_booking_time->format('H:i');

        return $timeOnly >= $earliest && $timeOnly <= $latest;
    }

    /**
     * Check if a staff member is preferred
     */
    public function isStaffPreferred(int $staffId): bool
    {
        return in_array($staffId, $this->preferred_staff_ids ?? []);
    }

    /**
     * Check if a staff member should be avoided
     */
    public function isStaffAvoided(int $staffId): bool
    {
        return in_array($staffId, $this->avoided_staff_ids ?? []);
    }

    /**
     * Check if a service is preferred
     */
    public function isServicePreferred(int $serviceId): bool
    {
        return in_array($serviceId, $this->preferred_services ?? []);
    }

    /**
     * Check if a service should be avoided
     */
    public function isServiceAvoided(int $serviceId): bool
    {
        return in_array($serviceId, $this->avoided_services ?? []);
    }

    /**
     * Get reminder preferences
     */
    public function getReminderPreferences(): array
    {
        return [
            '24h' => $this->reminder_24h,
            '2h' => $this->reminder_2h,
            'sms' => $this->reminder_sms,
            'whatsapp' => $this->reminder_whatsapp
        ];
    }

    /**
     * Check if communication is allowed at a specific time
     */
    public function isCommunicationAllowed(\Carbon\Carbon $time): bool
    {
        if (empty($this->communication_blackout_times)) {
            return true;
        }

        $timeString = $time->format('H:i');
        
        foreach ($this->communication_blackout_times as $blackout) {
            if (isset($blackout['start']) && isset($blackout['end'])) {
                if ($timeString >= $blackout['start'] && $timeString <= $blackout['end']) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Update booking patterns based on new appointment
     */
    public function updateBookingPatterns(array $newPattern): void
    {
        $patterns = $this->booking_patterns ?? [];
        
        // Merge new pattern data
        if (isset($newPattern['day_of_week'])) {
            $patterns['days'][$newPattern['day_of_week']] = 
                ($patterns['days'][$newPattern['day_of_week']] ?? 0) + 1;
        }
        
        if (isset($newPattern['time_slot'])) {
            $patterns['times'][$newPattern['time_slot']] = 
                ($patterns['times'][$newPattern['time_slot']] ?? 0) + 1;
        }
        
        if (isset($newPattern['advance_days'])) {
            $patterns['advance_days'][] = $newPattern['advance_days'];
        }

        $this->update(['booking_patterns' => $patterns]);
    }

    /**
     * Calculate preference match score for a proposed appointment
     */
    public function calculatePreferenceScore(array $appointmentData): float
    {
        $score = 0;
        $factors = 0;

        // Day of week preference
        if (isset($appointmentData['day_of_week'])) {
            $factors++;
            if ($this->isDayPreferred($appointmentData['day_of_week'])) {
                $score += 1;
            }
        }

        // Time slot preference
        if (isset($appointmentData['time_slot'])) {
            $factors++;
            if ($this->isTimeSlotPreferred($appointmentData['time_slot'])) {
                $score += 1;
            }
        }

        // Staff preference
        if (isset($appointmentData['staff_id'])) {
            $factors++;
            if ($this->isStaffPreferred($appointmentData['staff_id'])) {
                $score += 1;
            } elseif ($this->isStaffAvoided($appointmentData['staff_id'])) {
                $score -= 0.5;
            }
        }

        // Service preference
        if (isset($appointmentData['service_id'])) {
            $factors++;
            if ($this->isServicePreferred($appointmentData['service_id'])) {
                $score += 1;
            } elseif ($this->isServiceAvoided($appointmentData['service_id'])) {
                $score -= 0.5;
            }
        }

        // Branch preference
        if (isset($appointmentData['branch_id']) && $this->preferred_branch_id) {
            $factors++;
            if ($appointmentData['branch_id'] == $this->preferred_branch_id) {
                $score += 1;
            }
        }

        return $factors > 0 ? max(0, $score / $factors) : 0.5;
    }

    /**
     * Get recommended appointment slots based on preferences
     */
    public function getRecommendedSlots(\Carbon\Carbon $startDate, int $days = 7): array
    {
        $recommendations = [];
        $currentDate = $startDate->copy();

        for ($i = 0; $i < $days; $i++) {
            if ($this->isDayPreferred($currentDate->dayOfWeek)) {
                foreach ($this->preferred_time_slots ?? ['morning', 'afternoon'] as $slot) {
                    $recommendations[] = [
                        'date' => $currentDate->format('Y-m-d'),
                        'day_name' => $currentDate->format('l'),
                        'time_slot' => $slot,
                        'score' => 1.0
                    ];
                }
            }
            $currentDate->addDay();
        }

        return $recommendations;
    }
}