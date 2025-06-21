<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\Customer;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class HealthScoreWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.health-score-widget';
    protected int | string | array $columnSpan = 1;
    protected static ?int $sort = 1;

    public ?int $companyId = null;
    public ?int $selectedBranchId = null;

    protected function getListeners(): array
    {
        return [
            'tenantFilterUpdated' => 'handleTenantFilterUpdate',
        ];
    }

    public function handleTenantFilterUpdate($companyId, $branchId): void
    {
        $this->companyId = $companyId;
        $this->selectedBranchId = $branchId;
    }

    protected function getViewData(): array
    {
        $cacheKey = "health-score-{$this->companyId}-{$this->selectedBranchId}";
        
        $data = Cache::remember($cacheKey, 300, function () {
            return $this->calculateHealthScore();
        });

        return [
            'score' => $data['score'],
            'components' => $data['components'],
            'trend' => $data['trend'],
            'status' => $this->getScoreStatus($data['score']),
        ];
    }

    protected function calculateHealthScore(): array
    {
        // Gewichtung der verschiedenen Komponenten
        $weights = [
            'conversion' => 0.30,    // 30% - Konversionsrate
            'no_show' => 0.20,       // 20% - No-Show-Rate (invertiert)
            'occupancy' => 0.20,     // 20% - Auslastung
            'satisfaction' => 0.20,  // 20% - Kundenzufriedenheit
            'availability' => 0.10,  // 10% - Systemverfügbarkeit
        ];

        $components = [
            'conversion' => $this->calculateConversionRate(),
            'no_show' => 100 - $this->calculateNoShowRate(), // Invertiert
            'occupancy' => $this->calculateOccupancyRate(),
            'satisfaction' => $this->calculateSatisfactionScore(),
            'availability' => $this->calculateSystemAvailability(),
        ];

        // Gesamtscore berechnen
        $score = 0;
        foreach ($weights as $key => $weight) {
            $score += $components[$key] * $weight;
        }

        // Trend berechnen (Vergleich zu letzter Woche)
        $lastWeekScore = $this->calculateLastWeekScore();
        $trend = $score - $lastWeekScore;

        return [
            'score' => round($score),
            'components' => $components,
            'trend' => round($trend, 1),
        ];
    }

    protected function calculateConversionRate(): float
    {
        $calls = Call::query()
            ->when($this->companyId, function ($q) {
                $q->whereHas('branch', fn($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->count();

        $appointments = Appointment::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->whereNotNull('call_id')
            ->count();

        if ($calls === 0) return 0;

        return min(100, ($appointments / $calls) * 100);
    }

    protected function calculateNoShowRate(): float
    {
        $total = Appointment::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->where('starts_at', '<=', Carbon::now())
            ->where('starts_at', '>=', Carbon::now()->subDays(7))
            ->count();

        if ($total === 0) return 0;

        $noShows = Appointment::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->where('starts_at', '<=', Carbon::now())
            ->where('starts_at', '>=', Carbon::now()->subDays(7))
            ->where('status', 'no_show')
            ->count();

        return min(100, ($noShows / $total) * 100);
    }

    protected function calculateOccupancyRate(): float
    {
        // Annahme: 8 Stunden pro Tag, 5 Tage die Woche
        $totalSlots = 8 * 2 * 5 * 7; // 2 Slots pro Stunde, 7 Tage

        $bookedSlots = Appointment::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->where('starts_at', '>=', Carbon::now()->startOfWeek())
            ->where('starts_at', '<=', Carbon::now()->endOfWeek())
            ->whereIn('status', ['scheduled', 'confirmed', 'completed'])
            ->count();

        if ($totalSlots === 0) return 0;

        return min(100, ($bookedSlots / $totalSlots) * 100);
    }

    protected function calculateSatisfactionScore(): float
    {
        // Basierend auf Call-Sentiment-Analyse (wenn verfügbar)
        $avgSentiment = Call::query()
            ->when($this->companyId, function ($q) {
                $q->whereHas('branch', fn($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->whereNotNull('sentiment_score')
            ->avg('sentiment_score') ?? 80;

        return min(100, $avgSentiment);
    }

    protected function calculateSystemAvailability(): float
    {
        // Basierend auf API-Fehlern und Ausfällen
        $totalCalls = Call::query()
            ->when($this->companyId, function ($q) {
                $q->whereHas('branch', fn($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->where('created_at', '>=', Carbon::now()->subDays(1))
            ->count();

        $failedCalls = Call::query()
            ->when($this->companyId, function ($q) {
                $q->whereHas('branch', fn($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->where('created_at', '>=', Carbon::now()->subDays(1))
            ->where('call_status', 'failed')
            ->count();

        if ($totalCalls === 0) return 100;

        return max(0, 100 - (($failedCalls / $totalCalls) * 100));
    }

    protected function calculateLastWeekScore(): float
    {
        // Vereinfachte Berechnung für Trend
        return 75; // TODO: Implementiere historische Berechnung
    }

    protected function getScoreStatus(int $score): array
    {
        if ($score >= 85) {
            return [
                'label' => 'Exzellent',
                'color' => 'success',
                'icon' => 'heroicon-o-check-circle',
            ];
        } elseif ($score >= 70) {
            return [
                'label' => 'Gut',
                'color' => 'info',
                'icon' => 'heroicon-o-information-circle',
            ];
        } elseif ($score >= 50) {
            return [
                'label' => 'Verbesserungsbedarf',
                'color' => 'warning',
                'icon' => 'heroicon-o-exclamation-triangle',
            ];
        } else {
            return [
                'label' => 'Kritisch',
                'color' => 'danger',
                'icon' => 'heroicon-o-x-circle',
            ];
        }
    }
}