<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Branch;
// use App\Models\UnifiedEventType; // Model removed - use CalcomEventType instead
use App\Services\Calendar\CalcomCalendarService;

class ImportCalcomEventTypes extends Command
{
    protected $signature = 'calcom:import-event-types {--branch=all : Branch ID or "all" for all branches}';
    
    protected $description = 'Import event types from Cal.com for specified branches';

    public function handle()
    {
        $this->error('This command needs to be updated - UnifiedEventType model has been removed.');
        $this->info('Please use CalcomEventType model instead or use the EventTypeImportWizard in the admin panel.');
        return 1;
        
        /* Original code commented out - needs migration to CalcomEventType model
        $branchOption = $this->option('branch');
        
        if ($branchOption === 'all') {
            $branches = Branch::where('calcom_api_key', '!=', null)->get();
        } else {
            $branches = Branch::where('id', $branchOption)
                ->where('calcom_api_key', '!=', null)
                ->get();
        }
        
        if ($branches->isEmpty()) {
            $this->error('No branches found with Cal.com API keys.');
            return 1;
        }
        
        $this->info('Starting Cal.com event types import...');
        
        foreach ($branches as $branch) {
            $this->info("Processing branch: {$branch->name} (ID: {$branch->id})");
            
            try {
                $calcomService = new CalcomCalendarService([
                    'api_key' => $branch->calcom_api_key
                ]);
                
                if (!$calcomService->validateConnection()) {
                    $this->error("  ❌ Failed to connect to Cal.com for branch {$branch->name}");
                    continue;
                }
                
                $eventTypes = $calcomService->getEventTypes();
                
                if (empty($eventTypes)) {
                    $this->warn("  ⚠️  No event types found for branch {$branch->name}");
                    continue;
                }
                
                foreach ($eventTypes as $eventType) {
                    UnifiedEventType::updateOrCreate(
                        [
                            'branch_id' => $branch->id,
                            'provider' => 'calcom',
                            'external_id' => $eventType['external_id']
                        ],
                        [
                            'name' => $eventType['name'],
                            'description' => $eventType['description'] ?? null,
                            'duration_minutes' => $eventType['duration_minutes'],
                            'provider_data' => $eventType['provider_data'],
                            'is_active' => true
                        ]
                    );
                    
                    $this->info("  ✅ Imported: {$eventType['name']} ({$eventType['duration_minutes']} min)");
                }
                
            } catch (\Exception $e) {
                $this->error("  ❌ Error processing branch {$branch->name}: " . $e->getMessage());
            }
        }
        
        $this->info('Import completed!');
        return 0;
        */
    }
}
