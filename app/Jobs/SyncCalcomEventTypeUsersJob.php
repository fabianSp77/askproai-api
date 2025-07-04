<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\MCP\CalcomMCPServer;
use App\Models\Company;
use App\Models\CalcomEventType;

class SyncCalcomEventTypeUsersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $companyId;
    protected $eventTypeId;

    /**
     * Create a new job instance.
     *
     * @param int $companyId
     * @param int|null $eventTypeId Optional specific event type to sync
     */
    public function __construct(int $companyId, ?int $eventTypeId = null)
    {
        $this->companyId = $companyId;
        $this->eventTypeId = $eventTypeId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Log::info('Starting Cal.com event type users sync job', [
            'company_id' => $this->companyId,
            'event_type_id' => $this->eventTypeId
        ]);

        try {
            $company = Company::find($this->companyId);
            if (!$company) {
                Log::error('Company not found for sync job', ['company_id' => $this->companyId]);
                return;
            }

            $calcomMCP = new CalcomMCPServer();

            if ($this->eventTypeId) {
                // Sync specific event type
                $this->syncSingleEventType($calcomMCP, $this->eventTypeId);
            } else {
                // Sync all event types for the company
                $this->syncAllEventTypes($calcomMCP, $company);
            }

            Log::info('Cal.com event type users sync job completed', [
                'company_id' => $this->companyId
            ]);

        } catch (\Exception $e) {
            Log::error('Error in Cal.com event type users sync job', [
                'company_id' => $this->companyId,
                'event_type_id' => $this->eventTypeId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Sync a single event type
     */
    protected function syncSingleEventType(CalcomMCPServer $calcomMCP, int $eventTypeId)
    {
        $result = $calcomMCP->syncEventTypeUsers([
            'event_type_id' => $eventTypeId,
            'company_id' => $this->companyId
        ]);

        if ($result['success']) {
            Log::info('Successfully synced event type users', [
                'event_type_id' => $eventTypeId,
                'matched' => $result['matched'] ?? 0,
                'not_matched' => $result['not_matched'] ?? 0
            ]);
        } else {
            Log::error('Failed to sync event type users', [
                'event_type_id' => $eventTypeId,
                'error' => $result['error'] ?? 'Unknown error'
            ]);
        }
    }

    /**
     * Sync all event types for a company
     */
    protected function syncAllEventTypes(CalcomMCPServer $calcomMCP, Company $company)
    {
        // First, sync the event types themselves
        $syncResult = $calcomMCP->syncEventTypesWithDetails([
            'company_id' => $company->id
        ]);

        if (!$syncResult['success'] ?? false) {
            Log::error('Failed to sync event types', [
                'company_id' => $company->id,
                'error' => $syncResult['error'] ?? 'Unknown error'
            ]);
            return;
        }

        // Get all event types for this company
        $eventTypes = CalcomEventType::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->get();

        $successCount = 0;
        $failedCount = 0;

        foreach ($eventTypes as $eventType) {
            try {
                $result = $calcomMCP->syncEventTypeUsers([
                    'event_type_id' => $eventType->calcom_numeric_event_type_id,
                    'company_id' => $company->id
                ]);

                if ($result['success']) {
                    $successCount++;
                } else {
                    $failedCount++;
                }

                // Add a small delay to avoid rate limiting
                usleep(500000); // 0.5 seconds

            } catch (\Exception $e) {
                $failedCount++;
                Log::error('Error syncing event type users', [
                    'event_type_id' => $eventType->calcom_numeric_event_type_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Completed syncing all event type users', [
            'company_id' => $company->id,
            'total_event_types' => $eventTypes->count(),
            'success' => $successCount,
            'failed' => $failedCount
        ]);
    }

    /**
     * The job failed to process.
     */
    public function failed(\Throwable $exception)
    {
        Log::error('Cal.com sync job failed', [
            'company_id' => $this->companyId,
            'event_type_id' => $this->eventTypeId,
            'exception' => $exception->getMessage()
        ]);
    }
}