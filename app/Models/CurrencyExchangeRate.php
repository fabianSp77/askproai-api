<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class CurrencyExchangeRate extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'from_currency',
        'to_currency',
        'rate',
        'source',
        'valid_from',
        'valid_until',
        'is_active',
        'metadata'
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'rate' => 'decimal:6'
    ];

    /**
     * Get the current active rate for a currency pair
     */
    public static function getCurrentRate(string $from, string $to): ?float
    {
        // Return 1 if same currency
        if ($from === $to) {
            return 1.0;
        }

        // Try to get from cache first
        $cacheKey = "exchange_rate_{$from}_{$to}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $rate = self::where('from_currency', $from)
            ->where('to_currency', $to)
            ->where('is_active', true)
            ->where('valid_from', '<=', now())
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>', now());
            })
            ->orderBy('valid_from', 'desc')
            ->first();

        if ($rate) {
            // Cache for 1 hour
            Cache::put($cacheKey, $rate->rate, 3600);
            return $rate->rate;
        }

        // Try reverse rate
        $reverseRate = self::where('from_currency', $to)
            ->where('to_currency', $from)
            ->where('is_active', true)
            ->where('valid_from', '<=', now())
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>', now());
            })
            ->orderBy('valid_from', 'desc')
            ->first();

        if ($reverseRate) {
            $calculatedRate = 1 / $reverseRate->rate;
            Cache::put($cacheKey, $calculatedRate, 3600);
            return $calculatedRate;
        }

        return null;
    }

    /**
     * Convert amount from one currency to another
     */
    public static function convert(float $amount, string $from, string $to): ?float
    {
        $rate = self::getCurrentRate($from, $to);
        return $rate !== null ? $amount * $rate : null;
    }

    /**
     * Convert cents from one currency to another
     */
    public static function convertCents(int $cents, string $from, string $to): ?int
    {
        $rate = self::getCurrentRate($from, $to);
        return $rate !== null ? (int)round($cents * $rate) : null;
    }

    /**
     * Scope to get active rates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('valid_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>', now());
            });
    }

    /**
     * Deactivate old rates when adding a new one
     */
    public static function deactivateOldRates(string $from, string $to): void
    {
        self::where('from_currency', $from)
            ->where('to_currency', $to)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'valid_until' => now()
            ]);
    }

    /**
     * Create or update exchange rate
     */
    public static function updateRate(string $from, string $to, float $rate, string $source = 'manual'): self
    {
        // Deactivate old rates
        self::deactivateOldRates($from, $to);

        // Clear cache
        Cache::forget("exchange_rate_{$from}_{$to}");

        // Create new rate
        return self::create([
            'from_currency' => $from,
            'to_currency' => $to,
            'rate' => $rate,
            'source' => $source,
            'valid_from' => now(),
            'valid_until' => null,
            'is_active' => true,
            'metadata' => [
                'updated_by' => auth()->id() ?? 'system',
                'updated_at' => now()->toIso8601String()
            ]
        ]);
    }

    /**
     * Seed default exchange rates
     */
    public static function seedDefaultRates(): void
    {
        // Default USD to EUR rate (update this regularly)
        self::updateRate('USD', 'EUR', 0.92, 'manual');
        self::updateRate('EUR', 'USD', 1.09, 'manual');

        // GBP rates
        self::updateRate('GBP', 'EUR', 1.16, 'manual');
        self::updateRate('EUR', 'GBP', 0.86, 'manual');
        self::updateRate('GBP', 'USD', 1.27, 'manual');
        self::updateRate('USD', 'GBP', 0.79, 'manual');
    }
}