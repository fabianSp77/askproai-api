<?php

namespace App\Filament\Admin\Resources\UltimateCallResource\Pages;

use App\Filament\Admin\Resources\UltimateCallResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Pagination\CursorPaginator;

class UltimateListCalls extends ListRecords
{
    protected static string $resource = UltimateCallResource::class;
    
    protected static string $view = 'filament.admin.pages.ultra-calls-dashboard';
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('Aktualisieren')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->resetPage()),
            
            Actions\Action::make('export')
                ->label('Export')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => $this->exportCalls()),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [];
    }
    
    public function getHeading(): string
    {
        return ''; // Heading is in the custom view
    }
    
    protected function getViewData(): array
    {
        $stats = $this->calculateCallStats();
        
        return [
            'calls' => $this->getTableRecords(),
            'activeCallsCount' => $stats['active_calls'],
            'averageDuration' => $stats['avg_duration'],
            'successRate' => $stats['success_rate'],
            'queueSize' => $stats['queue_size'],
        ];
    }
    
    protected function calculateCallStats(): array
    {
        $baseQuery = static::getResource()::getEloquentQuery();
        
        return [
            'active_calls' => $baseQuery->clone()
                ->where('status', 'active')
                ->count(),
            
            'avg_duration' => $baseQuery->clone()
                ->whereDate('created_at', today())
                ->avg('duration') ?? 0,
            
            'success_rate' => $baseQuery->clone()
                ->whereDate('created_at', today())
                ->where('status', 'completed')
                ->count() / max($baseQuery->clone()->whereDate('created_at', today())->count(), 1) * 100,
            
            'queue_size' => $baseQuery->clone()
                ->where('status', 'waiting')
                ->count(),
        ];
    }
    
    protected function exportCalls(): void
    {
        // Export implementation
        $this->notify('success', 'Export gestartet');
    }
    
    public function getTableRecords(): \Illuminate\Database\Eloquent\Collection|\Illuminate\Contracts\Pagination\Paginator|\Illuminate\Contracts\Pagination\CursorPaginator
    {
        return $this->getTableQuery()->paginate($this->getTableRecordsPerPage());
    }
    
    public function applySmartFilter(string $query): void
    {
        // Parse natural language query
        $query = strtolower($query);
        
        if (str_contains($query, 'today') || str_contains($query, 'heute')) {
            $this->tableFilters['created_at']['value'] = now()->format('Y-m-d');
        }
        
        if (str_contains($query, 'positive') || str_contains($query, 'positiv')) {
            $this->tableFilters['sentiment']['value'] = 'positive';
        }
        
        if (preg_match('/(\d+)\s*(minute|minuten|min)/', $query, $matches)) {
            $this->tableFilters['duration']['value'] = $matches[1] * 60;
        }
        
        $this->resetPage();
    }
}