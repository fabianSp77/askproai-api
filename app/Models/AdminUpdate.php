<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * AdminUpdate Model
 *
 * Stores system updates, bugfixes, improvements for Super-Admin visibility
 * Only accessible to Super-Admin users
 *
 * @property int $id
 * @property string $title
 * @property string $description
 * @property string $content
 * @property string $category (bugfix|improvement|feature|general)
 * @property string $priority (low|medium|high|critical)
 * @property string $status (draft|published|archived)
 * @property array $attachments
 * @property array $code_snippets
 * @property string $related_files
 * @property string $related_issue
 * @property array $action_items
 * @property int $created_by
 * @property string $changelog
 * @property bool $is_public
 * @property \Carbon\Carbon $published_at
 * @property \Carbon\Carbon $archived_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 */
class AdminUpdate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'content',
        'category',
        'priority',
        'status',
        'attachments',
        'code_snippets',
        'related_files',
        'related_issue',
        'action_items',
        'created_by',
        'changelog',
        'is_public',
        'published_at',
        'archived_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'code_snippets' => 'array',
        'action_items' => 'array',
        'is_public' => 'boolean',
        'published_at' => 'date',
        'archived_at' => 'date',
    ];

    protected $dates = [
        'published_at',
        'archived_at',
    ];

    /**
     * Get the user who created this update
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope: Only published updates
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')->whereNotNull('published_at');
    }

    /**
     * Scope: By category
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: By priority
     */
    public function scopePriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Get badge color for priority
     */
    public function getPriorityColor(): string
    {
        return match($this->priority) {
            'critical' => 'danger',
            'high' => 'warning',
            'medium' => 'info',
            default => 'secondary',
        };
    }

    /**
     * Get badge color for category
     */
    public function getCategoryColor(): string
    {
        return match($this->category) {
            'bugfix' => 'danger',
            'improvement' => 'success',
            'feature' => 'primary',
            default => 'secondary',
        };
    }

    /**
     * Get display name for priority
     */
    public function getPriorityLabel(): string
    {
        return match($this->priority) {
            'critical' => '🔴 Kritisch',
            'high' => '🟠 Hoch',
            'medium' => '🟡 Mittel',
            default => '🟢 Niedrig',
        };
    }

    /**
     * Get display name for category
     */
    public function getCategoryLabel(): string
    {
        return match($this->category) {
            'bugfix' => '🐛 Bugfix',
            'improvement' => '⚡ Verbesserung',
            'feature' => '✨ Feature',
            default => '📋 Allgemein',
        };
    }
}
