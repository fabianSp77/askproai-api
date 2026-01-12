<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    use BelongsToCompany, HasFactory, SoftDeletes;

    /**
     * Default attribute values.
     *
     * These mirror the database defaults and are applied BEFORE
     * the saving event validation runs.
     */
    protected $attributes = [
        'priority' => 'normal',
        'urgency' => 'normal',
        'impact' => 'normal',
        'status' => 'new',
        'output_status' => 'pending',
        'source' => 'voice', // Default: phone calls from Retell AI
    ];

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
     * Enrichment status enumeration
     *
     * Part of 2-Phase Delivery-Gate Pattern:
     * - pending: Case created during call, awaiting enrichment
     * - enriched: Case enriched with transcript/audio after call ended
     * - timeout: Enrichment didn't complete within timeout, delivered with partial data
     * - skipped: Case doesn't require enrichment (e.g., non-voice source)
     */
    public const ENRICHMENT_PENDING = 'pending';

    public const ENRICHMENT_ENRICHED = 'enriched';

    public const ENRICHMENT_TIMEOUT = 'timeout';

    public const ENRICHMENT_SKIPPED = 'skipped';

    public const ENRICHMENT_STATUSES = [
        self::ENRICHMENT_PENDING,
        self::ENRICHMENT_ENRICHED,
        self::ENRICHMENT_TIMEOUT,
        self::ENRICHMENT_SKIPPED,
    ];

    /**
     * Billing status enumeration
     *
     * State machine for billing lifecycle:
     * - unbilled: Case not yet billed (initial state)
     * - billed: Case has been billed and linked to an invoice item
     * - waived: Case billing waived (e.g., support case, error)
     */
    public const BILLING_UNBILLED = 'unbilled';

    public const BILLING_BILLED = 'billed';

    public const BILLING_WAIVED = 'waived';

    public const BILLING_STATUSES = [
        self::BILLING_UNBILLED,
        self::BILLING_BILLED,
        self::BILLING_WAIVED,
    ];

    /**
     * German labels for status values
     * Single source of truth for all widgets and UI components
     */
    public const STATUS_LABELS = [
        self::STATUS_NEW => 'Neu',
        self::STATUS_OPEN => 'Offen',
        self::STATUS_PENDING => 'Wartend',
        self::STATUS_RESOLVED => 'Gelöst',
        self::STATUS_CLOSED => 'Geschlossen',
    ];

    /**
     * German labels for priority values
     * Single source of truth for all widgets and UI components
     */
    public const PRIORITY_LABELS = [
        self::PRIORITY_CRITICAL => 'Kritisch',
        self::PRIORITY_HIGH => 'Hoch',
        self::PRIORITY_NORMAL => 'Normal',
        self::PRIORITY_LOW => 'Niedrig',
    ];

    /**
     * German labels for case type values
     */
    public const TYPE_LABELS = [
        self::TYPE_INCIDENT => 'Incident',
        self::TYPE_REQUEST => 'Anfrage',
        self::TYPE_INQUIRY => 'Anfrage (allgemein)',
    ];

    /**
     * German labels for output status values
     */
    public const OUTPUT_STATUS_LABELS = [
        self::OUTPUT_PENDING => 'Ausstehend',
        self::OUTPUT_SENT => 'Gesendet',
        self::OUTPUT_FAILED => 'Fehlgeschlagen',
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
        'assigned_group_id',
        'sla_response_due_at',
        'sla_resolution_due_at',
        'sla_response_met_at',
        'output_status',
        'output_sent_at',
        'output_error',
        'audio_object_key',
        'audio_expires_at',
        // Lifecycle timestamps
        'resolved_at',
        'closed_at',
        // Enrichment fields (2-Phase Delivery-Gate)
        'enrichment_status',
        'enriched_at',
        'source',
        'retell_call_session_id',
        'transcript_segment_count',
        'transcript_char_count',
        // Billing fields
        'billing_status',
        'billed_at',
        'invoice_item_id',
        'billed_amount_cents',
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
        'sla_response_met_at' => 'datetime',
        'output_sent_at' => 'datetime',
        'audio_expires_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'enriched_at' => 'datetime',
        'billed_at' => 'datetime',
        'transcript_segment_count' => 'integer',
        'transcript_char_count' => 'integer',
        'invoice_item_id' => 'integer',
        'billed_amount_cents' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the company that owns the service case.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the call that originated this service case.
     */
    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }

    /**
     * Get the Retell call session for enrichment data.
     *
     * Links to RetellCallSession for transcript stats, function call counts,
     * and other call analytics. Populated during enrichment phase.
     */
    public function callSession(): BelongsTo
    {
        return $this->belongsTo(RetellCallSession::class, 'retell_call_session_id');
    }

    /**
     * Get the customer associated with this service case.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the category for this service case.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCaseCategory::class, 'category_id');
    }

    /**
     * Get all notes for this service case (newest first)
     *
     * @return HasMany<ServiceCaseNote>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(ServiceCaseNote::class)
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get top-level notes only (no replies, newest first)
     *
     * @return HasMany<ServiceCaseNote>
     */
    public function topLevelNotes(): HasMany
    {
        return $this->hasMany(ServiceCaseNote::class)
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get the staff member assigned to this service case.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_to');
    }

    /**
     * Get the assignment group for this service case.
     * ServiceNow-style team-based ticket assignment.
     */
    public function assignedGroup(): BelongsTo
    {
        return $this->belongsTo(AssignmentGroup::class, 'assigned_group_id');
    }

    /**
     * Get the invoice item this case was billed to.
     */
    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(AggregateInvoiceItem::class, 'invoice_item_id');
    }

    /**
     * Scope a query to only include cases with a specific status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include cases with a specific priority.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope a query to only include cases from a specific source.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Scope a query to only include cases for a specific company.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope a query to only include open cases (new, open, pending).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
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
     * @param  \Illuminate\Database\Eloquent\Builder  $query
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
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePendingOutput($query)
    {
        return $query->where('output_status', self::OUTPUT_PENDING);
    }

    /**
     * Scope a query to only include cases with failed output delivery.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailedOutput($query)
    {
        return $query->where('output_status', self::OUTPUT_FAILED);
    }

    /**
     * Scope a query to only include cases pending enrichment.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePendingEnrichment($query)
    {
        return $query->where('enrichment_status', self::ENRICHMENT_PENDING);
    }

    /**
     * Scope a query to only include enriched cases.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnriched($query)
    {
        return $query->where('enrichment_status', self::ENRICHMENT_ENRICHED);
    }

    /**
     * Scope a query to only include unbilled cases.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnbilled($query)
    {
        return $query->where('billing_status', self::BILLING_UNBILLED);
    }

    /**
     * Scope a query to only include billed cases.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBilled($query)
    {
        return $query->where('billing_status', self::BILLING_BILLED);
    }

    /**
     * Scope a query to only include waived cases.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWaived($query)
    {
        return $query->where('billing_status', self::BILLING_WAIVED);
    }

    /**
     * Check if the case is overdue for SLA response.
     */
    public function isResponseOverdue(): bool
    {
        return $this->sla_response_due_at && now()->isAfter($this->sla_response_due_at);
    }

    /**
     * Check if first response SLA was met.
     *
     * @return bool|null True if met, false if breached, null if not yet responded
     */
    public function isResponseSlaMet(): ?bool
    {
        if (! $this->sla_response_met_at) {
            return null; // Not yet responded
        }

        if (! $this->sla_response_due_at) {
            return true; // No SLA defined, consider as met
        }

        return $this->sla_response_met_at->lte($this->sla_response_due_at);
    }

    /**
     * Mark the first response as made (for SLA tracking).
     */
    public function markFirstResponse(): void
    {
        if (! $this->sla_response_met_at) {
            $this->update([
                'sla_response_met_at' => now(),
            ]);
        }
    }

    /**
     * Check if the case is overdue for SLA resolution.
     */
    public function isResolutionOverdue(): bool
    {
        return $this->sla_resolution_due_at && now()->isAfter($this->sla_resolution_due_at);
    }

    /**
     * Calculate and set SLA due dates based on category configuration.
     *
     * This method is called by the ServiceCaseObserver during case creation
     * and category changes. It MUST be wrapped in try-catch in the Observer
     * to ensure SLA failures never block case operations.
     *
     * BEHAVIOR:
     * - Skips calculation if company has sla_tracking_enabled = false
     * - Skips if category has no SLA configuration
     * - Sets sla_response_due_at and sla_resolution_due_at based on category hours
     */
    public function calculateSlaDueDates(): void
    {
        try {
            // Guard: Skip if company has SLA tracking disabled (pass-through mode)
            if (! $this->company?->sla_tracking_enabled) {
                \Log::debug('[ServiceCase] SLA calculation skipped: Company has SLA tracking disabled', [
                    'company_id' => $this->company_id,
                ]);

                return;
            }

            // Guard: Skip if no category assigned
            if (! $this->category) {
                \Log::debug('[ServiceCase] SLA calculation skipped: No category assigned', [
                    'case_id' => $this->id,
                ]);

                return;
            }

            // Use created_at as base time, fall back to now() for new cases
            $baseTime = $this->created_at ?? now();

            // Calculate response SLA
            if ($this->category->sla_response_hours) {
                $this->sla_response_due_at = $baseTime->copy()->addHours($this->category->sla_response_hours);
            }

            // Calculate resolution SLA
            if ($this->category->sla_resolution_hours) {
                $this->sla_resolution_due_at = $baseTime->copy()->addHours($this->category->sla_resolution_hours);
            }

            \Log::debug('[ServiceCase] SLA due dates calculated', [
                'company_id' => $this->company_id,
                'category_id' => $this->category_id,
                'response_hours' => $this->category->sla_response_hours,
                'resolution_hours' => $this->category->sla_resolution_hours,
                'sla_response_due_at' => $this->sla_response_due_at,
                'sla_resolution_due_at' => $this->sla_resolution_due_at,
            ]);
        } catch (\Exception $e) {
            // CRITICAL: SLA errors must NEVER block case creation!
            // Log the error but allow the case to be created without SLA dates
            \Log::error('[ServiceCase] SLA calculation failed - case creation continues', [
                'company_id' => $this->company_id,
                'category_id' => $this->category_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if the case is open (not resolved or closed).
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
     */
    public function markOutputAsFailed(string $error): void
    {
        $this->update([
            'output_status' => self::OUTPUT_FAILED,
            'output_error' => $error,
        ]);
    }

    /**
     * Check if the case is pending enrichment.
     */
    public function isPendingEnrichment(): bool
    {
        return $this->enrichment_status === self::ENRICHMENT_PENDING;
    }

    /**
     * Check if the case has been enriched.
     */
    public function isEnriched(): bool
    {
        return $this->enrichment_status === self::ENRICHMENT_ENRICHED;
    }

    /**
     * Mark the case as enriched with transcript/audio data.
     *
     * @param  RetellCallSession|null  $session  Optional session to link
     */
    public function markAsEnriched(?RetellCallSession $session = null): void
    {
        $updateData = [
            'enrichment_status' => self::ENRICHMENT_ENRICHED,
            'enriched_at' => now(),
        ];

        if ($session) {
            $updateData['retell_call_session_id'] = $session->id;
            $updateData['transcript_segment_count'] = $session->transcript_segment_count;
            // Calculate char count from transcript segments if available
            $updateData['transcript_char_count'] = $session->transcriptSegments()
                ->sum(\Illuminate\Support\Facades\DB::raw('LENGTH(text)'));
        }

        $this->update($updateData);
    }

    /**
     * Mark the case enrichment as timed out.
     */
    public function markEnrichmentTimeout(): void
    {
        $this->update([
            'enrichment_status' => self::ENRICHMENT_TIMEOUT,
        ]);
    }

    /**
     * Mark the case as billed and link to invoice item.
     *
     * State Guard: Can only bill unbilled cases.
     *
     * @param  int  $invoiceItemId  The aggregate invoice item ID
     * @param  int  $amountCents  The amount billed in cents
     *
     * @throws \LogicException if billing_status is not 'unbilled'
     */
    public function markAsBilled(int $invoiceItemId, int $amountCents): void
    {
        if ($this->billing_status !== self::BILLING_UNBILLED) {
            throw new \LogicException(
                "Cannot bill ServiceCase {$this->id}: Current billing_status is '{$this->billing_status}', expected 'unbilled'"
            );
        }

        $this->update([
            'billing_status' => self::BILLING_BILLED,
            'billed_at' => now(),
            'invoice_item_id' => $invoiceItemId,
            'billed_amount_cents' => $amountCents,
        ]);
    }

    /**
     * Mark the case billing as waived.
     *
     * State Guard: Cannot waive already-billed cases.
     *
     * @param  string  $reason  Reason for waiving (logged in metadata)
     *
     * @throws \LogicException if billing_status is 'billed'
     */
    public function markAsWaived(string $reason): void
    {
        if ($this->billing_status === self::BILLING_BILLED) {
            throw new \LogicException(
                "Cannot waive ServiceCase {$this->id}: Case is already billed (invoice_item_id: {$this->invoice_item_id})"
            );
        }

        $this->update([
            'billing_status' => self::BILLING_WAIVED,
            // Store waiver reason in ai_metadata (non-destructive)
            'ai_metadata' => array_merge($this->ai_metadata ?? [], [
                'billing_waived_at' => now()->toISOString(),
                'billing_waived_reason' => $reason,
            ]),
        ]);
    }

    /**
     * Check if the case is billable (unbilled status).
     *
     * @return bool True if case can be billed
     */
    public function isBillable(): bool
    {
        return $this->billing_status === self::BILLING_UNBILLED;
    }

    /**
     * Get formatted ticket ID (TKT-YYYY-NNNNN format).
     *
     * Example: TKT-2025-00042
     */
    public function getFormattedIdAttribute(): string
    {
        $year = $this->created_at?->format('Y') ?? date('Y');

        return sprintf('TKT-%s-%05d', $year, $this->id);
    }

    /**
     * Get human-readable source label (German).
     *
     * Example: 'voice' → 'Telefonanruf'
     */
    public function getSourceLabelAttribute(): string
    {
        return \App\Constants\ServiceGatewayConstants::getSourceLabel($this->source);
    }

    /**
     * Get human-readable case type label (German).
     *
     * Example: 'incident' → 'Incident'
     */
    public function getCaseTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->case_type] ?? $this->case_type ?? 'Unbekannt';
    }

    /**
     * Get ServiceNow-compatible contact_type from source.
     *
     * This maps our internal source values to ServiceNow's contact_type field:
     * - voice → phone
     * - email → email
     * - web → self-service
     * - chat → virtual_agent
     * etc.
     */
    public function getServiceNowContactTypeAttribute(): string
    {
        return \App\Constants\ServiceGatewayConstants::getServiceNowContactType($this->source);
    }

    /**
     * Get source icon for UI display.
     */
    public function getSourceIconAttribute(): string
    {
        return \App\Constants\ServiceGatewayConstants::SOURCE_ICONS[$this->source] ?? '📋';
    }

    /**
     * Get source color for badge styling.
     */
    public function getSourceColorAttribute(): string
    {
        return \App\Constants\ServiceGatewayConstants::SOURCE_COLORS[$this->source] ?? 'gray';
    }

    /**
     * Boot the model - Validation and defaults
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Validate case_type
            if (! in_array($model->case_type, self::CASE_TYPES)) {
                throw new \InvalidArgumentException("Invalid case type: {$model->case_type}");
            }

            // Validate priority
            if (! in_array($model->priority, self::PRIORITIES)) {
                throw new \InvalidArgumentException("Invalid priority: {$model->priority}");
            }

            // Validate status
            if (! in_array($model->status, self::STATUSES)) {
                throw new \InvalidArgumentException("Invalid status: {$model->status}");
            }

            // Validate output_status
            if (! in_array($model->output_status, self::OUTPUT_STATUSES)) {
                throw new \InvalidArgumentException("Invalid output status: {$model->output_status}");
            }

            // Validate billing_status
            if ($model->billing_status && ! in_array($model->billing_status, self::BILLING_STATUSES)) {
                throw new \InvalidArgumentException("Invalid billing status: {$model->billing_status}");
            }

            // Enforce billing state machine transitions (Security: prevent bypass via direct update)
            if ($model->isDirty('billing_status') && $model->exists) {
                $original = $model->getOriginal('billing_status');
                $new = $model->billing_status;

                // Define valid transitions
                $validTransitions = [
                    self::BILLING_UNBILLED => [self::BILLING_BILLED, self::BILLING_WAIVED],
                    self::BILLING_BILLED => [], // No transitions allowed from billed
                    self::BILLING_WAIVED => [], // No transitions allowed from waived
                ];

                if (isset($validTransitions[$original]) && ! in_array($new, $validTransitions[$original])) {
                    throw new \DomainException(
                        "Invalid billing_status transition from '{$original}' to '{$new}' for ServiceCase {$model->id}"
                    );
                }
            }
        });
    }
}
