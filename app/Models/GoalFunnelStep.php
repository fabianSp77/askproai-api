<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalFunnelStep extends Model
{
    protected $fillable = [
        'company_goal_id',
        'step_order',
        'step_name',
        'description',
        'step_type',
        'required_fields',
        'conditions',
        'expected_conversion_rate',
        'is_optional',
    ];

    protected $casts = [
        'required_fields' => 'array',
        'conditions' => 'array',
        'expected_conversion_rate' => 'decimal:2',
        'is_optional' => 'boolean',
    ];

    // Step types
    const TYPE_CALL_RECEIVED = 'call_received';
    const TYPE_CALL_ANSWERED = 'call_answered';
    const TYPE_DATA_CAPTURED = 'data_captured';
    const TYPE_EMAIL_CAPTURED = 'email_captured';
    const TYPE_PHONE_CAPTURED = 'phone_captured';
    const TYPE_ADDRESS_CAPTURED = 'address_captured';
    const TYPE_APPOINTMENT_REQUESTED = 'appointment_requested';
    const TYPE_APPOINTMENT_SCHEDULED = 'appointment_scheduled';
    const TYPE_APPOINTMENT_CONFIRMED = 'appointment_confirmed';
    const TYPE_APPOINTMENT_COMPLETED = 'appointment_completed';
    const TYPE_PAYMENT_RECEIVED = 'payment_received';
    const TYPE_CONSENT_GIVEN = 'consent_given';
    const TYPE_DATA_FORWARDED = 'data_forwarded';
    const TYPE_CUSTOM = 'custom';

    // Relationships
    public function companyGoal(): BelongsTo
    {
        return $this->belongsTo(CompanyGoal::class);
    }

    // Methods
    public function getStepCount($startDate = null, $endDate = null): int
    {
        // Ensure relationships are loaded
        $this->loadMissing(['companyGoal.company']);
        
        $company = $this->companyGoal->company;
        $startDate = $startDate ?: $this->companyGoal->start_date;
        $endDate = $endDate ?: now();

        switch ($this->step_type) {
            case self::TYPE_CALL_RECEIVED:
                return $this->countCallsReceived($company, $startDate, $endDate);
            
            case self::TYPE_CALL_ANSWERED:
                return $this->countCallsAnswered($company, $startDate, $endDate);
            
            case self::TYPE_DATA_CAPTURED:
                return $this->countDataCaptured($company, $startDate, $endDate);
            
            case self::TYPE_EMAIL_CAPTURED:
                return $this->countFieldCaptured($company, 'email', $startDate, $endDate);
            
            case self::TYPE_PHONE_CAPTURED:
                return $this->countFieldCaptured($company, 'phone', $startDate, $endDate);
            
            case self::TYPE_ADDRESS_CAPTURED:
                return $this->countFieldCaptured($company, 'address', $startDate, $endDate);
            
            case self::TYPE_APPOINTMENT_REQUESTED:
                return $this->countAppointmentRequests($company, $startDate, $endDate);
            
            case self::TYPE_APPOINTMENT_SCHEDULED:
                return $this->countAppointmentsScheduled($company, $startDate, $endDate);
            
            case self::TYPE_APPOINTMENT_COMPLETED:
                return $this->countAppointmentsCompleted($company, $startDate, $endDate);
            
            case self::TYPE_PAYMENT_RECEIVED:
                return $this->countPaymentsReceived($company, $startDate, $endDate);
            
            case self::TYPE_CONSENT_GIVEN:
                return $this->countConsentGiven($company, $startDate, $endDate);
            
            case self::TYPE_DATA_FORWARDED:
                return $this->countDataForwarded($company, $startDate, $endDate);
            
            case self::TYPE_CUSTOM:
                return $this->countCustomStep($company, $startDate, $endDate);
            
            default:
                return 0;
        }
    }

    public function getConversionRate($previousStepCount = null): float
    {
        if ($this->step_order == 1) {
            return 100; // First step is always 100%
        }

        $currentCount = $this->getStepCount();
        
        if ($previousStepCount === null) {
            // Ensure relationship is loaded
            $this->loadMissing('companyGoal');
            
            // Get previous step
            $previousStep = $this->companyGoal->funnelSteps()
                ->where('step_order', $this->step_order - 1)
                ->first();
            
            if (!$previousStep) {
                return 0;
            }
            
            $previousStepCount = $previousStep->getStepCount();
        }

        if ($previousStepCount == 0) {
            return 0;
        }

        return ($currentCount / $previousStepCount) * 100;
    }

    // Calculation methods
    private function countCallsReceived($company, $startDate, $endDate): int
    {
        $query = $company->calls()
            ->withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($this->conditions) {
            $this->applyConditions($query, $this->conditions);
        }

        return $query->count();
    }

    private function countCallsAnswered($company, $startDate, $endDate): int
    {
        $query = $company->calls()
            ->withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', 'missed');

        if ($this->conditions) {
            $this->applyConditions($query, $this->conditions);
        }

        return $query->count();
    }

    private function countDataCaptured($company, $startDate, $endDate): int
    {
        $query = $company->calls()
            ->withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('customer_id');

        if ($this->required_fields) {
            $query->whereHas('customer', function ($q) {
                foreach ($this->required_fields as $field) {
                    $q->whereNotNull($field);
                }
            });
        }

        if ($this->conditions) {
            $this->applyConditions($query, $this->conditions);
        }

        return $query->count();
    }

    private function countFieldCaptured($company, $field, $startDate, $endDate): int
    {
        return $company->calls()
            ->withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('customer_id')
            ->whereHas('customer', function ($q) use ($field) {
                $q->whereNotNull($field);
            })
            ->count();
    }

    private function countAppointmentRequests($company, $startDate, $endDate): int
    {
        // Count calls where appointment was requested
        return $company->calls()
            ->withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where(function ($q) {
                $q->where('appointment_requested', true)
                  ->orWhereNotNull('appointment_id');
            })
            ->count();
    }

    private function countAppointmentsScheduled($company, $startDate, $endDate): int
    {
        return $company->appointments()
            ->withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    private function countAppointmentsCompleted($company, $startDate, $endDate): int
    {
        return $company->appointments()
            ->withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->whereBetween('start_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->count();
    }

    private function countPaymentsReceived($company, $startDate, $endDate): int
    {
        // This would need to be implemented based on payment tracking
        return $company->appointments()
            ->withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->whereBetween('start_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->whereNotNull('paid_at')
            ->count();
    }

    private function countCustomStep($company, $startDate, $endDate): int
    {
        // Custom step counting based on conditions
        if (!$this->conditions || !isset($this->conditions['query'])) {
            return 0;
        }

        // This would be implemented based on specific custom requirements
        return 0;
    }

    private function countConsentGiven($company, $startDate, $endDate): int
    {
        return $company->calls()
            ->withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', 'missed')
            ->where(function ($query) {
                $query->where('consent_given', true)
                      ->orWhere(function ($q) {
                          // Check if consent is tracked in retell_dynamic_variables
                          $q->whereNotNull('retell_dynamic_variables')
                            ->where('retell_dynamic_variables->consent', 'true');
                      });
            })
            ->count();
    }

    private function countDataForwarded($company, $startDate, $endDate): int
    {
        return $company->calls()
            ->withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('consent_given', true)
            ->where('data_forwarded', true)
            ->count();
    }

    private function applyConditions($query, $conditions)
    {
        if (isset($conditions['min_duration'])) {
            $query->where('duration_sec', '>=', $conditions['min_duration']);
        }
        
        if (isset($conditions['max_duration'])) {
            $query->where('duration_sec', '<=', $conditions['max_duration']);
        }
        
        if (isset($conditions['status'])) {
            $query->where('status', $conditions['status']);
        }
        
        // Additional conditions can be added here
    }

    // Get funnel data for a specific period
    public function getFunnelData($startDate = null, $endDate = null): array
    {
        $count = $this->getStepCount($startDate, $endDate);
        $conversionRate = $this->getConversionRate();

        return [
            'step_order' => $this->step_order,
            'step_name' => $this->step_name,
            'step_type' => $this->step_type,
            'count' => $count,
            'conversion_rate' => round($conversionRate, 2),
            'is_optional' => $this->is_optional,
        ];
    }
}
