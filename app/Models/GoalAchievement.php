<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalAchievement extends Model
{
    protected $fillable = [
        'company_goal_id',
        'goal_metric_id',
        'branch_id',
        'period_start',
        'period_end',
        'period_type',
        'achieved_value',
        'target_value',
        'achievement_percentage',
        'breakdown',
        'funnel_data',
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'achieved_value' => 'decimal:2',
        'target_value' => 'decimal:2',
        'achievement_percentage' => 'decimal:2',
        'breakdown' => 'array',
        'funnel_data' => 'array',
    ];

    // Period types
    const PERIOD_HOURLY = 'hourly';
    const PERIOD_DAILY = 'daily';
    const PERIOD_WEEKLY = 'weekly';
    const PERIOD_MONTHLY = 'monthly';
    const PERIOD_QUARTERLY = 'quarterly';
    const PERIOD_YEARLY = 'yearly';
    const PERIOD_CUSTOM = 'custom';

    // Relationships
    public function companyGoal(): BelongsTo
    {
        return $this->belongsTo(CompanyGoal::class);
    }

    public function goalMetric(): BelongsTo
    {
        return $this->belongsTo(GoalMetric::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    // Scopes
    public function scopeForPeriod($query, $periodType, $date = null)
    {
        $date = $date ?: now();
        
        switch ($periodType) {
            case self::PERIOD_DAILY:
                return $query->whereDate('period_start', $date->toDateString());
            
            case self::PERIOD_WEEKLY:
                return $query->where('period_start', '>=', $date->startOfWeek())
                            ->where('period_end', '<=', $date->endOfWeek());
            
            case self::PERIOD_MONTHLY:
                return $query->whereMonth('period_start', $date->month)
                            ->whereYear('period_start', $date->year);
            
            case self::PERIOD_QUARTERLY:
                return $query->where('period_start', '>=', $date->startOfQuarter())
                            ->where('period_end', '<=', $date->endOfQuarter());
            
            case self::PERIOD_YEARLY:
                return $query->whereYear('period_start', $date->year);
            
            default:
                return $query;
        }
    }

    public function scopeSuccessful($query, $threshold = 100)
    {
        return $query->where('achievement_percentage', '>=', $threshold);
    }

    public function scopeFailed($query, $threshold = 100)
    {
        return $query->where('achievement_percentage', '<', $threshold);
    }

    // Attributes
    public function getIsSuccessfulAttribute(): bool
    {
        return $this->achievement_percentage >= 100;
    }

    public function getFormattedPeriodAttribute(): string
    {
        switch ($this->period_type) {
            case self::PERIOD_HOURLY:
                return $this->period_start->format('d.m.Y H:i');
            
            case self::PERIOD_DAILY:
                return $this->period_start->format('d.m.Y');
            
            case self::PERIOD_WEEKLY:
                return 'KW ' . $this->period_start->format('W Y');
            
            case self::PERIOD_MONTHLY:
                return $this->period_start->format('F Y');
            
            case self::PERIOD_QUARTERLY:
                return 'Q' . $this->period_start->quarter . ' ' . $this->period_start->year;
            
            case self::PERIOD_YEARLY:
                return $this->period_start->format('Y');
            
            default:
                return $this->period_start->format('d.m.Y') . ' - ' . $this->period_end->format('d.m.Y');
        }
    }

    // Methods
    public static function recordAchievement(CompanyGoal $goal, $periodType = self::PERIOD_DAILY, $date = null)
    {
        $date = $date ?: now();
        list($periodStart, $periodEnd) = self::getPeriodBounds($periodType, $date);

        // Check if achievement already exists
        $existing = self::where('company_goal_id', $goal->id)
            ->where('period_start', $periodStart)
            ->where('period_end', $periodEnd)
            ->where('period_type', $periodType)
            ->whereNull('goal_metric_id')
            ->first();

        if ($existing) {
            return self::updateAchievement($existing);
        }

        // Calculate overall goal achievement
        $totalAchievement = 0;
        $totalWeight = 0;
        $breakdown = [];

        foreach ($goal->metrics as $metric) {
            $currentValue = $metric->getCurrentValue($periodStart, $periodEnd);
            $achievementPercentage = $metric->getAchievementPercentage($currentValue);
            
            $totalAchievement += $achievementPercentage * $metric->weight;
            $totalWeight += $metric->weight;

            $breakdown[$metric->id] = [
                'metric_name' => $metric->metric_name,
                'current_value' => $currentValue,
                'target_value' => $metric->target_value,
                'achievement_percentage' => $achievementPercentage,
                'weight' => $metric->weight,
            ];

            // Record individual metric achievement
            self::recordMetricAchievement($goal, $metric, $periodType, $date, $currentValue, $achievementPercentage);
        }

        $overallAchievement = $totalWeight > 0 ? $totalAchievement / $totalWeight : 0;

        // Get funnel data
        $funnelData = [];
        foreach ($goal->funnelSteps as $step) {
            $funnelData[] = $step->getFunnelData($periodStart, $periodEnd);
        }

        return self::create([
            'company_goal_id' => $goal->id,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'period_type' => $periodType,
            'achieved_value' => $overallAchievement,
            'target_value' => 100,
            'achievement_percentage' => $overallAchievement,
            'breakdown' => $breakdown,
            'funnel_data' => $funnelData,
        ]);
    }

    private static function recordMetricAchievement($goal, $metric, $periodType, $date, $currentValue, $achievementPercentage)
    {
        list($periodStart, $periodEnd) = self::getPeriodBounds($periodType, $date);

        return self::updateOrCreate(
            [
                'company_goal_id' => $goal->id,
                'goal_metric_id' => $metric->id,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'period_type' => $periodType,
            ],
            [
                'achieved_value' => $currentValue,
                'target_value' => $metric->target_value,
                'achievement_percentage' => $achievementPercentage,
            ]
        );
    }

    private static function updateAchievement($achievement)
    {
        $goal = $achievement->companyGoal;
        $totalAchievement = 0;
        $totalWeight = 0;
        $breakdown = [];

        foreach ($goal->metrics as $metric) {
            $currentValue = $metric->getCurrentValue($achievement->period_start, $achievement->period_end);
            $achievementPercentage = $metric->getAchievementPercentage($currentValue);
            
            $totalAchievement += $achievementPercentage * $metric->weight;
            $totalWeight += $metric->weight;

            $breakdown[$metric->id] = [
                'metric_name' => $metric->metric_name,
                'current_value' => $currentValue,
                'target_value' => $metric->target_value,
                'achievement_percentage' => $achievementPercentage,
                'weight' => $metric->weight,
            ];
        }

        $overallAchievement = $totalWeight > 0 ? $totalAchievement / $totalWeight : 0;

        // Get funnel data
        $funnelData = [];
        foreach ($goal->funnelSteps as $step) {
            $funnelData[] = $step->getFunnelData($achievement->period_start, $achievement->period_end);
        }

        $achievement->update([
            'achieved_value' => $overallAchievement,
            'achievement_percentage' => $overallAchievement,
            'breakdown' => $breakdown,
            'funnel_data' => $funnelData,
        ]);

        return $achievement;
    }

    private static function getPeriodBounds($periodType, $date)
    {
        switch ($periodType) {
            case self::PERIOD_HOURLY:
                $start = $date->copy()->startOfHour();
                $end = $date->copy()->endOfHour();
                break;
            
            case self::PERIOD_DAILY:
                $start = $date->copy()->startOfDay();
                $end = $date->copy()->endOfDay();
                break;
            
            case self::PERIOD_WEEKLY:
                $start = $date->copy()->startOfWeek();
                $end = $date->copy()->endOfWeek();
                break;
            
            case self::PERIOD_MONTHLY:
                $start = $date->copy()->startOfMonth();
                $end = $date->copy()->endOfMonth();
                break;
            
            case self::PERIOD_QUARTERLY:
                $start = $date->copy()->startOfQuarter();
                $end = $date->copy()->endOfQuarter();
                break;
            
            case self::PERIOD_YEARLY:
                $start = $date->copy()->startOfYear();
                $end = $date->copy()->endOfYear();
                break;
            
            default:
                $start = $date->copy()->startOfDay();
                $end = $date->copy()->endOfDay();
        }

        return [$start, $end];
    }
}
