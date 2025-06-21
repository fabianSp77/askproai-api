<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class KnowledgeTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'color',
    ];

    /**
     * Get the documents for the tag
     */
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(KnowledgeDocument::class, 'knowledge_document_tags', 'tag_id', 'document_id')
            ->withTimestamps();
    }

    /**
     * Get the URL for the tag
     */
    public function getUrlAttribute(): string
    {
        return route('knowledge.tag', $this->slug);
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Decrement usage count
     */
    public function decrementUsage(): void
    {
        if ($this->usage_count > 0) {
            $this->decrement('usage_count');
        }
    }

    /**
     * Scope for popular tags
     */
    public function scopePopular($query, int $limit = 20)
    {
        return $query->orderBy('usage_count', 'desc')->limit($limit);
    }

    /**
     * Get CSS color style
     */
    public function getColorStyleAttribute(): string
    {
        return "background-color: {$this->color}20; color: {$this->color}; border-color: {$this->color};";
    }
}