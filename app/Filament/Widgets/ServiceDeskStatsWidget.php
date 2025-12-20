<?php

namespace App\Filament\Widgets;

use App\Models\ServiceCase;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Number;

class ServiceDeskStatsWidget extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '60s';

    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        try {
            // Cache for 5 minutes
            $cacheMinute = floor(now()->minute / 5) * 5;
            $cacheKey = 'service-desk-stats-' . now()->format('Y-m-d-H') . '-' . str_pad($cacheMinute, 2, '0', STR_PAD_LEFT);

            return Cache::remember($cacheKey, 300, function () {
                // Open Cases (new, open, pending)
                $openCases = ServiceCase::open()->count();
                $openCasesTrend = $this->getOpenCasesTrend();

                // Critical Cases (open + critical priority)
                $criticalCases = ServiceCase::open()
                    ->where('priority', ServiceCase::PRIORITY_CRITICAL)
                    ->count();

                // Cases created today
                $casesToday = ServiceCase::whereDate('created_at', today())->count();
                $casesYesterday = ServiceCase::whereDate('created_at', today()->subDay())->count();
                $casesTodayChange = $casesYesterday > 0
                    ? (($casesToday - $casesYesterday) / $casesYesterday) * 100
                    : 0;

                // Average Resolution Time (in hours)
                $avgResolutionTime = $this->getAverageResolutionTime();
                $avgResolutionFormatted = $this->formatHours($avgResolutionTime);

                // SLA Violations (overdue cases)
                $slaViolations = ServiceCase::where(function ($query) {
                    $query->where('sla_resolution_due_at', '<', now())
                          ->orWhere('sla_response_due_at', '<', now());
                })
                ->whereIn('status', [
                    ServiceCase::STATUS_NEW,
                    ServiceCase::STATUS_OPEN,
                    ServiceCase::STATUS_PENDING,
                ])
                ->count();

                // Output Failures
                $outputFailures = ServiceCase::where('output_status', ServiceCase::OUTPUT_FAILED)->count();

                // Case Distribution by Type
                $casesByType = [
                    'incident' => ServiceCase::where('case_type', ServiceCase::TYPE_INCIDENT)->whereDate('created_at', '>=', today()->subDays(7))->count(),
                    'request' => ServiceCase::where('case_type', ServiceCase::TYPE_REQUEST)->whereDate('created_at', '>=', today()->subDays(7))->count(),
                    'inquiry' => ServiceCase::where('case_type', ServiceCase::TYPE_INQUIRY)->whereDate('created_at', '>=', today()->subDays(7))->count(),
                ];

                return [
                    Stat::make('Offene Cases', Number::format($openCases))
                        ->description($this->getOpenCasesDescription($openCases))
                        ->descriptionIcon('heroicon-m-ticket')
                        ->chart($openCasesTrend)
                        ->color($this->getOpenCasesColor($openCases))
                        ->extraAttributes([
                            'title' => "Aktuell offene Service Cases\nNeu, Offen, Wartend",
                        ]),

                    Stat::make('Kritische Cases', Number::format($criticalCases))
                        ->description($criticalCases > 0 ? 'Sofortige Aufmerksamkeit erforderlich' : 'Keine kritischen Cases')
                        ->descriptionIcon($criticalCases > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                        ->color($this->getCriticalCasesColor($criticalCases))
                        ->extraAttributes([
                            'title' => "Cases mit kritischer Priorität\nOffene Cases: {$openCases}",
                        ]),

                    Stat::make('Heute erstellt', Number::format($casesToday))
                        ->description($this->getCasesTodayDescription($casesToday, $casesYesterday, $casesTodayChange))
                        ->descriptionIcon($this->getCasesTodayIcon($casesTodayChange))
                        ->color($this->getCasesTodayColor($casesToday))
                        ->extraAttributes([
                            'title' => "Heute: {$casesToday}\nGestern: {$casesYesterday}\nÄnderung: " . round($casesTodayChange, 1) . "%",
                        ]),

                    Stat::make('⌀ Bearbeitungszeit', $avgResolutionFormatted)
                        ->description($this->getResolutionTimeDescription($avgResolutionTime))
                        ->descriptionIcon('heroicon-m-clock')
                        ->color($this->getResolutionTimeColor($avgResolutionTime))
                        ->extraAttributes([
                            'title' => "Durchschnittliche Zeit bis zur Lösung\nLetzte 30 Tage",
                        ]),

                    Stat::make('SLA Verstöße', Number::format($slaViolations))
                        ->description($slaViolations > 0 ? 'Cases mit überschrittener Deadline' : 'Alle Deadlines eingehalten')
                        ->descriptionIcon($slaViolations > 0 ? 'heroicon-m-exclamation-circle' : 'heroicon-m-check-badge')
                        ->color($this->getSlaViolationsColor($slaViolations))
                        ->extraAttributes([
                            'title' => "SLA Verstöße\nResponse oder Resolution überschritten",
                        ]),

                    Stat::make('Output Fehler', Number::format($outputFailures))
                        ->description($outputFailures > 0 ? 'Cases mit fehlgeschlagener Zustellung' : 'Alle Outputs erfolgreich')
                        ->descriptionIcon($outputFailures > 0 ? 'heroicon-m-x-circle' : 'heroicon-m-check-circle')
                        ->color($outputFailures > 0 ? 'danger' : 'success')
                        ->extraAttributes([
                            'title' => "Fehlgeschlagene Email/Webhook Zustellungen",
                        ]),
                ];
            });

        } catch (\Exception $e) {
            \Log::error('ServiceDeskStatsWidget Error: ' . $e->getMessage());
            return [
                Stat::make('Fehler', 'Widget-Fehler')
                    ->description('Bitte neu laden')
                    ->color('danger'),
            ];
        }
    }

    /**
     * Get trend data for open cases (last 7 days)
     */
    protected function getOpenCasesTrend(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $count = ServiceCase::open()
                ->whereDate('created_at', '<=', $date)
                ->count();
            $data[] = $count;
        }
        return $data;
    }

    /**
     * Get average resolution time in hours (last 30 days)
     */
    protected function getAverageResolutionTime(): float
    {
        $resolvedCases = ServiceCase::where('status', ServiceCase::STATUS_RESOLVED)
            ->whereDate('updated_at', '>=', today()->subDays(30))
            ->get();

        if ($resolvedCases->isEmpty()) {
            return 0;
        }

        $totalHours = 0;
        foreach ($resolvedCases as $case) {
            $hours = $case->created_at->diffInHours($case->updated_at);
            $totalHours += $hours;
        }

        return $totalHours / $resolvedCases->count();
    }

    /**
     * Format hours to human-readable string
     */
    protected function formatHours(float $hours): string
    {
        if ($hours < 1) {
            return round($hours * 60) . ' Min';
        }
        if ($hours < 24) {
            return round($hours, 1) . ' Std';
        }
        $days = floor($hours / 24);
        $remainingHours = round($hours % 24);
        return "{$days}d {$remainingHours}h";
    }

    /**
     * Get description for open cases
     */
    protected function getOpenCasesDescription(int $count): string
    {
        if ($count === 0) {
            return 'Keine offenen Cases';
        }
        if ($count === 1) {
            return '1 Case erfordert Bearbeitung';
        }
        return "{$count} Cases erfordern Bearbeitung";
    }

    /**
     * Get color for open cases stat
     */
    protected function getOpenCasesColor(int $count): string
    {
        if ($count === 0) return 'success';
        if ($count <= 5) return 'primary';
        if ($count <= 10) return 'warning';
        return 'danger';
    }

    /**
     * Get color for critical cases stat
     */
    protected function getCriticalCasesColor(int $count): string
    {
        if ($count === 0) return 'success';
        if ($count <= 2) return 'warning';
        return 'danger';
    }

    /**
     * Get description for cases created today
     */
    protected function getCasesTodayDescription(int $today, int $yesterday, float $change): string
    {
        if ($yesterday === 0) {
            return 'Keine Vergleichsdaten';
        }
        $changeFormatted = round(abs($change), 1);
        if ($change > 0) {
            return "+{$changeFormatted}% vs. gestern";
        }
        if ($change < 0) {
            return "-{$changeFormatted}% vs. gestern";
        }
        return 'Gleichbleibend';
    }

    /**
     * Get icon for cases today stat
     */
    protected function getCasesTodayIcon(float $change): string
    {
        if ($change > 0) return 'heroicon-m-arrow-trending-up';
        if ($change < 0) return 'heroicon-m-arrow-trending-down';
        return 'heroicon-m-minus';
    }

    /**
     * Get color for cases today stat
     */
    protected function getCasesTodayColor(int $count): string
    {
        if ($count === 0) return 'gray';
        if ($count <= 3) return 'primary';
        if ($count <= 7) return 'info';
        return 'warning';
    }

    /**
     * Get description for resolution time
     */
    protected function getResolutionTimeDescription(float $hours): string
    {
        if ($hours === 0) return 'Keine gelösten Cases';
        if ($hours < 4) return 'Sehr schnell';
        if ($hours < 24) return 'Innerhalb eines Tages';
        if ($hours < 48) return '1-2 Tage';
        return 'Über 2 Tage';
    }

    /**
     * Get color for resolution time stat
     */
    protected function getResolutionTimeColor(float $hours): string
    {
        if ($hours === 0) return 'gray';
        if ($hours < 24) return 'success';
        if ($hours < 48) return 'primary';
        return 'warning';
    }

    /**
     * Get color for SLA violations stat
     */
    protected function getSlaViolationsColor(int $count): string
    {
        if ($count === 0) return 'success';
        if ($count <= 2) return 'warning';
        return 'danger';
    }
}
