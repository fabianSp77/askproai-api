<?php

namespace App\Filament\Resources\ServiceCaseResource\Pages;

use App\Filament\Resources\ServiceCaseResource;
use App\Models\ServiceCase;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListServiceCases extends ListRecords
{
    protected static string $resource = ServiceCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    /**
     * ServiceNow-Style Tabs für schnelle Filterung
     * - Alle: Komplette Liste
     * - Meine Cases: Nur dem aktuellen User zugewiesene Cases
     * - Nicht zugewiesen: Cases ohne Zuweisung (Queue)
     * - SLA überschritten: Cases mit überfälligem SLA
     */
    public function getTabs(): array
    {
        $staffId = Auth::user()->staff?->id;

        $tabs = [
            'all' => Tab::make('Alle')
                ->icon('heroicon-o-ticket')
                ->badge($this->getAllCasesCount())
                ->badgeColor('gray'),
        ];

        // Only show "Meine Cases" tab if user has a staff record
        if ($staffId !== null) {
            $tabs['mine'] = Tab::make('Meine Cases')
                ->icon('heroicon-o-user')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('assigned_to', $staffId))
                ->badge($this->getMyCasesCount())
                ->badgeColor('primary');
        }

        $tabs['unassigned'] = Tab::make('Nicht zugewiesen')
            ->icon('heroicon-o-user-minus')
            ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('assigned_to')->open())
            ->badge($this->getUnassignedCount())
            ->badgeColor($this->getUnassignedCount() > 0 ? 'warning' : 'gray');

        // FIX: Only check cases that have SLA dates set (exclude pass-through companies with NULL SLA)
        $tabs['overdue'] = Tab::make('SLA überschritten')
            ->icon('heroicon-o-exclamation-triangle')
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->where(function (Builder $q) {
                    $q->where(function (Builder $q2) {
                        $q2->whereNotNull('sla_response_due_at')
                           ->where('sla_response_due_at', '<', now());
                    })->orWhere(function (Builder $q2) {
                        $q2->whereNotNull('sla_resolution_due_at')
                           ->where('sla_resolution_due_at', '<', now());
                    });
                })
                ->open()
            )
            ->badge($this->getOverdueCount())
            ->badgeColor($this->getOverdueCount() > 0 ? 'danger' : 'success');

        $tabs['pending_output'] = Tab::make('Output ausstehend')
            ->icon('heroicon-o-paper-airplane')
            ->modifyQueryUsing(fn (Builder $query) => $query->where('output_status', ServiceCase::OUTPUT_PENDING))
            ->badge($this->getPendingOutputCount())
            ->badgeColor($this->getPendingOutputCount() > 0 ? 'info' : 'gray');

        // Only show "Failed Output" tab if there are failed outputs
        if ($this->getFailedOutputCount() > 0) {
            $tabs['failed_output'] = Tab::make('Output fehlgeschlagen')
                ->icon('heroicon-o-exclamation-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('output_status', ServiceCase::OUTPUT_FAILED))
                ->badge($this->getFailedOutputCount())
                ->badgeColor('danger');
        }

        return $tabs;
    }

    /**
     * Badge Counts - cached für Performance
     */
    protected function getAllCasesCount(): int
    {
        return ServiceCase::count();
    }

    protected function getMyCasesCount(): int
    {
        $staffId = Auth::user()->staff?->id;
        if (!$staffId) {
            return 0;
        }
        return ServiceCase::open()->where('assigned_to', $staffId)->count();
    }

    protected function getUnassignedCount(): int
    {
        return ServiceCase::open()->whereNull('assigned_to')->count();
    }

    protected function getOverdueCount(): int
    {
        // FIX: Only count cases that have SLA dates set (exclude pass-through companies with NULL SLA)
        return ServiceCase::open()
            ->where(function (Builder $q) {
                $q->where(function (Builder $q2) {
                    $q2->whereNotNull('sla_response_due_at')
                       ->where('sla_response_due_at', '<', now());
                })->orWhere(function (Builder $q2) {
                    $q2->whereNotNull('sla_resolution_due_at')
                       ->where('sla_resolution_due_at', '<', now());
                });
            })
            ->count();
    }

    protected function getPendingOutputCount(): int
    {
        return ServiceCase::where('output_status', ServiceCase::OUTPUT_PENDING)->count();
    }

    protected function getFailedOutputCount(): int
    {
        return ServiceCase::where('output_status', ServiceCase::OUTPUT_FAILED)->count();
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()->with([
            'company',
            'category',
            'customer',
            'call',
            'assignedTo',
            'assignedGroup', // Phase 1: N+1 Query Fix für Gruppenzuweisung
        ]);
    }

    /**
     * Widgets removed - use dedicated ServiceGateway Dashboard for statistics.
     * @see \App\Filament\Pages\ServiceGatewayDashboard
     */
    protected function getHeaderWidgets(): array
    {
        return [];
    }
}
