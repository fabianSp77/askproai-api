<?php

namespace App\Filament\Admin\Widgets;

use App\Services\Analytics\RoiCalculationService;
use Carbon\Carbon;

class FinancialIntelligenceWidget extends FilterableWidget
{
    protected static string $view = 'filament.admin.widgets.financial-intelligence-widget-v2';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 2,
        'xl' => 2,
    ];

    protected static ?int $sort = 2;

    protected static ?string $pollingInterval = '60s';

    public ?int $companyId = null;

    public ?int $branchId = null;

    protected function getRoiService(): RoiCalculationService
    {
        return app(RoiCalculationService::class);
    }

    public function mount(): void
    {
        parent::mount();
        $this->companyId = auth()->user()?->company_id;
        $this->branchId = $this->branchFilter !== 'all' ? $this->branchFilter : null;
    }

    protected function getListeners(): array
    {
        return [
            'branchFilterUpdated' => 'handleBranchFilterUpdate',
            'periodChanged' => 'handlePeriodChange',
        ];
    }

    public function handleBranchFilterUpdate($branchId): void
    {
        $this->branchId = $branchId;
    }

    public function handlePeriodChange($period): void
    {
        $this->period = $period;
    }

    protected function getViewData(): array
    {
        $company = auth()->user()?->company;
        if (! $company) {
            return array_merge(parent::getViewData(), [
                'roi' => ['summary' => ['roi_percentage' => 0, 'revenue' => 0, 'cost' => 0, 'profit' => 0]],
                'trend' => [],
                'costPerBooking' => 0,
                'expectedValuePerCall' => 0,
                'period' => $this->dateFilter,
                'periodLabel' => $this->getPeriodLabel(),
            ]);
        }

        $branch = $this->branchId ? $company->branches()->find($this->branchId) : null;

        [$startDate, $endDate] = $this->getDateRange();

        // Get ROI data
        $roiData = $this->getRoiService()->calculateRoi($company, $startDate, $endDate, $branch);

        // Get trend data for sparkline
        $trendDays = $this->dateFilter === 'today' ? 7 : 30;
        $trend = $this->getRoiService()->getRoiTrend($company, $trendDays, $branch);

        // Calculate some additional metrics
        $summary = $roiData['summary'];
        $callMetrics = $roiData['call_metrics'];
        $appointmentMetrics = $roiData['appointment_metrics'];

        // Cost per successful booking
        $costPerBooking = $callMetrics['calls_with_bookings'] > 0
            ? round($callMetrics['total_cost'] / $callMetrics['calls_with_bookings'], 2)
            : 0;

        // Expected value per call
        $expectedValuePerCall = $callMetrics['total_calls'] > 0
            ? round($appointmentMetrics['total_revenue'] / $callMetrics['total_calls'], 2)
            : 0;

        return array_merge(parent::getViewData(), [
            'roi' => $roiData,
            'trend' => $trend,
            'costPerBooking' => $costPerBooking,
            'expectedValuePerCall' => $expectedValuePerCall,
            'period' => $this->dateFilter,
            'periodLabel' => $this->getPeriodLabel(),
        ]);
    }

    protected function getDateRange(): array
    {
        $now = Carbon::now();

        return [
            $this->getStartDate(),
            $this->getEndDate(),
        ];
    }

    protected function getPeriodLabel(): string
    {
        return match ($this->dateFilter) {
            'today' => 'Heute',
            'yesterday' => 'Gestern',
            'last7days' => 'Letzte 7 Tage',
            'last30days' => 'Letzte 30 Tage',
            'thisMonth' => 'Dieser Monat',
            'lastMonth' => 'Letzter Monat',
            'thisYear' => 'Dieses Jahr',
            'custom' => Carbon::parse($this->startDate)->format('d.m.') . ' - ' . Carbon::parse($this->endDate)->format('d.m.'),
            default => 'Heute',
        };
    }

    public function getRoiColorClass(float $roi): string
    {
        if ($roi >= 100) {
            return 'from-green-500 to-green-600';
        } elseif ($roi >= 50) {
            return 'from-yellow-500 to-yellow-600';
        } elseif ($roi >= 0) {
            return 'from-orange-500 to-orange-600';
        } else {
            return 'from-red-500 to-red-600';
        }
    }

    public function getRoiGradientClass(float $roi): string
    {
        if ($roi >= 100) {
            return 'bg-gradient-to-br from-green-500 to-green-600';
        } elseif ($roi >= 50) {
            return 'bg-gradient-to-br from-yellow-500 to-yellow-600';
        } elseif ($roi >= 0) {
            return 'bg-gradient-to-br from-orange-500 to-orange-600';
        } else {
            return 'bg-gradient-to-br from-red-500 to-red-600';
        }
    }

    public function getRoiTextColorClass(float $roi): string
    {
        if ($roi >= 100) {
            return 'text-green-600';
        } elseif ($roi >= 50) {
            return 'text-yellow-600';
        } elseif ($roi >= 0) {
            return 'text-orange-600';
        } else {
            return 'text-red-600';
        }
    }
}
