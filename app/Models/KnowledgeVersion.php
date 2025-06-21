<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'version_number',
        'title',
        'content',
        'metadata',
        'diff',
        'commit_message',
        'change_summary',
        'created_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'version_number' => 'integer',
        'created_by' => 'integer',
    ];

    /**
     * Get the document this version belongs to
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDocument::class, 'document_id');
    }

    /**
     * Get the user who created this version
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    /**
     * Get previous version
     */
    public function getPreviousVersion()
    {
        return self::where('document_id', $this->document_id)
            ->where('version_number', '<', $this->version_number)
            ->orderBy('version_number', 'desc')
            ->first();
    }

    /**
     * Get next version
     */
    public function getNextVersion()
    {
        return self::where('document_id', $this->document_id)
            ->where('version_number', '>', $this->version_number)
            ->orderBy('version_number', 'asc')
            ->first();
    }

    /**
     * Check if this is the latest version
     */
    public function isLatest(): bool
    {
        $latestVersion = self::where('document_id', $this->document_id)
            ->orderBy('version_number', 'desc')
            ->first();
            
        return $this->id === $latestVersion->id;
    }

    /**
     * Restore this version as the current document content
     */
    public function restore(): void
    {
        $this->document->update([
            'content' => $this->content,
            'updated_by' => auth()->id(),
        ]);
        
        // Create a new version for the restoration
        KnowledgeVersion::create([
            'document_id' => $this->document_id,
            'version_number' => $this->document->versions()->max('version_number') + 1,
            'content' => $this->content,
            'commit_message' => "Restored from version {$this->version_number}",
            'created_by' => auth()->id(),
        ]);
    }
}