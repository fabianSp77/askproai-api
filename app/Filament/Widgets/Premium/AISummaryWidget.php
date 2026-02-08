<?php

namespace App\Filament\Widgets\Premium;

use App\Filament\Widgets\Premium\Concerns\HasPremiumStyling;
use App\Models\Appointment;
use App\Models\Call;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Log;

/**
 * AI Summary Widget
 *
 * Displays an AI-generated summary of business performance metrics.
 * Shows key KPIs and trends in a natural language format.
 */
class AISummaryWidget extends Widget
{
    use InteractsWithPageFilters;
    use HasPremiumStyling;

    protected static string $view = 'filament.widgets.premium.ai-summary';
    protected static bool $isLazy = false;
    protected int|string|array $columnSpan = 'full';

    /**
     * Generate AI summary text based on metrics.
     */
    public function generateAISummary(): string
    {
        try {
            $metrics = $this->getMetrics();

            // Build natural language summary
            $parts = [];

            // Revenue insight
            if ($metrics['revenue'] > 0) {
                $revenueFormatted = number_format($metrics['revenue'] / 100, 0, ',', '.');
                $revenueChange = $metrics['revenueChange'];

                if ($revenueChange > 10) {
                    $parts[] = "Der Umsatz liegt bei <strong>{$revenueFormatted} €</strong> - ein starkes Wachstum von {$revenueChange}% gegenüber dem Vormonat.";
                } elseif ($revenueChange > 0) {
                    $parts[] = "Der Umsatz beträgt <strong>{$revenueFormatted} €</strong> mit einem leichten Anstieg von {$revenueChange}%.";
                } elseif ($revenueChange < 0) {
                    $parts[] = "Der Umsatz liegt bei <strong>{$revenueFormatted} €</strong>. Ein Rückgang von " . abs($revenueChange) . "% gegenüber dem Vormonat.";
                } else {
                    $parts[] = "Der Umsatz liegt stabil bei <strong>{$revenueFormatted} €</strong>.";
                }
            }

            // Calls insight
            if ($metrics['totalCalls'] > 0) {
                $conversionRate = $metrics['totalCalls'] > 0
                    ? round(($metrics['successfulCalls'] / $metrics['totalCalls']) * 100, 1)
                    : 0;

                if ($conversionRate >= 70) {
                    $parts[] = "Die KI hat <strong>{$metrics['totalCalls']} Anrufe</strong> bearbeitet mit einer ausgezeichneten Konversionsrate von {$conversionRate}%.";
                } elseif ($conversionRate >= 50) {
                    $parts[] = "Bei <strong>{$metrics['totalCalls']} Anrufen</strong> wurde eine solide Konversionsrate von {$conversionRate}% erreicht.";
                } else {
                    $parts[] = "Von <strong>{$metrics['totalCalls']} Anrufen</strong> wurden {$conversionRate}% erfolgreich konvertiert.";
                }
            }

            // Appointments insight
            if ($metrics['appointments'] > 0) {
                $parts[] = "<strong>{$metrics['appointments']} Termine</strong> wurden in diesem Zeitraum gebucht.";
            }

            if (empty($parts)) {
                return 'Noch keine Daten für den ausgewählten Zeitraum verfügbar.';
            }

            return implode(' ', $parts);
        } catch (\Throwable $e) {
            Log::error('[AISummaryWidget] generateAISummary failed', ['error' => $e->getMessage()]);
            return 'Zusammenfassung konnte nicht geladen werden.';
        }
    }

    /**
     * Get metrics for summary generation.
     * Note: Caching disabled for reactive filter updates.
     */
    protected function getMetrics(): array
    {
        $companyId = $this->getEffectiveCompanyId();
        $timeRangeStart = $this->getTimeRangeStart();
        $timeRangeEnd = $this->getTimeRangeEnd();

        // Revenue (from calls)
        $revenueQuery = Call::query();
        if ($companyId) {
            $revenueQuery->where('company_id', $companyId);
        }
        if ($timeRangeStart) {
            $revenueQuery->where('created_at', '>=', $timeRangeStart);
        }
        if ($timeRangeEnd) {
            $revenueQuery->where('created_at', '<=', $timeRangeEnd);
        }
        $revenue = $revenueQuery->sum('total_profit') ?? 0;

        // Previous period revenue for comparison
        $prevStart = $timeRangeStart ? $timeRangeStart->copy()->subMonth() : now()->subMonth()->startOfMonth();
        $prevEnd = $timeRangeEnd ? $timeRangeEnd->copy()->subMonth() : now()->subMonth()->endOfMonth();

        $prevRevenueQuery = Call::query()
            ->whereBetween('created_at', [$prevStart, $prevEnd]);
        if ($companyId) {
            $prevRevenueQuery->where('company_id', $companyId);
        }
        $prevRevenue = $prevRevenueQuery->sum('total_profit') ?? 0;
        $revenueChange = $prevRevenue > 0 ? round((($revenue - $prevRevenue) / $prevRevenue) * 100, 1) : 0;

        // Calls
        $callsQuery = Call::query();
        if ($companyId) {
            $callsQuery->where('company_id', $companyId);
        }
        if ($timeRangeStart) {
            $callsQuery->where('created_at', '>=', $timeRangeStart);
        }
        if ($timeRangeEnd) {
            $callsQuery->where('created_at', '<=', $timeRangeEnd);
        }
        $totalCalls = (clone $callsQuery)->count();
        $successfulCalls = (clone $callsQuery)->where('status', 'completed')->count();

        // Appointments
        $appointmentsQuery = Appointment::query()
            ->whereNull('cancelled_at');
        if ($companyId) {
            $appointmentsQuery->where('company_id', $companyId);
        }
        if ($timeRangeStart) {
            $appointmentsQuery->where('start_time', '>=', $timeRangeStart);
        }
        if ($timeRangeEnd) {
            $appointmentsQuery->where('start_time', '<=', $timeRangeEnd);
        }
        $appointments = $appointmentsQuery->count();

        return [
            'revenue' => $revenue,
            'revenueChange' => $revenueChange,
            'totalCalls' => $totalCalls,
            'successfulCalls' => $successfulCalls,
            'appointments' => $appointments,
        ];
    }

    /**
     * Handle filter updates - refresh the widget when filters change.
     */
    public function updatedFilters(): void
    {
        // Widget will automatically re-render with new data
    }

    /**
     * Get mini KPIs for footer.
     * Note: Caching disabled for reactive filter updates.
     */
    public function getMiniKpis(): array
    {
        try {
            $companyId = $this->getEffectiveCompanyId();
            $timeRangeStart = $this->getTimeRangeStart();

            // Average call duration
            $avgDurationQuery = Call::query();
            if ($companyId) {
                $avgDurationQuery->where('company_id', $companyId);
            }
            if ($timeRangeStart) {
                $avgDurationQuery->where('created_at', '>=', $timeRangeStart);
            }
            $avgDuration = $avgDurationQuery->avg('duration_seconds') ?? 0;

            // Total call time
            $totalDuration = $avgDurationQuery->sum('duration_seconds') ?? 0;

            // Today's calls
            $todaysCallsQuery = Call::query()->whereDate('created_at', now());
            if ($companyId) {
                $todaysCallsQuery->where('company_id', $companyId);
            }
            $todaysCalls = $todaysCallsQuery->count();

            return [
                [
                    'icon' => 'heroicon-o-clock',
                    'iconColor' => 'primary',
                    'label' => 'Ø Anrufdauer',
                    'value' => gmdate('i:s', (int) $avgDuration),
                ],
                [
                    'icon' => 'heroicon-o-phone',
                    'iconColor' => 'success',
                    'label' => 'Anrufe heute',
                    'value' => (string) $todaysCalls,
                ],
                [
                    'icon' => 'heroicon-o-chart-bar',
                    'iconColor' => 'purple',
                    'label' => 'Gesamtzeit',
                    'value' => gmdate('H:i', (int) $totalDuration),
                ],
            ];
        } catch (\Throwable $e) {
            return [];
        }
    }
}
