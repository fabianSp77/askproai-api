<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErrorSolutionFeedback extends Model
{
    use HasFactory;

    protected $table = 'error_solution_feedback';

    protected $fillable = [
        'solution_id',
        'user_id',
        'was_helpful',
        'comment',
    ];

    protected $casts = [
        'was_helpful' => 'boolean',
    ];

    /**
     * Get the solution for this feedback.
     */
    public function solution(): BelongsTo
    {
        return $this->belongsTo(ErrorSolution::class);
    }

    /**
     * Get the user who provided the feedback.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for helpful feedback.
     */
    public function scopeHelpful($query)
    {
        return $query->where('was_helpful', true);
    }

    /**
     * Scope for not helpful feedback.
     */
    public function scopeNotHelpful($query)
    {
        return $query->where('was_helpful', false);
    }

    /**
     * Scope for feedback with comments.
     */
    public function scopeWithComments($query)
    {
        return $query->whereNotNull('comment');
    }
}