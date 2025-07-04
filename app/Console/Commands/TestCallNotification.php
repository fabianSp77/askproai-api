<?php

namespace App\Console\Commands;

use App\Models\Call;
use App\Services\CallNotificationService;
use Illuminate\Console\Command;

class TestCallNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calls:test-notification {call_id? : The ID of the call to test with}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test call notification system by triggering a notification for a call';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $callId = $this->argument('call_id');
        
        if ($callId) {
            $call = Call::find($callId);
            if (!$call) {
                $this->error("Call with ID {$callId} not found.");
                return 1;
            }
        } else {
            // Get the most recent call
            $call = Call::orderBy('created_at', 'desc')->first();
            if (!$call) {
                $this->error("No calls found in the database.");
                return 1;
            }
        }
        
        $this->info("Testing notification for call ID: {$call->id}");
        $this->info("From: {$call->from_number}");
        $companyName = $call->company ? $call->company->name : 'Unknown';
        $this->info("Company: {$companyName}");
        
        // Test new call notification
        $this->info("\nSending new call notification...");
        CallNotificationService::notifyNewCall($call);
        $this->info("✓ New call notification sent");
        
        // Test converted call notification if applicable
        if ($call->appointment_id) {
            $this->info("\nSending call converted notification...");
            CallNotificationService::notifyCallConverted($call);
            $this->info("✓ Call converted notification sent");
        }
        
        // Test failed call notification
        if ($call->call_status === 'failed') {
            $this->info("\nSending failed call notification...");
            CallNotificationService::notifyFailedCall($call);
            $this->info("✓ Failed call notification sent");
        }
        
        $this->info("\nNotification test completed successfully!");
        $this->info("Check the notification panel in the admin dashboard.");
        
        return 0;
    }
}