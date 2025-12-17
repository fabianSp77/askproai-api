<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

/**
 * CallbackRequest Model
 *
 * Manages customer callback requests when immediate appointment booking isn't possible.
 * Tracks assignment, contact attempts, and escalation workflows for failed appointments.
 * Includes expiration and priority management for efficient queue processing.
 * Multi-tenant isolation via BelongsToCompany trait.
 *
 * @property int $id
 * @property int|null $customer_id
 * @property int $branch_id
 * @property int|null $service_id
 * @property int|null $staff_id
 * @property string $phone_number
 * @property string $customer_name
 * @property array $preferred_time_window
 * @property string $priority normal|high|urgent
 * @property string $status pending|assigned|contacted|completed|expired|cancelled
 * @property int|null $assigned_to Staff ID
 * @property string|null $notes
 * @property array $metadata
 * @property \Illuminate\Support\Carbon|null $assigned_at
 * @property \Illuminate\Support\Carbon|null $contacted_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class CallbackRequest extends Model
{
    // ⚠️ FIXED: SoftDeletes removed - deleted_at column doesn't exist in Sept 21 backup
    // TODO: Re-enable SoftDeletes when database is fully restored
    use HasFactory, BelongsToCompany;

    /**
     * Priority enumeration
     */
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    public const PRIORITIES = [
        self::PRIORITY_NORMAL,
        self::PRIORITY_HIGH,
        self::PRIORITY_URGENT,
    ];

    /**
     * Status enumeration
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_ASSIGNED,
        self::STATUS_CONTACTED,
        self::STATUS_COMPLETED,
        self::STATUS_EXPIRED,
        self::STATUS_CANCELLED,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'company_id',      // ✅ Required for API contexts (Retell) without Auth
        'customer_id',
        'branch_id',
        'service_id',
        'staff_id',
        'phone_number',
        'customer_name',
        'customer_email',  // ✅ Phase 1: Email capture for callback requests
        'preferred_time_window',
        'priority',
        'status',
        'assigned_to',
        'notes',
        'metadata',
        'assigned_at',
        'contacted_at',
        'completed_at',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'preferred_time_window' => 'array',
        'metadata' => 'array',
        'assigned_at' => 'datetime',
        'contacted_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        // 'deleted_at' => 'datetime', // ❌ Removed - column doesn't exist
    ];

    /**
     * Get the customer (nullable for walk-ins).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the branch.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the service (nullable).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the preferred staff (nullable).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Get the staff member assigned to handle this callback.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_to');
    }

    /**
     * Get escalations for this callback request.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function escalations(): HasMany
    {
        return $this->hasMany(CallbackEscalation::class);
    }

    /**
     * Scope for overdue callback requests.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOverdue($query)
    {
        return $query->where('expires_at', '<', Carbon::now())
                     ->whereNotIn('status', [
                         self::STATUS_COMPLETED,
                         self::STATUS_EXPIRED,
                         self::STATUS_CANCELLED
                     ]);
    }

    /**
     * Scope for pending callback requests.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for filtering by priority.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $priority
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Assign callback to staff member.
     *
     * @param \App\Models\Staff $staff
     * @return bool
     */
    public function assign(Staff $staff): bool
    {
        $this->assigned_to = $staff->id;
        $this->status = self::STATUS_ASSIGNED;
        $this->assigned_at = Carbon::now();

        return $this->save();
    }

    /**
     * Mark callback as contacted.
     *
     * @return bool
     */
    public function markContacted(): bool
    {
        $this->status = self::STATUS_CONTACTED;
        $this->contacted_at = Carbon::now();

        return $this->save();
    }

    /**
     * Mark callback as completed.
     *
     * @return bool
     */
    public function markCompleted(): bool
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = Carbon::now();

        return $this->save();
    }

    /**
     * Escalate callback request.
     *
     * @param string $reason
     * @param string|null $escalateTo Staff ID (UUID) to escalate to
     * @return \App\Models\CallbackEscalation
     */
    public function escalate(string $reason, ?string $escalateTo = null): CallbackEscalation
    {
        return $this->escalations()->create([
            'escalation_reason' => $reason,
            'escalated_from' => $this->assigned_to,
            'escalated_to' => $escalateTo,
            'escalated_at' => Carbon::now(),
        ]);
    }

    /**
     * Check if callback is overdue.
     *
     * @return bool
     */
    public function getIsOverdueAttribute(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        if (in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_EXPIRED,
            self::STATUS_CANCELLED
        ])) {
            return false;
        }

        return $this->expires_at->lessThan(Carbon::now());
    }

    /**
     * Validate enums before saving.
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    protected static function boot()
    {
        parent::boot();

        // ✅ PHASE 3: Duplicate Detection (Prevent Spam)
        static::creating(function ($model) {
            // Check for duplicate callback requests
            // Criteria: Same phone + same status (pending/assigned) + created within last 30 minutes
            $duplicate = self::where('phone_number', $model->phone_number)
                ->whereIn('status', [self::STATUS_PENDING, self::STATUS_ASSIGNED])
                ->where('created_at', '>=', now()->subMinutes(30))
                ->first();

            if ($duplicate) {
                \Illuminate\Support\Facades\Log::warning('Duplicate callback request detected', [
                    'phone_number' => $model->phone_number,
                    'customer_name' => $model->customer_name,
                    'existing_callback_id' => $duplicate->id,
                    'existing_created_at' => $duplicate->created_at,
                ]);

                // Update existing callback instead of creating new one
                $duplicate->priority = $model->priority; // Use higher priority if urgent
                $duplicate->notes = ($duplicate->notes ? $duplicate->notes . "\n\n" : '') .
                    '**Duplicate Request**: ' . now()->format('Y-m-d H:i:s') .
                    ($model->notes ? ' - ' . $model->notes : '');
                $duplicate->save();

                // Prevent creation of new callback
                return false;
            }
        });

        static::saving(function ($model) {
            if ($model->priority && !in_array($model->priority, self::PRIORITIES)) {
                throw new \InvalidArgumentException("Invalid priority: {$model->priority}");
            }

            if ($model->status && !in_array($model->status, self::STATUSES)) {
                throw new \InvalidArgumentException("Invalid status: {$model->status}");
            }
        });

        // Invalidate caches when relevant fields change OR on create
        static::saved(function ($model) {
            // Invalidate on create (wasRecentlyCreated) OR when key fields change
            if ($model->wasRecentlyCreated ||
                $model->wasChanged('status') ||
                $model->wasChanged('priority') ||
                $model->wasChanged('expires_at') ||
                $model->wasChanged('assigned_to')) {
                \Illuminate\Support\Facades\Cache::forget('nav_badge_callbacks_pending');
                \Illuminate\Support\Facades\Cache::forget('overdue_callbacks_count');
                \Illuminate\Support\Facades\Cache::forget('callback_stats_widget');
                \Illuminate\Support\Facades\Cache::forget('callback_tabs_counts');
            }

            // ✅ PHASE 3: Webhook Dispatching
            // Dispatch webhooks for callback events (async via queue)
            try {
                // callback.created - new callback created
                if ($model->wasRecentlyCreated) {
                    \App\Services\Webhooks\CallbackWebhookService::dispatch(
                        \App\Models\WebhookConfiguration::EVENT_CALLBACK_CREATED,
                        $model
                    );
                }

                // callback.assigned - callback assigned to staff
                if ($model->wasChanged('assigned_to') && $model->assigned_to) {
                    \App\Services\Webhooks\CallbackWebhookService::dispatch(
                        \App\Models\WebhookConfiguration::EVENT_CALLBACK_ASSIGNED,
                        $model
                    );
                }

                // Status change webhooks
                if ($model->wasChanged('status')) {
                    switch ($model->status) {
                        case self::STATUS_CONTACTED:
                            \App\Services\Webhooks\CallbackWebhookService::dispatch(
                                \App\Models\WebhookConfiguration::EVENT_CALLBACK_CONTACTED,
                                $model
                            );
                            break;

                        case self::STATUS_COMPLETED:
                            \App\Services\Webhooks\CallbackWebhookService::dispatch(
                                \App\Models\WebhookConfiguration::EVENT_CALLBACK_COMPLETED,
                                $model
                            );
                            break;

                        case self::STATUS_CANCELLED:
                            \App\Services\Webhooks\CallbackWebhookService::dispatch(
                                \App\Models\WebhookConfiguration::EVENT_CALLBACK_CANCELLED,
                                $model
                            );
                            break;

                        case self::STATUS_EXPIRED:
                            \App\Services\Webhooks\CallbackWebhookService::dispatch(
                                \App\Models\WebhookConfiguration::EVENT_CALLBACK_EXPIRED,
                                $model
                            );
                            break;
                    }
                }
            } catch (\Exception $e) {
                // Non-blocking: webhook dispatch failures should not prevent callback save
                \Illuminate\Support\Facades\Log::error('[Webhook] Failed to dispatch webhook', [
                    'callback_id' => $model->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        static::deleted(function ($model) {
            \Illuminate\Support\Facades\Cache::forget('nav_badge_callbacks_pending');
            \Illuminate\Support\Facades\Cache::forget('overdue_callbacks_count');
            \Illuminate\Support\Facades\Cache::forget('callback_stats_widget');
            \Illuminate\Support\Facades\Cache::forget('callback_tabs_counts');
        });
    }
}
