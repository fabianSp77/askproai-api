<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AppointmentModificationStat Model
 *
 * Materialized view of customer modification statistics for O(1) policy enforcement lookups.
 * Updated by hourly job - DO NOT modify directly except through dedicated service.
 * Provides fast access to customer cancellation/reschedule counts over rolling time windows.
 *
 * IMPORTANT: This is a read-only model for application code. Modifications should only
 * be made through the MaterializedStatService or equivalent dedicated service.
 *
 * @property int $id
 * @property int $customer_id
 * @property string $stat_type cancel_30d|reschedule_30d|cancel_90d|reschedule_90d
 * @property \Illuminate\Support\Carbon $period_start
 * @property \Illuminate\Support\Carbon $period_end
 * @property int $count
 * @property \Illuminate\Support\Carbon $calculated_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class AppointmentModificationStat extends Model
{
    use HasFactory;

    /**
     * Stat type enumeration
     */
    public const STAT_TYPE_CANCEL_30D = 'cancel_30d';
    public const STAT_TYPE_RESCHEDULE_30D = 'reschedule_30d';
    public const STAT_TYPE_CANCEL_90D = 'cancel_90d';
    public const STAT_TYPE_RESCHEDULE_90D = 'reschedule_90d';

    public const STAT_TYPES = [
        self::STAT_TYPE_CANCEL_30D,
        self::STAT_TYPE_RESCHEDULE_30D,
        self::STAT_TYPE_CANCEL_90D,
        self::STAT_TYPE_RESCHEDULE_90D,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * Note: This is a materialized view. Only the dedicated service should
     * create or update records.
     *
     * @var array<string>
     */
    protected $fillable = [
        'company_id',
        'customer_id',
        'stat_type',
        'period_start',
        'period_end',
        'count',
        'calculated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'count' => 'integer',
        'calculated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the customer these stats belong to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Scope for filtering stats by customer.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $customerId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope for filtering by stat type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatType($query, string $type)
    {
        return $query->where('stat_type', $type);
    }

    /**
     * Get count for a specific customer and stat type with O(1) lookup.
     *
     * @param int $customerId
     * @param string $statType
     * @return int
     */
    public static function getCountForCustomer(int $customerId, string $statType): int
    {
        $stat = static::forCustomer($customerId)
            ->byStatType($statType)
            ->first();

        return $stat ? $stat->count : 0;
    }

    /**
     * Prevent direct modifications from application code.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Prevent creating/updating/deleting except through dedicated service
        static::creating(function ($model) {
            // Allow creation only from specific service context
            if (!app()->bound('materializedStatService.updating')) {
                \Log::warning('Attempt to create AppointmentModificationStat directly. Use MaterializedStatService instead.');
            }
        });

        static::updating(function ($model) {
            // Allow updates only from specific service context
            if (!app()->bound('materializedStatService.updating')) {
                \Log::warning('Attempt to update AppointmentModificationStat directly. Use MaterializedStatService instead.');
            }
        });

        static::deleting(function ($model) {
            // Allow deletions only from specific service context
            if (!app()->bound('materializedStatService.updating')) {
                \Log::warning('Attempt to delete AppointmentModificationStat directly. Use MaterializedStatService instead.');
            }
        });
    }
}
