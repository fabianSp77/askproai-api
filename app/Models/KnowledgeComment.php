<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'parent_id',
        'user_id',
        'content',
        'status',
        'position',
    ];

    protected $casts = [
        'position' => 'array',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_DELETED = 'deleted';

    /**
     * Get the document this comment belongs to
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDocument::class, 'document_id');
    }

    /**
     * Get the parent comment (for replies)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(KnowledgeComment::class, 'parent_id');
    }

    /**
     * Get child comments (replies)
     */
    public function replies(): HasMany
    {
        return $this->hasMany(KnowledgeComment::class, 'parent_id');
    }

    /**
     * Get the user who created the comment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope for active comments
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for resolved comments
     */
    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    /**
     * Scope for root comments (no parent)
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Mark comment as resolved
     */
    public function resolve(): void
    {
        $this->update(['status' => self::STATUS_RESOLVED]);
        
        // Also resolve all replies
        $this->replies()->update(['status' => self::STATUS_RESOLVED]);
    }

    /**
     * Soft delete the comment
     */
    public function softDelete(): void
    {
        $this->update([
            'status' => self::STATUS_DELETED,
            'content' => '[Comment deleted]',
        ]);
    }

    /**
     * Check if comment is inline (has position)
     */
    public function isInline(): bool
    {
        return !empty($this->position);
    }

    /**
     * Get the thread (all comments in the conversation)
     */
    public function getThread(): array
    {
        $thread = [];
        
        // Get root comment
        $root = $this->parent_id ? $this->parent->getRoot() : $this;
        
        // Get all descendants
        $thread[] = $root;
        $thread = array_merge($thread, $root->getAllReplies());
        
        return $thread;
    }

    /**
     * Get root comment of the thread
     */
    protected function getRoot(): self
    {
        $comment = $this;
        while ($comment->parent_id) {
            $comment = $comment->parent;
        }
        return $comment;
    }

    /**
     * Get all replies recursively
     */
    protected function getAllReplies(): array
    {
        $allReplies = [];
        
        foreach ($this->replies as $reply) {
            $allReplies[] = $reply;
            $allReplies = array_merge($allReplies, $reply->getAllReplies());
        }
        
        return $allReplies;
    }

    /**
     * Get formatted content with mentions
     */
    public function getFormattedContentAttribute(): string
    {
        $content = e($this->content);
        
        // Convert @mentions to links
        $content = preg_replace(
            '/@(\w+)/',
            '<a href="/users/$1" class="mention">@$1</a>',
            $content
        );
        
        // Convert line breaks to <br>
        $content = nl2br($content);
        
        return $content;
    }
}