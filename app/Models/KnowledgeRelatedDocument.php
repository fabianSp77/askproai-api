<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeRelatedDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'related_document_id',
        'relevance_score',
        'relation_type',
    ];

    protected $casts = [
        'relevance_score' => 'float',
    ];

    /**
     * Get the source document
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDocument::class, 'document_id');
    }

    /**
     * Get the related document
     */
    public function relatedDocument(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDocument::class, 'related_document_id');
    }

    /**
     * Scope for specific relation type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('relation_type', $type);
    }

    /**
     * Scope for high relevance
     */
    public function scopeHighRelevance($query, float $threshold = 0.7)
    {
        return $query->where('relevance_score', '>=', $threshold);
    }
}