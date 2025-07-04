<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingAlertConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'alert_type',
        'is_enabled',
        'thresholds',
        'notification_channels',
        'advance_days',
        'amount_threshold',
        'recipients',
        'notify_primary_contact',
        'notify_billing_contact',
        'preferred_time',
        'quiet_hours',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'thresholds' => 'array',
        'notification_channels' => 'array',
        'recipients' => 'array',
        'notify_primary_contact' => 'boolean',
        'notify_billing_contact' => 'boolean',
        'quiet_hours' => 'array',
        'preferred_time' => 'datetime:H:i',
    ];

    // Alert type constants
    const TYPE_USAGE_LIMIT = 'usage_limit';
    const TYPE_PAYMENT_REMINDER = 'payment_reminder';
    const TYPE_SUBSCRIPTION_RENEWAL = 'subscription_renewal';
    const TYPE_OVERAGE_WARNING = 'overage_warning';
    const TYPE_PAYMENT_FAILED = 'payment_failed';
    const TYPE_BUDGET_EXCEEDED = 'budget_exceeded';
    const TYPE_LOW_BALANCE = 'low_balance';
    const TYPE_INVOICE_GENERATED = 'invoice_generated';

    // Default thresholds
    const DEFAULT_USAGE_THRESHOLDS = [80, 90, 100];
    const DEFAULT_BUDGET_THRESHOLDS = [75, 90, 100];
    const DEFAULT_PAYMENT_REMINDER_DAYS = [7, 3, 1];
    const DEFAULT_RENEWAL_REMINDER_DAYS = [14, 7, 1];
    
    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    /**
     * Get the company that owns this alert configuration.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the alerts generated from this configuration.
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(BillingAlert::class, 'config_id');
    }

    /**
     * Check if notification should be sent based on quiet hours.
     */
    public function isWithinNotificationHours(\DateTime $time = null): bool
    {
        if (!$this->quiet_hours) {
            return true;
        }

        $time = $time ?? now();
        $currentHour = $time->format('H');

        $quietStart = $this->quiet_hours['start'] ?? '22';
        $quietEnd = $this->quiet_hours['end'] ?? '08';

        if ($quietStart < $quietEnd) {
            return $currentHour < $quietStart || $currentHour >= $quietEnd;
        } else {
            return $currentHour < $quietStart && $currentHour >= $quietEnd;
        }
    }

    /**
     * Get all recipient emails for this alert.
     */
    public function getRecipientEmails(): array
    {
        $emails = [];

        if ($this->notify_primary_contact && $this->company->email) {
            $emails[] = $this->company->email;
        }

        if ($this->notify_billing_contact && $this->company->billing_contact_email) {
            $emails[] = $this->company->billing_contact_email;
        }

        if ($this->recipients && is_array($this->recipients)) {
            $emails = array_merge($emails, $this->recipients);
        }

        return array_unique(array_filter($emails));
    }

    /**
     * Check if a threshold has been crossed.
     */
    public function shouldTriggerForValue(float $currentValue, float $maxValue): ?float
    {
        if (!$this->thresholds || !is_array($this->thresholds)) {
            return null;
        }

        $percentage = ($currentValue / $maxValue) * 100;
        
        // Find the highest threshold that has been crossed
        $crossedThreshold = null;
        foreach ($this->thresholds as $threshold) {
            if ($percentage >= $threshold && $threshold > ($crossedThreshold ?? 0)) {
                $crossedThreshold = $threshold;
            }
        }

        return $crossedThreshold;
    }

    /**
     * Get severity based on threshold.
     */
    public function getSeverityForThreshold(float $threshold): string
    {
        if ($threshold >= 100) {
            return 'critical';
        } elseif ($threshold >= 90) {
            return 'warning';
        } else {
            return 'info';
        }
    }

    /**
     * Create default configurations for a company.
     */
    public static function createDefaultsForCompany(Company $company): void
    {
        $defaults = [
            [
                'alert_type' => self::TYPE_USAGE_LIMIT,
                'thresholds' => self::DEFAULT_USAGE_THRESHOLDS,
                'notification_channels' => ['email'],
            ],
            [
                'alert_type' => self::TYPE_PAYMENT_REMINDER,
                'advance_days' => 3,
                'notification_channels' => ['email'],
            ],
            [
                'alert_type' => self::TYPE_SUBSCRIPTION_RENEWAL,
                'advance_days' => 7,
                'notification_channels' => ['email'],
            ],
            [
                'alert_type' => self::TYPE_PAYMENT_FAILED,
                'notification_channels' => ['email'],
                'is_enabled' => true,
            ],
            [
                'alert_type' => self::TYPE_BUDGET_EXCEEDED,
                'thresholds' => self::DEFAULT_BUDGET_THRESHOLDS,
                'notification_channels' => ['email'],
            ],
        ];

        foreach ($defaults as $config) {
            self::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'alert_type' => $config['alert_type'],
                ],
                array_merge($config, [
                    'is_enabled' => true,
                    'notify_primary_contact' => true,
                    'notify_billing_contact' => true,
                ])
            );
        }
    }
}