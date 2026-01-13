<?php

namespace App\Filament\Widgets\CrmAppointments;

use App\Filament\Widgets\CrmAppointments\Concerns\HasCrmFilters;
use App\Models\Call;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Calls by Status Chart
 *
 * Doughnut chart showing distribution of calls by status.
 * SECURITY: All queries filtered by company_id for multi-tenancy.
 * FEATURE: Supports company, agent, and time_range filters.
 */
class CallsByStatusChart extends ChartWidget
{
    use InteractsWithPageFilters;
    use HasCrmFilters;

    protected static ?string $heading = 'Anrufe nach Status';
    protected static bool $isLazy = true;
    protected static ?string $maxHeight = '350px';

    protected function getData(): array
    {
        try {
            $companyId = $this->getEffectiveCompanyId();
            $agentId = $this->getEffectiveAgentId();
            $timeRangeStart = $this->getTimeRangeStart();
            $cacheKey = "crm_calls_by_status_{$this->getFilterCacheKey()}";

            $data = Cache::remember($cacheKey, 60, function () use ($companyId, $agentId, $timeRangeStart) {
                $query = Call::query()
                    ->select('status', DB::raw('COUNT(*) as count'))
                    ->groupBy('status');

                if ($companyId) {
                    $query->where('company_id', $companyId);
                }
                if ($agentId) {
                    $query->where('retell_agent_id', $agentId);
                }
                if ($timeRangeStart) {
                    $query->where('created_at', '>=', $timeRangeStart);
                }

                return $query->pluck('count', 'status')->toArray();
            });

            // Define status labels and colors
            $statusConfig = [
                'completed' => ['label' => 'Erfolgreich', 'color' => '#10B981'],
                'failed' => ['label' => 'Fehlgeschlagen', 'color' => '#EF4444'],
                'ongoing' => ['label' => 'Laufend', 'color' => '#3B82F6'],
                'in-progress' => ['label' => 'In Bearbeitung', 'color' => '#3B82F6'],
                'in_progress' => ['label' => 'In Bearbeitung', 'color' => '#3B82F6'],
                'active' => ['label' => 'Aktiv', 'color' => '#8B5CF6'],
                'missed' => ['label' => 'Verpasst', 'color' => '#F59E0B'],
                'busy' => ['label' => 'Besetzt', 'color' => '#F97316'],
                'no_answer' => ['label' => 'Keine Antwort', 'color' => '#6B7280'],
                'error' => ['label' => 'Fehler', 'color' => '#DC2626'],
            ];

            $labels = [];
            $counts = [];
            $colors = [];

            foreach ($data as $status => $count) {
                $config = $statusConfig[$status] ?? ['label' => ucfirst($status), 'color' => '#6B7280'];
                $labels[] = $config['label'];
                $counts[] = $count;
                $colors[] = $config['color'];
            }

            return [
                'datasets' => [
                    [
                        'label' => 'Anrufe',
                        'data' => $counts,
                        'backgroundColor' => $colors,
                        'borderColor' => '#ffffff',
                        'borderWidth' => 2,
                    ],
                ],
                'labels' => $labels,
            ];
        } catch (\Throwable $e) {
            Log::error('[CallsByStatusChart] getData failed', ['error' => $e->getMessage()]);
            return ['datasets' => [], 'labels' => []];
        }
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 15,
                    ],
                ],
            ],
            'cutout' => '60%',
            'maintainAspectRatio' => false,
        ];
    }
}
