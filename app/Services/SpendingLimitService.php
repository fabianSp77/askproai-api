<?php

namespace App\Services;

use App\Models\Company;
use App\Models\BillingSpendingLimit;
use App\Mail\SpendingLimitAlert;
use App\Mail\SpendingLimitExceeded;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SpendingLimitService
{
    /**
     * Get or create spending limits for company
     */
    public function getOrCreateSpendingLimits(Company $company): BillingSpendingLimit
    {
        return BillingSpendingLimit::firstOrCreate(
            ['company_id' => $company->id],
            [
                'daily_limit' => null,
                'weekly_limit' => null,
                'monthly_limit' => null,
                'alert_thresholds' => [50, 80, 100],
                'current_day_date' => now()->toDateString(),
                'current_week_start' => now()->startOfWeek()->toDateString(),
                'current_month_start' => now()->startOfMonth()->toDateString(),
            ]
        );
    }

    /**
     * Check if spending is allowed
     */
    public function canSpend(Company $company, float $amount): bool
    {
        $limits = $this->getOrCreateSpendingLimits($company);
        return $limits->canSpend($amount);
    }

    /**
     * Record spending and check limits
     */
    public function recordSpending(Company $company, float $amount): array
    {
        $limits = $this->getOrCreateSpendingLimits($company);
        
        // Record the spending
        $violations = $limits->recordSpending($amount);
        
        // Check for alerts
        if ($limits->send_alerts) {
            $alerts = $limits->getAlertLevels();
            if (!empty($alerts)) {
                $this->sendAlerts($company, $limits, $alerts);
                $limits->updateAlertLevels($alerts);
            }
        }

        // Log violations if any
        if (!empty($violations)) {
            Log::warning('Spending limits exceeded', [
                'company_id' => $company->id,
                'violations' => $violations,
            ]);
            
            if ($limits->send_alerts) {
                $this->sendLimitExceededNotification($company, $limits, $violations);
            }
        }

        return [
            'allowed' => empty($violations) || !$limits->hard_limit,
            'violations' => $violations,
            'alerts' => $alerts ?? [],
        ];
    }

    /**
     * Configure spending limits
     */
    public function configureLimits(
        Company $company,
        ?float $dailyLimit = null,
        ?float $weeklyLimit = null,
        ?float $monthlyLimit = null,
        ?array $alertThresholds = null,
        ?bool $hardLimit = null,
        ?bool $sendAlerts = null
    ): BillingSpendingLimit {
        $limits = $this->getOrCreateSpendingLimits($company);
        
        $updateData = [];
        
        if ($dailyLimit !== null) {
            $updateData['daily_limit'] = $dailyLimit > 0 ? $dailyLimit : null;
        }
        
        if ($weeklyLimit !== null) {
            $updateData['weekly_limit'] = $weeklyLimit > 0 ? $weeklyLimit : null;
        }
        
        if ($monthlyLimit !== null) {
            $updateData['monthly_limit'] = $monthlyLimit > 0 ? $monthlyLimit : null;
        }
        
        if ($alertThresholds !== null) {
            // Validate thresholds are between 0 and 100
            $validThresholds = array_filter($alertThresholds, function ($t) {
                return is_numeric($t) && $t > 0 && $t <= 100;
            });
            $updateData['alert_thresholds'] = array_values($validThresholds);
        }
        
        if ($hardLimit !== null) {
            $updateData['hard_limit'] = $hardLimit;
        }
        
        if ($sendAlerts !== null) {
            $updateData['send_alerts'] = $sendAlerts;
        }
        
        if (!empty($updateData)) {
            $limits->update($updateData);
            
            Log::info('Spending limits updated', [
                'company_id' => $company->id,
                'limits' => $updateData,
            ]);
        }
        
        return $limits->fresh();
    }

    /**
     * Get spending summary
     */
    public function getSpendingSummary(Company $company): array
    {
        $limits = $this->getOrCreateSpendingLimits($company);
        $limits->resetIfNeeded();
        
        $summary = [
            'daily' => [
                'spent' => $limits->current_day_spent,
                'limit' => $limits->daily_limit,
                'percentage' => $limits->daily_limit ? 
                    round(($limits->current_day_spent / $limits->daily_limit) * 100, 2) : 0,
                'remaining' => $limits->daily_limit ? 
                    max(0, $limits->daily_limit - $limits->current_day_spent) : null,
            ],
            'weekly' => [
                'spent' => $limits->current_week_spent,
                'limit' => $limits->weekly_limit,
                'percentage' => $limits->weekly_limit ? 
                    round(($limits->current_week_spent / $limits->weekly_limit) * 100, 2) : 0,
                'remaining' => $limits->weekly_limit ? 
                    max(0, $limits->weekly_limit - $limits->current_week_spent) : null,
            ],
            'monthly' => [
                'spent' => $limits->current_month_spent,
                'limit' => $limits->monthly_limit,
                'percentage' => $limits->monthly_limit ? 
                    round(($limits->current_month_spent / $limits->monthly_limit) * 100, 2) : 0,
                'remaining' => $limits->monthly_limit ? 
                    max(0, $limits->monthly_limit - $limits->current_month_spent) : null,
            ],
            'settings' => [
                'hard_limit' => $limits->hard_limit,
                'send_alerts' => $limits->send_alerts,
                'alert_thresholds' => $limits->alert_thresholds,
            ],
            'periods' => [
                'day_start' => $limits->current_day_date,
                'week_start' => $limits->current_week_start,
                'month_start' => $limits->current_month_start,
            ],
        ];
        
        // Add days remaining in periods
        $now = now();
        $summary['periods']['days_in_week_remaining'] = 7 - $now->dayOfWeek;
        $summary['periods']['days_in_month_remaining'] = $now->daysInMonth - $now->day;
        
        return $summary;
    }

    /**
     * Reset spending counters (manual reset)
     */
    public function resetCounters(Company $company, array $periods = ['daily', 'weekly', 'monthly']): void
    {
        $limits = $this->getOrCreateSpendingLimits($company);
        $updates = [];
        
        if (in_array('daily', $periods)) {
            $updates['current_day_spent'] = 0;
            $updates['current_day_date'] = now()->toDateString();
            $updates['last_daily_alert_level'] = 0;
        }
        
        if (in_array('weekly', $periods)) {
            $updates['current_week_spent'] = 0;
            $updates['current_week_start'] = now()->startOfWeek()->toDateString();
            $updates['last_weekly_alert_level'] = 0;
        }
        
        if (in_array('monthly', $periods)) {
            $updates['current_month_spent'] = 0;
            $updates['current_month_start'] = now()->startOfMonth()->toDateString();
            $updates['last_monthly_alert_level'] = 0;
        }
        
        if (!empty($updates)) {
            $limits->update($updates);
            
            Log::info('Spending counters reset', [
                'company_id' => $company->id,
                'periods' => $periods,
            ]);
        }
    }

    /**
     * Send alert notifications
     */
    protected function sendAlerts(Company $company, BillingSpendingLimit $limits, array $alerts): void
    {
        $recipients = $this->getNotificationRecipients($company);
        
        if (empty($recipients)) {
            return;
        }

        try {
            Mail::to($recipients)->send(new SpendingLimitAlert(
                $company,
                $alerts,
                $this->getSpendingSummary($company)
            ));
            
            Log::info('Spending limit alerts sent', [
                'company_id' => $company->id,
                'alerts' => array_keys($alerts),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send spending limit alerts', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send limit exceeded notification
     */
    protected function sendLimitExceededNotification(
        Company $company, 
        BillingSpendingLimit $limits, 
        array $violations
    ): void {
        $recipients = $this->getNotificationRecipients($company);
        
        if (empty($recipients)) {
            return;
        }

        try {
            Mail::to($recipients)->send(new SpendingLimitExceeded(
                $company,
                $violations,
                $limits->hard_limit
            ));
            
            Log::info('Spending limit exceeded notification sent', [
                'company_id' => $company->id,
                'violations' => array_keys($violations),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send limit exceeded notification', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get notification recipients
     */
    protected function getNotificationRecipients(Company $company): array
    {
        return $company->portalUsers()
            ->where('is_active', true)
            ->whereHas('permissions', function ($q) {
                $q->where('permission', 'billing.view')
                  ->orWhere('permission', 'billing.admin');
            })
            ->pluck('email')
            ->toArray();
    }

    /**
     * Get spending trend data for charts
     */
    public function getSpendingTrends(Company $company, int $days = 30): array
    {
        $endDate = now();
        $startDate = now()->subDays($days);
        
        // Get daily spending from call charges
        $dailySpending = \DB::table('call_charges')
            ->where('company_id', $company->id)
            ->whereBetween('charged_at', [$startDate, $endDate])
            ->selectRaw('DATE(charged_at) as date, SUM(amount_charged) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date')
            ->toArray();
        
        // Fill in missing days with zero
        $trends = [];
        $current = $startDate->copy();
        
        while ($current <= $endDate) {
            $dateStr = $current->toDateString();
            $trends[] = [
                'date' => $dateStr,
                'amount' => $dailySpending[$dateStr]->total ?? 0,
                'day_of_week' => $current->dayOfWeek,
                'is_weekend' => $current->isWeekend(),
            ];
            $current->addDay();
        }
        
        // Calculate averages
        $weekdayAvg = collect($trends)
            ->where('is_weekend', false)
            ->avg('amount');
            
        $weekendAvg = collect($trends)
            ->where('is_weekend', true)
            ->avg('amount');
        
        return [
            'daily' => $trends,
            'averages' => [
                'overall' => collect($trends)->avg('amount'),
                'weekday' => $weekdayAvg,
                'weekend' => $weekendAvg,
            ],
            'totals' => [
                'period' => collect($trends)->sum('amount'),
                'highest_day' => collect($trends)->max('amount'),
                'lowest_day' => collect($trends)->min('amount'),
            ],
        ];
    }
}