<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetellConfiguration extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'webhook_url',
        'webhook_secret',
        'webhook_events',
        'custom_functions',
        'last_tested_at',
        'test_status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'webhook_events' => 'array',
        'custom_functions' => 'array',
        'last_tested_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'webhook_secret',
    ];

    /**
     * Get the company that owns the configuration.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Check if webhook test is recent (within last hour)
     */
    public function hasRecentTest(): bool
    {
        return $this->last_tested_at && 
               $this->last_tested_at->isAfter(now()->subHour());
    }

    /**
     * Get enabled custom functions
     */
    public function getEnabledCustomFunctions(): array
    {
        if (!$this->custom_functions) {
            return [];
        }

        return collect($this->custom_functions)
            ->where('enabled', true)
            ->values()
            ->toArray();
    }

    /**
     * Find custom function by name
     */
    public function findCustomFunction(string $name): ?array
    {
        if (!$this->custom_functions) {
            return null;
        }

        return collect($this->custom_functions)
            ->firstWhere('name', $name);
    }
}