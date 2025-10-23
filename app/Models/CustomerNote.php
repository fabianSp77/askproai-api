<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerNote extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'customer_id',
        'type',
        'visibility',
        'subject',
        'content',
        'category',
        'is_pinned',
        'is_important',
        'created_by',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_important' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopeImportant($query)
    {
        return $query->where('is_important', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByVisibility($query, $visibility)
    {
        return $query->where('visibility', $visibility);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors for Livewire Serialization (2025-10-22)
    |--------------------------------------------------------------------------
    | These accessors replace closures in RelationManagers to fix Livewire
    | serialization issues. Closures are not JSON-serializable and cause
    | "Snapshot missing on Livewire component" errors.
    */

    /**
     * Get creator name or 'System' if no creator
     *
     * @return string
     */
    public function getCreatorNameAttribute(): string
    {
        return $this->creator?->name ?? 'System';
    }

    /**
     * Get text weight based on importance
     *
     * @return string
     */
    public function getTextWeightAttribute(): string
    {
        return $this->is_important ? 'bold' : 'regular';
    }

    /**
     * Get content preview (first 100 chars, stripped of HTML)
     *
     * @return string
     */
    public function getContentPreviewAttribute(): string
    {
        return strip_tags(substr($this->content, 0, 100)) . '...';
    }
}