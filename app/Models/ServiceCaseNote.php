<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

/**
 * ServiceCaseNote - Threaded notes for ServiceCases
 *
 * Multi-tenancy: Inherited via ServiceCase relationship (NO company_id)
 * Threading: Self-referential via parent_id (adjacency list pattern)
 *
 * @property int $id
 * @property int $service_case_id
 * @property int $user_id
 * @property int|null $parent_id
 * @property string $content
 * @property bool $is_internal
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read ServiceCase $serviceCase
 * @property-read User $user
 * @property-read ServiceCaseNote|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<ServiceCaseNote> $replies
 */
class ServiceCaseNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'service_case_id',
        'user_id',
        'parent_id',
        'content',
        'is_internal',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    // ========================================
    // Relationships
    // ========================================

    public function serviceCase(): BelongsTo
    {
        return $this->belongsTo(ServiceCase::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ServiceCaseNote::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ServiceCaseNote::class, 'parent_id')
            ->orderBy('created_at', 'asc');
    }

    // ========================================
    // Scopes
    // ========================================

    /**
     * Only top-level notes (no parent)
     */
    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Only internal notes
     */
    public function scopeInternal(Builder $query): Builder
    {
        return $query->where('is_internal', true);
    }

    /**
     * Only external (visible) notes
     */
    public function scopeExternal(Builder $query): Builder
    {
        return $query->where('is_internal', false);
    }

    /**
     * For a specific service case
     */
    public function scopeForCase(Builder $query, int $serviceCaseId): Builder
    {
        return $query->where('service_case_id', $serviceCaseId);
    }

    // ========================================
    // Accessors
    // ========================================

    /**
     * Get formatted content (supports basic markdown)
     */
    public function getFormattedContentAttribute(): string
    {
        return nl2br(e($this->content));
    }

    /**
     * Check if this note has replies
     * Optimized: Uses loaded relation if available to avoid extra query
     */
    public function getHasRepliesAttribute(): bool
    {
        if ($this->relationLoaded('replies')) {
            return $this->replies->isNotEmpty();
        }
        return $this->replies()->exists();
    }

    /**
     * Get the nesting depth (0 = top level)
     */
    public function getDepthAttribute(): int
    {
        $depth = 0;
        $note = $this;

        while ($note->parent_id) {
            $depth++;
            $note = $note->parent;
            if ($depth > 10) break; // Safety limit
        }

        return $depth;
    }
}
