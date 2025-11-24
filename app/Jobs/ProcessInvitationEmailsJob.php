<?php

namespace App\Jobs;

use App\Models\InvitationEmailQueue;
use App\Models\UserInvitation;
use App\Notifications\UserInvitationNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Process Invitation Emails Job
 *
 * PURPOSE: Background processing of invitation email queue with retry mechanism
 *
 * FEATURES:
 * - Processes emails ready to send (pending + scheduled time passed)
 * - Exponential backoff: 5min → 30min → 2hr
 * - Max 3 attempts before marking as failed
 * - Activity logging for monitoring
 *
 * QUEUE: 'emails' (dedicated queue for email processing)
 * SCHEDULE: Every 5 minutes via Laravel Scheduler
 */
class ProcessInvitationEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 1; // Don't retry job itself - we handle retries in the email queue

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('[ProcessInvitationEmails] Starting email queue processing');

        $processedCount = 0;
        $failedCount = 0;
        $successCount = 0;

        // Get all emails ready to send
        $emailQueue = InvitationEmailQueue::readyToSend()
            ->with('userInvitation')
            ->orderBy('next_attempt_at')
            ->limit(100) // Process max 100 emails per run
            ->get();

        if ($emailQueue->isEmpty()) {
            Log::info('[ProcessInvitationEmails] No emails to process');
            return;
        }

        Log::info('[ProcessInvitationEmails] Processing emails', [
            'count' => $emailQueue->count()
        ]);

        foreach ($emailQueue as $queueItem) {
            $processedCount++;

            try {
                // Validate invitation still exists and is valid
                if (!$queueItem->userInvitation) {
                    Log::warning('[ProcessInvitationEmails] Invitation not found', [
                        'queue_id' => $queueItem->id,
                        'invitation_id' => $queueItem->user_invitation_id,
                    ]);
                    $queueItem->cancel();
                    continue;
                }

                $invitation = $queueItem->userInvitation;

                // Skip if invitation already accepted or expired
                if ($invitation->accepted_at) {
                    Log::info('[ProcessInvitationEmails] Invitation already accepted', [
                        'queue_id' => $queueItem->id,
                        'invitation_id' => $invitation->id,
                    ]);
                    $queueItem->cancel();
                    continue;
                }

                if ($invitation->isExpired()) {
                    Log::info('[ProcessInvitationEmails] Invitation expired', [
                        'queue_id' => $queueItem->id,
                        'invitation_id' => $invitation->id,
                    ]);
                    $queueItem->cancel();
                    continue;
                }

                // Send email
                $this->sendInvitationEmail($invitation, $queueItem);
                $successCount++;

            } catch (\Exception $e) {
                $failedCount++;

                Log::error('[ProcessInvitationEmails] Email sending failed', [
                    'queue_id' => $queueItem->id,
                    'invitation_id' => $queueItem->user_invitation_id,
                    'attempt' => $queueItem->attempts + 1,
                    'error' => $e->getMessage(),
                ]);

                // Record failure (will handle retry logic)
                $queueItem->recordFailure($e->getMessage());
            }
        }

        Log::info('[ProcessInvitationEmails] Finished processing', [
            'processed' => $processedCount,
            'success' => $successCount,
            'failed' => $failedCount,
        ]);

        // Log metrics for monitoring
        activity()
            ->withProperties([
                'processed' => $processedCount,
                'success' => $successCount,
                'failed' => $failedCount,
                'stats' => InvitationEmailQueue::getDeliveryStats(7), // Last 7 days
            ])
            ->log('invitation_emails_processed');
    }

    /**
     * Send invitation email via notification
     */
    private function sendInvitationEmail(UserInvitation $invitation, InvitationEmailQueue $queueItem): void
    {
        // Get the inviter (user who created the invitation)
        $inviter = $invitation->inviter;

        if (!$inviter) {
            throw new \Exception('Inviter user not found');
        }

        // Send notification
        $inviter->notify(new UserInvitationNotification($invitation));

        // Mark as sent
        $queueItem->markAsSent();

        Log::info('[ProcessInvitationEmails] Email sent successfully', [
            'queue_id' => $queueItem->id,
            'invitation_id' => $invitation->id,
            'email' => $invitation->email,
            'attempt' => $queueItem->attempts + 1,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[ProcessInvitationEmails] Job failed completely', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        activity()
            ->withProperties([
                'error' => $exception->getMessage(),
            ])
            ->log('invitation_email_job_failed');
    }
}
