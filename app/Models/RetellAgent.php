<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RetellAgent extends Model
{
    use BelongsToCompany;
    protected $fillable = [
        'company_id',
        'agent_id',
        'agent_name',
        'voice_id',
        'voice_model',
        'language',
        'response_engine',
        'llm_model',
        'prompt',
        'first_sentence',
        'webhook_url',
        'is_active',
        'max_call_duration',
        'interruption_sensitivity',
        'backchannel_frequency',
        'boosted_keywords',
        'metadata',
        'last_used_at',
        'call_count',
        'total_duration_minutes',
        'average_call_duration',
        'settings',
    ];

    protected $casts = [
        'boosted_keywords' => 'array',
        'metadata' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class, 'agent_id');
    }
}