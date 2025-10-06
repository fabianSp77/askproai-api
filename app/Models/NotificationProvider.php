<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class NotificationProvider extends Model
{
    use BelongsToCompany;
    protected $fillable = [
        'company_id',
        'name',
        'type',
        'channel',
        'credentials',
        'config',
        'is_default',
        'is_active',
        'priority',
        'balance',
        'rate_limit',
        'allowed_countries',
        'statistics'
    ];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'config' => 'array',
        'allowed_countries' => 'array',
        'statistics' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'balance' => 'decimal:2'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeForChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function hasBalance(): bool
    {
        if (!$this->balance) {
            return true; // No balance tracking
        }

        return $this->balance > ($this->config['min_balance'] ?? 0);
    }

    public function isRateLimited(): bool
    {
        if (!$this->rate_limit) {
            return false;
        }

        // Check current usage from cache
        $key = "{$this->channel}_rate_limit:{$this->id}";
        $current = cache($key, 0);

        return $current >= $this->rate_limit;
    }
}