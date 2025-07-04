<?php

namespace App\Console\Commands;

use App\Jobs\SyncCalcomEventTypeUsersJob;
use App\Models\CalcomEventType;
use App\Models\Company;
use App\Models\Staff;
use App\Services\CalcomV2Service;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SyncCalcomEventTypeUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calcom:sync-event-type-users 
                            {--company= : Company ID to sync for}
                            {--event-type= : Specific event type ID to sync}
                            {--dry-run : Show what would be synced without making changes}
                            {--immediate : Process immediately instead of queueing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize Cal.com event type host assignments to staff_event_types table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Starting Cal.com event type users synchronization...');

        $companyId = $this->option('company');
        $eventTypeId = $this->option('event-type');
        
        if ($companyId) {
            $companies = Company::where('id', $companyId)->get();
        } else {
            $companies = Company::whereNotNull('calcom_api_key')->get();
        }

        if ($companies->isEmpty()) {
            $this->error('No companies with Cal.com API keys found!');
            return 1;
        }

        $totalEventTypes = 0;
        $totalSynced = 0;
        $totalQueued = 0;

        foreach ($companies as $company) {
            $this->info("\n📊 Processing company: {$company->name}");
            
            // Get event types
            $query = CalcomEventType::where('company_id', $company->id)
                ->where('is_active', true);
                
            if ($eventTypeId) {
                $query->where('id', $eventTypeId);
            }
            
            $eventTypes = $query->get();
            
            if ($eventTypes->isEmpty()) {
                $this->warn("  No active event types found for this company");
                continue;
            }
            
            $totalEventTypes += $eventTypes->count();
            
            if ($this->option('immediate')) {
                // Process immediately
                $calcomService = new CalcomV2Service(decrypt($company->calcom_api_key));
                
                foreach ($eventTypes as $eventType) {
                    $this->info("\n  📅 Processing: {$eventType->name}");
                    
                    if ($this->option('dry-run')) {
                        $this->dryRunSync($eventType, $calcomService);
                    } else {
                        $result = $this->syncEventTypeUsers($eventType, $calcomService);
                        if ($result) {
                            $totalSynced++;
                        }
                    }
                }
            } else {
                // Queue for processing
                foreach ($eventTypes as $eventType) {
                    SyncCalcomEventTypeUsersJob::dispatch($eventType);
                    $totalQueued++;
                    $this->info("  ⏳ Queued sync for: {$eventType->name}");
                }
            }
        }

        $this->info("\n✨ Synchronization complete!");
        $this->info("  Total event types processed: {$totalEventTypes}");
        
        if ($this->option('immediate')) {
            $this->info("  Successfully synced: {$totalSynced}");
        } else {
            $this->info("  Jobs queued: {$totalQueued}");
        }
        
        return 0;
    }

    protected function syncEventTypeUsers(CalcomEventType $eventType, CalcomV2Service $calcomService): bool
    {
        try {
            // Fetch event type details from Cal.com
            $response = $calcomService->getEventTypeDetails($eventType->calcom_numeric_event_type_id);
            
            if (!$response || !$response['success']) {
                $this->error("    ❌ Failed to fetch details from Cal.com");
                return false;
            }

            $eventTypeData = $response['data']['event_type'] ?? $response['data'] ?? null;
            
            if (!$eventTypeData) {
                $this->error("    ❌ No event type data returned");
                return false;
            }

            // Extract hosts/users
            $hosts = $eventTypeData['hosts'] ?? $eventTypeData['users'] ?? [];
            
            if (empty($hosts)) {
                $this->warn("    ⚠️  No hosts found for this event type");
                return true; // Not an error, just no hosts
            }

            $this->info("    👥 Found " . count($hosts) . " hosts");

            // Begin transaction
            DB::beginTransaction();
            
            try {
                // Get current assignments
                $currentAssignments = DB::table('staff_event_types')
                    ->where('event_type_id', $eventType->id)
                    ->pluck('staff_id')
                    ->toArray();

                $newAssignments = [];
                $assignedCount = 0;

                foreach ($hosts as $host) {
                    $staff = $this->findStaffForHost($host, $eventType->company_id);
                    
                    if ($staff) {
                        // Check if assignment already exists
                        $exists = DB::table('staff_event_types')
                            ->where('staff_id', $staff->id)
                            ->where('event_type_id', $eventType->id)
                            ->exists();

                        if (!$exists) {
                            DB::table('staff_event_types')->insert([
                                'staff_id' => $staff->id,
                                'event_type_id' => $eventType->id,
                                'calcom_user_id' => $host['id'] ?? null,
                                'is_primary' => $host['isPrimary'] ?? false,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            $assignedCount++;
                            $this->info("      ✅ Assigned: {$staff->name}");
                        } else {
                            $this->info("      ↔️  Already assigned: {$staff->name}");
                        }
                        
                        $newAssignments[] = $staff->id;
                    } else {
                        $this->warn("      ⚠️  No staff found for Cal.com user: " . ($host['name'] ?? $host['email'] ?? 'Unknown'));
                    }
                }

                // Remove assignments for staff no longer hosts
                $toRemove = array_diff($currentAssignments, $newAssignments);
                if (!empty($toRemove)) {
                    DB::table('staff_event_types')
                        ->where('event_type_id', $eventType->id)
                        ->whereIn('staff_id', $toRemove)
                        ->delete();
                    
                    $this->info("      🗑️  Removed " . count($toRemove) . " outdated assignments");
                }

                DB::commit();
                
                $this->info("    ✅ Sync completed: {$assignedCount} new assignments");
                return true;
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            $this->error("    ❌ Sync failed: " . $e->getMessage());
            Log::error('Event type user sync failed', [
                'event_type_id' => $eventType->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    protected function dryRunSync(CalcomEventType $eventType, CalcomV2Service $calcomService): void
    {
        try {
            $response = $calcomService->getEventTypeDetails($eventType->calcom_numeric_event_type_id);
            
            if (!$response || !$response['success']) {
                $this->error("    ❌ [DRY-RUN] Would fail to fetch details");
                return;
            }

            $eventTypeData = $response['data']['event_type'] ?? $response['data'] ?? null;
            $hosts = $eventTypeData['hosts'] ?? $eventTypeData['users'] ?? [];
            
            $this->info("    [DRY-RUN] Would sync " . count($hosts) . " hosts:");
            
            foreach ($hosts as $host) {
                $staff = $this->findStaffForHost($host, $eventType->company_id);
                if ($staff) {
                    $this->info("      - Would assign: {$staff->name}");
                } else {
                    $this->warn("      - Would skip (no staff): " . ($host['name'] ?? 'Unknown'));
                }
            }
        } catch (\Exception $e) {
            $this->error("    [DRY-RUN] Would fail: " . $e->getMessage());
        }
    }

    protected function findStaffForHost(array $host, int $companyId): ?Staff
    {
        // Try to find by Cal.com user ID first
        if (isset($host['id'])) {
            $staff = Staff::where('calcom_user_id', $host['id'])->first();
            if ($staff) return $staff;
        }

        // Try by email
        if (isset($host['email'])) {
            $staff = Staff::where('email', $host['email'])
                ->whereHas('branch', function($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })
                ->first();
            if ($staff) return $staff;
        }

        // Try by name (last resort)
        if (isset($host['name'])) {
            $staff = Staff::where('name', $host['name'])
                ->whereHas('branch', function($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })
                ->first();
            if ($staff) return $staff;
        }

        return null;
    }
}