<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Call extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'customer_id',
        'agent_id',
        'call_id',
        'conversation_id',
        'from_number',
        'to_number',
        'start_timestamp',
        'end_timestamp',
        'duration_sec',
        'call_successful',
        'disconnect_reason',
        'transcript',
        'analysis',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'analysis' => 'array',
        'call_successful' => 'boolean',
        'start_timestamp' => 'datetime',
        'end_timestamp' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns the call.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the customer that made the call.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the agent that handled the call.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * Get all appointments created from this call.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Scope for successful calls.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('call_successful', true);
    }

    /**
     * Scope for failed calls.
     */
    public function scopeFailed($query)
    {
        return $query->where('call_successful', false);
    }

    /**
     * Scope for calls within a date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('start_timestamp', [$startDate, $endDate]);
    }

    /**
     * Get formatted duration.
     */
    public function getFormattedDurationAttribute(): string
    {
        if (! $this->duration_sec) {
            return '0:00';
        }

        $minutes = floor($this->duration_sec / 60);
        $seconds = $this->duration_sec % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Get call status based on success and reason.
     */
    public function getStatusAttribute(): string
    {
        if (! $this->call_successful) {
            return 'failed';
        }

        return match ($this->disconnect_reason) {
            'hangup_by_customer' => 'completed',
            'hangup_by_agent' => 'completed',
            'transferred' => 'transferred',
            'timeout' => 'timeout',
            default => 'completed'
        };
    }

    /**
     * Check if call resulted in an appointment.
     */
    public function hasAppointment(): bool
    {
        return $this->appointments()->exists();
    }

    /**
     * Get the primary intent from analysis.
     */
    public function getIntentAttribute(): ?string
    {
        return $this->analysis['intent'] ?? null;
    }

    /**
     * Get the sentiment from analysis.
     */
    public function getSentimentAttribute(): ?string
    {
        return $this->analysis['sentiment'] ?? null;
    }

    /**
     * Get the confidence score from analysis.
     */
    public function getConfidenceAttribute(): ?float
    {
        return $this->analysis['confidence'] ?? null;
    }
}
