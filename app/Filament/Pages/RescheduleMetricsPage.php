<?php

namespace App\Filament\Pages;

use App\Services\Metrics\AppointmentMetricsService;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;

/**
 * ADR-005: Reschedule-First Metrics Dashboard
 *
 * Detailed view of reschedule-first flow metrics with filtering
 */
class RescheduleMetricsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.pages.reschedule-metrics-page';

    protected static ?string $navigationLabel = 'Reschedule Metriken';

    protected static ?string $title = 'ADR-005: Reschedule-First Metriken';

    protected static ?string $navigationGroup = 'Analytics';

    protected static ?int $navigationSort = 90;

    public ?array $filters = [];

    public function mount(): void
    {
        $this->filters = [
            'start_date' => Carbon::now()->subDays(30)->toDateString(),
            'end_date' => Carbon::now()->toDateString(),
            'branch_id' => null,
            'service_id' => null,
        ];
    }

    public function getMetrics(): array
    {
        $metricsService = app(AppointmentMetricsService::class);

        return $metricsService->getRescheduleFirstMetrics(
            startDate: Carbon::parse($this->filters['start_date']),
            endDate: Carbon::parse($this->filters['end_date']),
            branchId: $this->filters['branch_id'],
            serviceId: $this->filters['service_id']
        );
    }

    public function getDetailedBreakdown()
    {
        $metricsService = app(AppointmentMetricsService::class);

        return $metricsService->getDetailedBreakdown(
            startDate: Carbon::parse($this->filters['start_date']),
            endDate: Carbon::parse($this->filters['end_date'])
        );
    }

    public function getMetricsByBranch()
    {
        $metricsService = app(AppointmentMetricsService::class);

        return $metricsService->getMetricsByBranch(
            startDate: Carbon::parse($this->filters['start_date']),
            endDate: Carbon::parse($this->filters['end_date'])
        );
    }
}
