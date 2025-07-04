<?php

namespace App\Jobs;

use App\Models\Call;
use App\Jobs\TranslateCallContentJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DetectLanguageMismatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $callId;

    /**
     * Create a new job instance.
     */
    public function __construct($callId)
    {
        $this->callId = $callId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $call = Call::find($this->callId);
        
        if (!$call || !$call->detected_language) {
            return;
        }
        
        // Get company's default language
        $companyLanguage = $call->company->default_language ?? 'de';
        
        // Check for mismatch
        $isMismatch = $call->detected_language !== $companyLanguage;
        
        // Update the call record
        $call->language_mismatch = $isMismatch;
        $call->save();
        
        if ($isMismatch) {
            Log::info('Language mismatch detected', [
                'call_id' => $call->id,
                'detected' => $call->detected_language,
                'expected' => $companyLanguage,
                'confidence' => $call->language_confidence
            ]);
            
            // Trigger translation if auto_translate is enabled
            if ($call->company->auto_translate) {
                TranslateCallContentJob::dispatch($call->id, $companyLanguage)
                    ->onQueue('default')
                    ->delay(now()->addSeconds(10));
                    
                Log::info('Translation job dispatched', [
                    'call_id' => $call->id,
                    'target_language' => $companyLanguage
                ]);
            }
        }
    }
}