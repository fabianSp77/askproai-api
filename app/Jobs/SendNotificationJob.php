<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Appointment;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array
     */
    public $backoff = [30, 60, 120];

    protected Appointment $appointment;
    protected string $type;
    protected array $channels;

    /**
     * Create a new job instance.
     */
    public function __construct(Appointment $appointment, string $type, array $channels = ['email'])
    {
        $this->appointment = $appointment;
        $this->type = $type;
        $this->channels = $channels;
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        try {
            Log::info('Processing notification job', [
                'appointment_id' => $this->appointment->id,
                'type' => $this->type,
                'channels' => $this->channels
            ]);

            foreach ($this->channels as $channel) {
                $method = 'send' . ucfirst($channel) . 'Notification';
                
                if (method_exists($notificationService, $method)) {
                    $notificationService->$method($this->appointment, $this->type);
                }
            }

            // Log successful notification
            DB::table('notification_log')->insert([
                'appointment_id' => $this->appointment->id,
                'customer_id' => $this->appointment->customer_id,
                'type' => $this->type,
                'channels' => json_encode($this->channels),
                'status' => 'sent',
                'sent_at' => now(),
                'created_at' => now()
            ]);

        } catch (\Exception $e) {
            Log::error('Notification job failed', [
                'appointment_id' => $this->appointment->id,
                'type' => $this->type,
                'error' => $e->getMessage()
            ]);

            // Log failed notification
            DB::table('notification_log')->insert([
                'appointment_id' => $this->appointment->id,
                'customer_id' => $this->appointment->customer_id,
                'type' => $this->type,
                'channels' => json_encode($this->channels),
                'status' => 'failed',
                'error' => $e->getMessage(),
                'created_at' => now()
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Notification job permanently failed', [
            'appointment_id' => $this->appointment->id,
            'type' => $this->type,
            'error' => $exception->getMessage()
        ]);
    }
}