<?php

namespace App\Services;

use App\Models\Call;
use App\Models\Customer;
use App\Models\PhoneNumber;
use App\Models\RetellAgent;
use App\Services\PhoneNumberNormalizer;
use App\Services\DeterministicCustomerMatcher;
use App\Services\CostCalculator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RetellApiClient
{
    private string $apiKey;
    private string $baseUrl;
    private $httpClient;

    public function __construct()
    {
        $this->apiKey = config('services.retellai.api_key') ?? config('services.retell.api_key');
        $this->baseUrl = rtrim(config('services.retellai.base_url') ?? config('services.retell.base_url'), '/');
        
        if (!$this->apiKey || !$this->baseUrl) {
            throw new \Exception('Retell API credentials not configured');
        }
    }

    /**
     * Fetch all calls from Retell API
     */
    public function getAllCalls(array $params = [])
    {
        $allCalls = [];
        $limit = $params['limit'] ?? 1000;
        $sortOrder = $params['sort_order'] ?? 'descending';
        $filterCriteria = $params['filter_criteria'] ?? [];
        
        // Build query parameters
        $queryParams = [
            'limit' => min($limit, 1000),
            'sort_order' => $sortOrder
        ];
        
        // Add filter criteria if provided
        if (!empty($filterCriteria)) {
            $queryParams['filter_criteria'] = $filterCriteria;
        }
        
        try {
            Log::info('Fetching calls from Retell API', ['params' => $queryParams]);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/v2/list-calls', $queryParams);
            
            if ($response->successful()) {
                $data = $response->json();
                $allCalls = $data ?? [];
                Log::info('Successfully fetched calls from Retell', ['count' => count($allCalls)]);
            } else {
                Log::error('Failed to fetch calls from Retell', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Exception while fetching Retell calls', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        return $allCalls;
    }

    /**
     * Get detailed call information
     */
    public function getCallDetail(string $callId)
    {
        try {
            Log::info('Fetching call detail from Retell', ['call_id' => $callId]);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ])->get($this->baseUrl . '/v2/get-call/' . $callId);
            
            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Failed to fetch call detail', [
                    'call_id' => $callId,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Exception while fetching call detail', [
                'call_id' => $callId,
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }

    /**
     * Sync a single call to database
     */
    public function syncCallToDatabase(array $callData)
    {
        try {
            // Extract relevant fields
            $callId = $callData['call_id'] ?? null;
            if (!$callId) {
                Log::warning('Call data missing call_id', ['data' => $callData]);
                return null;
            }
            
            // Use deterministic customer matching
            $fromNumber = $callData['from_number'] ?? null;
            $toNumber = $callData['to_number'] ?? null;

            // Match customer using deterministic rules
            $matchResult = DeterministicCustomerMatcher::matchCustomer(
                $fromNumber,
                $toNumber,
                $callData
            );

            $customer = $matchResult['customer'];
            $companyId = $matchResult['company_id'];
            $matchConfidence = $matchResult['confidence'];

            // 🔧 BUG #7b FIX: Preserve existing company_id if call already exists
            // This prevents call_ended webhook from overwriting the correct company_id
            // that was set during call_started (especially important for anonymous callers)
            $existingCall = Call::where('retell_call_id', $callId)->first();
            if ($existingCall && $existingCall->company_id) {
                $companyId = $existingCall->company_id;
                Log::info('🔒 Preserving existing company_id for call', [
                    'call_id' => $callId,
                    'preserved_company_id' => $companyId,
                    'matcher_suggested' => $matchResult['company_id']
                ]);
            }

            // Handle unknown customers with proper workflow
            if ($matchResult['is_unknown'] && $fromNumber) {
                // Create or update unknown customer placeholder
                $customer = DeterministicCustomerMatcher::handleUnknownCustomer(
                    $fromNumber,
                    $companyId,
                    $callData
                );

                if ($customer) {
                    Log::info('Unknown customer handled', [
                        'customer_id' => $customer->id,
                        'reason' => $matchResult['unknown_reason'],
                        'call_id' => $callId
                    ]);
                }
            }

            // Log matching result
            if ($customer) {
                Log::info('Customer matched for call', [
                    'call_id' => $callId,
                    'customer_id' => $customer->id,
                    'confidence' => $matchConfidence,
                    'match_method' => $matchResult['match_method'],
                ]);
            } else {
                Log::warning('No customer match for call', [
                    'call_id' => $callId,
                    'from_number' => $fromNumber,
                    'unknown_reason' => $matchResult['unknown_reason'],
                ]);
            }
            
            // Find phone number record - skip for now due to type mismatch
            // TODO: Fix phone_number_id column type mismatch (UUID vs bigint)
            $phoneNumber = null;
            $phoneNumberId = null;

            // 🔧 FIX 2025-11-24: Preserve existing phone_number_id if already set
            // Similar to company_id preservation (lines 144-152)
            // Prevents call_analyzed event from overwriting phone_number_id set by call_started
            $existingCall = Call::where('retell_call_id', $callId)->first();
            if ($existingCall && $existingCall->phone_number_id) {
                $phoneNumberId = $existingCall->phone_number_id;
                Log::debug('📞 Preserved existing phone_number_id', [
                    'call_id' => $callId,
                    'phone_number_id' => $phoneNumberId
                ]);
            }

            // if ($toNumber) {
            //     $phoneNumber = PhoneNumber::where('number', $toNumber)->first();
            //     if ($phoneNumber) {
            //         // Don't set phone_number_id due to type mismatch
            //         // $phoneNumberId = $phoneNumber->id;
            //     }
            // }
            
            // Find agent if provided
            $agent = null;
            if (!empty($callData['agent_id'])) {
                $agent = RetellAgent::where('agent_id', $callData['agent_id'])->first();
            }
            
            // Prepare call data for database
            $callRecord = [
                'retell_call_id' => $callId,
                'call_id' => $callData['call_id'],

                // 🔴 CRITICAL FIX: Improved from_number extraction
                // Try multiple sources: from_number → telephony_identifier → anonymous/unknown fallback
                'from_number' => $fromNumber
                    ?? ($callData['telephony_identifier']['caller_number'] ?? null)
                    ?? ($callData['from'] ?? 'unknown'),

                'to_number' => $toNumber,
                'customer_id' => $customer?->id,
                'customer_match_confidence' => $matchConfidence ?? 0,
                'customer_match_method' => $matchResult['match_method'] ?? null,
                'is_unknown_customer' => $matchResult['is_unknown'] ?? false,
                'unknown_reason' => $matchResult['unknown_reason'] ?? null,
                'company_id' => $companyId ?? $customer?->company_id ?? 1,
                'phone_number_id' => $phoneNumberId,
                'agent_id' => $agent?->id,
                'direction' => $callData['direction'] ?? 'inbound',
                'call_status' => $callData['call_status'] ?? $callData['status'] ?? null,
                // Set main status field based on call_status
                'status' => match($callData['call_status'] ?? $callData['status'] ?? null) {
                    'ended', 'completed' => 'completed',
                    'analyzed', 'call_analyzed' => 'analyzed',
                    'ongoing', 'in-progress' => 'ongoing',
                    'failed' => 'failed',
                    default => 'completed'  // Default to completed for analyzed calls
                },
                'disconnection_reason' => $callData['disconnection_reason'] ?? null,
                'retell_agent_id' => $callData['agent_id'] ?? $callData['retell_agent_id'] ?? $agent?->agent_id,

                // 🟡 HIGH: Agent version tracking
                'agent_version' => $callData['agent_version'] ?? null,

                // Call metrics
                'duration_ms' => $callData['duration_ms'] ?? null,
                'duration_sec' => isset($callData['duration_ms']) ? round($callData['duration_ms'] / 1000) : null,

                // 🟡 HIGH: Complete cost tracking
                // CRITICAL: combined_cost is in CENTS (not dollars!)
                'cost_cents' => isset($callData['call_cost']['combined_cost']) ? round($callData['call_cost']['combined_cost']) : null,
                'cost' => isset($callData['call_cost']['combined_cost']) ? ($callData['call_cost']['combined_cost'] / 100) : null,
                'cost_breakdown' => isset($callData['call_cost']) ? json_encode($callData['call_cost']) : null,

                // 🔥 FIX: Don't fallback to combined_cost - let webhook handler process actual costs
                // combined_cost includes ALL costs (Retell + Twilio + Add-ons), not just Retell
                'retell_cost_usd' => $callData['call_cost']['retell_cost'] ?? null,
                'twilio_cost_usd' => $callData['call_cost']['twilio_cost'] ?? null,

                // 🟡 HIGH: Timing metrics (agent/customer/silence talk time)
                // Note: These fields may not be available in all Retell API responses
                'agent_talk_time_ms' => $callData['latency']['agent_talk_time'] ?? null,
                'customer_talk_time_ms' => $callData['latency']['customer_talk_time'] ?? null,
                'silence_time_ms' => $callData['latency']['silence_time'] ?? null,

                // 🟡 HIGH: Performance metrics - Store full latency JSON
                'latency_metrics' => isset($callData['latency']) ? json_encode($callData['latency']) : null,

                // 🔥 FIX: Use e2e.p50 as end_to_end_latency (median)
                'end_to_end_latency' => $callData['latency']['e2e']['p50'] ?? $callData['latency']['end_to_end_latency'] ?? null,
                
                // Timestamps - Use actual call time from Retell, converted to Berlin timezone
                'created_at' => isset($callData['start_timestamp']) ? Carbon::createFromTimestampMs($callData['start_timestamp'])->setTimezone('Europe/Berlin') : now(),
                'updated_at' => isset($callData['end_timestamp']) ? Carbon::createFromTimestampMs($callData['end_timestamp'])->setTimezone('Europe/Berlin') : now(),
                'start_timestamp' => isset($callData['start_timestamp']) ? Carbon::createFromTimestampMs($callData['start_timestamp'])->setTimezone('Europe/Berlin') : null,
                'end_timestamp' => isset($callData['end_timestamp']) ? Carbon::createFromTimestampMs($callData['end_timestamp'])->setTimezone('Europe/Berlin') : null,
                'call_time' => isset($callData['start_timestamp']) ? Carbon::createFromTimestampMs($callData['start_timestamp'])->setTimezone('Europe/Berlin') : null,
                'called_at' => isset($callData['start_timestamp']) ? Carbon::createFromTimestampMs($callData['start_timestamp'])->setTimezone('Europe/Berlin') : now(),
                
                // Transcript and analysis
                'transcript' => $callData['transcript'] ?? $callData['transcript_object'] ?? null,
                'recording_url' => $callData['recording_url'] ?? $callData['recording_multi_channel_url'] ?? null,

                // Call analysis from Retell
                'analysis' => $callData['call_analysis'] ?? null,
                'summary' => $callData['call_analysis']['call_summary'] ?? $callData['call_summary'] ?? null,
                // Normalize sentiment to consistent capitalization (Positive, Neutral, Negative)
                'sentiment' => isset($callData['call_analysis']['user_sentiment'])
                    ? ucfirst(strtolower($callData['call_analysis']['user_sentiment']))
                    : null,
                'call_successful' => $callData['call_analysis']['call_successful'] ?? null,
                
                // Additional metadata
                'metadata' => $callData['metadata'] ?? null,
                'raw' => $callData,
                
                // LLM usage
                'llm_token_usage' => $callData['llm_token_usage'] ?? [
                    'completion_tokens' => 0,
                    'prompt_tokens' => 0,
                    'total_tokens' => 0,
                ],
            ];
            
            // Check if we need to fetch more detailed transcript
            if (!empty($callData['transcript_url']) && empty($callRecord['transcript'])) {
                $transcriptData = $this->fetchTranscript($callData['transcript_url']);
                if ($transcriptData) {
                    $callRecord['transcript'] = $transcriptData;
                }
            }
            
            // Create or update the call record
            // NOTE: Some fields are guarded (cost, cost_cents, cost_breakdown) to prevent mass assignment
            // We need to use unguard() temporarily since we're syncing from trusted Retell API
            Call::unguard();
            $call = Call::updateOrCreate(
                ['retell_call_id' => $callId],
                $callRecord
            );
            Call::reguard();

            // Auto-translate summary to German if it's in English
            if (!empty($call->summary)) {
                try {
                    $translationService = app(\App\Services\FreeTranslationService::class);
                    $detectedLang = $translationService->detectLanguage($call->summary);

                    if ($detectedLang !== 'de') {
                        Log::info('Auto-translating call summary from ' . $detectedLang . ' to German', [
                            'call_id' => $call->id
                        ]);

                        $germanTranslation = $translationService->translateToGerman($call->summary);

                        $call->update([
                            'summary' => $germanTranslation, // Store German version in main field
                            'summary_language' => 'de',
                            'summary_translations' => [
                                'original' => $call->summary,
                                'de' => $germanTranslation
                            ]
                        ]);
                    } else {
                        // Already German, just mark the language
                        $call->update(['summary_language' => 'de']);
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to auto-translate call summary', [
                        'call_id' => $call->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Calculate and update costs with full call data
            try {
                $costCalculator = new CostCalculator();
                $costCalculator->updateCallCosts($call);
                Log::info('Call costs calculated during sync', [
                    'call_id' => $call->id,
                    'base_cost' => $call->base_cost,
                    'reseller_cost' => $call->reseller_cost,
                    'customer_cost' => $call->customer_cost,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to calculate call costs during sync', [
                    'call_id' => $call->id,
                    'error' => $e->getMessage()
                ]);
            }

            Log::info('Successfully synced call to database', [
                'call_id' => $callId,
                'database_id' => $call->id
            ]);

            return $call;
            
        } catch (\Exception $e) {
            Log::error('Failed to sync call to database', [
                'error' => $e->getMessage(),
                'call_data' => $callData
            ]);
            return null;
        }
    }

    /**
     * Fetch transcript from URL
     */
    private function fetchTranscript($url)
    {
        try {
            $response = Http::get($url);
            if ($response->successful()) {
                return $response->body();
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch transcript', ['url' => $url, 'error' => $e->getMessage()]);
        }
        return null;
    }

    /**
     * Sync all calls from Retell to database
     */
    public function syncAllCalls(array $params = [])
    {
        $stats = [
            'total' => 0,
            'synced' => 0,
            'failed' => 0,
            'skipped' => 0
        ];
        
        try {
            // Fetch all calls from Retell
            $calls = $this->getAllCalls($params);
            $stats['total'] = count($calls);
            
            Log::info('Starting sync of Retell calls', ['total' => $stats['total']]);
            
            foreach ($calls as $callData) {
                // Get detailed call information if needed
                if (!empty($callData['call_id']) && empty($callData['transcript_object'])) {
                    $detailedCall = $this->getCallDetail($callData['call_id']);
                    if ($detailedCall) {
                        $callData = array_merge($callData, $detailedCall);
                    }
                }
                
                $result = $this->syncCallToDatabase($callData);
                
                if ($result) {
                    $stats['synced']++;
                } else {
                    $stats['failed']++;
                }
                
                // Add small delay to avoid rate limiting
                usleep(100000); // 100ms delay
            }
            
            Log::info('Completed Retell call sync', $stats);
            
        } catch (\Exception $e) {
            Log::error('Failed to sync calls from Retell', [
                'error' => $e->getMessage(),
                'stats' => $stats
            ]);
        }
        
        return $stats;
    }

    /**
     * Get list of agents
     */
    public function getAgents()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ])->get($this->baseUrl . '/v2/list-agents');
            
            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch agents', ['error' => $e->getMessage()]);
        }
        
        return [];
    }

    /**
     * Get list of phone numbers
     */
    public function getPhoneNumbers()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ])->get($this->baseUrl . '/v2/list-phone-numbers');
            
            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch phone numbers', ['error' => $e->getMessage()]);
        }
        
        return [];
    }
}
