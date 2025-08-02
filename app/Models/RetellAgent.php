<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Scopes\TenantScope;

class RetellAgent extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'retell_agent_id',
        'name',
        'description',
        'type',
        'language',
        'capabilities',
        'voice_settings',
        'prompt_settings',
        'integration_settings',
        'is_active',
        'is_default',
        'priority',
        'total_calls',
        'successful_calls',
        'average_duration',
        'satisfaction_score',
        'is_test_agent',
        'test_config',
    ];

    protected $casts = [
        'capabilities' => 'array',
        'voice_settings' => 'array',
        'prompt_settings' => 'array',
        'integration_settings' => 'array',
        'test_config' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'is_test_agent' => 'boolean',
        'average_duration' => 'float',
        'satisfaction_score' => 'float',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
        
        static::creating(function ($agent) {
            if (!$agent->company_id && auth()->check()) {
                $agent->company_id = auth()->user()->company_id;
            }
        });
    }

    /**
     * Agent types
     */
    const TYPE_GENERAL = 'general';
    const TYPE_SALES = 'sales';
    const TYPE_SUPPORT = 'support';
    const TYPE_APPOINTMENTS = 'appointments';
    const TYPE_CUSTOM = 'custom';

    /**
     * Get available agent types
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_GENERAL => 'General Purpose',
            self::TYPE_SALES => 'Sales & Outreach',
            self::TYPE_SUPPORT => 'Customer Support',
            self::TYPE_APPOINTMENTS => 'Appointment Booking',
            self::TYPE_CUSTOM => 'Custom Configuration',
        ];
    }

    /**
     * Supported languages
     */
    public static function getSupportedLanguages(): array
    {
        return [
            'de' => 'German',
            'en' => 'English',
            'es' => 'Spanish',
            'fr' => 'French',
            'it' => 'Italian',
            'pt' => 'Portuguese',
            'nl' => 'Dutch',
            'pl' => 'Polish',
            'tr' => 'Turkish',
            'ru' => 'Russian',
            'ja' => 'Japanese',
            'ko' => 'Korean',
            'zh' => 'Chinese (Mandarin)',
        ];
    }

    /**
     * Company relationship
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Assignments relationship
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(AgentAssignment::class, 'retell_agent_id');
    }

    /**
     * Calls made by this agent
     */
    public function calls(): HasMany
    {
        return $this->hasMany(Call::class, 'metadata->agent_id', 'retell_agent_id');
    }

    /**
     * Campaigns using this agent
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(RetellAICallCampaign::class, 'agent_id', 'retell_agent_id');
    }

    /**
     * Scope for active agents
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for agents by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Calculate success rate
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_calls === 0) {
            return 0;
        }
        
        return round(($this->successful_calls / $this->total_calls) * 100, 2);
    }

    /**
     * Update performance metrics
     */
    public function updateMetrics(): void
    {
        $calls = $this->calls()
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        $this->total_calls = $calls->count();
        $this->successful_calls = $calls->where('metadata.outcome', 'success')->count();
        
        if ($calls->count() > 0) {
            $this->average_duration = $calls->avg('duration_sec');
            
            // Calculate satisfaction if available
            $ratedCalls = $calls->whereNotNull('metadata.satisfaction_rating');
            if ($ratedCalls->count() > 0) {
                $this->satisfaction_score = $ratedCalls->avg('metadata.satisfaction_rating');
            }
        }
        
        $this->save();
    }

    /**
     * Check if agent can handle a specific capability
     */
    public function hasCapability(string $capability): bool
    {
        return in_array($capability, $this->capabilities ?? []);
    }

    /**
     * Get agent configuration for Retell API
     */
    public function getRetellConfiguration(): array
    {
        return [
            'agent_id' => $this->retell_agent_id,
            'voice_settings' => $this->voice_settings ?? [],
            'prompt_settings' => $this->prompt_settings ?? [],
            'language' => $this->language,
            'capabilities' => $this->capabilities ?? [],
        ];
    }
}