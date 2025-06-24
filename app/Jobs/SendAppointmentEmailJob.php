<?php

namespace App\Jobs;

use App\Mail\AppointmentConfirmationMail;
use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendAppointmentEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min
    
    /**
     * Create a new job instance.
     */
    public function __construct(
        public Appointment $appointment,
        public string $emailType = 'confirmation',
        public string $locale = 'de'
    ) {
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Check if email notifications are enabled for the company
        $company = $this->appointment->branch->company;
        
        if (!$company->send_booking_confirmations) {
            Log::info('Email notifications disabled for company', [
                'company_id' => $company->id,
                'appointment_id' => $this->appointment->id,
            ]);
            return;
        }
        
        // Check if customer has email
        if (!$this->appointment->customer->email) {
            Log::warning('Customer has no email address', [
                'customer_id' => $this->appointment->customer->id,
                'appointment_id' => $this->appointment->id,
            ]);
            return;
        }
        
        try {
            switch ($this->emailType) {
                case 'confirmation':
                    $this->sendConfirmationEmail();
                    break;
                    
                case 'reminder':
                    $this->sendReminderEmail();
                    break;
                    
                case 'cancellation':
                    $this->sendCancellationEmail();
                    break;
                    
                default:
                    Log::error('Unknown email type', [
                        'type' => $this->emailType,
                        'appointment_id' => $this->appointment->id,
                    ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send appointment email', [
                'type' => $this->emailType,
                'appointment_id' => $this->appointment->id,
                'customer_email' => $this->appointment->customer->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e; // Re-throw to trigger retry
        }
    }
    
    /**
     * Send confirmation email
     */
    protected function sendConfirmationEmail(): void
    {
        Mail::to($this->appointment->customer->email)
            ->send(new AppointmentConfirmationMail($this->appointment, $this->locale));
            
        // Log success
        Log::info('Appointment confirmation email sent', [
            'appointment_id' => $this->appointment->id,
            'customer_email' => $this->appointment->customer->email,
            'locale' => $this->locale,
        ]);
        
        // Update appointment metadata
        $metadata = $this->appointment->metadata ?? [];
        $metadata['confirmation_email_sent_at'] = now()->toDateTimeString();
        $this->appointment->update(['metadata' => $metadata]);
    }
    
    /**
     * Send reminder email (placeholder for future implementation)
     */
    protected function sendReminderEmail(): void
    {
        // TODO: Implement reminder email
        Log::info('Reminder email requested (not yet implemented)', [
            'appointment_id' => $this->appointment->id,
        ]);
    }
    
    /**
     * Send cancellation email (placeholder for future implementation)
     */
    protected function sendCancellationEmail(): void
    {
        // TODO: Implement cancellation email
        Log::info('Cancellation email requested (not yet implemented)', [
            'appointment_id' => $this->appointment->id,
        ]);
    }
    
    /**
     * Calculate the number of seconds until the job should be retried.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addHours(2);
    }
    
    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Email job permanently failed', [
            'type' => $this->emailType,
            'appointment_id' => $this->appointment->id,
            'customer_email' => $this->appointment->customer->email,
            'error' => $exception->getMessage(),
        ]);
        
        // Update appointment metadata to track failure
        $metadata = $this->appointment->metadata ?? [];
        $metadata['email_failed'] = [
            'type' => $this->emailType,
            'error' => $exception->getMessage(),
            'failed_at' => now()->toDateTimeString(),
        ];
        $this->appointment->update(['metadata' => $metadata]);
    }
}