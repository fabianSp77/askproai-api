<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BalanceBonusTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'min_amount',
        'max_amount',
        'bonus_percentage',
        'name',
        'description',
        'is_active',
        'valid_from',
        'valid_until',
    ];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'bonus_percentage' => 'decimal:2',
        'is_active' => 'boolean',
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];

    /**
     * Get the bonus tier for a specific amount
     */
    public static function getBonusForAmount(float $amount): array
    {
        $tier = self::where('is_active', true)
            ->where('min_amount', '<=', $amount)
            ->where(function ($query) use ($amount) {
                $query->whereNull('max_amount')
                    ->orWhere('max_amount', '>=', $amount);
            })
            ->where(function ($query) {
                $query->whereNull('valid_from')
                    ->orWhereDate('valid_from', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhereDate('valid_until', '>=', now());
            })
            ->orderBy('bonus_percentage', 'desc')
            ->first();

        if (!$tier) {
            return [
                'percentage' => 0,
                'bonus_amount' => 0,
                'total_amount' => $amount,
                'tier_name' => 'Kein Bonus',
                'refundable_amount' => $amount,
            ];
        }

        $bonusAmount = ($amount * $tier->bonus_percentage) / 100;
        $totalAmount = $amount + $bonusAmount;

        return [
            'percentage' => $tier->bonus_percentage,
            'bonus_amount' => round($bonusAmount, 2),
            'total_amount' => round($totalAmount, 2),
            'tier_name' => $tier->name,
            'tier_description' => $tier->description,
            'refundable_amount' => $amount, // Nur der eingezahlte Betrag ist erstattungsfähig
        ];
    }

    /**
     * Get all active tiers formatted for display
     */
    public static function getActiveTiersForDisplay(): array
    {
        return self::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhereDate('valid_until', '>=', now());
            })
            ->orderBy('min_amount')
            ->get()
            ->map(function ($tier) {
                $minFormatted = number_format($tier->min_amount, 0, ',', '.');
                $maxFormatted = $tier->max_amount
                    ? number_format($tier->max_amount, 0, ',', '.') . '€'
                    : 'unbegrenzt';

                return [
                    'range' => $tier->max_amount
                        ? "{$minFormatted}€ - {$maxFormatted}"
                        : "ab {$minFormatted}€",
                    'bonus' => $tier->bonus_percentage . '%',
                    'name' => $tier->name,
                    'example' => $tier->min_amount >= 100
                        ? number_format($tier->min_amount + ($tier->min_amount * $tier->bonus_percentage / 100), 0, ',', '.') . '€ Guthaben bei ' . $minFormatted . '€ Aufladung'
                        : null,
                ];
            })
            ->toArray();
    }
}