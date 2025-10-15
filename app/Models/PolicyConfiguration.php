<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PolicyConfiguration Model
 *
 * Manages flexible policy configurations for cancellation, reschedule, and recurring appointments.
 * Supports polymorphic relationships with Company, Branch, Service, and Staff entities.
 * Implements hierarchical override system for policy inheritance.
 * Multi-tenant isolation via BelongsToCompany trait.
 *
 * @property int $id
 * @property string $configurable_type Company|Branch|Service|Staff
 * @property int $configurable_id
 * @property string $policy_type cancellation|reschedule|recurring
 * @property array $config Policy configuration data
 * @property bool $is_override Whether this policy overrides a parent
 * @property int|null $overrides_id Parent policy ID if override
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class PolicyConfiguration extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    /**
     * Policy type enumeration
     */
    public const POLICY_TYPE_CANCELLATION = 'cancellation';
    public const POLICY_TYPE_RESCHEDULE = 'reschedule';
    public const POLICY_TYPE_RECURRING = 'recurring';

    public const POLICY_TYPES = [
        self::POLICY_TYPE_CANCELLATION,
        self::POLICY_TYPE_RESCHEDULE,
        self::POLICY_TYPE_RECURRING,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'company_id',
        'configurable_type',
        'configurable_id',
        'policy_type',
        'config',
        'is_override',
        'overrides_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'config' => 'array',
        'is_override' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the owning configurable model (Company|Branch|Service|Staff).
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function configurable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the parent policy that this policy overrides.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function overrides(): BelongsTo
    {
        return $this->belongsTo(PolicyConfiguration::class, 'overrides_id');
    }

    /**
     * Get policies that override this policy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function overriddenBy(): HasMany
    {
        return $this->hasMany(PolicyConfiguration::class, 'overrides_id');
    }

    /**
     * Get the effective configuration by traversing the hierarchy.
     *
     * If this policy is not an override, returns its config.
     * Otherwise, merges parent config with this config, with this taking precedence.
     *
     * @return array
     */
    public function getEffectiveConfig(): array
    {
        if (!$this->is_override || !$this->overrides_id) {
            return $this->config ?? [];
        }

        $parentPolicy = $this->overrides;
        if (!$parentPolicy) {
            return $this->config ?? [];
        }

        // Recursively get parent's effective config
        $parentConfig = $parentPolicy->getEffectiveConfig();

        // Merge with current config, current takes precedence
        return array_merge($parentConfig, $this->config ?? []);
    }

    /**
     * Scope for filtering by entity (configurable).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $entity Model instance
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForEntity($query, $entity)
    {
        return $query->where('configurable_type', get_class($entity))
                     ->where('configurable_id', $entity->id);
    }

    /**
     * Scope for filtering by policy type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type cancellation|reschedule|recurring
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('policy_type', $type);
    }

    /**
     * Validate policy type before saving.
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if (!in_array($model->policy_type, self::POLICY_TYPES)) {
                throw new \InvalidArgumentException("Invalid policy type: {$model->policy_type}");
            }
        });
    }
}
