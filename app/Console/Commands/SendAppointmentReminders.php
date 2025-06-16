<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificationService;

class SendAppointmentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send appointment reminders (24h, 2h, 30min)';

    protected NotificationService $notificationService;

    /**
     * Create a new command instance.
     */
    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Sending appointment reminders...');
        
        try {
            $this->notificationService->sendAppointmentReminders();
            $this->info('Reminders sent successfully.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to send reminders: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}