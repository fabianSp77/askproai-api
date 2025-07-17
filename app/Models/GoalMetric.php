<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoalMetric extends Model
{
    protected $fillable = [
        'company_goal_id',
        'metric_type',
        'metric_name',
        'description',
        'target_value',
        'target_unit',
        'weight',
        'calculation_method',
        'conditions',
        'comparison_operator',
        'is_primary',
    ];

    protected $casts = [
        'target_value' => 'decimal:2',
        'weight' => 'decimal:2',
        'calculation_method' => 'array',
        'conditions' => 'array',
        'is_primary' => 'boolean',
    ];

    // Metric types
    const TYPE_CALLS_RECEIVED = 'calls_received';
    const TYPE_CALLS_ANSWERED = 'calls_answered';
    const TYPE_DATA_COLLECTED = 'data_collected';
    const TYPE_APPOINTMENTS_BOOKED = 'appointments_booked';
    const TYPE_APPOINTMENTS_COMPLETED = 'appointments_completed';
    const TYPE_REVENUE_GENERATED = 'revenue_generated';
    const TYPE_CUSTOMER_SATISFACTION = 'customer_satisfaction';
    const TYPE_AVERAGE_CALL_DURATION = 'average_call_duration';
    const TYPE_CONVERSION_RATE = 'conversion_rate';
    const TYPE_DATA_WITH_CONSENT = 'data_with_consent';
    const TYPE_DATA_FORWARDED = 'data_forwarded';
    const TYPE_CUSTOM = 'custom';

    // Target units
    const UNIT_COUNT = 'count';
    const UNIT_PERCENTAGE = 'percentage';
    const UNIT_CURRENCY = 'currency';
    const UNIT_SECONDS = 'seconds';
    const UNIT_SCORE = 'score';

    // Comparison operators
    const OPERATOR_GTE = 'gte'; // Greater than or equal
    const OPERATOR_LTE = 'lte'; // Less than or equal
    const OPERATOR_EQ = 'eq';   // Equal
    const OPERATOR_BETWEEN = 'between'; // Between two values

    // Relationships
    public function companyGoal(): BelongsTo
    {
        return $this->belongsTo(CompanyGoal::class);
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(GoalAchievement::class);
    }

    // Methods
    public function getCurrentValue($startDate = null, $endDate = null)
    {
        // Ensure relationships are loaded
        $this->loadMissing(['companyGoal.company']);
        
        $company = $this->companyGoal->company;
        $startDate = $startDate ?: $this->companyGoal->start_date;
        $endDate = $endDate ?: now();

        switch ($this->metric_type) {
            case self::TYPE_CALLS_RECEIVED:
                return $this->calculateCallsReceived($company, $startDate, $endDate);
            
            case self::TYPE_CALLS_ANSWERED:
                return $this->calculateCallsAnswered($company, $startDate, $endDate);
            
            case self::TYPE_DATA_COLLECTED:
                return $this->calculateDataCollected($company, $startDate, $endDate);
            
            case self::TYPE_APPOINTMENTS_BOOKED:
                return $this->calculateAppointmentsBooked($company, $startDate, $endDate);
            
            case self::TYPE_APPOINTMENTS_COMPLETED:
                return $this->calculateAppointmentsCompleted($company, $startDate, $endDate);
            
            case self::TYPE_REVENUE_GENERATED:
                return $this->calculateRevenueGenerated($company, $startDate, $endDate);
            
            case self::TYPE_CONVERSION_RATE:
                return $this->calculateConversionRate($company, $startDate, $endDate);
            
            case self::TYPE_AVERAGE_CALL_DURATION:
                return $this->calculateAverageCallDuration($company, $startDate, $endDate);
            
            case self::TYPE_DATA_WITH_CONSENT:
                return $this->calculateDataWithConsent($company, $startDate, $endDate);
            
            case self::TYPE_DATA_FORWARDED:
                return $this->calculateDataForwarded($company, $startDate, $endDate);
            
            case self::TYPE_CUSTOM:
                return $this->calculateCustomMetric($company, $startDate, $endDate);
            
            default:
                return 0;
        }
    }

    public function getAchievementPercentage($currentValue = null): float
    {
        if ($currentValue === null) {
            $currentValue = $this->getCurrentValue();
        }

        if ($this->target_value == 0) {
            return 0;
        }

        switch ($this->comparison_operator) {
            case self::OPERATOR_GTE:
                return min(100, ($currentValue / $this->target_value) * 100);
            
            case self::OPERATOR_LTE:
                return min(100, ($this->target_value / $currentValue) * 100);
            
            case self::OPERATOR_EQ:
                $difference = abs($currentValue - $this->target_value);
                $percentage = 100 - (($difference / $this->target_value) * 100);
                return max(0, $percentage);
            
            case self::OPERATOR_BETWEEN:
                // Assuming conditions contains min and max values
                $min = $this->conditions['min'] ?? 0;
                $max = $this->conditions['max'] ?? $this->target_value;
                
                if ($currentValue >= $min && $currentValue <= $max) {
                    return 100;
                } elseif ($currentValue < $min) {
                    return ($currentValue / $min) * 100;
                } else {
                    return max(0, 100 - (($currentValue - $max) / $max) * 100);
                }
            
            default:
                return 0;
        }
    }

    // Calculation methods
    private function calculateCallsReceived($company, $startDate, $endDate)
    {
        $query = $company->calls()
            ->withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($this->conditions) {
            // Apply additional conditions
            if (isset($this->conditions['min_duration'])) {
                $query->where('duration_sec', '>=', $this->conditions['min_duration']);
            }
        }

        return $query->count();
    }

    private function calculateCallsAnswered($company, $startDate, $endDate)
    {
        return $company->calls()
            ->withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', 'missed')
            ->count();
    }

    private function calculateDataCollected($company, $startDate, $endDate)
    {
        $totalCalls = $company->calls()
            ->withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        if ($totalCalls == 0) {
            return 0;
        }

        $requiredFields = $this->conditions['required_fields'] ?? ['name', 'email', 'phone'];
        
        $callsWithData = $company->calls()
            ->withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('customer_id')
            ->whereHas('customer', function ($query) use ($requiredFields) {
                foreach ($requiredFields as $field) {
                    $query->whereNotNull($field);
                }
            })
            ->count();

        return ($callsWithData / $totalCalls) * 100;
    }

    private function calculateAppointmentsBooked($company, $startDate, $endDate)
    {
        return $company->appointments()
            ->withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    private function calculateAppointmentsCompleted($company, $startDate, $endDate)
    {
        return $company->appointments()
            ->withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->whereBetween('start_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->count();
    }

    private function calculateRevenueGenerated($company, $startDate, $endDate)
    {
        return $company->appointments()
            ->withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->whereBetween('start_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->sum('price') ?? 0;
    }

    private function calculateConversionRate($company, $startDate, $endDate)
    {
        $totalCalls = $company->calls()
            ->withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        if ($totalCalls == 0) {
            return 0;
        }

        $appointments = $company->appointments()
            ->withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        return ($appointments / $totalCalls) * 100;
    }

    private function calculateAverageCallDuration($company, $startDate, $endDate)
    {
        return $company->calls()
            ->withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->avg('duration_sec') ?? 0;
    }

    private function calculateCustomMetric($company, $startDate, $endDate)
    {
        // Custom calculation based on calculation_method
        if (!$this->calculation_method) {
            return 0;
        }

        // This would be implemented based on specific custom metric requirements
        return 0;
    }

    private function calculateDataWithConsent($company, $startDate, $endDate)
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
            ->whereNotNull('customer_id') // Ensure customer data was captured
            ->count();
    }

    private function calculateDataForwarded($company, $startDate, $endDate)
    {
        return $company->calls()
            ->withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('consent_given', true)
            ->where('data_forwarded', true)
            ->count();
    }

    // Format value based on unit
    public function formatValue($value = null): string
    {
        if ($value === null) {
            $value = $this->getCurrentValue();
        }

        switch ($this->target_unit) {
            case self::UNIT_COUNT:
                return number_format($value, 0);
            
            case self::UNIT_PERCENTAGE:
                return number_format($value, 1) . '%';
            
            case self::UNIT_CURRENCY:
                return number_format($value, 2, ',', '.') . ' €';
            
            case self::UNIT_SECONDS:
                return gmdate('i:s', $value);
            
            case self::UNIT_SCORE:
                return number_format($value, 1);
            
            default:
                return (string) $value;
        }
    }
}
