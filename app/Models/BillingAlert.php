<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class BillingAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'config_id',
        'alert_type',
        'severity',
        'title',
        'message',
        'data',
        'threshold_value',
        'current_value',
        'status',
        'delivery_attempts',
        'sent_at',
        'acknowledged_at',
        'acknowledged_by',
        'channels_used',
        'channel_results',
    ];

    protected $casts = [
        'data' => 'array',
        'delivery_attempts' => 'array',
        'channels_used' => 'array',
        'channel_results' => 'array',
        'sent_at' => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';
    const STATUS_ACKNOWLEDGED = 'acknowledged';

    // Severity constants
    const SEVERITY_INFO = 'info';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_CRITICAL = 'critical';
    
    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    /**
     * Get the company that owns this alert.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the configuration that generated this alert.
     */
    public function config(): BelongsTo
    {
        return $this->belongsTo(BillingAlertConfig::class, 'config_id');
    }

    /**
     * Get the user who acknowledged this alert.
     */
    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    /**
     * Mark alert as sent.
     */
    public function markAsSent(array $channels = [], array $results = []): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
            'channels_used' => $channels,
            'channel_results' => $results,
        ]);
    }

    /**
     * Mark alert as failed.
     */
    public function markAsFailed(array $attempts = []): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'delivery_attempts' => array_merge($this->delivery_attempts ?? [], $attempts),
        ]);
    }

    /**
     * Acknowledge the alert.
     */
    public function acknowledge(User $user): void
    {
        $this->update([
            'status' => self::STATUS_ACKNOWLEDGED,
            'acknowledged_at' => now(),
            'acknowledged_by' => $user->id,
        ]);
    }

    /**
     * Check if alert is actionable.
     */
    public function isActionable(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_SENT]);
    }

    /**
     * Get formatted data for notification.
     */
    public function getNotificationData(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->alert_type,
            'severity' => $this->severity,
            'title' => $this->title,
            'message' => $this->message,
            'company' => $this->company->name,
            'threshold' => $this->threshold_value,
            'current' => $this->current_value,
            'data' => $this->data,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }

    /**
     * Get severity color for UI.
     */
    public function getSeverityColor(): string
    {
        return match ($this->severity) {
            self::SEVERITY_CRITICAL => 'danger',
            self::SEVERITY_WARNING => 'warning',
            self::SEVERITY_INFO => 'info',
            default => 'secondary',
        };
    }

    /**
     * Get severity icon for UI.
     */
    public function getSeverityIcon(): string
    {
        return match ($this->severity) {
            self::SEVERITY_CRITICAL => 'heroicon-o-exclamation-circle',
            self::SEVERITY_WARNING => 'heroicon-o-exclamation-triangle',
            self::SEVERITY_INFO => 'heroicon-o-information-circle',
            default => 'heroicon-o-bell',
        };
    }

    /**
     * Scope for pending alerts.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for unacknowledged alerts.
     */
    public function scopeUnacknowledged($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_SENT]);
    }

    /**
     * Scope for critical alerts.
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', self::SEVERITY_CRITICAL);
    }
}