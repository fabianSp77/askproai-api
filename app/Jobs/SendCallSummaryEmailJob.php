<?php

namespace App\Jobs;

use App\Mail\CallSummaryEmail;
use App\Models\Call;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendCallSummaryEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $callId;
    protected $recipients;
    protected $includeTranscript;
    protected $includeCsv;
    protected $customMessage;
    protected $recipientType;

    public $tries = 3;
    public $timeout = 300;

    public function __construct(
        int $callId,
        array $recipients,
        bool $includeTranscript = true,
        bool $includeCsv = false,
        ?string $customMessage = null,
        string $recipientType = 'internal'
    ) {
        $this->callId = $callId;
        $this->recipients = $recipients;
        $this->includeTranscript = $includeTranscript;
        $this->includeCsv = $includeCsv;
        $this->customMessage = $customMessage;
        $this->recipientType = $recipientType;
        
        // Specify the queue
        $this->onQueue('emails');
    }

    public function handle()
    {
        \Illuminate\Support\Facades\Log::info('[SendCallSummaryEmailJob] Starting job', [
            'call_id' => $this->callId,
            'recipients' => $this->recipients
        ]);
        
        $call = Call::find($this->callId);
        
        if (!$call) {
            \Illuminate\Support\Facades\Log::error('[SendCallSummaryEmailJob] Call not found', [
                'call_id' => $this->callId
            ]);
            return;
        }
        
        // Set company context
        app()->instance('current_company_id', $call->company_id);
        
        // Send email to each recipient (using SEND not QUEUE to avoid double queueing)
        foreach ($this->recipients as $recipient) {
            try {
                \Illuminate\Support\Facades\Log::info('[SendCallSummaryEmailJob] Sending to recipient', [
                    'recipient' => $recipient
                ]);
                
                Mail::to($recipient)->send(new CallSummaryEmail(
                    $call,
                    $this->includeTranscript,
                    $this->includeCsv,
                    $this->customMessage,
                    $this->recipientType
                ));
                
                \Illuminate\Support\Facades\Log::info('[SendCallSummaryEmailJob] Email sent successfully', [
                    'recipient' => $recipient
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('[SendCallSummaryEmailJob] Failed to send email', [
                    'recipient' => $recipient,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e; // Re-throw to trigger retry
            }
        }
    }
}