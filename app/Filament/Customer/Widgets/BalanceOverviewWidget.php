<?php

namespace App\Filament\Customer\Widgets;

use App\Models\Transaction;
use App\Models\BalanceTopup;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

class BalanceOverviewWidget extends ChartWidget
{
    protected static ?string $heading = 'Kontoverlauf (30 Tage)';

    protected static ?int $sort = 3;

    protected static ?string $maxHeight = '300px';

    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = '300s';

    protected function getData(): array
    {
        $companyId = auth()->user()->company_id;

        // Get transactions for the last 30 days
        // Note: transactions table uses tenant_id, not company_id
        $transactions = Transaction::withoutGlobalScopes()
            ->where('tenant_id', $companyId)
            ->where('created_at', '>=', now()->subDays(30))
            ->orderBy('created_at', 'asc')
            ->get(['created_at', 'balance_after_cents']);

        $labels = [];
        $data = [];

        // Get the starting balance (30 days ago or earliest transaction)
        $startingBalance = Transaction::withoutGlobalScopes()
            ->where('tenant_id', $companyId)
            ->where('created_at', '<', now()->subDays(30))
            ->orderBy('created_at', 'desc')
            ->value('balance_after_cents') ?? 0;

        // Create daily balance data
        for ($i = 30; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $labels[] = $date->format('d.m.');

            // Get the last transaction for this day
            $dayTransaction = $transactions->filter(function($transaction) use ($date) {
                return $transaction->created_at->isSameDay($date);
            })->last();

            if ($dayTransaction) {
                $data[] = $dayTransaction->balance_after_cents / 100;
            } else {
                // Use the last known balance
                $data[] = end($data) !== false ? end($data) : ($startingBalance / 100);
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Kontostand (€)',
                    'data' => $data,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            let label = context.dataset.label || "";
                            if (label) {
                                label += ": ";
                            }
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat("de-DE", {
                                    style: "currency",
                                    currency: "EUR"
                                }).format(context.parsed.y);
                            }
                            return label;
                        }',
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) {
                            return new Intl.NumberFormat("de-DE", {
                                style: "currency",
                                currency: "EUR",
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0
                            }).format(value);
                        }',
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }

    protected function getFooter(): ?string
    {
        $companyId = auth()->user()->company_id;

        // Current balance (transactions uses tenant_id)
        $currentBalance = Transaction::withoutGlobalScopes()
            ->where('tenant_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->value('balance_after_cents') ?? 0;

        // Last topup (balance_topups uses company_id)
        $lastTopup = BalanceTopup::where('company_id', $companyId)
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->first();

        // Average monthly usage (transactions uses tenant_id)
        $monthlyUsage = Transaction::withoutGlobalScopes()
            ->where('tenant_id', $companyId)
            ->where('type', Transaction::TYPE_USAGE)
            ->where('created_at', '>=', now()->subMonth())
            ->sum('amount_cents');

        $monthlyUsageFormatted = Number::currency(abs($monthlyUsage) / 100, 'EUR', 'de');

        $stats = [
            'Aktueller Stand: ' . Number::currency($currentBalance / 100, 'EUR', 'de'),
            'Letzte Aufladung: ' . ($lastTopup ? Number::currency($lastTopup->amount_cents / 100, 'EUR', 'de') . ' am ' . $lastTopup->created_at->format('d.m.Y') : 'Keine'),
            'Ø Monatlicher Verbrauch: ' . $monthlyUsageFormatted,
        ];

        return implode(' | ', $stats);
    }
}
