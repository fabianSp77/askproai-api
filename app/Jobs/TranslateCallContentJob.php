<?php

namespace App\Jobs;

use App\Models\Call;
use App\Services\TranslationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TranslateCallContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $callId;
    protected $targetLanguage;

    /**
     * Create a new job instance.
     */
    public function __construct($callId, string $targetLanguage)
    {
        $this->callId = $callId;
        $this->targetLanguage = $targetLanguage;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $call = Call::find($this->callId);
        
        if (!$call) {
            return;
        }
        
        // Skip if auto-translate is disabled
        if (!$call->company->auto_translate) {
            return;
        }
        
        $translationService = app(TranslationService::class);
        
        // Prepare translations array
        $translations = $call->metadata['translations'] ?? [];
        
        // Translate transcript
        if ($call->transcript && !isset($translations[$this->targetLanguage]['transcript'])) {
            $translatedTranscript = $translationService->translate(
                $call->transcript,
                $this->targetLanguage,
                $call->detected_language
            );
            
            $translations[$this->targetLanguage]['transcript'] = $translatedTranscript;
            
            Log::info('Translated call transcript', [
                'call_id' => $call->id,
                'from' => $call->detected_language,
                'to' => $this->targetLanguage,
                'length' => strlen($call->transcript)
            ]);
        }
        
        // Translate summary if available
        if ($call->call_summary && !isset($translations[$this->targetLanguage]['summary'])) {
            $translatedSummary = $translationService->translate(
                $call->call_summary,
                $this->targetLanguage,
                $call->detected_language
            );
            
            $translations[$this->targetLanguage]['summary'] = $translatedSummary;
        }
        
        // Translate key phrases from analysis
        if (isset($call->analysis['important_phrases']) && !empty($call->analysis['important_phrases'])) {
            $translatedPhrases = [];
            foreach ($call->analysis['important_phrases'] as $phrase) {
                $translatedPhrases[] = $translationService->translate(
                    $phrase,
                    $this->targetLanguage,
                    $call->detected_language
                );
            }
            
            if (!isset($translations[$this->targetLanguage]['important_phrases'])) {
                $translations[$this->targetLanguage]['important_phrases'] = $translatedPhrases;
            }
        }
        
        // Update metadata with translations
        $metadata = $call->metadata ?? [];
        $metadata['translations'] = $translations;
        $metadata['last_translated_at'] = now()->toISOString();
        $metadata['translation_languages'] = array_keys($translations);
        
        $call->metadata = $metadata;
        $call->save();
        
        Log::info('Call content translation completed', [
            'call_id' => $call->id,
            'target_language' => $this->targetLanguage,
            'translations_available' => array_keys($translations)
        ]);
    }
}