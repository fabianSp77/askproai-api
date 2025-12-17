<?php

namespace App\Jobs;

use App\Models\InvitationEmailQueue;
use App\Models\UserInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Cleanup Expired Invitations Job
 *
 * PURPOSE: Housekeeping job to clean up old expired invitations
 *
 * FEATURES:
 * - Soft-deletes invitations older than X days (default: 30)
 * - Cancels pending email queue items for expired invitations
 * - Logs statistics for monitoring
 *
 * QUEUE: 'low' (low priority background task)
 * SCHEDULE: Daily via Laravel Scheduler
 */
class CleanupExpiredInvitationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 300; // 5 minutes

    /**
     * Days after expiry to keep invitations before cleanup
     */
    private int $retentionDays;

    /**
     * Create a new job instance.
     */
    public function __construct(int $retentionDays = 30)
    {
        $this->retentionDays = $retentionDays;
        $this->onQueue('low');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('[CleanupExpiredInvitations] Starting cleanup', [
            'retention_days' => $this->retentionDays
        ]);

        $cutoffDate = now()->subDays($this->retentionDays);

        // ==========================================
        // STEP 1: Cancel pending email queue items
        // ==========================================
        $cancelledEmails = InvitationEmailQueue::where('status', InvitationEmailQueue::STATUS_PENDING)
            ->whereHas('userInvitation', function ($query) use ($cutoffDate) {
                $query->where('expires_at', '<', $cutoffDate)
                    ->whereNull('accepted_at');
            })
            ->get();

        $emailsCancelled = 0;
        foreach ($cancelledEmails as $emailQueue) {
            $emailQueue->cancel();
            $emailsCancelled++;
        }

        Log::info('[CleanupExpiredInvitations] Cancelled pending emails', [
            'count' => $emailsCancelled
        ]);

        // ==========================================
        // STEP 2: Soft-delete expired invitations
        // ==========================================
        $expiredInvitations = UserInvitation::whereNull('accepted_at')
            ->where('expires_at', '<', $cutoffDate)
            ->whereNull('deleted_at')
            ->get();

        $invitationsDeleted = 0;
        foreach ($expiredInvitations as $invitation) {
            $invitation->delete();
            $invitationsDeleted++;
        }

        Log::info('[CleanupExpiredInvitations] Deleted expired invitations', [
            'count' => $invitationsDeleted
        ]);

        // ==========================================
        // STEP 3: Hard-delete old soft-deleted invitations
        // ==========================================
        $oldCutoffDate = now()->subDays($this->retentionDays * 2); // 60 days for soft-deleted
        $hardDeletedCount = UserInvitation::onlyTrashed()
            ->where('deleted_at', '<', $oldCutoffDate)
            ->forceDelete();

        Log::info('[CleanupExpiredInvitations] Hard-deleted old invitations', [
            'count' => $hardDeletedCount
        ]);

        // ==========================================
        // STEP 4: Clean up old failed email queue items
        // ==========================================
        $failedEmailsDeleted = InvitationEmailQueue::where('status', InvitationEmailQueue::STATUS_FAILED)
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        Log::info('[CleanupExpiredInvitations] Deleted old failed emails', [
            'count' => $failedEmailsDeleted
        ]);

        // ==========================================
        // STEP 5: Log statistics
        // ==========================================
        $stats = [
            'emails_cancelled' => $emailsCancelled,
            'invitations_soft_deleted' => $invitationsDeleted,
            'invitations_hard_deleted' => $hardDeletedCount,
            'failed_emails_deleted' => $failedEmailsDeleted,
            'retention_days' => $this->retentionDays,
            'cutoff_date' => $cutoffDate->toIso8601String(),
        ];

        Log::info('[CleanupExpiredInvitations] Cleanup completed', $stats);

        activity()
            ->withProperties($stats)
            ->log('expired_invitations_cleaned');
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[CleanupExpiredInvitations] Job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        activity()
            ->withProperties([
                'error' => $exception->getMessage(),
            ])
            ->log('cleanup_expired_invitations_failed');
    }
}
