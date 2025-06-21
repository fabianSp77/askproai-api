<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeRelationship extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_document_id',
        'target_document_id',
        'relationship_type',
        'strength',
        'is_auto_detected',
    ];

    protected $casts = [
        'strength' => 'float',
        'is_auto_detected' => 'boolean',
    ];

    const TYPES = [
        'related' => 'Related',
        'prerequisite' => 'Prerequisite',
        'next' => 'Next',
        'previous' => 'Previous',
        'parent' => 'Parent',
        'child' => 'Child',
        'references' => 'References',
        'referenced_by' => 'Referenced By',
    ];

    /**
     * Get the source document
     */
    public function sourceDocument(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDocument::class, 'source_document_id');
    }

    /**
     * Get the target document
     */
    public function targetDocument(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDocument::class, 'target_document_id');
    }

    /**
     * Get the inverse relationship
     */
    public function getInverseRelationship()
    {
        $inverseTypes = [
            'next' => 'previous',
            'previous' => 'next',
            'parent' => 'child',
            'child' => 'parent',
            'references' => 'referenced_by',
            'referenced_by' => 'references',
        ];

        $inverseType = $inverseTypes[$this->relationship_type] ?? $this->relationship_type;

        return self::firstOrCreate([
            'source_document_id' => $this->target_document_id,
            'target_document_id' => $this->source_document_id,
            'relationship_type' => $inverseType,
        ], [
            'strength' => $this->strength,
            'is_auto_detected' => $this->is_auto_detected,
        ]);
    }

    /**
     * Scope for strong relationships
     */
    public function scopeStrong($query, float $minStrength = 0.7)
    {
        return $query->where('strength', '>=', $minStrength);
    }

    /**
     * Scope for manual relationships
     */
    public function scopeManual($query)
    {
        return $query->where('is_auto_detected', false);
    }

    /**
     * Scope for auto-detected relationships
     */
    public function scopeAutoDetected($query)
    {
        return $query->where('is_auto_detected', true);
    }

    /**
     * Get human-readable relationship type
     */
    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->relationship_type] ?? ucfirst($this->relationship_type);
    }
}