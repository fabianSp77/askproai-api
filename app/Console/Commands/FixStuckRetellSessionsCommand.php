<?php

namespace App\Console\Commands;

use App\Jobs\ServiceGateway\EnrichServiceCaseJob;
use App\Models\Call;
use App\Models\RetellCallSession;
use App\Models\ServiceCase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Fix Stuck Retell Sessions Command
 *
 * Repairs RetellCallSessions that are stuck in 'in_progress' status
 * even though their associated Call is already 'completed'.
 *
 * Root Cause (2025-12-29):
 * Retell changed their webhook signature format from t=timestamp,v1=hmac to v=timestamp,d=digest.
 * The middleware was rejecting all lifecycle webhooks (call_ended, call_analyzed) with 401,
 * preventing handleCallEnded() from being called and leaving sessions stuck.
 *
 * This command:
 * 1. Finds all stuck sessions (in_progress with completed Call)
 * 2. Updates them to 'completed' status
 * 3. Re-triggers EnrichServiceCaseJob for affected ServiceCases with 'timeout' status
 *
 * Usage:
 *   php artisan retell:fix-stuck-sessions              # Fix all stuck sessions
 *   php artisan retell:fix-stuck-sessions --dry-run    # Preview without changes
 *   php artisan retell:fix-stuck-sessions --limit=100  # Process in batches
 */
class FixStuckRetellSessionsCommand extends Command
{
    protected $signature = 'retell:fix-stuck-sessions
                          {--dry-run : Show what would be fixed without making changes}
                          {--limit=0 : Maximum number of sessions to process (0 = unlimited)}
                          {--skip-enrichment : Skip re-triggering EnrichServiceCaseJob}';

    protected $description = 'Fix RetellCallSessions stuck in in_progress status due to webhook signature mismatch';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');
        $skipEnrichment = $this->option('skip-enrichment');

        $this->info('🔍 Searching for stuck RetellCallSessions...');

        // Build query for stuck sessions
        // A session is stuck if:
        // 1. call_status = 'in_progress'
        // 2. Associated Call exists (via retell_call_id) and is 'completed'
        //
        // NOTE: The RetellCallSession.call relationship uses external_id which is often empty.
        // We need to join on retell_call_id instead for accurate matching.
        $query = RetellCallSession::where('call_status', 'in_progress')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('calls')
                    ->whereColumn('calls.retell_call_id', 'retell_call_sessions.call_id')
                    ->where('calls.status', 'completed')
                    ->whereNull('calls.deleted_at');
            });

        $totalStuck = $query->count();

        if ($totalStuck === 0) {
            $this->info('✅ No stuck sessions found. System is healthy!');
            return Command::SUCCESS;
        }

        $this->warn("⚠️  Found {$totalStuck} stuck session(s)");

        // Apply limit if specified
        if ($limit > 0) {
            $query->limit($limit);
            $this->info("📊 Processing up to {$limit} sessions (--limit)");
        }

        // Eager load call via retell_call_id (not the broken relationship)
        $stuckSessions = $query->get()->map(function ($session) {
            $session->setRelation('linkedCall', Call::where('retell_call_id', $session->call_id)->first());
            return $session;
        });

        // Display sample of stuck sessions
        $sampleSize = min(10, $stuckSessions->count());
        $this->info("\n📋 Sample of stuck sessions (showing {$sampleSize} of {$stuckSessions->count()}):");

        $this->table(
            ['Session ID', 'Call ID', 'Call Status', 'Session Status', 'Started At', 'Hours Stuck'],
            $stuckSessions->take($sampleSize)->map(function ($session) {
                return [
                    substr($session->id, 0, 8) . '...',
                    $session->call_id,
                    $session->linkedCall?->status ?? 'N/A',
                    $session->call_status,
                    $session->started_at?->format('Y-m-d H:i') ?? 'N/A',
                    $session->started_at ? $session->started_at->diffInHours(now()) : 'N/A',
                ];
            })
        );

        // Count affected ServiceCases
        $callIds = $stuckSessions->map(fn($s) => $s->linkedCall?->id)->filter()->values();
        $affectedCases = ServiceCase::where('enrichment_status', 'timeout')
            ->whereIn('call_id', $callIds)
            ->count();

        $this->info("\n📊 Statistics:");
        $this->line("   • Total stuck sessions: {$totalStuck}");
        $this->line("   • Processing in this run: {$stuckSessions->count()}");
        $this->line("   • ServiceCases with 'timeout' status: {$affectedCases}");

        if ($dryRun) {
            $this->comment("\n🔬 DRY RUN MODE - No changes will be made");
            return Command::SUCCESS;
        }

        // Confirm before proceeding (skip in non-interactive mode/cron)
        if ($this->input->isInteractive()) {
            if (!$this->confirm('Do you want to fix these stuck sessions?', true)) {
                $this->comment('Aborted by user.');
                return Command::SUCCESS;
            }
        }

        $fixed = 0;
        $enrichmentTriggered = 0;
        $errors = 0;

        $progressBar = $this->output->createProgressBar($stuckSessions->count());
        $progressBar->start();

        foreach ($stuckSessions as $session) {
            try {
                // Get the linked call via retell_call_id
                $linkedCall = $session->linkedCall;

                // Get ended_at from the Call if available
                $endedAt = $linkedCall?->end_timestamp ?? $linkedCall?->updated_at ?? now();

                // Update session to completed
                $session->update([
                    'call_status' => 'completed',
                    'ended_at' => $endedAt,
                    'disconnection_reason' => $session->disconnection_reason ?? 'cleanup_command',
                ]);

                Log::info('[FixStuckRetellSessions] Fixed stuck session', [
                    'session_id' => $session->id,
                    'call_id' => $session->call_id,
                    'ended_at' => $endedAt,
                ]);

                $fixed++;

                // Re-trigger enrichment for affected ServiceCase if not skipped
                if (!$skipEnrichment && $linkedCall) {
                    $case = ServiceCase::where('call_id', $linkedCall->id)
                        ->where('enrichment_status', 'timeout')
                        ->first();

                    if ($case) {
                        EnrichServiceCaseJob::dispatch($linkedCall->id, $session->call_id);

                        Log::info('[FixStuckRetellSessions] Dispatched EnrichServiceCaseJob', [
                            'case_id' => $case->id,
                            'call_id' => $linkedCall->id,
                            'retell_call_id' => $session->call_id,
                        ]);

                        $enrichmentTriggered++;
                    }
                }

            } catch (\Exception $e) {
                Log::error('[FixStuckRetellSessions] Failed to fix session', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);

                $errors++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info("✅ Results:");
        $this->line("   • Sessions fixed: {$fixed}");
        if (!$skipEnrichment) {
            $this->line("   • EnrichServiceCaseJobs dispatched: {$enrichmentTriggered}");
        }
        if ($errors > 0) {
            $this->error("   • Errors: {$errors}");
        }

        if ($limit > 0 && $totalStuck > $limit) {
            $remaining = $totalStuck - $limit;
            $this->comment("\n💡 {$remaining} more sessions remaining. Run again to process more.");
        }

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
