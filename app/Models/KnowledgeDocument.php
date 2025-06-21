<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Company;

class KnowledgeDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'raw_content',
        'file_path',
        'file_type',
        'category_id',
        'metadata',
        'status',
        'order',
        'view_count',
        'helpful_count',
        'not_helpful_count',
        'last_indexed_at',
        'file_modified_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_indexed_at' => 'datetime',
        'file_modified_at' => 'datetime',
        'view_count' => 'integer',
        'helpful_count' => 'integer',
        'not_helpful_count' => 'integer',
        'order' => 'integer',
        'company_id' => 'integer',
    ];

    /**
     * Get the company for the document
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the category for the document
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(KnowledgeCategory::class, 'category_id');
    }

    /**
     * Get the tags for the document
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(KnowledgeTag::class, 'knowledge_document_tags', 'document_id', 'tag_id')
            ->withTimestamps();
    }

    /**
     * Get the versions for the document
     */
    public function versions(): HasMany
    {
        return $this->hasMany(KnowledgeVersion::class, 'document_id');
    }

    /**
     * Get the search index entries for the document
     */
    public function searchIndexes(): HasMany
    {
        return $this->hasMany(KnowledgeSearchIndex::class, 'document_id');
    }

    /**
     * Get the code snippets for the document
     */
    public function codeSnippets(): HasMany
    {
        return $this->hasMany(KnowledgeCodeSnippet::class, 'document_id');
    }

    /**
     * Get the relationships where this document is the source
     */
    public function sourceRelationships(): HasMany
    {
        return $this->hasMany(KnowledgeRelationship::class, 'source_document_id');
    }

    /**
     * Get the relationships where this document is the target
     */
    public function targetRelationships(): HasMany
    {
        return $this->hasMany(KnowledgeRelationship::class, 'target_document_id');
    }

    /**
     * Get related documents
     */
    public function relatedDocuments()
    {
        return $this->belongsToMany(
            KnowledgeDocument::class,
            'knowledge_related_documents',
            'document_id',
            'related_document_id'
        )->withPivot(['relevance_score', 'relation_type'])
        ->withTimestamps();
    }
    
    /**
     * Get feedback for the document
     */
    public function feedback(): HasMany
    {
        return $this->hasMany(KnowledgeFeedback::class, 'document_id');
    }

    /**
     * Get the comments for the document
     */
    public function comments(): HasMany
    {
        return $this->hasMany(KnowledgeComment::class, 'document_id');
    }

    /**
     * Get the analytics for the document
     */
    public function analytics(): HasMany
    {
        return $this->hasMany(KnowledgeAnalytic::class, 'document_id');
    }

    /**
     * Get the user who created the document
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the document
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope for published documents
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope for documents of a specific type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get the URL for the document
     */
    public function getUrlAttribute(): string
    {
        return route('knowledge.show', $this->slug);
    }

    /**
     * Get the edit URL for the document
     */
    public function getEditUrlAttribute(): string
    {
        return route('knowledge.edit', $this->slug);
    }

    /**
     * Get the file path for editing
     */
    public function getFilePathAttribute(): string
    {
        return base_path($this->path);
    }

    /**
     * Check if document has been recently updated
     */
    public function isRecentlyUpdated(): bool
    {
        return $this->updated_at->gt(now()->subDays(7));
    }

    /**
     * Get primary tags
     */
    public function getPrimaryTags()
    {
        return $this->tags()->wherePivot('is_primary', true)->get();
    }

    /**
     * Get prerequisite documents
     */
    public function getPrerequisites()
    {
        return $this->relatedDocuments()
            ->wherePivot('relationship_type', 'prerequisite')
            ->get();
    }

    /**
     * Get next document in sequence
     */
    public function getNextDocument()
    {
        return $this->relatedDocuments()
            ->wherePivot('relationship_type', 'next')
            ->first();
    }

    /**
     * Get previous document in sequence
     */
    public function getPreviousDocument()
    {
        return $this->relatedDocuments()
            ->wherePivot('relationship_type', 'previous')
            ->first();
    }
}