<?php

namespace App\Services\Billing;

use App\Models\BillingAlert;
use App\Models\BillingAlertConfig;
use App\Models\BillingPeriod;
use App\Models\Company;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Mail\BillingAlertMail;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class BillingAlertService
{
    private NotificationService $notificationService;
    private UsageCalculationService $usageService;

    public function __construct(
        NotificationService $notificationService,
        UsageCalculationService $usageService
    ) {
        $this->notificationService = $notificationService;
        $this->usageService = $usageService;
    }

    /**
     * Check all companies for alerts that need to be triggered.
     */
    public function checkAllAlerts(): void
    {
        Company::where('is_active', true)
            ->where('alerts_enabled', true)
            ->chunk(100, function ($companies) {
                foreach ($companies as $company) {
                    try {
                        $this->checkCompanyAlerts($company);
                    } catch (\Exception $e) {
                        Log::error('Failed to check alerts for company', [
                            'company_id' => $company->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });
    }

    /**
     * Check all alert types for a specific company.
     */
    public function checkCompanyAlerts(Company $company): void
    {
        // Skip if company has active suppression
        if ($this->hasActiveSuppression($company, 'all')) {
            return;
        }

        // Check each alert type
        $this->checkUsageLimitAlerts($company);
        $this->checkPaymentReminderAlerts($company);
        $this->checkSubscriptionRenewalAlerts($company);
        $this->checkBudgetAlerts($company);
    }

    /**
     * Check usage limit alerts.
     */
    protected function checkUsageLimitAlerts(Company $company): void
    {
        $config = $this->getAlertConfig($company, BillingAlertConfig::TYPE_USAGE_LIMIT);
        
        if (!$config || !$config->is_enabled) {
            return;
        }

        if ($this->hasActiveSuppression($company, BillingAlertConfig::TYPE_USAGE_LIMIT)) {
            return;
        }

        $currentPeriod = $company->currentBillingPeriod();
        if (!$currentPeriod || !$currentPeriod->included_minutes) {
            return;
        }

        $usage = $this->usageService->getCurrentPeriodUsage($company);
        $totalMinutes = $usage['calls']['total_minutes'] ?? 0;
        $includedMinutes = $currentPeriod->included_minutes;

        $threshold = $config->shouldTriggerForValue($totalMinutes, $includedMinutes);
        
        if ($threshold !== null) {
            // Check if we already sent an alert for this threshold in this period
            $existingAlert = BillingAlert::where('company_id', $company->id)
                ->where('alert_type', BillingAlertConfig::TYPE_USAGE_LIMIT)
                ->where('threshold_value', $threshold)
                ->where('created_at', '>=', $currentPeriod->start_date)
                ->exists();

            if (!$existingAlert) {
                $this->createAndSendAlert($company, $config, [
                    'severity' => $config->getSeverityForThreshold($threshold),
                    'title' => "Usage Alert: {$threshold}% of included minutes used",
                    'message' => sprintf(
                        "You have used %.1f of your %d included minutes (%.1f%%).",
                        $totalMinutes,
                        $includedMinutes,
                        ($totalMinutes / $includedMinutes) * 100
                    ),
                    'threshold_value' => $threshold,
                    'current_value' => $totalMinutes,
                    'data' => [
                        'period_id' => $currentPeriod->id,
                        'included_minutes' => $includedMinutes,
                        'used_minutes' => $totalMinutes,
                        'remaining_minutes' => max(0, $includedMinutes - $totalMinutes),
                    ],
                ]);
            }
        }
    }

    /**
     * Check payment reminder alerts.
     */
    protected function checkPaymentReminderAlerts(Company $company): void
    {
        $config = $this->getAlertConfig($company, BillingAlertConfig::TYPE_PAYMENT_REMINDER);
        
        if (!$config || !$config->is_enabled) {
            return;
        }

        if ($this->hasActiveSuppression($company, BillingAlertConfig::TYPE_PAYMENT_REMINDER)) {
            return;
        }

        // Check for unpaid invoices
        $unpaidInvoices = Invoice::where('company_id', $company->id)
            ->where('status', 'open')
            ->where('due_date', '>', now())
            ->get();

        foreach ($unpaidInvoices as $invoice) {
            $daysUntilDue = now()->diffInDays($invoice->due_date, false);
            
            if ($daysUntilDue > 0 && $daysUntilDue <= ($config->advance_days ?? 7)) {
                // Check if we already sent a reminder for this invoice
                $existingAlert = BillingAlert::where('company_id', $company->id)
                    ->where('alert_type', BillingAlertConfig::TYPE_PAYMENT_REMINDER)
                    ->whereJsonContains('data->invoice_id', $invoice->id)
                    ->where('created_at', '>=', now()->subDays(1))
                    ->exists();

                if (!$existingAlert) {
                    $this->createAndSendAlert($company, $config, [
                        'severity' => $daysUntilDue <= 1 ? 'warning' : 'info',
                        'title' => "Payment Due in {$daysUntilDue} days",
                        'message' => sprintf(
                            "Invoice %s for €%.2f is due on %s.",
                            $invoice->number,
                            $invoice->total,
                            $invoice->due_date->format('M d, Y')
                        ),
                        'data' => [
                            'invoice_id' => $invoice->id,
                            'invoice_number' => $invoice->number,
                            'amount' => $invoice->total,
                            'due_date' => $invoice->due_date->toIso8601String(),
                            'days_until_due' => $daysUntilDue,
                        ],
                    ]);
                }
            }
        }
    }

    /**
     * Check subscription renewal alerts.
     */
    protected function checkSubscriptionRenewalAlerts(Company $company): void
    {
        $config = $this->getAlertConfig($company, BillingAlertConfig::TYPE_SUBSCRIPTION_RENEWAL);
        
        if (!$config || !$config->is_enabled) {
            return;
        }

        if ($this->hasActiveSuppression($company, BillingAlertConfig::TYPE_SUBSCRIPTION_RENEWAL)) {
            return;
        }

        $subscription = $company->activeSubscription();
        if (!$subscription || !$subscription->current_period_end) {
            return;
        }

        $daysUntilRenewal = now()->diffInDays($subscription->current_period_end, false);
        
        if ($daysUntilRenewal > 0 && $daysUntilRenewal <= ($config->advance_days ?? 14)) {
            // Check if we already sent a reminder for this renewal period
            $existingAlert = BillingAlert::where('company_id', $company->id)
                ->where('alert_type', BillingAlertConfig::TYPE_SUBSCRIPTION_RENEWAL)
                ->where('created_at', '>=', now()->subDays(1))
                ->exists();

            if (!$existingAlert) {
                $this->createAndSendAlert($company, $config, [
                    'severity' => 'info',
                    'title' => "Subscription Renewal in {$daysUntilRenewal} days",
                    'message' => sprintf(
                        "Your subscription will renew on %s for €%.2f.",
                        $subscription->current_period_end->format('M d, Y'),
                        $subscription->amount ?? 0
                    ),
                    'data' => [
                        'subscription_id' => $subscription->id,
                        'renewal_date' => $subscription->current_period_end->toIso8601String(),
                        'amount' => $subscription->amount ?? 0,
                        'days_until_renewal' => $daysUntilRenewal,
                    ],
                ]);
            }
        }
    }

    /**
     * Check budget alerts.
     */
    protected function checkBudgetAlerts(Company $company): void
    {
        $config = $this->getAlertConfig($company, BillingAlertConfig::TYPE_BUDGET_EXCEEDED);
        
        if (!$config || !$config->is_enabled || !$company->usage_budget) {
            return;
        }

        if ($this->hasActiveSuppression($company, BillingAlertConfig::TYPE_BUDGET_EXCEEDED)) {
            return;
        }

        $usage = $this->usageService->getCurrentPeriodUsage($company);
        $currentCost = $usage['calculations']['total_cost'] ?? 0;
        $budget = $company->usage_budget;

        $threshold = $config->shouldTriggerForValue($currentCost, $budget);
        
        if ($threshold !== null) {
            // Check if we already sent an alert for this threshold this month
            $existingAlert = BillingAlert::where('company_id', $company->id)
                ->where('alert_type', BillingAlertConfig::TYPE_BUDGET_EXCEEDED)
                ->where('threshold_value', $threshold)
                ->where('created_at', '>=', now()->startOfMonth())
                ->exists();

            if (!$existingAlert) {
                $this->createAndSendAlert($company, $config, [
                    'severity' => $config->getSeverityForThreshold($threshold),
                    'title' => "Budget Alert: {$threshold}% of budget used",
                    'message' => sprintf(
                        "Current usage charges of €%.2f have reached %.1f%% of your €%.2f budget.",
                        $currentCost,
                        ($currentCost / $budget) * 100,
                        $budget
                    ),
                    'threshold_value' => $threshold,
                    'current_value' => $currentCost,
                    'data' => [
                        'budget' => $budget,
                        'current_cost' => $currentCost,
                        'remaining_budget' => max(0, $budget - $currentCost),
                        'usage_percentage' => ($currentCost / $budget) * 100,
                    ],
                ]);
            }
        }
    }

    /**
     * Create and send an alert.
     */
    protected function createAndSendAlert(Company $company, BillingAlertConfig $config, array $alertData): BillingAlert
    {
        // Check if within notification hours
        if (!$config->isWithinNotificationHours()) {
            Log::info('Alert delayed due to quiet hours', [
                'company_id' => $company->id,
                'alert_type' => $config->alert_type,
            ]);
            return null;
        }

        DB::beginTransaction();
        try {
            // Create alert record
            $alert = BillingAlert::create(array_merge($alertData, [
                'company_id' => $company->id,
                'config_id' => $config->id,
                'alert_type' => $config->alert_type,
                'status' => BillingAlert::STATUS_PENDING,
            ]));

            // Send notifications
            $results = $this->sendAlertNotifications($alert, $config);

            // Update alert status based on results
            if (!empty($results['success'])) {
                $alert->markAsSent(
                    array_keys($results['success']),
                    $results
                );
            } else {
                $alert->markAsFailed($results['failed'] ?? []);
            }

            DB::commit();
            return $alert;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create and send alert', [
                'company_id' => $company->id,
                'alert_type' => $config->alert_type,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Send alert notifications through configured channels.
     */
    protected function sendAlertNotifications(BillingAlert $alert, BillingAlertConfig $config): array
    {
        $results = [
            'success' => [],
            'failed' => [],
        ];

        foreach ($config->notification_channels as $channel) {
            try {
                switch ($channel) {
                    case 'email':
                        $this->sendEmailNotification($alert, $config);
                        $results['success']['email'] = true;
                        break;
                    case 'sms':
                        // SMS implementation would go here
                        Log::info('SMS notifications not yet implemented');
                        break;
                    case 'webhook':
                        // Webhook implementation would go here
                        Log::info('Webhook notifications not yet implemented');
                        break;
                }
            } catch (\Exception $e) {
                $results['failed'][$channel] = $e->getMessage();
                Log::error("Failed to send {$channel} notification", [
                    'alert_id' => $alert->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Send email notification for an alert.
     */
    protected function sendEmailNotification(BillingAlert $alert, BillingAlertConfig $config): void
    {
        $recipients = $config->getRecipientEmails();
        
        if (empty($recipients)) {
            throw new \Exception('No recipients configured for email notification');
        }

        foreach ($recipients as $email) {
            Mail::to($email)->send(new BillingAlertMail($alert));
        }
    }

    /**
     * Get alert configuration for a company and type.
     */
    protected function getAlertConfig(Company $company, string $alertType): ?BillingAlertConfig
    {
        return BillingAlertConfig::where('company_id', $company->id)
            ->where('alert_type', $alertType)
            ->first();
    }

    /**
     * Check if company has active suppression for alert type.
     */
    protected function hasActiveSuppression(Company $company, string $alertType): bool
    {
        return DB::table('billing_alert_suppressions')
            ->where('company_id', $company->id)
            ->whereIn('alert_type', [$alertType, 'all'])
            ->where('starts_at', '<=', now())
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->exists();
    }

    /**
     * Create an immediate alert (for events like payment failures).
     */
    public function createImmediateAlert(Company $company, string $alertType, array $data): ?BillingAlert
    {
        $config = $this->getAlertConfig($company, $alertType);
        
        if (!$config) {
            // Create a default config if none exists
            $config = BillingAlertConfig::create([
                'company_id' => $company->id,
                'alert_type' => $alertType,
                'is_enabled' => true,
                'notification_channels' => ['email'],
                'notify_primary_contact' => true,
                'notify_billing_contact' => true,
            ]);
        }

        if (!$config->is_enabled) {
            return null;
        }

        return $this->createAndSendAlert($company, $config, $data);
    }
}