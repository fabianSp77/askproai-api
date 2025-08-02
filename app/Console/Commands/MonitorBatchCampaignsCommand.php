<?php

namespace App\Console\Commands;

use App\Models\RetellAICallCampaign;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class MonitorBatchCampaignsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:monitor-batches
                            {--campaign-id= : Monitor specific campaign batch}
                            {--status=running : Filter by campaign status}
                            {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor batch processing status for AI call campaigns';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $campaignId = $this->option('campaign-id');
        $status = $this->option('status');
        $asJson = $this->option('json');

        if ($campaignId) {
            $campaigns = RetellAICallCampaign::where('id', $campaignId)->get();
        } else {
            $campaigns = RetellAICallCampaign::where('status', $status)->get();
        }

        if ($campaigns->isEmpty()) {
            $this->warn('No campaigns found.');

            return 0;
        }

        $batchStatuses = [];

        foreach ($campaigns as $campaign) {
            $batchId = $campaign->metadata['batch_id'] ?? null;

            if (! $batchId) {
                continue;
            }

            try {
                $batch = Bus::findBatch($batchId);

                if (! $batch) {
                    continue;
                }

                $batchStatus = [
                    'campaign_id' => $campaign->id,
                    'campaign_name' => $campaign->name,
                    'batch_id' => $batch->id,
                    'batch_name' => $batch->name,
                    'total_jobs' => $batch->totalJobs,
                    'pending_jobs' => $batch->pendingJobs,
                    'processed_jobs' => $batch->processedJobs(),
                    'failed_jobs' => $batch->failedJobs,
                    'progress' => $batch->progress() . '%',
                    'finished' => $batch->finished(),
                    'cancelled' => $batch->cancelled(),
                    'created_at' => date('Y-m-d H:i:s', $batch->createdAt),
                ];

                if ($batch->finishedAt) {
                    $batchStatus['finished_at'] = date('Y-m-d H:i:s', $batch->finishedAt);
                    $batchStatus['duration'] = round(($batch->finishedAt - $batch->createdAt) / 60, 2) . ' minutes';
                }

                // Calculate throughput
                if ($batch->processedJobs() > 0) {
                    $runtime = (time() - $batch->createdAt) / 60; // minutes
                    $batchStatus['throughput'] = round($batch->processedJobs() / $runtime, 2) . ' calls/minute';
                }

                $batchStatuses[] = $batchStatus;

                if (! $asJson) {
                    $this->displayBatchStatus($batchStatus);
                }
            } catch (\Exception $e) {
                $this->error("Error fetching batch for campaign {$campaign->id}: " . $e->getMessage());
            }
        }

        if ($asJson) {
            $this->line(json_encode($batchStatuses, JSON_PRETTY_PRINT));
        } else {
            $this->info("\nTotal campaigns monitored: " . count($batchStatuses));
        }

        return 0;
    }

    protected function displayBatchStatus(array $status): void
    {
        $this->info("\n=== Campaign: {$status['campaign_name']} ===");
        $this->line("Campaign ID: {$status['campaign_id']}");
        $this->line("Batch ID: {$status['batch_id']}");

        $table = [
            ['Total Jobs', $status['total_jobs']],
            ['Processed', $status['processed_jobs']],
            ['Pending', $status['pending_jobs']],
            ['Failed', $status['failed_jobs']],
            ['Progress', $status['progress']],
        ];

        if (isset($status['throughput'])) {
            $table[] = ['Throughput', $status['throughput']];
        }

        if ($status['finished']) {
            $table[] = ['Status', 'Finished'];
            if (isset($status['duration'])) {
                $table[] = ['Duration', $status['duration']];
            }
        } elseif ($status['cancelled']) {
            $table[] = ['Status', 'Cancelled'];
        } else {
            $table[] = ['Status', 'Running'];
        }

        $this->table(['Metric', 'Value'], $table);

        // Show progress bar
        if (! $status['finished'] && ! $status['cancelled']) {
            $progress = intval(str_replace('%', '', $status['progress']));
            $this->output->write($this->getProgressBar($progress));
        }
    }

    protected function getProgressBar(int $progress): string
    {
        $filled = floor($progress / 2);
        $empty = 50 - $filled;

        return "\n[" . str_repeat('█', $filled) . str_repeat('░', $empty) . "] {$progress}%\n";
    }
}
