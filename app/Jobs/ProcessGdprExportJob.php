<?php

namespace App\Jobs;

use App\Models\GdprRequest;
use App\Services\GdprService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessGdprExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected GdprRequest $gdprRequest;

    /**
     * Create a new job instance.
     */
    public function __construct(GdprRequest $gdprRequest)
    {
        $this->gdprRequest = $gdprRequest;
    }

    /**
     * Execute the job.
     */
    public function handle(GdprService $gdprService)
    {
        Log::info('Processing GDPR export job', [
            'request_id' => $this->gdprRequest->id,
            'customer_id' => $this->gdprRequest->customer_id,
        ]);

        try {
            $this->gdprRequest->markAsProcessing();
            
            $customer = $this->gdprRequest->customer;
            if (!$customer) {
                throw new \Exception('Customer not found');
            }

            $exportPath = $gdprService->createExportFile($customer);
            
            $this->gdprRequest->markAsCompleted([
                'export_file_path' => $exportPath,
            ]);

            // Send notification email
            if (class_exists('App\Notifications\GdprExportReadyNotification')) {
                $customer->notify(new \App\Notifications\GdprExportReadyNotification($this->gdprRequest));
            }

            Log::info('GDPR export completed', [
                'request_id' => $this->gdprRequest->id,
                'export_path' => $exportPath,
            ]);
            
        } catch (\Exception $e) {
            Log::error('GDPR export failed', [
                'request_id' => $this->gdprRequest->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $this->gdprRequest->markAsRejected('Export failed: ' . $e->getMessage());
            
            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * The job failed to process.
     */
    public function failed(\Throwable $exception)
    {
        Log::error('GDPR export job failed', [
            'request_id' => $this->gdprRequest->id,
            'exception' => $exception->getMessage(),
        ]);
    }
}