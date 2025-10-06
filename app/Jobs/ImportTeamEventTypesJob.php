<?php

namespace App\Jobs;

use App\Models\Company;
use App\Services\CalcomV2Service;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportTeamEventTypesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array
     */
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    /**
     * The maximum number of seconds the job can run.
     *
     * @var int
     */
    public $timeout = 300; // 5 minutes

    protected Company $company;
    protected bool $syncMembers;

    /**
     * Create a new job instance.
     *
     * @param Company $company
     * @param bool $syncMembers
     */
    public function __construct(Company $company, bool $syncMembers = true)
    {
        $this->company = $company;
        $this->syncMembers = $syncMembers;
        $this->queue = 'calcom-sync';
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        Log::info('[Team Import] Starting team event types import', [
            'company_id' => $this->company->id,
            'company_name' => $this->company->name,
            'team_id' => $this->company->calcom_team_id
        ]);

        // Validate that company has a team ID
        if (!$this->company->calcom_team_id) {
            Log::error('[Team Import] Company has no Cal.com team ID', [
                'company_id' => $this->company->id
            ]);

            $this->company->update([
                'team_sync_status' => 'error',
                'team_sync_error' => 'No Cal.com team ID configured',
                'last_team_sync' => now()
            ]);

            return;
        }

        // Update status to syncing
        $this->company->update([
            'team_sync_status' => 'syncing',
            'team_sync_error' => null
        ]);

        try {
            // Initialize CalcomV2Service with company context
            $calcomService = new CalcomV2Service($this->company);

            // Import team event types
            $importResult = $calcomService->importTeamEventTypes($this->company);

            if (!$importResult['success']) {
                throw new \Exception($importResult['message'] ?? 'Import failed');
            }

            Log::info('[Team Import] Team event types imported successfully', [
                'company_id' => $this->company->id,
                'summary' => $importResult['summary'] ?? []
            ]);

            // Optionally sync team members
            if ($this->syncMembers) {
                $membersResult = $calcomService->syncTeamMembers($this->company);

                if (!$membersResult['success']) {
                    Log::warning('[Team Import] Failed to sync team members', [
                        'company_id' => $this->company->id,
                        'error' => $membersResult['error'] ?? 'Unknown error'
                    ]);
                } else {
                    Log::info('[Team Import] Team members synced', [
                        'company_id' => $this->company->id,
                        'members_count' => $membersResult['members_count'] ?? 0
                    ]);
                }
            }

            // Update company with successful sync
            $this->company->update([
                'team_sync_status' => 'synced',
                'team_sync_error' => null,
                'last_team_sync' => now()
            ]);

            // Clear team cache
            $calcomService->clearTeamCache($this->company->calcom_team_id);

        } catch (\Exception $e) {
            Log::error('[Team Import] Failed to import team event types', [
                'company_id' => $this->company->id,
                'team_id' => $this->company->calcom_team_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Update company with error
            $this->company->update([
                'team_sync_status' => 'error',
                'team_sync_error' => $e->getMessage(),
                'last_team_sync' => now()
            ]);

            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[Team Import] Job failed after all retries', [
            'company_id' => $this->company->id,
            'error' => $exception->getMessage()
        ]);

        // Update company with permanent failure
        $this->company->update([
            'team_sync_status' => 'error',
            'team_sync_error' => 'Failed after multiple attempts: ' . $exception->getMessage(),
            'last_team_sync' => now()
        ]);
    }
}