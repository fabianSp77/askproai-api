<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Models\CalcomEventType;
use App\Services\CalcomV2Service;
use Illuminate\Support\Facades\Log;

class SyncCalcomEventTypes extends Command
{
    protected $signature = 'calcom:sync-event-types 
                            {--company= : Company ID to sync for}
                            {--deactivate-missing : Deactivate event types not found in Cal.com}
                            {--dry-run : Show what would be changed without making changes}';
                            
    protected $description = 'Sync Cal.com event types and optionally deactivate missing ones';

    public function handle()
    {
        $this->info('🔄 Starting Cal.com Event Types synchronization...');

        $companyId = $this->option('company');
        
        if ($companyId) {
            $companies = Company::where('id', $companyId)->get();
        } else {
            $companies = Company::whereNotNull('calcom_api_key')->get();
        }

        foreach ($companies as $company) {
            $this->info("\n📊 Processing company: {$company->name}");
            
            if (!$company->calcom_api_key) {
                $this->warn("  No Cal.com API key configured, skipping...");
                continue;
            }

            $calcomService = new CalcomV2Service(decrypt($company->calcom_api_key));
            
            // Fetch event types from Cal.com
            $response = $calcomService->getEventTypes();
            
            if (!$response || !$response['success']) {
                $this->error("  Failed to fetch event types from Cal.com");
                continue;
            }

            $eventTypes = $response['data']['event_types'] ?? [];
            $calcomEventTypeIds = collect($eventTypes)->pluck('id')->map(fn($id) => (string)$id)->toArray();
            
            $this->info("  Found " . count($eventTypes) . " event types in Cal.com");

            // Import/update event types
            $imported = 0;
            $updated = 0;
            
            foreach ($eventTypes as $eventType) {
                $existing = CalcomEventType::where('company_id', $company->id)
                    ->where('calcom_event_type_id', (string)$eventType['id'])
                    ->first();
                    
                if ($existing) {
                    if (!$this->option('dry-run')) {
                        $existing->update([
                            'name' => $eventType['title'],
                            'slug' => $eventType['slug'],
                            'description' => $eventType['description'] ?? null,
                            'duration_minutes' => $eventType['length'],
                            'metadata' => json_encode($eventType),
                            'is_active' => true, // Reactivate if it was deactivated
                        ]);
                    }
                    $updated++;
                    $this->info("    ✅ Updated: {$eventType['title']}");
                } else {
                    if (!$this->option('dry-run')) {
                        CalcomEventType::create([
                            'company_id' => $company->id,
                            'calcom_event_type_id' => (string)$eventType['id'],
                            'calcom_numeric_event_type_id' => $eventType['id'],
                            'name' => $eventType['title'],
                            'slug' => $eventType['slug'],
                            'description' => $eventType['description'] ?? null,
                            'duration_minutes' => $eventType['length'],
                            'metadata' => json_encode($eventType),
                            'is_active' => true,
                        ]);
                    }
                    $imported++;
                    $this->info("    ➕ Imported: {$eventType['title']}");
                }
            }

            // Handle missing event types
            if ($this->option('deactivate-missing')) {
                $localEventTypes = CalcomEventType::where('company_id', $company->id)
                    ->where('is_active', true)
                    ->get();
                    
                $deactivated = 0;
                
                foreach ($localEventTypes as $localEventType) {
                    if (!in_array($localEventType->calcom_event_type_id, $calcomEventTypeIds)) {
                        if (!$this->option('dry-run')) {
                            $localEventType->update(['is_active' => false]);
                        }
                        $deactivated++;
                        $this->warn("    🔴 Deactivated (not in Cal.com): {$localEventType->name}");
                    }
                }
                
                $this->info("  Deactivated {$deactivated} missing event types");
            }

            $this->info("  Summary: {$imported} imported, {$updated} updated");
        }

        $this->info("\n✨ Synchronization complete!");
        
        if ($this->option('dry-run')) {
            $this->warn("This was a dry run. No changes were made.");
        }
        
        return 0;
    }
}