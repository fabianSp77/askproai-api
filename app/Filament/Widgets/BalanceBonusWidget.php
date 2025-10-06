<?php

namespace App\Filament\Widgets;

use App\Models\BalanceBonusTier;
use App\Models\Tenant;
use Filament\Widgets\Widget;
use Illuminate\Support\HtmlString;

class BalanceBonusWidget extends Widget
{
    protected static string $view = 'filament.widgets.balance-bonus-widget';

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()->hasRole(['admin', 'super-admin', 'tenant']);
    }

    public function getHeading(): string
    {
        return '💰 Guthaben-Bonus-System';
    }

    public function getDescription(): string
    {
        return 'Je mehr Sie aufladen, desto mehr Bonus erhalten Sie!';
    }

    public function getBonusTiers(): array
    {
        return BalanceBonusTier::where('is_active', true)
            ->orderBy('min_amount')
            ->get()
            ->map(function ($tier) {
                $examples = $this->getExamples($tier);

                return [
                    'name' => $tier->name,
                    'badge_color' => $this->getBadgeColor($tier->name),
                    'range' => $this->formatRange($tier),
                    'bonus' => $tier->bonus_percentage . '%',
                    'description' => $tier->description,
                    'examples' => $examples,
                ];
            })
            ->toArray();
    }

    protected function getExamples($tier): array
    {
        $examples = [];

        if ($tier->bonus_percentage > 0) {
            // Zeige 2-3 Beispiele pro Tier
            if ($tier->min_amount >= 100) {
                $amount = $tier->min_amount;
                $bonus = ($amount * $tier->bonus_percentage) / 100;
                $examples[] = [
                    'amount' => number_format($amount, 0, ',', '.') . '€',
                    'bonus' => '+' . number_format($bonus, 0, ',', '.') . '€',
                    'total' => number_format($amount + $bonus, 0, ',', '.') . '€',
                ];
            }

            // Mittleres Beispiel
            if ($tier->max_amount && $tier->max_amount > $tier->min_amount) {
                $amount = ($tier->min_amount + $tier->max_amount) / 2;
                $bonus = ($amount * $tier->bonus_percentage) / 100;
                $examples[] = [
                    'amount' => number_format($amount, 0, ',', '.') . '€',
                    'bonus' => '+' . number_format($bonus, 0, ',', '.') . '€',
                    'total' => number_format($amount + $bonus, 0, ',', '.') . '€',
                ];
            }
        }

        return $examples;
    }

    protected function formatRange($tier): string
    {
        $min = number_format($tier->min_amount, 0, ',', '.');

        if ($tier->max_amount === null) {
            return "ab {$min}€";
        }

        $max = number_format($tier->max_amount, 0, ',', '.');
        return "{$min}€ - {$max}€";
    }

    protected function getBadgeColor(string $name): string
    {
        return match(strtolower($name)) {
            'bronze' => 'warning',
            'silber', 'silver' => 'gray',
            'gold' => 'warning',
            'platin', 'platinum' => 'info',
            'diamond' => 'success',
            default => 'primary',
        };
    }

    public function getCurrentBalance(): array
    {
        if (!auth()->user()->tenant) {
            return [
                'balance' => 0,
                'bonus' => 0,
                'total' => 0,
            ];
        }

        $tenant = auth()->user()->tenant;

        return [
            'balance' => number_format($tenant->balance ?? 0, 2, ',', '.'),
            'bonus' => number_format($tenant->bonus_balance ?? 0, 2, ',', '.'),
            'total' => number_format(($tenant->balance ?? 0) + ($tenant->bonus_balance ?? 0), 2, ',', '.'),
        ];
    }

    public function getImportantNotes(): array
    {
        return [
            '✅ Bonus-Guthaben wird automatisch bei Aufladung gutgeschrieben',
            '⚡ Bonus-Guthaben wird zuerst für Anrufe verwendet',
            '⚠️ Bei Rückerstattungen wird nur der eingezahlte Betrag erstattet (ohne Bonus)',
            '🔒 Bonus-Guthaben kann nicht ausgezahlt werden',
            '📅 Bonus-Aktionen können zeitlich begrenzt sein',
        ];
    }
}