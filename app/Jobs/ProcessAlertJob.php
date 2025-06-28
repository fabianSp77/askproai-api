<?php

namespace App\Jobs;

use App\Services\Monitoring\UnifiedAlertingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAlertJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public string $rule;

    public array $data;

    public int $tries = 3;

    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(string $rule, array $data = [])
    {
        $this->rule = $rule;
        $this->data = $data;
        $this->queue = 'alerts';
    }

    /**
     * Execute the job.
     */
    public function handle(UnifiedAlertingService $alertingService): void
    {
        try {
            Log::info('Processing alert job', [
                'rule' => $this->rule,
                'data' => $this->data,
            ]);

            $alertingService->alert($this->rule, $this->data);

            Log::info('Alert job processed successfully', [
                'rule' => $this->rule,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process alert job', [
                'rule' => $this->rule,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('Alert job failed after all retries', [
            'rule' => $this->rule,
            'data' => $this->data,
            'error' => $exception->getMessage(),
        ]);

        // Could send a fallback notification here
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(15);
    }
}
