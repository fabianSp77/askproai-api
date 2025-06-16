<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\CalcomEventType;
use App\Services\CalcomSyncService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PrecacheAvailabilityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $tries = 2;
    public $timeout = 120; // 2 Minuten
    
    protected $eventTypeId;
    protected $dateFrom;
    protected $dateTo;
    
    public function __construct($eventTypeId, $dateFrom = null, $dateTo = null)
    {
        $this->eventTypeId = $eventTypeId;
        $this->dateFrom = $dateFrom ?? Carbon::now()->toIso8601String();
        $this->dateTo = $dateTo ?? Carbon::now()->addDays(7)->toIso8601String();
    }
    
    public function handle(CalcomSyncService $syncService)
    {
        Log::info('Starting availability precache job', [
            'event_type_id' => $this->eventTypeId,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo
        ]);
        
        try {
            // Prüfe Verfügbarkeit ohne spezifischen Mitarbeiter
            $availability = $syncService->checkAvailability(
                $this->eventTypeId,
                $this->dateFrom,
                $this->dateTo
            );
            
            // Cache für 15 Minuten
            $cacheKey = "availability.{$this->eventTypeId}.{$this->dateFrom}.{$this->dateTo}";
            Cache::put($cacheKey, $availability, now()->addMinutes(15));
            
            // Cache auch für jeden verfügbaren Mitarbeiter
            if (!empty($availability['slots'])) {
                $staffIds = collect($availability['slots'])
                    ->pluck('staff_id')
                    ->unique()
                    ->filter();
                
                foreach ($staffIds as $staffId) {
                    $staffAvailability = $syncService->checkAvailability(
                        $this->eventTypeId,
                        $this->dateFrom,
                        $this->dateTo,
                        $staffId
                    );
                    
                    $staffCacheKey = "availability.{$this->eventTypeId}.{$this->dateFrom}.{$this->dateTo}.staff.{$staffId}";
                    Cache::put($staffCacheKey, $staffAvailability, now()->addMinutes(15));
                }
            }
            
            Log::info('Availability precache job completed', [
                'event_type_id' => $this->eventTypeId,
                'slots_found' => count($availability['slots'] ?? [])
            ]);
            
        } catch (\Exception $e) {
            Log::error('Availability precache job failed', [
                'event_type_id' => $this->eventTypeId,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
}