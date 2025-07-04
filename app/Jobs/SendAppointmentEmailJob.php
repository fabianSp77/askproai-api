<?php

namespace App\Jobs;

use App\Mail\AppointmentConfirmationMail;
use App\Mail\AppointmentCancellationMail;
use App\Mail\AppointmentRescheduledMail;
use App\Mail\AppointmentReminder;
use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

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
        public string $locale = 'de',
        public ?string $cancellationReason = null,
        public ?string $rescheduleReason = null,
        public ?Carbon $oldStartTime = null,
        public ?Carbon $oldEndTime = null
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
                    
                case 'rescheduled':
                    $this->sendRescheduledEmail();
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
     * Send reminder email
     */
    protected function sendReminderEmail(): void
    {
        Mail::to($this->appointment->customer->email)
            ->send(new AppointmentReminder($this->appointment, $this->locale));
            
        Log::info('Appointment reminder email sent', [
            'appointment_id' => $this->appointment->id,
            'customer_email' => $this->appointment->customer->email,
            'locale' => $this->locale,
        ]);
        
        // Update appointment metadata based on time until appointment
        $metadata = $this->appointment->metadata ?? [];
        $hoursUntil = now()->diffInHours($this->appointment->starts_at);
        
        if ($hoursUntil <= 2) {
            $metadata['reminder_2h_sent_at'] = now()->toDateTimeString();
        } elseif ($hoursUntil <= 24) {
            $metadata['reminder_24h_sent_at'] = now()->toDateTimeString();
        }
        
        $this->appointment->update(['metadata' => $metadata]);
    }
    
    /**
     * Send cancellation email
     */
    protected function sendCancellationEmail(): void
    {
        Mail::to($this->appointment->customer->email)
            ->send(new AppointmentCancellationMail(
                $this->appointment, 
                $this->cancellationReason,
                $this->locale
            ));
            
        Log::info('Appointment cancellation email sent', [
            'appointment_id' => $this->appointment->id,
            'customer_email' => $this->appointment->customer->email,
            'reason' => $this->cancellationReason,
            'locale' => $this->locale,
        ]);
        
        // Update appointment metadata
        $metadata = $this->appointment->metadata ?? [];
        $metadata['cancellation_email_sent_at'] = now()->toDateTimeString();
        $this->appointment->update(['metadata' => $metadata]);
    }
    
    /**
     * Send rescheduled email
     */
    protected function sendRescheduledEmail(): void
    {
        if (!$this->oldStartTime || !$this->oldEndTime) {
            Log::error('Cannot send rescheduled email without old times', [
                'appointment_id' => $this->appointment->id,
            ]);
            return;
        }
        
        Mail::to($this->appointment->customer->email)
            ->send(new AppointmentRescheduledMail(
                $this->appointment,
                $this->oldStartTime,
                $this->oldEndTime,
                $this->rescheduleReason,
                $this->locale
            ));
            
        Log::info('Appointment rescheduled email sent', [
            'appointment_id' => $this->appointment->id,
            'customer_email' => $this->appointment->customer->email,
            'old_time' => $this->oldStartTime->format('Y-m-d H:i'),
            'new_time' => $this->appointment->starts_at->format('Y-m-d H:i'),
            'reason' => $this->rescheduleReason,
            'locale' => $this->locale,
        ]);
        
        // Update appointment metadata
        $metadata = $this->appointment->metadata ?? [];
        $metadata['rescheduled_email_sent_at'] = now()->toDateTimeString();
        $metadata['rescheduled_from'] = $this->oldStartTime->format('Y-m-d H:i');
        $this->appointment->update(['metadata' => $metadata]);
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