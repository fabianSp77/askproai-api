<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeSearchIndex extends Model
{
    use HasFactory;

    protected $table = 'knowledge_search_index';

    protected $fillable = [
        'document_id',
        'section_title',
        'content_chunk',
        'embedding',
        'keywords',
        'relevance_score',
    ];

    protected $casts = [
        'embedding' => 'array',
        'keywords' => 'array',
        'relevance_score' => 'float',
    ];

    /**
     * Get the document this index entry belongs to
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDocument::class, 'document_id');
    }

    /**
     * Calculate similarity with another embedding
     */
    public function calculateSimilarity(array $otherEmbedding): float
    {
        if (!$this->embedding || !$otherEmbedding) {
            return 0.0;
        }

        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;

        for ($i = 0; $i < count($this->embedding); $i++) {
            $dotProduct += $this->embedding[$i] * ($otherEmbedding[$i] ?? 0);
            $magnitude1 += $this->embedding[$i] * $this->embedding[$i];
            $magnitude2 += ($otherEmbedding[$i] ?? 0) * ($otherEmbedding[$i] ?? 0);
        }

        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);

        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0.0;
        }

        return $dotProduct / ($magnitude1 * $magnitude2);
    }

    /**
     * Scope for searching by keywords
     */
    public function scopeWithKeywords($query, array $keywords)
    {
        return $query->where(function ($q) use ($keywords) {
            foreach ($keywords as $keyword) {
                $q->orWhereJsonContains('keywords', $keyword);
            }
        });
    }

    /**
     * Scope for full-text search
     */
    public function scopeSearch($query, string $searchTerm)
    {
        return $query->whereRaw(
            "MATCH(content_chunk) AGAINST(? IN BOOLEAN MODE)",
            [$searchTerm]
        );
    }
}