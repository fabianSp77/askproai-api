<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use App\Models\Call;
use Carbon\Carbon;

class CallQueueWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.call-queue-widget';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 3;
    protected static ?string $pollingInterval = '60s';

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
        return [
            'activeCalls' => $this->getActiveCalls(),
            'queuedCalls' => $this->getQueuedCalls(),
            'recentCalls' => $this->getRecentCalls(),
            'stats' => $this->getCallStats(),
        ];
    }

    protected function getActiveCalls(): array
    {
        // TODO: Implement real active call tracking
        return [];
    }

    protected function getQueuedCalls(): array
    {
        // TODO: Implement real call queue
        return [];
    }

    protected function getRecentCalls()
    {
        return Call::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->with(['customer', 'branch'])
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(function ($call) {
                return [
                    'id' => $call->id,
                    'customer_name' => $call->customer ? 
                        $call->customer->first_name . ' ' . $call->customer->last_name : 
                        'Unbekannt',
                    'phone' => $call->from_number ?? 'Unbekannt',
                    'duration' => $call->duration_sec ? gmdate('i:s', $call->duration_sec) : '-',
                    'time' => $call->created_at->diffForHumans(),
                    'branch' => $call->branch?->name ?? '-',
                    'status' => $this->getCallStatus($call),
                ];
            });
    }

    protected function getCallStatus($call): array
    {
        if ($call->appointment_id) {
            return [
                'label' => 'Termin gebucht',
                'color' => 'success',
            ];
        }

        if ($call->transcript) {
            return [
                'label' => 'Bearbeitet',
                'color' => 'info',
            ];
        }

        return [
            'label' => 'Verpasst',
            'color' => 'danger',
        ];
    }

    protected function getCallStats(): array
    {
        $today = Carbon::today();
        
        $totalToday = Call::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->whereDate('created_at', $today)
            ->count();
            
        $answeredToday = Call::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->whereDate('created_at', $today)
            ->whereNotNull('duration_sec')
            ->where('duration_sec', '>', 0)
            ->count();
            
        $appointmentsBooked = Call::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->whereDate('created_at', $today)
            ->whereNotNull('appointment_id')
            ->count();
            
        $avgDuration = Call::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->whereDate('created_at', $today)
            ->whereNotNull('duration_sec')
            ->where('duration_sec', '>', 0)
            ->avg('duration_sec');

        return [
            'total' => $totalToday,
            'answered' => $answeredToday,
            'answer_rate' => $totalToday > 0 ? round(($answeredToday / $totalToday) * 100) : 0,
            'appointments' => $appointmentsBooked,
            'conversion_rate' => $answeredToday > 0 ? round(($appointmentsBooked / $answeredToday) * 100) : 0,
            'avg_duration' => $avgDuration ? gmdate('i:s', $avgDuration) : '0:00',
        ];
    }
}