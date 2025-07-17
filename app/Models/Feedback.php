<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedback';

    protected $fillable = [
        'company_id',
        'user_id',
        'type',
        'priority',
        'subject',
        'message',
        'status',
        'attachments',
        'first_response_at',
        'resolved_at'
    ];

    protected $casts = [
        'attachments' => 'array',
        'first_response_at' => 'datetime',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(PortalUser::class, 'user_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(FeedbackResponse::class);
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'open' => 'red',
            'in_progress' => 'orange',
            'resolved' => 'green',
            'closed' => 'gray',
            default => 'default'
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'low' => 'blue',
            'medium' => 'orange',
            'high' => 'red',
            'urgent' => 'purple',
            default => 'default'
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'bug' => 'bug',
            'feature' => 'bulb',
            'improvement' => 'rise',
            'question' => 'question-circle',
            'complaint' => 'warning',
            default => 'info-circle'
        };
    }
}