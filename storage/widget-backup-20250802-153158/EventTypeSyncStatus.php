<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use App\Models\CalcomEventType;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class EventTypeSyncStatus extends Widget
{
    protected static string $view = 'filament.admin.widgets.event-type-sync-status';
    
    protected int|string|array $columnSpan = 'full';
    
    protected static ?int $sort = -1;
    
    public function getSyncData(): array
    {
        return Cache::remember('event-type-sync-status', 60, function () {
            $company = auth()->user()->company ?? Company::first();
            
            if (!$company) {
                return [
                    'status' => 'error',
                    'message' => 'Kein Unternehmen ausgewählt',
                    'lastSync' => null,
                    'totalEventTypes' => 0,
                    'syncedEventTypes' => 0,
                    'failedEventTypes' => 0,
                    'canSync' => false
                ];
            }
            
            $eventTypes = CalcomEventType::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->get();
            
            $lastSync = $eventTypes->max('last_synced_at');
            $syncedCount = $eventTypes->where('sync_status', 'synced')->count();
            $failedCount = $eventTypes->where('sync_status', 'failed')->count();
            
            // Determine overall status
            $status = 'success';
            $message = 'Alle Event Types sind synchronisiert';
            
            if ($failedCount > 0) {
                $status = 'error';
                $message = "{$failedCount} Event Types konnten nicht synchronisiert werden";
            } elseif (!$lastSync) {
                $status = 'warning';
                $message = 'Noch nie synchronisiert';
            } elseif (Carbon::parse($lastSync)->isBefore(now()->subHours(24))) {
                $status = 'warning';
                $message = 'Letzte Synchronisation ist älter als 24 Stunden';
            }
            
            return [
                'status' => $status,
                'message' => $message,
                'lastSync' => $lastSync ? Carbon::parse($lastSync)->diffForHumans() : 'Nie',
                'totalEventTypes' => $eventTypes->count(),
                'syncedEventTypes' => $syncedCount,
                'failedEventTypes' => $failedCount,
                'canSync' => !empty($company->calcom_api_key)
            ];
        });
    }
    
    public function syncNow(): void
    {
        $company = auth()->user()->company ?? Company::first();
        
        if (!$company || empty($company->calcom_api_key)) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Cal.com API Key fehlt!'
            ]);
            return;
        }
        
        // Dispatch sync job
        \App\Jobs\SyncCompanyEventTypesJob::dispatch($company);
        
        // Clear cache
        Cache::forget('event-type-sync-status');
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Synchronisation gestartet! Die Event Types werden im Hintergrund aktualisiert.'
        ]);
    }
}