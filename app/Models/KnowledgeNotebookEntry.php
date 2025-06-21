<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeNotebookEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'notebook_id',
        'title',
        'content',
        'tags',
        'order',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    /**
     * Get the notebook this entry belongs to
     */
    public function notebook(): BelongsTo
    {
        return $this->belongsTo(KnowledgeNotebook::class, 'notebook_id');
    }

    /**
     * Scope for entries with specific tag
     */
    public function scopeWithTag($query, string $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    /**
     * Scope for entries with any of the specified tags
     */
    public function scopeWithAnyTag($query, array $tags)
    {
        return $query->where(function ($q) use ($tags) {
            foreach ($tags as $tag) {
                $q->orWhereJsonContains('tags', $tag);
            }
        });
    }

    /**
     * Get word count
     */
    public function getWordCountAttribute(): int
    {
        return str_word_count(strip_tags($this->content));
    }

    /**
     * Get reading time in minutes
     */
    public function getReadingTimeAttribute(): int
    {
        $wordsPerMinute = 200;
        return max(1, ceil($this->word_count / $wordsPerMinute));
    }

    /**
     * Get excerpt
     */
    public function getExcerptAttribute(): string
    {
        $plainText = strip_tags($this->content);
        return \Str::limit($plainText, 200);
    }

    /**
     * Move entry up in order
     */
    public function moveUp(): void
    {
        $prevEntry = $this->notebook->entries()
            ->where('order', '<', $this->order)
            ->orderBy('order', 'desc')
            ->first();

        if ($prevEntry) {
            $tempOrder = $this->order;
            $this->update(['order' => $prevEntry->order]);
            $prevEntry->update(['order' => $tempOrder]);
        }
    }

    /**
     * Move entry down in order
     */
    public function moveDown(): void
    {
        $nextEntry = $this->notebook->entries()
            ->where('order', '>', $this->order)
            ->orderBy('order', 'asc')
            ->first();

        if ($nextEntry) {
            $tempOrder = $this->order;
            $this->update(['order' => $nextEntry->order]);
            $nextEntry->update(['order' => $tempOrder]);
        }
    }

    /**
     * Move to specific position
     */
    public function moveTo(int $position): void
    {
        $entries = $this->notebook->entries()
            ->where('id', '!=', $this->id)
            ->orderBy('order')
            ->get();

        // Remove this entry from its current position
        $entries = $entries->filter(function ($entry) {
            return $entry->id !== $this->id;
        });

        // Insert at new position
        $entries->splice($position - 1, 0, [$this]);

        // Update order for all entries
        foreach ($entries->values() as $index => $entry) {
            $entry->update(['order' => $index + 1]);
        }
    }

    /**
     * Export entry as markdown
     */
    public function toMarkdown(): string
    {
        $markdown = "# {$this->title}\n\n";
        
        if (!empty($this->tags)) {
            $markdown .= "Tags: " . implode(', ', $this->tags) . "\n\n";
        }
        
        $markdown .= $this->content;
        
        return $markdown;
    }
}