<?php

namespace App\Filament\Widgets;

use App\Models\PolicyConfiguration;
use App\Models\AppointmentModificationStat;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PolicyEffectivenessWidget extends ChartWidget
{
    protected static ?string $heading = 'Policy-Effektivität nach Konfiguration';

    protected static ?int $sort = 8;

    protected static ?string $pollingInterval = '120s';

    protected int | string | array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $companyId = auth()->user()->company_id;

        // Get all active policy configurations
        $policies = PolicyConfiguration::where('company_id', $companyId)
            ->where('is_active', true)
            ->get();

        if ($policies->isEmpty()) {
            return [
                'datasets' => [
                    [
                        'label' => 'Keine Policies',
                        'data' => [0],
                        'borderColor' => 'rgb(156, 163, 175)',
                        'backgroundColor' => 'rgba(156, 163, 175, 0.1)',
                    ],
                ],
                'labels' => ['Keine Daten'],
            ];
        }

        $datasets = [];
        $labels = [];

        // Generate last 14 days for effectiveness tracking
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('d.m');
        }

        // Color palette for different policy types
        $colors = [
            'cancellation' => ['rgb(239, 68, 68)', 'rgba(239, 68, 68, 0.1)'],
            'reschedule' => ['rgb(251, 191, 36)', 'rgba(251, 191, 36, 0.1)'],
            'no_show' => ['rgb(147, 51, 234)', 'rgba(147, 51, 234, 0.1)'],
            'late_arrival' => ['rgb(59, 130, 246)', 'rgba(59, 130, 246, 0.1)'],
            'payment' => ['rgb(16, 185, 129)', 'rgba(16, 185, 129, 0.1)'],
        ];

        // Get unique policy types
        $policyTypes = $policies->pluck('policy_type')->unique();

        // SECURITY: Whitelist allowed policy types to prevent SQL injection
        $allowedPolicyTypes = ['cancellation', 'reschedule', 'no_show', 'late_arrival', 'payment'];

        foreach ($policyTypes as $policyType) {
            // SECURITY: Validate policy type against whitelist
            $safePolicyType = in_array($policyType, $allowedPolicyTypes) ? $policyType : 'invalid';

            $data = [];

            for ($i = 13; $i >= 0; $i--) {
                $date = now()->subDays($i);

                // Count violations for this policy type on this date
                // SECURITY FIX: Use JSON_UNQUOTE with validated policy type
                $violationCount = AppointmentModificationStat::whereHas('customer', function ($query) use ($companyId) {
                        $query->where('company_id', $companyId);
                    })
                    ->where('stat_type', 'violation')
                    ->whereDate('created_at', $date)
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.policy_type')) = ?", [$safePolicyType])
                    ->sum('count');

                $data[] = $violationCount;
            }

            $color = $colors[$policyType] ?? ['rgb(107, 114, 128)', 'rgba(107, 114, 128, 0.1)'];

            $datasets[] = [
                'label' => ucfirst(str_replace('_', ' ', $policyType)),
                'data' => $data,
                'borderColor' => $color[0],
                'backgroundColor' => $color[1],
                'fill' => true,
                'tension' => 0.4,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'enabled' => true,
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
        ];
    }

    public function getColumnSpan(): string | array | int
    {
        return 'full';
    }
}
