<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeCodeSnippet extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'language',
        'title',
        'code',
        'description',
        'is_executable',
        'execution_config',
        'usage_count',
    ];

    protected $casts = [
        'is_executable' => 'boolean',
        'execution_config' => 'array',
    ];

    /**
     * Get the document this snippet belongs to
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDocument::class, 'document_id');
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Scope for executable snippets
     */
    public function scopeExecutable($query)
    {
        return $query->where('is_executable', true);
    }

    /**
     * Scope for snippets of a specific language
     */
    public function scopeOfLanguage($query, string $language)
    {
        return $query->where('language', $language);
    }

    /**
     * Scope for popular snippets
     */
    public function scopePopular($query)
    {
        return $query->orderBy('usage_count', 'desc');
    }

    /**
     * Get syntax highlighted code
     */
    public function getHighlightedCodeAttribute(): string
    {
        // This would integrate with a syntax highlighting library
        return htmlspecialchars($this->code);
    }

    /**
     * Check if snippet can be executed
     */
    public function canExecute(): bool
    {
        if (!$this->is_executable) {
            return false;
        }

        // Additional security checks
        $dangerousPatterns = [
            'rm -rf',
            'drop database',
            'delete from',
            'truncate',
            'system(',
            'exec(',
            'eval(',
        ];

        $lowerCode = strtolower($this->code);
        foreach ($dangerousPatterns as $pattern) {
            if (strpos($lowerCode, $pattern) !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Execute the code snippet (sandbox environment)
     */
    public function execute(): array
    {
        if (!$this->canExecute()) {
            return [
                'success' => false,
                'error' => 'This code snippet cannot be executed for security reasons.',
            ];
        }

        // This would integrate with a sandboxed execution environment
        // For now, return a placeholder response
        return [
            'success' => true,
            'output' => 'Code execution is not yet implemented.',
            'execution_time' => 0,
        ];
    }
}