<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Scopes\TenantScope;

class CustomerInteraction extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'company_id',
        'branch_id',
        'interaction_type',
        'channel',
        'interaction_at',
        'duration_seconds',
        'call_id',
        'from_phone',
        'to_phone',
        'call_outcome',
        'summary',
        'extracted_data',
        'sentiment_analysis',
        'transcript',
        'intent_classification',
        'appointment_id',
        'staff_id',
        'handled_by',
        'customer_mood',
        'issue_resolved',
        'satisfaction_score',
        'requires_follow_up',
        'follow_up_at',
        'follow_up_notes',
        'follow_up_completed',
        'metadata',
        'tags'
    ];

    protected $casts = [
        'interaction_at' => 'datetime',
        'follow_up_at' => 'datetime',
        'extracted_data' => 'array',
        'sentiment_analysis' => 'array',
        'intent_classification' => 'array',
        'metadata' => 'array',
        'tags' => 'array',
        'issue_resolved' => 'boolean',
        'requires_follow_up' => 'boolean',
        'follow_up_completed' => 'boolean',
        'duration_seconds' => 'integer',
        'satisfaction_score' => 'integer'
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    /**
     * Get the customer
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the branch
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the related appointment
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the staff member
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Get the related call
     */
    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class, 'call_id', 'retell_call_id');
    }

    /**
     * Scope for interactions requiring follow-up
     */
    public function scopeRequiringFollowUp($query)
    {
        return $query->where('requires_follow_up', true)
                     ->where('follow_up_completed', false);
    }

    /**
     * Scope for overdue follow-ups
     */
    public function scopeOverdueFollowUps($query)
    {
        return $query->requireFollowUp()
                     ->where('follow_up_at', '<', now());
    }

    /**
     * Scope for interactions by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('interaction_type', $type);
    }

    /**
     * Scope for interactions by channel
     */
    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope for interactions with positive sentiment
     */
    public function scopePositiveSentiment($query)
    {
        return $query->whereJsonContains('sentiment_analysis->overall', 'positive');
    }

    /**
     * Scope for interactions with negative sentiment
     */
    public function scopeNegativeSentiment($query)
    {
        return $query->whereJsonContains('sentiment_analysis->overall', 'negative');
    }

    /**
     * Get duration in human-readable format
     */
    public function getDurationFormatted(): string
    {
        if (!$this->duration_seconds) {
            return 'N/A';
        }

        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;

        if ($minutes > 0) {
            return sprintf('%d:%02d', $minutes, $seconds);
        }

        return sprintf('0:%02d', $seconds);
    }

    /**
     * Get sentiment score
     */
    public function getSentimentScore(): ?float
    {
        if (!$this->sentiment_analysis || !isset($this->sentiment_analysis['score'])) {
            return null;
        }

        return (float) $this->sentiment_analysis['score'];
    }

    /**
     * Get primary intent
     */
    public function getPrimaryIntent(): ?string
    {
        if (!$this->intent_classification || !isset($this->intent_classification['primary'])) {
            return null;
        }

        return $this->intent_classification['primary'];
    }

    /**
     * Check if interaction was successful
     */
    public function wasSuccessful(): bool
    {
        // Define success based on interaction type
        switch ($this->interaction_type) {
            case 'appointment_booking':
                return $this->appointment_id !== null;
            
            case 'appointment_cancellation':
            case 'appointment_reschedule':
                return $this->call_outcome === 'appointment_cancelled' || 
                       $this->call_outcome === 'appointment_rescheduled';
            
            case 'inquiry':
                return $this->call_outcome === 'information_provided';
            
            case 'complaint':
                return $this->issue_resolved === true;
            
            default:
                return !in_array($this->call_outcome, ['hung_up', 'technical_issue']);
        }
    }

    /**
     * Mark follow-up as completed
     */
    public function completeFollowUp(string $notes = null): void
    {
        $this->update([
            'follow_up_completed' => true,
            'follow_up_notes' => $notes
        ]);
    }

    /**
     * Add tag to interaction
     */
    public function addTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->update(['tags' => $tags]);
        }
    }

    /**
     * Remove tag from interaction
     */
    public function removeTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        $tags = array_values(array_diff($tags, [$tag]));
        $this->update(['tags' => $tags]);
    }

    /**
     * Check if interaction has specific tag
     */
    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags ?? []);
    }

    /**
     * Get interaction type label
     */
    public function getTypeLabel(): string
    {
        $labels = [
            'phone_call' => 'Anruf',
            'appointment_booking' => 'Terminbuchung',
            'appointment_cancellation' => 'Terminabsage',
            'appointment_reschedule' => 'Terminverschiebung',
            'inquiry' => 'Anfrage',
            'complaint' => 'Beschwerde',
            'feedback' => 'Feedback',
            'no_show' => 'Nicht erschienen',
            'walk_in' => 'Walk-In',
            'online_booking' => 'Online-Buchung',
            'sms' => 'SMS',
            'email' => 'E-Mail',
            'whatsapp' => 'WhatsApp'
        ];

        return $labels[$this->interaction_type] ?? $this->interaction_type;
    }

    /**
     * Get channel label
     */
    public function getChannelLabel(): string
    {
        $labels = [
            'phone' => 'Telefon',
            'web' => 'Webseite',
            'mobile' => 'Mobile App',
            'in_person' => 'Persönlich'
        ];

        return $labels[$this->channel] ?? $this->channel;
    }

    /**
     * Get mood label
     */
    public function getMoodLabel(): string
    {
        $labels = [
            'happy' => 'Zufrieden',
            'neutral' => 'Neutral',
            'frustrated' => 'Frustriert',
            'angry' => 'Verärgert'
        ];

        return $labels[$this->customer_mood] ?? $this->customer_mood ?? 'Unbekannt';
    }

    /**
     * Get satisfaction label
     */
    public function getSatisfactionLabel(): string
    {
        if (!$this->satisfaction_score) {
            return 'N/A';
        }

        $labels = [
            1 => 'Sehr unzufrieden',
            2 => 'Unzufrieden',
            3 => 'Neutral',
            4 => 'Zufrieden',
            5 => 'Sehr zufrieden'
        ];

        return $labels[$this->satisfaction_score] ?? $this->satisfaction_score . '/5';
    }

    /**
     * Calculate interaction quality score
     */
    public function calculateQualityScore(): float
    {
        $score = 0;
        $factors = 0;

        // Satisfaction score (0-1)
        if ($this->satisfaction_score) {
            $score += $this->satisfaction_score / 5;
            $factors++;
        }

        // Issue resolution (0-1)
        if ($this->interaction_type === 'complaint' || $this->requires_follow_up) {
            $score += $this->issue_resolved ? 1 : 0;
            $factors++;
        }

        // Sentiment score (0-1)
        $sentimentScore = $this->getSentimentScore();
        if ($sentimentScore !== null) {
            $score += ($sentimentScore + 1) / 2; // Convert -1 to 1 range to 0 to 1
            $factors++;
        }

        // Call outcome success (0-1)
        $score += $this->wasSuccessful() ? 1 : 0;
        $factors++;

        return $factors > 0 ? round($score / $factors, 2) : 0.5;
    }
}