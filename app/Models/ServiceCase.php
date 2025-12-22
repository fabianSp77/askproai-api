<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ServiceCase Model
 *
 * Represents a service desk case created from AI voice interactions.
 * Supports incident management, service requests, and general inquiries.
 * Multi-tenant isolation via BelongsToCompany trait.
 *
 * @property int $id
 * @property int $company_id
 * @property int|null $call_id
 * @property int|null $customer_id
 * @property int $category_id
 * @property string $case_type incident|request|inquiry
 * @property string $priority critical|high|normal|low
 * @property string $urgency critical|high|normal|low
 * @property string $impact critical|high|normal|low
 * @property string $subject
 * @property string $description
 * @property array|null $structured_data
 * @property array|null $ai_metadata
 * @property string $status new|open|pending|resolved|closed
 * @property string|null $external_reference
 * @property int|null $assigned_to
 * @property \Illuminate\Support\Carbon|null $sla_response_due_at
 * @property \Illuminate\Support\Carbon|null $sla_resolution_due_at
 * @property string $output_status pending|sent|failed
 * @property \Illuminate\Support\Carbon|null $output_sent_at
 * @property string|null $output_error
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class ServiceCase extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    /**
     * Case type enumeration
     */
    public const TYPE_INCIDENT = 'incident';
    public const TYPE_REQUEST = 'request';
    public const TYPE_INQUIRY = 'inquiry';

    public const CASE_TYPES = [
        self::TYPE_INCIDENT,
        self::TYPE_REQUEST,
        self::TYPE_INQUIRY,
    ];

    /**
     * Priority enumeration
     */
    public const PRIORITY_CRITICAL = 'critical';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_LOW = 'low';

    public const PRIORITIES = [
        self::PRIORITY_CRITICAL,
        self::PRIORITY_HIGH,
        self::PRIORITY_NORMAL,
        self::PRIORITY_LOW,
    ];

    /**
     * Status enumeration
     */
    public const STATUS_NEW = 'new';
    public const STATUS_OPEN = 'open';
    public const STATUS_PENDING = 'pending';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    public const STATUSES = [
        self::STATUS_NEW,
        self::STATUS_OPEN,
        self::STATUS_PENDING,
        self::STATUS_RESOLVED,
        self::STATUS_CLOSED,
    ];

    /**
     * Output status enumeration
     */
    public const OUTPUT_PENDING = 'pending';
    public const OUTPUT_SENT = 'sent';
    public const OUTPUT_FAILED = 'failed';

    public const OUTPUT_STATUSES = [
        self::OUTPUT_PENDING,
        self::OUTPUT_SENT,
        self::OUTPUT_FAILED,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'company_id', // Required for API webhooks and tests where Auth::check() is false
        'call_id',
        'customer_id',
        'category_id',
        'case_type',
        'priority',
        'urgency',
        'impact',
        'subject',
        'description',
        'structured_data',
        'ai_metadata',
        'status',
        'external_reference',
        'assigned_to',
        'sla_response_due_at',
        'sla_resolution_due_at',
        'output_status',
        'output_sent_at',
        'output_error',
        'audio_object_key',
        'audio_expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'structured_data' => 'array',
        'ai_metadata' => 'array',
        'sla_response_due_at' => 'datetime',
        'sla_resolution_due_at' => 'datetime',
        'output_sent_at' => 'datetime',
        'audio_expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the company that owns the service case.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the call that originated this service case.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }

    /**
     * Get the customer associated with this service case.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the category for this service case.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCaseCategory::class, 'category_id');
    }

    /**
     * Get the staff member assigned to this service case.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_to');
    }

    /**
     * Scope a query to only include cases with a specific status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include cases with a specific priority.
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
     * Scope a query to only include cases for a specific company.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $companyId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope a query to only include open cases (new, open, pending).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', [
            self::STATUS_NEW,
            self::STATUS_OPEN,
            self::STATUS_PENDING,
        ]);
    }

    /**
     * Scope a query to only include closed cases (resolved, closed).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeClosed($query)
    {
        return $query->whereIn('status', [
            self::STATUS_RESOLVED,
            self::STATUS_CLOSED,
        ]);
    }

    /**
     * Scope a query to only include cases with pending output delivery.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePendingOutput($query)
    {
        return $query->where('output_status', self::OUTPUT_PENDING);
    }

    /**
     * Scope a query to only include cases with failed output delivery.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailedOutput($query)
    {
        return $query->where('output_status', self::OUTPUT_FAILED);
    }

    /**
     * Check if the case is overdue for SLA response.
     *
     * @return bool
     */
    public function isResponseOverdue(): bool
    {
        return $this->sla_response_due_at && now()->isAfter($this->sla_response_due_at);
    }

    /**
     * Check if the case is overdue for SLA resolution.
     *
     * @return bool
     */
    public function isResolutionOverdue(): bool
    {
        return $this->sla_resolution_due_at && now()->isAfter($this->sla_resolution_due_at);
    }

    /**
     * Check if the case is open (not resolved or closed).
     *
     * @return bool
     */
    public function isOpen(): bool
    {
        return in_array($this->status, [
            self::STATUS_NEW,
            self::STATUS_OPEN,
            self::STATUS_PENDING,
        ]);
    }

    /**
     * Check if the case is closed (resolved or closed).
     *
     * @return bool
     */
    public function isClosed(): bool
    {
        return in_array($this->status, [
            self::STATUS_RESOLVED,
            self::STATUS_CLOSED,
        ]);
    }

    /**
     * Mark the case output as sent.
     *
     * @return void
     */
    public function markOutputAsSent(): void
    {
        $this->update([
            'output_status' => self::OUTPUT_SENT,
            'output_sent_at' => now(),
            'output_error' => null,
        ]);
    }

    /**
     * Mark the case output as failed.
     *
     * @param string $error
     * @return void
     */
    public function markOutputAsFailed(string $error): void
    {
        $this->update([
            'output_status' => self::OUTPUT_FAILED,
            'output_error' => $error,
        ]);
    }

    /**
     * Get formatted ticket ID (TKT-YYYY-NNNNN format).
     *
     * Example: TKT-2025-00042
     *
     * @return string
     */
    public function getFormattedIdAttribute(): string
    {
        $year = $this->created_at?->format('Y') ?? date('Y');
        return sprintf('TKT-%s-%05d', $year, $this->id);
    }

    /**
     * Boot the model - Validation and defaults
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Validate case_type
            if (!in_array($model->case_type, self::CASE_TYPES)) {
                throw new \InvalidArgumentException("Invalid case type: {$model->case_type}");
            }

            // Validate priority
            if (!in_array($model->priority, self::PRIORITIES)) {
                throw new \InvalidArgumentException("Invalid priority: {$model->priority}");
            }

            // Validate status
            if (!in_array($model->status, self::STATUSES)) {
                throw new \InvalidArgumentException("Invalid status: {$model->status}");
            }

            // Validate output_status
            if (!in_array($model->output_status, self::OUTPUT_STATUSES)) {
                throw new \InvalidArgumentException("Invalid output status: {$model->output_status}");
            }
        });
    }
}
