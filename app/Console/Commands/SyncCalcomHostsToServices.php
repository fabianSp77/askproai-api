<?php

namespace App\Console\Commands;

use App\Models\Service;
use App\Models\Staff;
use App\Models\Branch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncCalcomHostsToServices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calcom:sync-hosts
                            {--branch= : Branch name to sync (optional, syncs all if not provided)}
                            {--company= : Company ID to sync (optional)}
                            {--dry-run : Simulate without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize Cal.com event type hosts to service-staff assignments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Starting Cal.com Hosts Synchronization...');
        $this->newLine();

        $baseUrl = rtrim(config('services.calcom.base_url'), '/');
        $apiKey = config('services.calcom.api_key');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Determine which branches to process
        $branches = $this->getBranchesToSync();

        if ($branches->isEmpty()) {
            $this->error('No branches found to sync.');
            return Command::FAILURE;
        }

        $totalSynced = 0;
        $totalErrors = 0;
        $totalHostsAssigned = 0;

        foreach ($branches as $branch) {
            $this->info("📍 Processing Branch: {$branch->name} (Company ID: {$branch->company_id})");

            $result = $this->syncBranch($branch, $baseUrl, $apiKey, $dryRun);

            $totalSynced += $result['synced'];
            $totalErrors += $result['errors'];
            $totalHostsAssigned += $result['hosts'];

            $this->newLine();
        }

        // Summary
        $this->info('═════════════════════════════════════════');
        $this->info('📊 SYNCHRONIZATION SUMMARY');
        $this->info('═════════════════════════════════════════');
        $this->info("✅ Services Synchronized: {$totalSynced}");
        $this->info("❌ Errors: {$totalErrors}");
        $this->info("📋 Total Host Assignments: {$totalHostsAssigned}");

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN - No actual changes were made');
        }

        return $totalErrors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Get branches to sync based on command options
     */
    protected function getBranchesToSync()
    {
        $branchName = $this->option('branch');
        $companyId = $this->option('company');

        $query = Branch::query();

        if ($branchName) {
            $query->where('name', 'LIKE', "%{$branchName}%");
        }

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->get();
    }

    /**
     * Sync a single branch
     */
    protected function syncBranch(Branch $branch, string $baseUrl, string $apiKey, bool $dryRun): array
    {
        // Get all services with Cal.com IDs
        $services = Service::where('company_id', $branch->company_id)
            ->where('is_active', true)
            ->whereNotNull('calcom_event_type_id')
            ->get();

        if ($services->isEmpty()) {
            $this->warn("  ⚠️  No services with Cal.com IDs found");
            return ['synced' => 0, 'errors' => 0, 'hosts' => 0];
        }

        $this->info("  Services to sync: {$services->count()}");

        // Get all staff for this company
        $allStaff = Staff::where('company_id', $branch->company_id)->get();

        // Build mapping: calcom_user_id => staff_id
        $calcomUserIdToStaffId = [];
        foreach ($allStaff as $staff) {
            if ($staff->calcom_user_id) {
                $calcomUserIdToStaffId[$staff->calcom_user_id] = $staff->id;
            }
        }

        if (empty($calcomUserIdToStaffId)) {
            $this->warn("  ⚠️  No staff with Cal.com User IDs found");
        } else {
            $this->info("  Staff with Cal.com IDs: " . count($calcomUserIdToStaffId));
        }

        $syncedServices = 0;
        $errors = 0;
        $totalHosts = 0;

        $progressBar = $this->output->createProgressBar($services->count());
        $progressBar->start();

        foreach ($services as $service) {
            try {
                // Fetch event type details from Cal.com
                $response = Http::timeout(10)
                    ->withHeaders(['Authorization' => "Bearer {$apiKey}"])
                    ->acceptJson()
                    ->get("{$baseUrl}/event-types/{$service->calcom_event_type_id}");

                if (!$response->successful()) {
                    $errors++;
                    Log::warning("Cal.com API error for service {$service->id}", [
                        'status' => $response->status(),
                        'service_id' => $service->id,
                    ]);
                    $progressBar->advance();
                    continue;
                }

                $eventTypeData = $response->json();
                $hosts = $eventTypeData['data']['hosts'] ?? [];

                // Extract Cal.com user IDs from hosts
                $calcomUserIds = array_filter(array_map(function($host) {
                    return $host['userId'] ?? null;
                }, $hosts));

                // Map Cal.com user IDs to our Staff IDs
                $staffIdsToAssign = [];
                $unknownCalcomIds = [];

                foreach ($calcomUserIds as $calcomUserId) {
                    if (isset($calcomUserIdToStaffId[$calcomUserId])) {
                        $staffIdsToAssign[] = $calcomUserIdToStaffId[$calcomUserId];
                    } else {
                        $unknownCalcomIds[] = $calcomUserId;
                    }
                }

                // Sync staff to service via pivot table (unless dry run)
                if (!$dryRun) {
                    $service->staff()->sync($staffIdsToAssign);
                }

                $totalHosts += count($staffIdsToAssign);
                $syncedServices++;

                if (!empty($unknownCalcomIds)) {
                    Log::info("Unknown Cal.com user IDs for service {$service->id}", [
                        'service_id' => $service->id,
                        'unknown_ids' => $unknownCalcomIds,
                    ]);
                }

                usleep(200000); // 200ms delay to avoid rate limiting

            } catch (\Exception $e) {
                $errors++;
                Log::error("Error syncing service {$service->id}: {$e->getMessage()}", [
                    'service_id' => $service->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        return [
            'synced' => $syncedServices,
            'errors' => $errors,
            'hosts' => $totalHosts,
        ];
    }
}
