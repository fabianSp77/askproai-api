<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'source',
        'endpoint',
        'method',
        'headers',
        'payload',
        'ip_address',
        'status',
        'error_message',
        'response_code',
        'processing_time_ms',
        'event_type',
        'event_id',
    ];

    protected $casts = [
        'headers' => 'array',
        'payload' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope for filtering by source
     */
    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Scope for successful webhooks
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'processed');
    }

    /**
     * Scope for failed webhooks
     */
    public function scopeFailed($query)
    {
        return $query->whereIn('status', ['failed', 'error']);
    }

    /**
     * Scope for recent webhooks
     */
    public function scopeRecent($query, int $minutes = 60)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * Mark as processed
     */
    public function markAsProcessed(int $responseCode = 200, int $processingTime = null): void
    {
        $this->update([
            'status' => 'processed',
            'response_code' => $responseCode,
            'processing_time_ms' => $processingTime,
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $errorMessage, int $responseCode = 500): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'response_code' => $responseCode,
        ]);
    }

    /**
     * Get formatted processing time
     */
    public function getFormattedProcessingTimeAttribute(): ?string
    {
        if (!$this->processing_time_ms) {
            return null;
        }

        if ($this->processing_time_ms < 1000) {
            return $this->processing_time_ms . 'ms';
        }

        return round($this->processing_time_ms / 1000, 2) . 's';
    }
}