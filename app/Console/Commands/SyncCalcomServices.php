<?php

namespace App\Console\Commands;

use App\Jobs\ImportEventTypeJob;
use App\Jobs\UpdateCalcomEventTypeJob;
use App\Models\Service;
use App\Services\CalcomService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncCalcomServices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calcom:sync-services
                            {--force : Force sync all Event Types regardless of last sync time}
                            {--check-only : Only check for differences without syncing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Services with Cal.com Event Types (backup for missed webhooks)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $startTime = now();
        $this->info('========================================');
        $this->info(' Cal.com Service Synchronization');
        $this->info(' ' . now()->format('Y-m-d H:i:s'));
        $this->info('========================================');

        try {
            $calcomService = new CalcomService();

            // Fetch all Event Types from Cal.com
            $this->info('Fetching Event Types from Cal.com...');
            $response = $calcomService->fetchEventTypes();

            if (!$response->successful()) {
                $this->error('Failed to fetch Event Types from Cal.com: ' . $response->status());
                Log::error('[Cal.com Sync] Failed to fetch Event Types', [
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);
                return Command::FAILURE;
            }

            // V2 API returns 'data' field, not 'event_types'
            $eventTypes = $response->json()['data'] ?? $response->json()['event_types'] ?? [];
            $this->info('Found ' . count($eventTypes) . ' Event Types in Cal.com (Team: ' . config('calcom.team_id') . ')');

            // Statistics
            $stats = [
                'total' => count($eventTypes),
                'new' => 0,
                'updated' => 0,
                'unchanged' => 0,
                'errors' => 0
            ];

            // Process each Event Type
            $this->newLine();
            $progressBar = $this->output->createProgressBar(count($eventTypes));
            $progressBar->start();

            foreach ($eventTypes as $eventType) {
                $progressBar->advance();
                $eventTypeId = $eventType['id'] ?? null;

                if (!$eventTypeId) {
                    $stats['errors']++;
                    continue;
                }

                // Check if we have this service
                $service = Service::where('calcom_event_type_id', $eventTypeId)->first();

                if ($this->option('check-only')) {
                    // Just check differences
                    if (!$service) {
                        $stats['new']++;
                    } else {
                        $needsUpdate = $this->checkIfUpdateNeeded($service, $eventType);
                        if ($needsUpdate) {
                            $stats['updated']++;
                        } else {
                            $stats['unchanged']++;
                        }
                    }
                } else {
                    // Actually sync
                    if (!$service) {
                        // New Event Type - import it
                        ImportEventTypeJob::dispatch($eventType);
                        $stats['new']++;
                    } else {
                        // Check if we should update
                        if ($this->option('force') || $this->shouldSync($service, $eventType)) {
                            ImportEventTypeJob::dispatch($eventType);
                            $stats['updated']++;
                        } else {
                            $stats['unchanged']++;
                        }
                    }
                }
            }

            $progressBar->finish();
            $this->newLine(2);

            // Check for orphaned services (exist locally but not in Cal.com)
            $orphanedServices = Service::whereNotNull('calcom_event_type_id')
                ->whereNotIn('calcom_event_type_id', array_column($eventTypes, 'id'))
                ->get();

            if ($orphanedServices->isNotEmpty()) {
                $this->warn('Found ' . $orphanedServices->count() . ' orphaned services (exist locally but not in Cal.com):');
                foreach ($orphanedServices as $service) {
                    $this->warn("  - {$service->name} (Cal.com ID: {$service->calcom_event_type_id})");

                    if (!$this->option('check-only')) {
                        // Deactivate orphaned services
                        $service->update([
                            'is_active' => false,
                            'sync_status' => 'error',
                            'sync_error' => 'Event Type not found in Cal.com',
                            'last_calcom_sync' => now()
                        ]);
                        $this->info("    → Deactivated");
                    }
                }
            }

            // Display statistics
            $this->newLine();
            $this->info('========================================');
            $this->info(' Synchronization Complete');
            $this->info('========================================');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Event Types', $stats['total']],
                    ['New Imports', $stats['new']],
                    ['Updated', $stats['updated']],
                    ['Unchanged', $stats['unchanged']],
                    ['Errors', $stats['errors']],
                    ['Orphaned Services', $orphanedServices->count()]
                ]
            );

            $duration = now()->diffInSeconds($startTime);
            $this->info("Sync completed in {$duration} seconds");

            if ($this->option('check-only')) {
                $this->warn("\nCHECK ONLY - No actual changes were made");
            }

            // Log summary
            Log::info('[Cal.com Sync] Command completed', [
                'stats' => $stats,
                'orphaned' => $orphanedServices->count(),
                'duration_seconds' => $duration,
                'check_only' => $this->option('check-only'),
                'force' => $this->option('force')
            ]);

            // Push local changes to Cal.com if needed
            if (!$this->option('check-only')) {
                $this->syncLocalChangesToCalcom();
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            Log::error('[Cal.com Sync] Command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Sync any local changes back to Cal.com
     */
    private function syncLocalChangesToCalcom(): void
    {
        // Find services that need to sync to Cal.com
        $pendingServices = Service::where('sync_status', 'pending')
            ->whereNotNull('calcom_event_type_id')
            ->get();

        if ($pendingServices->isNotEmpty()) {
            $this->newLine();
            $this->info('Syncing ' . $pendingServices->count() . ' local changes to Cal.com...');

            foreach ($pendingServices as $service) {
                UpdateCalcomEventTypeJob::dispatch($service);
                $this->line("  → Queued sync for: {$service->name}");
            }
        }
    }

    /**
     * Check if service should be synced based on last sync time
     */
    protected function shouldSync(Service $service, array $eventType): bool
    {
        // Always sync if status is error or pending
        if (in_array($service->sync_status, ['error', 'pending'])) {
            return true;
        }

        // Check if significant time has passed (30 minutes)
        if (!$service->last_calcom_sync || $service->last_calcom_sync->lt(now()->subMinutes(30))) {
            return true;
        }

        // Check for field differences
        return $this->checkIfUpdateNeeded($service, $eventType);
    }

    /**
     * Check if Event Type data differs from local service
     */
    protected function checkIfUpdateNeeded(Service $service, array $eventType): bool
    {
        $checks = [
            'name' => $eventType['title'] ?? '',
            'duration_minutes' => $eventType['length'] ?? 30,
            'price' => $eventType['price'] ?? 0,
            'description' => $eventType['description'] ?? '',
            'slug' => $eventType['slug'] ?? null,
            'schedule_id' => $eventType['scheduleId'] ?? null,
            'is_active' => !($eventType['hidden'] ?? false),
            'buffer_time_minutes' => ($eventType['beforeEventBuffer'] ?? 0) + ($eventType['afterEventBuffer'] ?? 0)
        ];

        foreach ($checks as $field => $calcomValue) {
            $localValue = $service->{$field};

            // Special handling for different field types
            if (is_bool($calcomValue)) {
                if ((bool)$localValue !== $calcomValue) {
                    return true;
                }
            } elseif (is_numeric($calcomValue)) {
                if ((float)$localValue !== (float)$calcomValue) {
                    return true;
                }
            } else {
                if ((string)$localValue !== (string)$calcomValue) {
                    return true;
                }
            }
        }

        return false;
    }
}