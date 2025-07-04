<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Appointment;
use App\Jobs\SendAppointmentReminderJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ScheduleAppointmentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:schedule-reminders 
                            {--type= : Reminder type (24h, 2h, 30m, all)}
                            {--dry-run : Show what would be scheduled without actually scheduling}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Schedule appointment reminders for upcoming appointments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type') ?? 'all';
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('DRY RUN MODE - No reminders will actually be sent');
        }
        
        $this->info('Scheduling appointment reminders...');
        
        if ($type === 'all' || $type === '24h') {
            $this->schedule24HourReminders($dryRun);
        }
        
        if ($type === 'all' || $type === '2h') {
            $this->schedule2HourReminders($dryRun);
        }
        
        if ($type === 'all' || $type === '30m') {
            $this->schedule30MinuteReminders($dryRun);
        }
        
        $this->info('Reminder scheduling completed!');
    }
    
    /**
     * Schedule 24-hour reminders
     */
    protected function schedule24HourReminders(bool $dryRun): void
    {
        $this->info('Checking for 24-hour reminders...');
        
        $appointments = Appointment::with(['customer', 'staff', 'service', 'branch'])
            ->where('starts_at', '>=', now()->addHours(23))
            ->where('starts_at', '<=', now()->addHours(25))
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->whereNull('reminder_24h_sent_at')
            ->get();
        
        $count = 0;
        foreach ($appointments as $appointment) {
            // Check if customer has any notification preference enabled
            if (!$appointment->customer->email && 
                !$appointment->customer->sms_opt_in && 
                !$appointment->customer->whatsapp_opt_in) {
                $this->warn("Skipping appointment {$appointment->id} - No notification channels enabled");
                continue;
            }
            
            if ($dryRun) {
                $this->line("Would schedule 24h reminder for appointment {$appointment->id} - {$appointment->customer->name} at {$appointment->starts_at}");
            } else {
                SendAppointmentReminderJob::dispatch($appointment, '24h', $this->getEnabledChannels($appointment));
                $count++;
            }
        }
        
        $this->info("Scheduled {$count} 24-hour reminders");
    }
    
    /**
     * Schedule 2-hour reminders
     */
    protected function schedule2HourReminders(bool $dryRun): void
    {
        $this->info('Checking for 2-hour reminders...');
        
        $appointments = Appointment::with(['customer', 'staff', 'service', 'branch'])
            ->where('starts_at', '>=', now()->addMinutes(110))
            ->where('starts_at', '<=', now()->addMinutes(130))
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->whereNull('reminder_2h_sent_at')
            ->get();
        
        $count = 0;
        foreach ($appointments as $appointment) {
            if (!$appointment->customer->email) {
                $this->warn("Skipping appointment {$appointment->id} - No email for 2h reminder");
                continue;
            }
            
            if ($dryRun) {
                $this->line("Would schedule 2h reminder for appointment {$appointment->id} - {$appointment->customer->name} at {$appointment->starts_at}");
            } else {
                SendAppointmentReminderJob::dispatch($appointment, '2h', ['email']);
                $count++;
            }
        }
        
        $this->info("Scheduled {$count} 2-hour reminders");
    }
    
    /**
     * Schedule 30-minute reminders
     */
    protected function schedule30MinuteReminders(bool $dryRun): void
    {
        $this->info('Checking for 30-minute reminders...');
        
        $appointments = Appointment::with(['customer', 'staff', 'service', 'branch'])
            ->where('starts_at', '>=', now()->addMinutes(25))
            ->where('starts_at', '<=', now()->addMinutes(35))
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->whereNull('reminder_30m_sent_at')
            ->get();
        
        $count = 0;
        foreach ($appointments as $appointment) {
            if (!$appointment->customer->push_token && 
                !$appointment->customer->sms_opt_in) {
                $this->warn("Skipping appointment {$appointment->id} - No push token or SMS opt-in for 30m reminder");
                continue;
            }
            
            if ($dryRun) {
                $this->line("Would schedule 30m reminder for appointment {$appointment->id} - {$appointment->customer->name} at {$appointment->starts_at}");
            } else {
                $channels = [];
                if ($appointment->customer->push_token) {
                    $channels[] = 'push';
                }
                if ($appointment->customer->sms_opt_in) {
                    $channels[] = 'sms';
                }
                
                SendAppointmentReminderJob::dispatch($appointment, '30m', $channels);
                $count++;
            }
        }
        
        $this->info("Scheduled {$count} 30-minute reminders");
    }
    
    /**
     * Get enabled notification channels for appointment
     */
    protected function getEnabledChannels(Appointment $appointment): array
    {
        $channels = [];
        
        if ($appointment->customer->email) {
            $channels[] = 'email';
        }
        
        if ($appointment->customer->phone && $appointment->customer->sms_opt_in) {
            $channels[] = 'sms';
        }
        
        if ($appointment->customer->phone && $appointment->customer->whatsapp_opt_in) {
            $channels[] = 'whatsapp';
        }
        
        if ($appointment->customer->push_token) {
            $channels[] = 'push';
        }
        
        return $channels;
    }
}