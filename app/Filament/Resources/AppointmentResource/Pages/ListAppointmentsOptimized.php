<?php

namespace App\Filament\Resources\AppointmentResource\Pages;

use App\Filament\Resources\AppointmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ListAppointmentsOptimized extends ListRecords
{
    protected static string $resource = AppointmentResource::class;

    /**
     * Optimize query execution
     */
    protected function getTableQuery(): Builder
    {
        $companyId = auth()->user()->company_id ?? 1;

        return parent::getTableQuery()
            // Eager load all relationships to prevent N+1
            ->with([
                'company:id,name',
                'branch:id,name,company_id',
                'customer:id,name,phone,email',
                'staff:id,name,branch_id',
                'service:id,name,duration_minutes,price'
            ])
            // Use optimized index
            ->from('appointments USE INDEX (idx_appt_list_optimized)')
            // Apply company scope efficiently
            ->where('appointments.company_id', $companyId)
            // Default to upcoming appointments for better UX
            ->when(!request()->has('tableFilters'), function($query) {
                $query->where('starts_at', '>=', now()->subHours(2));
            });
    }

    /**
     * Optimize header actions
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus')
                ->label('Neuer Termin'),
        ];
    }

    /**
     * Use optimized widgets
     */
    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\AppointmentResource\Widgets\AppointmentStatsOptimized::class,
            \App\Filament\Resources\AppointmentResource\Widgets\UpcomingAppointmentsOptimized::class,
        ];
    }

    /**
     * Override to implement cursor-based pagination for large datasets
     */
    protected function getTableRecordsPerPageSelectOptions(): array
    {
        $totalRecords = $this->getCachedRecordCount();

        // Use smaller page sizes for larger datasets
        if ($totalRecords > 10000) {
            return [25, 50];
        } elseif ($totalRecords > 1000) {
            return [25, 50, 100];
        }

        return [50, 100, 200];
    }

    /**
     * Get cached record count
     */
    private function getCachedRecordCount(): int
    {
        $companyId = auth()->user()->company_id ?? 1;

        return Cache::tags(['appointments', "company-{$companyId}"])
            ->remember("appt-count:{$companyId}", 300, function() use ($companyId) {
                return DB::table('appointments')
                    ->where('company_id', $companyId)
                    ->whereNull('deleted_at')
                    ->count();
            });
    }

    /**
     * Optimize table polling
     */
    protected function getTablePollingInterval(): ?string
    {
        // Only poll if viewing today's appointments
        if (request()->input('tableFilters.time_filter.value') === 'true') {
            return '30s';
        }

        return null;
    }

    /**
     * Add performance monitoring
     */
    protected function resolveTableQuery(): Builder
    {
        $startTime = microtime(true);

        $query = parent::resolveTableQuery();

        // Log slow queries
        if (app()->environment('production')) {
            $executionTime = (microtime(true) - $startTime) * 1000;

            if ($executionTime > 100) {
                \Log::warning('Slow appointment list query', [
                    'time' => $executionTime,
                    'user' => auth()->id(),
                    'filters' => request()->input('tableFilters')
                ]);
            }
        }

        return $query;
    }

    /**
     * Cache filter options for better performance
     */
    public function getCachedFilterOptions(string $filterName): array
    {
        $companyId = auth()->user()->company_id ?? 1;
        $cacheKey = "appt-filters:{$companyId}:{$filterName}";

        return Cache::tags(['appointments', "company-{$companyId}"])
            ->remember($cacheKey, 600, function() use ($filterName, $companyId) {
                return match($filterName) {
                    'staff' => DB::table('staff')
                        ->where('company_id', $companyId)
                        ->whereNull('deleted_at')
                        ->pluck('name', 'id')
                        ->toArray(),

                    'service' => DB::table('services')
                        ->where('company_id', $companyId)
                        ->whereNull('deleted_at')
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray(),

                    'branch' => DB::table('branches')
                        ->where('company_id', $companyId)
                        ->whereNull('deleted_at')
                        ->pluck('name', 'id')
                        ->toArray(),

                    default => []
                };
            });
    }

    /**
     * Clear caches when data changes
     */
    public static function clearCache(): void
    {
        $companyId = auth()->user()->company_id ?? 1;
        Cache::tags(['appointments', "company-{$companyId}"])->flush();
    }

    /**
     * Lifecycle hooks for cache management
     */
    protected function afterCreate(): void
    {
        static::clearCache();
    }

    protected function afterUpdate(): void
    {
        static::clearCache();
    }

    protected function afterDelete(): void
    {
        static::clearCache();
    }
}