<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationQueue extends Model
{
    use BelongsToCompany;
    protected $table = 'notification_queue';

    protected $fillable = [
        'uuid',
        'notifiable_type',
        'notifiable_id',
        'channel',
        'template_key',
        'type',
        'data',
        'recipient',
        'language',
        'priority',
        'status',
        'attempts',
        'scheduled_at',
        'sent_at',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'metadata',
        'error_message',
        'provider_message_id',
        'cost'
    ];

    protected $casts = [
        'data' => 'array',
        'recipient' => 'array',
        'metadata' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'cost' => 'decimal:4'
    ];

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class);
    }

    public function template()
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_key', 'key');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'pending')
            ->where('scheduled_at', '<=', now());
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function getIsDeliveredAttribute(): bool
    {
        return $this->delivered_at !== null;
    }

    public function getIsOpenedAttribute(): bool
    {
        return $this->opened_at !== null;
    }

    public function getIsClickedAttribute(): bool
    {
        return $this->clicked_at !== null;
    }

    public function getDeliveryTimeAttribute(): ?int
    {
        if ($this->sent_at && $this->delivered_at) {
            return $this->delivered_at->diffInSeconds($this->sent_at);
        }
        return null;
    }
}