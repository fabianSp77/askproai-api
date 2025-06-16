<?php

namespace App\Jobs;

use App\Models\Call;
use App\Models\RetellWebhook;
use App\Services\PhoneNumberResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessRetellCallEndedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];
    
    protected array $data;
    
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->queue = 'webhooks';
    }

    public function handle()
    {
        Log::info('Processing Retell call_ended webhook', [
            'call_id' => $this->data['call']['call_id'] ?? 'unknown',
            'event' => $this->data['event'] ?? 'unknown'
        ]);

        try {
            // Store raw webhook data
            $this->storeWebhookRecord();
            
            // Process call data
            $call = $this->processCallData();
            
            // Extract and store advanced metrics
            $this->processAdvancedMetrics($call);
            
            // Process transcript if available
            if (!empty($this->data['call']['transcript_object'])) {
                $this->processTranscript($call);
            }
            
            // Update call status
            $call->update(['call_status' => 'analyzed']);
            
            Log::info('Successfully processed Retell call_ended webhook', [
                'call_id' => $call->id,
                'retell_call_id' => $call->retell_call_id
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to process Retell call_ended webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $this->data
            ]);
            
            throw $e;
        }
    }
    
    protected function storeWebhookRecord(): void
    {
        RetellWebhook::create([
            'event_type' => $this->data['event'] ?? 'call_ended',
            'payload' => $this->data,
            'processed_at' => now()
        ]);
    }
    
    protected function processCallData(): Call
    {
        $callData = $this->data['call'] ?? $this->data;
        $callId = $callData['call_id'] ?? 'retell_' . uniqid();
        
        // Resolve branch and company
        $resolver = new PhoneNumberResolver();
        $resolved = $resolver->resolveFromWebhook($callData);
        
        // Extract all available fields
        $attributes = [
            // Basic identification
            'retell_call_id' => $callId,
            'call_id' => $callId,
            'agent_id' => $callData['agent_id'] ?? null,
            
            // Phone numbers
            'from_number' => $callData['from_number'] ?? $callData['from'] ?? null,
            'to_number' => $callData['to_number'] ?? $callData['to'] ?? null,
            
            // Call details
            'call_type' => $callData['call_type'] ?? 'phone_call',
            'direction' => $callData['direction'] ?? 'inbound',
            'call_status' => $callData['call_status'] ?? 'ended',
            'disconnection_reason' => $callData['disconnection_reason'] ?? null,
            
            // Timestamps (Retell sends milliseconds, convert to seconds)
            'start_timestamp' => isset($callData['start_timestamp']) 
                ? \Carbon\Carbon::createFromTimestampMs($callData['start_timestamp']) 
                : null,
            'end_timestamp' => isset($callData['end_timestamp']) 
                ? \Carbon\Carbon::createFromTimestampMs($callData['end_timestamp']) 
                : null,
            
            // Duration
            'duration_sec' => $callData['duration'] ?? 
                (isset($callData['duration_ms']) ? round($callData['duration_ms'] / 1000) : 0),
            'duration_minutes' => isset($callData['duration']) 
                ? round($callData['duration'] / 60, 2) 
                : (isset($callData['duration_ms']) ? round($callData['duration_ms'] / 60000, 2) : 0),
            
            // Content
            'transcript' => $callData['transcript'] ?? null,
            'transcript_object' => $callData['transcript_object'] ?? null,
            'transcript_with_tools' => $callData['transcript_with_tool_calls'] ?? null,
            'summary' => $callData['summary'] ?? null,
            
            // URLs
            'audio_url' => $callData['recording_url'] ?? $callData['audio_url'] ?? null,
            'public_log_url' => $callData['public_log_url'] ?? null,
            
            // Costs
            'cost' => $callData['call_cost']['total_cost'] ?? $callData['cost'] ?? null,
            'cost_cents' => isset($callData['call_cost']['total_cost']) 
                ? intval($callData['call_cost']['total_cost'] * 100) 
                : null,
            
            // Metadata
            'metadata' => $callData['metadata'] ?? [],
            'retell_llm_dynamic_variables' => $callData['retell_llm_dynamic_variables'] ?? [],
            
            // Privacy
            'opt_out_sensitive_data' => $callData['opt_out_sensitive_data_storage'] ?? false,
            
            // Raw data
            'raw_data' => $callData,
            'webhook_data' => $this->data,
            
            // Multi-tenant
            'company_id' => $resolved['company_id'] ?? null,
            'branch_id' => $resolved['branch_id'] ?? null,
            
            // Additional Retell fields
            'agent_version' => $callData['agent_version'] ?? null,
            'retell_cost' => isset($callData['call_cost']['total_cost']) 
                ? $callData['call_cost']['total_cost'] 
                : null,
            'custom_sip_headers' => $callData['custom_sip_headers'] ?? null,
        ];
        
        // Store custom fields in details
        $details = [];
        foreach ($callData as $key => $value) {
            if (strpos($key, '_') === 0) {
                $details[$key] = $value;
            }
        }
        if (!empty($details)) {
            $attributes['details'] = $details;
        }
        
        return Call::updateOrCreate(
            ['retell_call_id' => $callId],
            $attributes
        );
    }
    
    protected function processAdvancedMetrics(Call $call): void
    {
        $callData = $this->data['call'] ?? $this->data;
        $analysis = $call->analysis ?? [];
        
        // Latency metrics
        if (isset($callData['latency'])) {
            $analysis['latency'] = $callData['latency'];
        }
        
        // Cost breakdown
        if (isset($callData['call_cost'])) {
            $analysis['cost_breakdown'] = $callData['call_cost'];
        }
        
        // LLM usage
        if (isset($callData['llm_token_usage'])) {
            $analysis['llm_usage'] = $callData['llm_token_usage'];
        }
        
        // Sentiment analysis
        if (isset($callData['user_sentiment'])) {
            $analysis['sentiment'] = $callData['user_sentiment'];
        }
        
        // Intent detection
        if (isset($callData['detected_intent'])) {
            $analysis['intent'] = $callData['detected_intent'];
        }
        
        // Extract entities from custom fields
        $entities = [];
        foreach ($callData as $key => $value) {
            if (strpos($key, '_') === 0 && !empty($value)) {
                $entityName = str_replace('_', '', $key);
                $entities[$entityName] = $value;
            }
        }
        if (!empty($entities)) {
            $analysis['entities'] = $entities;
        }
        
        $call->update(['analysis' => $analysis]);
    }
    
    protected function processTranscript(Call $call): void
    {
        $callData = $this->data['call'] ?? $this->data;
        
        if (isset($callData['transcript_object'])) {
            // Store structured transcript
            $call->update([
                'transcript_object' => $callData['transcript_object']
            ]);
            
            // Analyze conversation flow
            $this->analyzeConversationFlow($call, $callData['transcript_object']);
        }
        
        if (isset($callData['transcript_with_tool_calls'])) {
            // Store tool calls
            $analysis = $call->analysis ?? [];
            $analysis['tool_calls'] = $callData['transcript_with_tool_calls'];
            $call->update(['analysis' => $analysis]);
        }
    }
    
    protected function analyzeConversationFlow(Call $call, array $transcriptObject): void
    {
        $analysis = $call->analysis ?? [];
        
        // Count turns
        $userTurns = 0;
        $agentTurns = 0;
        $totalWords = 0;
        
        foreach ($transcriptObject as $turn) {
            if ($turn['role'] === 'user') {
                $userTurns++;
            } elseif ($turn['role'] === 'agent') {
                $agentTurns++;
            }
            
            $totalWords += str_word_count($turn['content'] ?? '');
        }
        
        $analysis['conversation_metrics'] = [
            'user_turns' => $userTurns,
            'agent_turns' => $agentTurns,
            'total_turns' => count($transcriptObject),
            'total_words' => $totalWords,
            'avg_words_per_turn' => count($transcriptObject) > 0 
                ? round($totalWords / count($transcriptObject), 1) 
                : 0
        ];
        
        $call->update(['analysis' => $analysis]);
    }
}