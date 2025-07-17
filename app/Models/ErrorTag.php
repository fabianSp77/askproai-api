<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class ErrorTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'color',
    ];

    /**
     * Get the errors for the tag.
     */
    public function errors(): BelongsToMany
    {
        return $this->belongsToMany(ErrorCatalog::class, 'error_tag_assignments');
    }

    /**
     * Set the slug attribute.
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
        $this->attributes['slug'] = Str::slug($value);
    }

    /**
     * Get error count for the tag.
     */
    public function getErrorCountAttribute(): int
    {
        return $this->errors()->count();
    }

    /**
     * Scope for tags with errors.
     */
    public function scopeWithErrors($query)
    {
        return $query->has('errors');
    }

    /**
     * Scope for popular tags.
     */
    public function scopePopular($query, int $minErrors = 5)
    {
        return $query->withCount('errors')
                     ->having('errors_count', '>=', $minErrors)
                     ->orderBy('errors_count', 'desc');
    }
}