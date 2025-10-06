<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Carbon\Carbon;

/**
 * AppointmentModification Model
 *
 * Tracks all appointment cancellations and reschedules for policy compliance tracking.
 * Records who made the change, when, and whether it was within policy guidelines.
 * Essential for calculating customer modification statistics and fee enforcement.
 * Multi-tenant isolation via BelongsToCompany trait.
 *
 * @property int $id
 * @property int $appointment_id
 * @property int $customer_id
 * @property string $modification_type cancel|reschedule
 * @property bool $within_policy
 * @property float $fee_charged
 * @property string|null $reason
 * @property string $modified_by_type User|Staff|Customer|System
 * @property int $modified_by_id
 * @property array $metadata
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class AppointmentModification extends Model
{
    use HasFactory, BelongsToCompany;

    /**
     * Modification type enumeration
     */
    public const TYPE_CANCEL = 'cancel';
    public const TYPE_RESCHEDULE = 'reschedule';

    public const MODIFICATION_TYPES = [
        self::TYPE_CANCEL,
        self::TYPE_RESCHEDULE,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'appointment_id',
        'customer_id',
        'company_id',  // ← ADDED: Required for API webhooks (Retell, Cal.com) where Auth::check() is false
        'modification_type',
        'within_policy',
        'fee_charged',
        'reason',
        'modified_by_type',
        'modified_by_id',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'within_policy' => 'boolean',
        'fee_charged' => 'decimal:2',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the appointment that was modified.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the customer who owns the appointment.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the entity that made the modification (polymorphic).
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function modifiedBy(): MorphTo
    {
        return $this->morphTo('modified_by');
    }

    /**
     * Scope for modifications within a specific timeframe.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days Number of days to look back (default 30)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithinTimeframe($query, int $days = 30)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    /**
     * Scope for filtering by modification type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type cancel|reschedule
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('modification_type', $type);
    }

    /**
     * Check if the modification is recent (within 30 days).
     *
     * @return bool
     */
    public function getIsRecentAttribute(): bool
    {
        return $this->created_at->greaterThanOrEqualTo(Carbon::now()->subDays(30));
    }

    /**
     * Validate modification type before saving.
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if (!in_array($model->modification_type, self::MODIFICATION_TYPES)) {
                throw new \InvalidArgumentException("Invalid modification type: {$model->modification_type}");
            }
        });
    }
}
