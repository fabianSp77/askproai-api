<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToCompany;

class CompanyGoal extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'is_active',
        'start_date',
        'end_date',
        'template_type',
        'configuration',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'configuration' => 'array',
    ];

    // Template types
    const TEMPLATE_MAX_APPOINTMENTS = 'max_appointments';
    const TEMPLATE_DATA_COLLECTION = 'data_collection';
    const TEMPLATE_REVENUE_OPTIMIZATION = 'revenue_optimization';
    const TEMPLATE_CUSTOM = 'custom';

    const TEMPLATES = [
        self::TEMPLATE_MAX_APPOINTMENTS => 'Maximale Termine',
        self::TEMPLATE_DATA_COLLECTION => 'Datensammlung Fokus',
        self::TEMPLATE_REVENUE_OPTIMIZATION => 'Umsatz-Optimierung',
        self::TEMPLATE_CUSTOM => 'Benutzerdefiniert',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(GoalMetric::class);
    }

    public function funnelSteps(): HasMany
    {
        return $this->hasMany(GoalFunnelStep::class)->orderBy('step_order');
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(GoalAchievement::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrent($query)
    {
        $now = now();
        return $query->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now());
    }

    public function scopePast($query)
    {
        return $query->where('end_date', '<', now());
    }

    // Attributes
    public function getIsCurrentAttribute(): bool
    {
        $now = now();
        return $this->start_date <= $now && $this->end_date >= $now;
    }

    public function getProgressAttribute(): float
    {
        if (!$this->is_current) {
            return 0;
        }

        $totalDays = $this->start_date->diffInDays($this->end_date);
        $elapsedDays = $this->start_date->diffInDays(now());

        return min(100, ($elapsedDays / $totalDays) * 100);
    }

    public function getDaysRemainingAttribute(): int
    {
        return max(0, now()->diffInDays($this->end_date, false));
    }

    // Methods
    public function getPrimaryMetric()
    {
        return $this->metrics()->where('is_primary', true)->first();
    }

    public function getLatestAchievement()
    {
        return $this->achievements()->latest('period_end')->first();
    }

    public function getAchievementForPeriod($startDate, $endDate)
    {
        return $this->achievements()
            ->where('period_start', $startDate)
            ->where('period_end', $endDate)
            ->first();
    }

    public function createDefaultMetrics()
    {
        $defaultMetrics = [];

        switch ($this->template_type) {
            case self::TEMPLATE_MAX_APPOINTMENTS:
                $defaultMetrics = [
                    [
                        'metric_type' => GoalMetric::TYPE_APPOINTMENTS_BOOKED,
                        'metric_name' => 'Termine gebucht',
                        'target_value' => 100,
                        'target_unit' => GoalMetric::UNIT_COUNT,
                        'weight' => 1.0,
                        'is_primary' => true,
                    ],
                    [
                        'metric_type' => GoalMetric::TYPE_CONVERSION_RATE,
                        'metric_name' => 'Konversionsrate Anruf zu Termin',
                        'target_value' => 30,
                        'target_unit' => GoalMetric::UNIT_PERCENTAGE,
                        'weight' => 0.8,
                    ],
                ];
                break;

            case self::TEMPLATE_DATA_COLLECTION:
                $defaultMetrics = [
                    [
                        'metric_type' => GoalMetric::TYPE_DATA_COLLECTED,
                        'metric_name' => 'Vollständige Kundendaten',
                        'target_value' => 80,
                        'target_unit' => GoalMetric::UNIT_PERCENTAGE,
                        'weight' => 1.0,
                        'is_primary' => true,
                    ],
                    [
                        'metric_type' => GoalMetric::TYPE_CALLS_ANSWERED,
                        'metric_name' => 'Anrufe beantwortet',
                        'target_value' => 200,
                        'target_unit' => GoalMetric::UNIT_COUNT,
                        'weight' => 0.5,
                    ],
                ];
                break;

            case self::TEMPLATE_REVENUE_OPTIMIZATION:
                $defaultMetrics = [
                    [
                        'metric_type' => GoalMetric::TYPE_REVENUE_GENERATED,
                        'metric_name' => 'Generierter Umsatz',
                        'target_value' => 10000,
                        'target_unit' => GoalMetric::UNIT_CURRENCY,
                        'weight' => 1.0,
                        'is_primary' => true,
                    ],
                    [
                        'metric_type' => GoalMetric::TYPE_APPOINTMENTS_COMPLETED,
                        'metric_name' => 'Durchgeführte Termine',
                        'target_value' => 50,
                        'target_unit' => GoalMetric::UNIT_COUNT,
                        'weight' => 0.6,
                    ],
                ];
                break;
        }

        foreach ($defaultMetrics as $metric) {
            $this->metrics()->create($metric);
        }
    }

    public function createDefaultFunnelSteps()
    {
        $defaultSteps = [];

        switch ($this->template_type) {
            case self::TEMPLATE_MAX_APPOINTMENTS:
                $defaultSteps = [
                    ['order' => 1, 'name' => 'Anruf erhalten', 'type' => GoalFunnelStep::TYPE_CALL_RECEIVED],
                    ['order' => 2, 'name' => 'Anruf angenommen', 'type' => GoalFunnelStep::TYPE_CALL_ANSWERED],
                    ['order' => 3, 'name' => 'Termin angefragt', 'type' => GoalFunnelStep::TYPE_APPOINTMENT_REQUESTED],
                    ['order' => 4, 'name' => 'Termin vereinbart', 'type' => GoalFunnelStep::TYPE_APPOINTMENT_SCHEDULED],
                ];
                break;

            case self::TEMPLATE_DATA_COLLECTION:
                $defaultSteps = [
                    ['order' => 1, 'name' => 'Anruf erhalten', 'type' => GoalFunnelStep::TYPE_CALL_RECEIVED],
                    ['order' => 2, 'name' => 'Name erfasst', 'type' => GoalFunnelStep::TYPE_DATA_CAPTURED, 'fields' => ['name']],
                    ['order' => 3, 'name' => 'Email erfasst', 'type' => GoalFunnelStep::TYPE_EMAIL_CAPTURED, 'fields' => ['email']],
                    ['order' => 4, 'name' => 'Telefon erfasst', 'type' => GoalFunnelStep::TYPE_PHONE_CAPTURED, 'fields' => ['phone']],
                    ['order' => 5, 'name' => 'Adresse erfasst', 'type' => GoalFunnelStep::TYPE_ADDRESS_CAPTURED, 'fields' => ['address']],
                ];
                break;

            case self::TEMPLATE_REVENUE_OPTIMIZATION:
                $defaultSteps = [
                    ['order' => 1, 'name' => 'Anruf erhalten', 'type' => GoalFunnelStep::TYPE_CALL_RECEIVED],
                    ['order' => 2, 'name' => 'Termin vereinbart', 'type' => GoalFunnelStep::TYPE_APPOINTMENT_SCHEDULED],
                    ['order' => 3, 'name' => 'Termin durchgeführt', 'type' => GoalFunnelStep::TYPE_APPOINTMENT_COMPLETED],
                    ['order' => 4, 'name' => 'Zahlung erhalten', 'type' => GoalFunnelStep::TYPE_PAYMENT_RECEIVED],
                ];
                break;
        }

        foreach ($defaultSteps as $step) {
            $this->funnelSteps()->create([
                'step_order' => $step['order'],
                'step_name' => $step['name'],
                'step_type' => $step['type'],
                'required_fields' => $step['fields'] ?? null,
            ]);
        }
    }
}
