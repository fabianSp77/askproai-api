<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeFeedback extends Model
{
    use HasFactory;

    protected $table = 'knowledge_feedback';
    
    protected $fillable = [
        'document_id',
        'user_id',
        'session_id',
        'is_helpful',
        'comment',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'is_helpful' => 'boolean',
    ];

    /**
     * Get the document that received the feedback
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDocument::class, 'document_id');
    }

    /**
     * Get the user who gave the feedback
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope for helpful feedback
     */
    public function scopeHelpful($query)
    {
        return $query->where('is_helpful', true);
    }

    /**
     * Scope for not helpful feedback
     */
    public function scopeNotHelpful($query)
    {
        return $query->where('is_helpful', false);
    }

    /**
     * Scope for feedback with comments
     */
    public function scopeWithComments($query)
    {
        return $query->whereNotNull('comment');
    }
}