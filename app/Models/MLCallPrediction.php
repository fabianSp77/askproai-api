<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MLCallPrediction extends Model
{
    use HasFactory;

    protected $table = 'ml_call_predictions';

    protected $fillable = [
        'call_id',
        'model_version',
        'sentiment_score',
        'satisfaction_score',
        'goal_achievement_score',
        'sentence_sentiments',
        'feature_contributions',
        'prediction_confidence',
        'processing_time_ms',
    ];

    protected $casts = [
        'sentiment_score' => 'decimal:2',
        'satisfaction_score' => 'decimal:2',
        'goal_achievement_score' => 'decimal:2',
        'prediction_confidence' => 'decimal:2',
        'sentence_sentiments' => 'array',
        'feature_contributions' => 'array',
        'processing_time_ms' => 'integer',
    ];

    /**
     * Get the call that owns the prediction
     */
    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }

    /**
     * Get sentiment label from score
     */
    public function getSentimentLabelAttribute(): string
    {
        if ($this->sentiment_score > 0.3) {
            return 'positive';
        } elseif ($this->sentiment_score < -0.3) {
            return 'negative';
        }
        return 'neutral';
    }

    /**
     * Get sentiment color for UI
     */
    public function getSentimentColorAttribute(): string
    {
        if ($this->sentiment_score > 0.3) {
            return 'success';
        } elseif ($this->sentiment_score < -0.3) {
            return 'danger';
        }
        return 'gray';
    }

    /**
     * Get formatted confidence percentage
     */
    public function getConfidencePercentageAttribute(): string
    {
        return round($this->prediction_confidence * 100) . '%';
    }

    /**
     * Get sentences with positive sentiment
     */
    public function getPositiveSentencesAttribute(): array
    {
        if (!$this->sentence_sentiments) {
            return [];
        }

        return collect($this->sentence_sentiments)
            ->filter(fn($sentence) => $sentence['sentiment'] === 'positive')
            ->values()
            ->toArray();
    }

    /**
     * Get sentences with negative sentiment
     */
    public function getNegativeSentencesAttribute(): array
    {
        if (!$this->sentence_sentiments) {
            return [];
        }

        return collect($this->sentence_sentiments)
            ->filter(fn($sentence) => $sentence['sentiment'] === 'negative')
            ->values()
            ->toArray();
    }

    /**
     * Get top contributing features
     */
    public function getTopFeaturesAttribute(): array
    {
        if (!$this->feature_contributions) {
            return [];
        }

        return collect($this->feature_contributions)
            ->sortByDesc(fn($value) => abs($value))
            ->take(5)
            ->toArray();
    }
}