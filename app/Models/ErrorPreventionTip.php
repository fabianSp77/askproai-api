<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErrorPreventionTip extends Model
{
    use HasFactory;

    protected $fillable = [
        'error_catalog_id',
        'order',
        'tip',
        'category',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * Get the error catalog that owns the prevention tip.
     */
    public function errorCatalog(): BelongsTo
    {
        return $this->belongsTo(ErrorCatalog::class);
    }

    /**
     * Scope for tips by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}