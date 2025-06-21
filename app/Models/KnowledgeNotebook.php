<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeNotebook extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'description',
        'is_public',
        'metadata',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the user who owns the notebook
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the entries in this notebook
     */
    public function entries(): HasMany
    {
        return $this->hasMany(KnowledgeNotebookEntry::class, 'notebook_id')
            ->orderBy('order');
    }

    /**
     * Scope for public notebooks
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope for notebooks owned by a user
     */
    public function scopeOwnedBy($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if user can view this notebook
     */
    public function canView(?User $user = null): bool
    {
        if ($this->is_public) {
            return true;
        }

        if (!$user) {
            return false;
        }

        return $this->user_id === $user->id;
    }

    /**
     * Check if user can edit this notebook
     */
    public function canEdit(?User $user = null): bool
    {
        if (!$user) {
            return false;
        }

        return $this->user_id === $user->id;
    }

    /**
     * Get the URL for the notebook
     */
    public function getUrlAttribute(): string
    {
        return route('knowledge.notebooks.show', [
            'user' => $this->user->username ?? $this->user->id,
            'notebook' => $this->slug,
        ]);
    }

    /**
     * Get total word count
     */
    public function getTotalWordCountAttribute(): int
    {
        return $this->entries->sum(function ($entry) {
            return str_word_count(strip_tags($entry->content));
        });
    }

    /**
     * Clone notebook for another user
     */
    public function cloneFor(User $user): self
    {
        $clone = $this->replicate();
        $clone->user_id = $user->id;
        $clone->slug = $this->slug . '-' . uniqid();
        $clone->title = $this->title . ' (Copy)';
        $clone->is_public = false;
        $clone->save();

        // Clone entries
        foreach ($this->entries as $entry) {
            $entryClone = $entry->replicate();
            $entryClone->notebook_id = $clone->id;
            $entryClone->save();
        }

        return $clone;
    }
}