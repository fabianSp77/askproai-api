<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Service Synonym Model
 *
 * Stores alternative terms/phrases that customers might use to refer to a service.
 * Used for intelligent service matching during phone conversations.
 *
 * @property int $id
 * @property int $service_id
 * @property string $synonym Alternative term for the service
 * @property float $confidence Confidence score (0.0-1.0) indicating how likely this synonym matches
 * @property string|null $notes Additional notes about usage context
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Service $service
 */
class ServiceSynonym extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'service_synonyms';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'service_id',
        'synonym',
        'confidence',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'confidence' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the service that this synonym belongs to.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the confidence level as a percentage string.
     *
     * @return string
     */
    public function getConfidencePercentageAttribute(): string
    {
        return number_format($this->confidence * 100, 0) . '%';
    }

    /**
     * Determine if this is a high-confidence synonym (>= 85%).
     *
     * @return bool
     */
    public function isHighConfidence(): bool
    {
        return $this->confidence >= 0.85;
    }

    /**
     * Determine if this is a medium-confidence synonym (75%-84%).
     *
     * @return bool
     */
    public function isMediumConfidence(): bool
    {
        return $this->confidence >= 0.75 && $this->confidence < 0.85;
    }

    /**
     * Determine if this is a low-confidence synonym (< 75%).
     *
     * @return bool
     */
    public function isLowConfidence(): bool
    {
        return $this->confidence < 0.75;
    }
}
