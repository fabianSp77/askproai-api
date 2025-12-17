<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\CalcomWebhookController;
use App\Http\Requests\CalcomWebhookRequest;
use Illuminate\Http\Request;

class ProcessQueuedCalcomWebhooks extends Command
{
    protected $signature = 'calcom:process-queued-webhooks';
    protected $description = 'Process all queued Cal.com webhooks from storage/app/webhook-queue';

    public function handle()
    {
        $queueDir = storage_path('app/webhook-queue');

        if (!is_dir($queueDir)) {
            $this->info('No webhook queue directory found');
            return 0;
        }

        $files = glob($queueDir . '/webhook-*.json');

        if (empty($files)) {
            $this->info('No queued webhooks to process');
            return 0;
        }

        $this->info('Found ' . count($files) . ' queued webhooks');

        $processed = 0;
        $failed = 0;

        foreach ($files as $file) {
            try {
                $payload = file_get_contents($file);

                // Call the existing artisan command that processes single webhooks
                $exitCode = $this->call('calcom:process-webhook', [
                    'payload' => $payload
                ]);

                $triggerEvent = json_decode($payload, true)['triggerEvent'] ?? 'unknown';

                if ($exitCode === 0) {
                    $this->info("✅ Processed: $triggerEvent");
                    $processed++;

                    // Delete processed webhook file
                    @unlink($file);
                } else {
                    $this->error("❌ Failed: $triggerEvent");
                    $failed++;

                    // Move to failed directory
                    $failedDir = $queueDir . '/failed';
                    if (!is_dir($failedDir)) {
                        mkdir($failedDir, 0755, true);
                    }
                    @rename($file, $failedDir . '/' . basename($file));
                }

            } catch (\Exception $e) {
                $this->error("Error processing $file: " . $e->getMessage());
                $failed++;

                // Move failed webhook to failed directory
                $failedDir = $queueDir . '/failed';
                if (!is_dir($failedDir)) {
                    mkdir($failedDir, 0755, true);
                }
                @rename($file, $failedDir . '/' . basename($file));
            }
        }

        $this->info("Processed: $processed | Failed: $failed");
        return 0;
    }
}
