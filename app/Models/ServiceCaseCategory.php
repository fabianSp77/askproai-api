<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ServiceCaseCategory Model
 *
 * Hierarchical category system for service cases.
 * Supports AI intent matching via keyword patterns.
 * Multi-tenant isolation via BelongsToCompany trait.
 *
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string $slug
 * @property int|null $parent_id
 * @property array|null $intent_keywords
 * @property float $confidence_threshold
 * @property string|null $default_case_type incident|request|inquiry
 * @property string|null $default_priority critical|high|normal|low
 * @property int|null $output_configuration_id
 * @property bool $is_active
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class ServiceCaseCategory extends Model
{
    use HasFactory, BelongsToCompany;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'company_id', // Required for API webhooks and tests where Auth::check() is false
        'name',
        'slug',
        'parent_id',
        'intent_keywords',
        'confidence_threshold',
        'default_case_type',
        'default_priority',
        'sla_response_hours',
        'sla_resolution_hours',
        'output_configuration_id',
        'is_active',
        'is_default',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'intent_keywords' => 'array',
        'confidence_threshold' => 'decimal:2',
        'sla_response_hours' => 'integer',
        'sla_resolution_hours' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the company that owns the category.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the parent category.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ServiceCaseCategory::class, 'parent_id');
    }

    /**
     * Get the child categories.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(ServiceCaseCategory::class, 'parent_id');
    }

    /**
     * Get active child categories.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activeChildren(): HasMany
    {
        return $this->children()->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Get the service cases in this category.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cases(): HasMany
    {
        return $this->hasMany(ServiceCase::class, 'category_id');
    }

    /**
     * Get the output configuration for this category.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function outputConfiguration(): BelongsTo
    {
        return $this->belongsTo(ServiceOutputConfiguration::class, 'output_configuration_id');
    }

    /**
     * Scope a query to only include active categories.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include root categories (no parent).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope a query to order by sort_order.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Check if this category has children.
     *
     * @return bool
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Check if this category is a root category.
     *
     * @return bool
     */
    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Match intent against this category's keywords.
     *
     * @param string $intent
     * @return float Confidence score (0.0 - 1.0)
     */
    public function matchIntent(string $intent): float
    {
        if (empty($this->intent_keywords)) {
            return 0.0;
        }

        $intent = strtolower($intent);
        $matches = 0;
        $totalKeywords = count($this->intent_keywords);

        foreach ($this->intent_keywords as $keyword) {
            if (str_contains($intent, strtolower($keyword))) {
                $matches++;
            }
        }

        return $totalKeywords > 0 ? ($matches / $totalKeywords) : 0.0;
    }

    /**
     * Check if intent matches this category above threshold.
     *
     * @param string $intent
     * @return bool
     */
    public function matchesIntent(string $intent): bool
    {
        $confidence = $this->matchIntent($intent);
        return $confidence >= $this->confidence_threshold;
    }

    /**
     * Boot the model - Slug validation
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Auto-generate slug from name if not provided
            if (empty($model->slug)) {
                $model->slug = \Illuminate\Support\Str::slug($model->name);
            }

            // Validate case_type if provided
            if ($model->default_case_type && !in_array($model->default_case_type, ServiceCase::CASE_TYPES)) {
                throw new \InvalidArgumentException("Invalid default case type: {$model->default_case_type}");
            }

            // Validate priority if provided
            if ($model->default_priority && !in_array($model->default_priority, ServiceCase::PRIORITIES)) {
                throw new \InvalidArgumentException("Invalid default priority: {$model->default_priority}");
            }

            // Validate parent_id doesn't create circular reference
            // Note: During creation, both parent_id and id are null, so we must check that parent_id is set
            if ($model->parent_id !== null && $model->parent_id === $model->id) {
                throw new \InvalidArgumentException("Category cannot be its own parent");
            }
        });
    }
}
