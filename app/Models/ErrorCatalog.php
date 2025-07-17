<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ErrorCatalog extends Model
{
    use HasFactory;

    protected $table = 'error_catalog';

    protected $fillable = [
        'error_code',
        'category',
        'service',
        'title',
        'description',
        'symptoms',
        'stack_pattern',
        'root_causes',
        'severity',
        'is_active',
        'auto_detectable',
        'occurrence_count',
        'last_occurred_at',
        'avg_resolution_time',
    ];

    protected $casts = [
        'root_causes' => 'array',
        'is_active' => 'boolean',
        'auto_detectable' => 'boolean',
        'last_occurred_at' => 'datetime',
        'occurrence_count' => 'integer',
        'avg_resolution_time' => 'float',
    ];

    /**
     * Get the solutions for the error.
     */
    public function solutions(): HasMany
    {
        return $this->hasMany(ErrorSolution::class)->orderBy('order');
    }

    /**
     * Get the prevention tips for the error.
     */
    public function preventionTips(): HasMany
    {
        return $this->hasMany(ErrorPreventionTip::class)->orderBy('order');
    }

    /**
     * Get the occurrences for the error.
     */
    public function occurrences(): HasMany
    {
        return $this->hasMany(ErrorOccurrence::class);
    }

    /**
     * Get the tags for the error.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ErrorTag::class, 'error_tag_assignments');
    }

    /**
     * Get errors related to this error.
     */
    public function relatedErrors(): BelongsToMany
    {
        return $this->belongsToMany(
            ErrorCatalog::class,
            'error_relationships',
            'error_id',
            'related_error_id'
        )->withPivot('relationship_type', 'relevance_score')
          ->orderByPivot('relevance_score', 'desc');
    }

    /**
     * Get errors that relate to this error.
     */
    public function relatedByErrors(): BelongsToMany
    {
        return $this->belongsToMany(
            ErrorCatalog::class,
            'error_relationships',
            'related_error_id',
            'error_id'
        )->withPivot('relationship_type', 'relevance_score');
    }

    /**
     * Increment occurrence count and update last occurred timestamp.
     */
    public function recordOccurrence(): void
    {
        $this->increment('occurrence_count');
        $this->update(['last_occurred_at' => now()]);
    }

    /**
     * Calculate and update average resolution time based on resolved occurrences.
     */
    public function updateAverageResolutionTime(): void
    {
        $avgTime = $this->occurrences()
            ->whereNotNull('resolution_time')
            ->avg('resolution_time');
        
        if ($avgTime) {
            $this->update(['avg_resolution_time' => $avgTime / 60]); // Convert seconds to minutes
        }
    }

    /**
     * Check if error pattern matches given text.
     */
    public function matchesPattern(string $text): bool
    {
        if (!$this->stack_pattern || !$this->auto_detectable) {
            return false;
        }

        return (bool) preg_match('/' . $this->stack_pattern . '/i', $text);
    }

    /**
     * Scope for active errors.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for critical errors.
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    /**
     * Scope for errors by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for errors by service.
     */
    public function scopeByService($query, string $service)
    {
        return $query->where('service', $service);
    }

    /**
     * Scope for frequently occurring errors.
     */
    public function scopeFrequent($query, int $minOccurrences = 10)
    {
        return $query->where('occurrence_count', '>=', $minOccurrences)
                     ->orderBy('occurrence_count', 'desc');
    }

    /**
     * Scope for recently occurred errors.
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('last_occurred_at', '>=', now()->subDays($days))
                     ->orderBy('last_occurred_at', 'desc');
    }

    /**
     * Search errors by text.
     */
    public function scopeSearch($query, string $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('error_code', 'like', "%{$searchTerm}%")
              ->orWhere('title', 'like', "%{$searchTerm}%")
              ->orWhere('description', 'like', "%{$searchTerm}%")
              ->orWhere('symptoms', 'like', "%{$searchTerm}%");
        });
    }

    /**
     * Get the most effective solution based on success rate.
     */
    public function getMostEffectiveSolution()
    {
        return $this->solutions()
            ->where('success_rate', '>', 0)
            ->orderBy('success_rate', 'desc')
            ->first();
    }

    /**
     * Get similar errors based on tags and category.
     */
    public function getSimilarErrors(int $limit = 5)
    {
        $tagIds = $this->tags->pluck('id');
        
        return static::where('id', '!=', $this->id)
            ->where(function ($query) use ($tagIds) {
                $query->where('category', $this->category)
                      ->orWhereHas('tags', function ($q) use ($tagIds) {
                          $q->whereIn('error_tag_id', $tagIds);
                      });
            })
            ->withCount(['tags' => function ($q) use ($tagIds) {
                $q->whereIn('error_tag_id', $tagIds);
            }])
            ->orderBy('tags_count', 'desc')
            ->limit($limit)
            ->get();
    }
}