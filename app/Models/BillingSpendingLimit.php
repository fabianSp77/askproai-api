<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BillingSpendingLimit extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'daily_limit',
        'weekly_limit',
        'monthly_limit',
        'alert_thresholds',
        'current_day_spent',
        'current_week_spent',
        'current_month_spent',
        'last_daily_alert_level',
        'last_weekly_alert_level',
        'last_monthly_alert_level',
        'current_day_date',
        'current_week_start',
        'current_month_start',
        'send_alerts',
        'last_alert_sent_at',
        'hard_limit',
    ];

    protected $casts = [
        'daily_limit' => 'decimal:2',
        'weekly_limit' => 'decimal:2',
        'monthly_limit' => 'decimal:2',
        'alert_thresholds' => 'array',
        'current_day_spent' => 'decimal:2',
        'current_week_spent' => 'decimal:2',
        'current_month_spent' => 'decimal:2',
        'last_daily_alert_level' => 'integer',
        'last_weekly_alert_level' => 'integer',
        'last_monthly_alert_level' => 'integer',
        'current_day_date' => 'date',
        'current_week_start' => 'date',
        'current_month_start' => 'date',
        'send_alerts' => 'boolean',
        'last_alert_sent_at' => 'datetime',
        'hard_limit' => 'boolean',
    ];

    protected $attributes = [
        'alert_thresholds' => '[50, 80, 100]',
        'current_day_spent' => 0,
        'current_week_spent' => 0,
        'current_month_spent' => 0,
        'last_daily_alert_level' => 0,
        'last_weekly_alert_level' => 0,
        'last_monthly_alert_level' => 0,
        'send_alerts' => true,
        'hard_limit' => false,
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Helper Methods
    public function resetIfNeeded(): void
    {
        $now = now();
        $hasChanges = false;

        // Reset daily
        if ($this->current_day_date->lt($now->startOfDay())) {
            $this->current_day_spent = 0;
            $this->current_day_date = $now->toDateString();
            $this->last_daily_alert_level = 0;
            $hasChanges = true;
        }

        // Reset weekly
        if ($this->current_week_start->lt($now->startOfWeek())) {
            $this->current_week_spent = 0;
            $this->current_week_start = $now->startOfWeek()->toDateString();
            $this->last_weekly_alert_level = 0;
            $hasChanges = true;
        }

        // Reset monthly
        if ($this->current_month_start->lt($now->startOfMonth())) {
            $this->current_month_spent = 0;
            $this->current_month_start = $now->startOfMonth()->toDateString();
            $this->last_monthly_alert_level = 0;
            $hasChanges = true;
        }

        if ($hasChanges) {
            $this->save();
        }
    }

    public function recordSpending(float $amount): array
    {
        $this->resetIfNeeded();
        
        $this->increment('current_day_spent', $amount);
        $this->increment('current_week_spent', $amount);
        $this->increment('current_month_spent', $amount);

        return $this->checkLimits();
    }

    public function checkLimits(): array
    {
        $violations = [];

        // Check daily limit
        if ($this->daily_limit !== null && $this->current_day_spent > $this->daily_limit) {
            $violations['daily'] = [
                'limit' => $this->daily_limit,
                'spent' => $this->current_day_spent,
                'percentage' => ($this->current_day_spent / $this->daily_limit) * 100,
            ];
        }

        // Check weekly limit
        if ($this->weekly_limit !== null && $this->current_week_spent > $this->weekly_limit) {
            $violations['weekly'] = [
                'limit' => $this->weekly_limit,
                'spent' => $this->current_week_spent,
                'percentage' => ($this->current_week_spent / $this->weekly_limit) * 100,
            ];
        }

        // Check monthly limit
        if ($this->monthly_limit !== null && $this->current_month_spent > $this->monthly_limit) {
            $violations['monthly'] = [
                'limit' => $this->monthly_limit,
                'spent' => $this->current_month_spent,
                'percentage' => ($this->current_month_spent / $this->monthly_limit) * 100,
            ];
        }

        return $violations;
    }

    public function getAlertLevels(): array
    {
        $alerts = [];

        // Daily alerts
        if ($this->daily_limit !== null) {
            $percentage = ($this->current_day_spent / $this->daily_limit) * 100;
            $alertLevel = $this->getAlertLevel($percentage);
            if ($alertLevel > $this->last_daily_alert_level) {
                $alerts['daily'] = [
                    'level' => $alertLevel,
                    'percentage' => $percentage,
                    'limit' => $this->daily_limit,
                    'spent' => $this->current_day_spent,
                ];
            }
        }

        // Weekly alerts
        if ($this->weekly_limit !== null) {
            $percentage = ($this->current_week_spent / $this->weekly_limit) * 100;
            $alertLevel = $this->getAlertLevel($percentage);
            if ($alertLevel > $this->last_weekly_alert_level) {
                $alerts['weekly'] = [
                    'level' => $alertLevel,
                    'percentage' => $percentage,
                    'limit' => $this->weekly_limit,
                    'spent' => $this->current_week_spent,
                ];
            }
        }

        // Monthly alerts
        if ($this->monthly_limit !== null) {
            $percentage = ($this->current_month_spent / $this->monthly_limit) * 100;
            $alertLevel = $this->getAlertLevel($percentage);
            if ($alertLevel > $this->last_monthly_alert_level) {
                $alerts['monthly'] = [
                    'level' => $alertLevel,
                    'percentage' => $percentage,
                    'limit' => $this->monthly_limit,
                    'spent' => $this->current_month_spent,
                ];
            }
        }

        return $alerts;
    }

    protected function getAlertLevel(float $percentage): int
    {
        $level = 0;
        foreach ($this->alert_thresholds as $threshold) {
            if ($percentage >= $threshold) {
                $level++;
            }
        }
        return $level;
    }

    public function updateAlertLevels(array $alerts): void
    {
        if (isset($alerts['daily'])) {
            $this->last_daily_alert_level = $alerts['daily']['level'];
        }
        if (isset($alerts['weekly'])) {
            $this->last_weekly_alert_level = $alerts['weekly']['level'];
        }
        if (isset($alerts['monthly'])) {
            $this->last_monthly_alert_level = $alerts['monthly']['level'];
        }
        
        if (!empty($alerts)) {
            $this->last_alert_sent_at = now();
            $this->save();
        }
    }

    public function canSpend(float $amount): bool
    {
        if (!$this->hard_limit) {
            return true;
        }

        $this->resetIfNeeded();

        // Check if any limit would be exceeded
        if ($this->daily_limit !== null && ($this->current_day_spent + $amount) > $this->daily_limit) {
            return false;
        }
        if ($this->weekly_limit !== null && ($this->current_week_spent + $amount) > $this->weekly_limit) {
            return false;
        }
        if ($this->monthly_limit !== null && ($this->current_month_spent + $amount) > $this->monthly_limit) {
            return false;
        }

        return true;
    }
}