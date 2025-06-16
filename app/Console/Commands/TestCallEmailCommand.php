<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Call;
use App\Mail\CallRecordingMail;
use Illuminate\Support\Facades\Mail;

class TestCallEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:call-email {call_id} {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test sending a call recording email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $callId = $this->argument('call_id');
        $email = $this->argument('email');

        $call = Call::with([
            'customer',
            'company', 
            'branch',
            'staff',
            'service',
            'appointment'
        ])->find($callId);

        if (!$call) {
            $this->error("Call with ID {$callId} not found!");
            return 1;
        }

        $this->info("Sending email for call {$callId} to {$email}...");

        try {
            $emailData = [
                'call' => $call,
                'subject' => 'Test Call Recording - ' . $call->created_at->format('d.m.Y H:i'),
                'custom_message' => 'This is a test email sent from the command line.',
                'sender_name' => 'System Test',
                'sender_email' => config('mail.from.address')
            ];

            Mail::to($email)->send(new CallRecordingMail($emailData));

            $this->info("Email sent successfully!");
            return 0;

        } catch (\Exception $e) {
            $this->error("Failed to send email: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}