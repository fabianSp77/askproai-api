<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchHistory extends Model
{
    protected $table = 'search_history';
    
    protected $fillable = [
        'user_id',
        'query',
        'selected_type',
        'selected_id',
        'context',
        'results_count',
    ];

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for recent searches.
     */
    public function scopeRecent($query)
    {
        return $query->where('created_at', '>=', now()->subDays(30));
    }

    /**
     * Scope for successful searches.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('results_count', '>', 0);
    }
}