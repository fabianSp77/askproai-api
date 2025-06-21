<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CookieConsent extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'session_id',
        'ip_address',
        'user_agent',
        'necessary_cookies',
        'functional_cookies',
        'analytics_cookies',
        'marketing_cookies',
        'consent_details',
        'consented_at',
        'withdrawn_at',
    ];

    protected $casts = [
        'necessary_cookies' => 'boolean',
        'functional_cookies' => 'boolean',
        'analytics_cookies' => 'boolean',
        'marketing_cookies' => 'boolean',
        'consent_details' => 'array',
        'consented_at' => 'datetime',
        'withdrawn_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function withdraw(): void
    {
        $this->update(['withdrawn_at' => now()]);
    }

    public function isActive(): bool
    {
        return is_null($this->withdrawn_at);
    }

    public function getConsentedCategoriesAttribute(): array
    {
        $categories = [];
        
        if ($this->necessary_cookies) $categories[] = 'necessary';
        if ($this->functional_cookies) $categories[] = 'functional';
        if ($this->analytics_cookies) $categories[] = 'analytics';
        if ($this->marketing_cookies) $categories[] = 'marketing';
        
        return $categories;
    }

    public static function createFromRequest(array $data): self
    {
        return self::create([
            'customer_id' => auth('customer')->id(),
            'session_id' => session()->getId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'necessary_cookies' => true, // Always true
            'functional_cookies' => $data['functional_cookies'] ?? false,
            'analytics_cookies' => $data['analytics_cookies'] ?? false,
            'marketing_cookies' => $data['marketing_cookies'] ?? false,
            'consent_details' => [
                'version' => config('gdpr.cookie_policy_version', '1.0'),
                'language' => app()->getLocale(),
                'timestamp' => now()->toIso8601String(),
            ],
            'consented_at' => now(),
        ]);
    }
}