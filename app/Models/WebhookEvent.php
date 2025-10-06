<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WebhookEvent extends Model
{
    use BelongsToCompany;
    protected $fillable = [
        'provider',
        'company_id',
        'event_type',
        'event_id',
        'idempotency_key',
        'payload',
        'headers',
        'status',
        'processed_at',
        'error_message',
        'notes',
        'retry_count',
        'correlation_id',
        'received_at',
    ];

    protected $casts = [
        'headers' => 'array',
        'payload' => 'array',
        'processed_at' => 'datetime',
        'received_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    public function scopeByProvider($query, $provider)
    {
        return $query->where('provider', $provider);
    }

    public function markAsProcessed($notes = null)
    {
        $this->status = 'processed';
        $this->processed_at = now();

        if ($notes) {
            $this->notes = $notes;
        }

        $this->save();
    }

    public function markAsFailed($errorMessage)
    {
        $this->status = 'failed';
        $this->error_message = $errorMessage;
        $this->retry_count++;
        $this->save();
    }

    public function shouldRetry(): bool
    {
        return $this->status === 'failed' && $this->retry_count < 3;
    }
}
