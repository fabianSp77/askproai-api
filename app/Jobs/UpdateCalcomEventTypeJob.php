<?php

namespace App\Jobs;

use App\Models\Service;
use App\Services\CalcomService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateCalcomEventTypeJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    /**
     * The maximum number of seconds the job can run.
     */
    public $timeout = 120;

    protected Service $service;

    /**
     * Create a new job instance.
     */
    public function __construct(Service $service)
    {
        $this->service = $service;
        $this->queue = 'calcom-sync';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            if (!$this->service->calcom_event_type_id) {
                Log::error('[Cal.com Update] Service has no Cal.com Event Type ID', [
                    'service_id' => $this->service->id
                ]);
                return;
            }

            $calcomService = new CalcomService();

            // Call the updateEventType method from CalcomService
            $response = $calcomService->updateEventType($this->service);

            if ($response->successful()) {
                // Update sync status
                $this->service->update([
                    'sync_status' => 'synced',
                    'last_calcom_sync' => now(),
                    'sync_error' => null
                ]);

                Log::info('[Cal.com Update] Successfully updated Event Type', [
                    'service_id' => $this->service->id,
                    'event_type_id' => $this->service->calcom_event_type_id
                ]);
            } else {
                // Handle error response
                $errorMessage = 'Cal.com API error: ' . $response->status();
                $responseBody = $response->json();

                if (isset($responseBody['message'])) {
                    $errorMessage .= ' - ' . $responseBody['message'];
                }

                $this->service->update([
                    'sync_status' => 'error',
                    'sync_error' => $errorMessage,
                    'last_calcom_sync' => now()
                ]);

                Log::error('[Cal.com Update] Failed to update Event Type', [
                    'service_id' => $this->service->id,
                    'event_type_id' => $this->service->calcom_event_type_id,
                    'error' => $errorMessage,
                    'response' => $responseBody
                ]);

                throw new \Exception($errorMessage);
            }

        } catch (\Exception $e) {
            Log::error('[Cal.com Update] Exception during update', [
                'service_id' => $this->service->id,
                'event_type_id' => $this->service->calcom_event_type_id ?? 'none',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Update service with error status
            $this->service->update([
                'sync_status' => 'error',
                'sync_error' => $e->getMessage(),
                'last_calcom_sync' => now()
            ]);

            throw $e; // Re-throw to mark job as failed
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[Cal.com Update] Job failed', [
            'service_id' => $this->service->id,
            'exception' => $exception->getMessage()
        ]);

        // Update service status
        $this->service->update([
            'sync_status' => 'error',
            'sync_error' => 'Job failed: ' . $exception->getMessage()
        ]);
    }
}