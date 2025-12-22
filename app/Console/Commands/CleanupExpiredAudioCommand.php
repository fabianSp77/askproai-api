<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Audio\AudioStorageService;
use Illuminate\Console\Command;

/**
 * CleanupExpiredAudioCommand
 *
 * Daily cleanup of expired audio recordings from S3/MinIO.
 *
 * Retention policy: 60 days from upload
 * Fallback: S3 lifecycle rules (if configured)
 *
 * @package App\Console\Commands
 */
class CleanupExpiredAudioCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audio:cleanup
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete audio files older than 60 days from service cases';

    /**
     * Execute the console command.
     */
    public function handle(AudioStorageService $audioService): int
    {
        $this->info('🔊 Audio Cleanup Process');
        $this->info(str_repeat('=', 50));

        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No files will be deleted');
            $this->newLine();
        }

        // Get count of expired files
        $expiredCount = \App\Models\ServiceCase::where('audio_expires_at', '<', now())
            ->whereNotNull('audio_object_key')
            ->count();

        if ($expiredCount === 0) {
            $this->info('✅ No expired audio files found.');
            return Command::SUCCESS;
        }

        $this->info("📊 Found {$expiredCount} expired audio files");
        $this->newLine();

        if ($isDryRun) {
            // Show what would be deleted
            $expiredCases = \App\Models\ServiceCase::where('audio_expires_at', '<', now())
                ->whereNotNull('audio_object_key')
                ->select(['id', 'case_number', 'audio_object_key', 'audio_expires_at', 'company_id'])
                ->limit(20)
                ->get();

            $this->table(
                ['Case ID', 'Case Number', 'Company ID', 'Expired At'],
                $expiredCases->map(fn ($case) => [
                    $case->id,
                    $case->case_number ?? 'N/A',
                    $case->company_id,
                    $case->audio_expires_at->format('Y-m-d H:i'),
                ])->toArray()
            );

            if ($expiredCount > 20) {
                $this->warn("... and " . ($expiredCount - 20) . " more");
            }

            $this->newLine();
            $this->info("Run without --dry-run to delete these files");
            return Command::SUCCESS;
        }

        // Execute cleanup
        $this->info('🔧 Deleting expired audio files...');

        $result = $audioService->cleanupExpired();

        $this->newLine();

        // Show results
        if ($result['deleted'] > 0) {
            $this->info("✅ Deleted {$result['deleted']} expired audio files");
        }

        if ($result['failed'] > 0) {
            $this->warn("⚠️ Failed to delete {$result['failed']} files");
            foreach ($result['errors'] as $error) {
                $this->error("   - {$error}");
            }
        }

        // Summary
        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Files deleted', $result['deleted']],
                ['Files failed', $result['failed']],
            ]
        );

        // Check remaining
        $remainingCount = \App\Models\ServiceCase::whereNotNull('audio_object_key')->count();
        $this->info("📦 {$remainingCount} audio files remaining in storage");

        return $result['failed'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
