<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class AgentPerformanceMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'company_id',
        'date',
        'total_calls',
        'avg_sentiment_score',
        'avg_satisfaction_score',
        'conversion_rate',
        'avg_call_duration_sec',
        'positive_calls',
        'neutral_calls',
        'negative_calls',
        'converted_calls',
        'hourly_metrics',
    ];

    protected $casts = [
        'date' => 'date',
        'total_calls' => 'integer',
        'avg_sentiment_score' => 'decimal:2',
        'avg_satisfaction_score' => 'decimal:2',
        'conversion_rate' => 'decimal:2',
        'avg_call_duration_sec' => 'integer',
        'positive_calls' => 'integer',
        'neutral_calls' => 'integer',
        'negative_calls' => 'integer',
        'converted_calls' => 'integer',
        'hourly_metrics' => 'array',
    ];

    /**
     * Get the company that owns the metrics
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Calculate performance score (0-100)
     */
    public function getPerformanceScoreAttribute(): int
    {
        $sentimentScore = ($this->avg_sentiment_score + 1) / 2 * 40; // 40 points max
        $conversionScore = min($this->conversion_rate, 50); // 50 points max
        $satisfactionScore = ($this->avg_satisfaction_score ?? 0.5) * 10; // 10 points max
        
        return (int) round($sentimentScore + $conversionScore + $satisfactionScore);
    }

    /**
     * Get performance trend compared to previous period
     */
    public function getTrendAttribute(): array
    {
        $previousDate = Carbon::parse($this->date)->subDay();
        $previous = self::where('agent_id', $this->agent_id)
            ->where('date', $previousDate)
            ->first();
        
        if (!$previous) {
            return [
                'score' => 0,
                'sentiment' => 0,
                'conversion' => 0,
            ];
        }
        
        return [
            'score' => $this->performance_score - $previous->performance_score,
            'sentiment' => round($this->avg_sentiment_score - $previous->avg_sentiment_score, 2),
            'conversion' => round($this->conversion_rate - $previous->conversion_rate, 1),
        ];
    }

    /**
     * Get busiest hour of the day
     */
    public function getBusiestHourAttribute(): ?int
    {
        if (!$this->hourly_metrics) {
            return null;
        }
        
        $busiest = collect($this->hourly_metrics)
            ->sortByDesc('calls')
            ->first();
        
        return $busiest ? (int) $busiest['hour'] : null;
    }

    /**
     * Get sentiment distribution percentage
     */
    public function getSentimentDistributionAttribute(): array
    {
        $total = $this->total_calls ?: 1;
        
        return [
            'positive' => round(($this->positive_calls / $total) * 100, 1),
            'neutral' => round(($this->neutral_calls / $total) * 100, 1),
            'negative' => round(($this->negative_calls / $total) * 100, 1),
        ];
    }

    /**
     * Get average call duration in minutes
     */
    public function getAvgDurationMinutesAttribute(): float
    {
        return round($this->avg_call_duration_sec / 60, 1);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope for specific agent
     */
    public function scopeForAgent($query, $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    /**
     * Scope for high performers
     */
    public function scopeHighPerformers($query, $threshold = 80)
    {
        return $query->whereRaw('((avg_sentiment_score + 1) / 2 * 40 + LEAST(conversion_rate, 50) + COALESCE(avg_satisfaction_score, 0.5) * 10) >= ?', [$threshold]);
    }
}