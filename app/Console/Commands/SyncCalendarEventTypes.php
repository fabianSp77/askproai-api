<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Branch;
use App\Models\UnifiedEventType;
use App\Services\Calendar\CalendarFactory;
use Illuminate\Support\Facades\Log;

class SyncCalendarEventTypes extends Command
{
    protected $signature = 'calendar:sync-event-types 
                            {--branch= : Specific branch ID to sync}
                            {--provider= : Specific provider to sync}';
    
    protected $description = 'Synchronize event types from all configured calendar providers';

    public function handle()
    {
        $this->info('Starting calendar event types synchronization...');
        
        $query = Branch::query();
        
        if ($branchId = $this->option('branch')) {
            $query->where('id', $branchId);
        }
        
        if ($provider = $this->option('provider')) {
            switch ($provider) {
                case 'calcom':
                    $query->whereNotNull('calcom_api_key');
                    break;
                // Weitere Provider hier hinzufügen
            }

} else {
            // Alle Provider - nur Cal.com vorerst
            $query->whereNotNull('calcom_api_key');
        }
        
        $branches = $query->get();
        
        if ($branches->isEmpty()) {
            $this->warn('No branches found with calendar configurations.');
            return 1;
        }
        
        $totalSynced = 0;
        $totalErrors = 0;
        
        foreach ($branches as $branch) {
            $this->info("\nProcessing branch: {$branch->name}");
            
            // Cal.com synchronisieren
            if ($branch->calcom_api_key) {
                try {
                    $synced = $this->syncProvider($branch, 'calcom', [
                        'api_key' => $branch->calcom_api_key
                    ]);
                    $totalSynced += $synced;
                } catch (\Exception $e) {
                    $this->error("  Error syncing Cal.com: " . $e->getMessage());
                    $totalErrors++;
                }
            }
            
            // Weitere Provider hier hinzufügen
        }
        
        $this->info("\nSynchronization completed!");
        $this->info("Total synced: {$totalSynced} event types");
        if ($totalErrors > 0) {
            $this->warn("Total errors: {$totalErrors}");
        }
        
        return 0;
    }
    
    private function syncProvider(Branch $branch, string $provider, array $config): int
    {
        $this->info("  Syncing {$provider}...");
        
        $calendarService = CalendarFactory::create($provider, $config);
        
        if (!$calendarService->validateConnection()) {
            throw new \Exception("Failed to connect to {$provider}");
        }
        
        $eventTypes = $calendarService->getEventTypes();
        $synced = 0;
        
        // Alle existierenden Event Types für diesen Provider als inaktiv markieren
        UnifiedEventType::where('branch_id', $branch->id)
            ->where('provider', $provider)
            ->update(['is_active' => false]);
        
        foreach ($eventTypes as $eventType) {
            $unified = UnifiedEventType::updateOrCreate(
                [
                    'branch_id' => $branch->id,
                    'provider' => $provider,
                    'external_id' => $eventType['external_id']
                ],
                [
                    'name' => $eventType['name'],
                    'description' => $eventType['description'] ?? null,
                    'duration_minutes' => $eventType['duration_minutes'],
                    'provider_data' => $eventType['provider_data'] ?? [],
                    'is_active' => true
                ]
            );
            
            $this->line("    ✓ {$eventType['name']} ({$eventType['duration_minutes']} min)");
            $synced++;
        }
        
        // Event Types die nicht mehr existieren, als gelöscht markieren
        $deleted = UnifiedEventType::where('branch_id', $branch->id)
            ->where('provider', $provider)
            ->where('is_active', false)
            ->count();
        
        if ($deleted > 0) {
            $this->warn("    {$deleted} event types marked as inactive");
        }
        
        return $synced;
    }
}
