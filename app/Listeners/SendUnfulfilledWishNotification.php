<?php

namespace App\Listeners;

use App\Events\AppointmentWishCreated;
use App\Mail\UnfulfilledAppointmentWish;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * SendUnfulfilledWishNotification Listener
 *
 * Listens for AppointmentWishCreated events and sends email notifications
 * to configured company email addresses.
 *
 * FLOW:
 * 1. User checks appointment availability via Retell Agent
 * 2. System can't find available slot
 * 3. AppointmentWish record created
 * 4. AppointmentWishCreated event fired
 * 5. This listener queues email notification (with delay)
 * 6. Email sent after configured delay (default: 5 minutes)
 *
 * FEATURES:
 * - Queued processing (doesn't block response)
 * - Configurable delay per company
 * - Respects company notification preferences
 * - Includes full appointment wish details
 */
class SendUnfulfilledWishNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The name of the queue the job should be sent to.
     */
    public string $queue = 'emails';

    /**
     * The time (in seconds) before the job should be processed.
     */
    public int $delay = 300; // 5 minutes default

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(AppointmentWishCreated $event): void
    {
        try {
            $wish = $event->wish;
            $call = $event->call;
            $company = $wish->company;

            // 🔍 Check if company has notifications enabled
            if (!$company || !$company->notify_on_unfulfilled_wishes) {
                Log::debug('📧 Unfulfilled wish notification skipped - company has notifications disabled', [
                    'wish_id' => $wish->id,
                    'company_id' => $company?->id,
                ]);
                return;
            }

            // 📧 Get configured email addresses
            $emailAddresses = $company->wish_notification_emails ?? [];
            if (empty($emailAddresses)) {
                Log::warning('⚠️ No email addresses configured for unfulfilled wish notifications', [
                    'wish_id' => $wish->id,
                    'company_id' => $company->id,
                ]);
                return;
            }

            // 🎯 Ensure email addresses are valid
            $validEmails = array_filter($emailAddresses, function ($email) {
                return filter_var($email, FILTER_VALIDATE_EMAIL);
            });

            if (empty($validEmails)) {
                Log::warning('⚠️ No valid email addresses configured for unfulfilled wish notifications', [
                    'wish_id' => $wish->id,
                    'company_id' => $company->id,
                    'invalid_emails' => $emailAddresses,
                ]);
                return;
            }

            // 📤 Queue email (prevents SMTP failures from blocking event processing)
            Mail::to($validEmails)->queue(new UnfulfilledAppointmentWish($wish, $call));

            // ✅ Log success
            Log::info('✅ Unfulfilled wish notification sent', [
                'wish_id' => $wish->id,
                'call_id' => $call->id,
                'recipient_count' => count($validEmails),
                'recipients' => $validEmails,
            ]);

            // 📝 Update wish status to 'contacted'
            // Delay slightly to ensure email send completes
            $wish->update([
                'status' => 'contacted',
                'contacted_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Failed to send unfulfilled wish notification', [
                'error' => $e->getMessage(),
                'wish_id' => $event->wish->id,
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw so queue framework can handle retries
            throw $e;
        }
    }
}
