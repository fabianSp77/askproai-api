<?php

namespace App\Filament\Admin\Resources\UltimateAppointmentResource\Pages;

use App\Filament\Admin\Resources\UltimateAppointmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Pagination\CursorPaginator;

class UltimateListAppointments extends ListRecords
{
    protected static string $resource = UltimateAppointmentResource::class;
    
    protected static string $view = 'filament.admin.pages.ultra-appointments-dashboard';
    
    protected function getHeaderActions(): array
    {
        return [];
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
        $stats = $this->calculateAppointmentStats();
        
        return [
            'appointments' => $this->getTableRecords(),
            'todayCount' => $stats['today_count'],
            'weekCount' => $stats['week_count'],
            'confirmationRate' => $stats['confirmation_rate'],
            'cancellationRate' => $stats['cancellation_rate'],
        ];
    }
    
    protected function calculateAppointmentStats(): array
    {
        $baseQuery = static::getResource()::getEloquentQuery();
        
        $todayCount = $baseQuery->clone()
            ->whereDate('starts_at', today())
            ->count();
            
        $weekCount = $baseQuery->clone()
            ->whereBetween('starts_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();
            
        $totalThisMonth = $baseQuery->clone()
            ->whereMonth('starts_at', now()->month)
            ->count();
            
        $confirmedThisMonth = $baseQuery->clone()
            ->whereMonth('starts_at', now()->month)
            ->where('status', 'confirmed')
            ->count();
            
        $cancelledThisMonth = $baseQuery->clone()
            ->whereMonth('starts_at', now()->month)
            ->where('status', 'cancelled')
            ->count();
        
        return [
            'today_count' => $todayCount,
            'week_count' => $weekCount,
            'confirmation_rate' => $totalThisMonth > 0 ? round(($confirmedThisMonth / $totalThisMonth) * 100) : 0,
            'cancellation_rate' => $totalThisMonth > 0 ? round(($cancelledThisMonth / $totalThisMonth) * 100, 1) : 0,
        ];
    }
    
    public function getTableRecords(): \Illuminate\Database\Eloquent\Collection|\Illuminate\Contracts\Pagination\Paginator|\Illuminate\Contracts\Pagination\CursorPaginator
    {
        return $this->getTableQuery()->paginate($this->getTableRecordsPerPage());
    }
}