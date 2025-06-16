<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BulkAssignStaffToEventTypesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $tries = 3;
    public $timeout = 180; // 3 Minuten
    
    protected $assignments;
    
    /**
     * @param array $assignments Array of assignments with structure:
     * [
     *   ['staff_id' => x, 'event_type_id' => y, 'custom_duration' => z],
     *   ...
     * ]
     */
    public function __construct(array $assignments)
    {
        $this->assignments = $assignments;
    }
    
    public function handle()
    {
        Log::info('Starting bulk staff assignment job', [
            'assignment_count' => count($this->assignments)
        ]);
        
        DB::beginTransaction();
        
        try {
            $processedCount = 0;
            
            // Verarbeite in Batches von 100
            foreach (array_chunk($this->assignments, 100) as $batch) {
                $inserts = [];
                
                foreach ($batch as $assignment) {
                    // Prüfe ob Zuordnung bereits existiert
                    $exists = DB::table('staff_event_types')
                        ->where('staff_id', $assignment['staff_id'])
                        ->where('event_type_id', $assignment['event_type_id'])
                        ->exists();
                    
                    if (!$exists) {
                        $inserts[] = [
                            'staff_id' => $assignment['staff_id'],
                            'event_type_id' => $assignment['event_type_id'],
                            'custom_duration' => $assignment['custom_duration'] ?? null,
                            'custom_price' => $assignment['custom_price'] ?? null,
                            'is_primary' => $assignment['is_primary'] ?? false,
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    }
                }
                
                if (!empty($inserts)) {
                    DB::table('staff_event_types')->insert($inserts);
                    $processedCount += count($inserts);
                }
            }
            
            DB::commit();
            
            Log::info('Bulk staff assignment job completed', [
                'processed_count' => $processedCount
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Bulk staff assignment job failed', [
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    public function failed(\Throwable $exception)
    {
        Log::error('Bulk staff assignment job permanently failed', [
            'error' => $exception->getMessage()
        ]);
    }
}