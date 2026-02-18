<?php

namespace App\Services\Webhook;

use App\Models\Call;
use App\Models\PhoneNumber;
use App\Models\RetellAgent;
use App\Services\CostCalculator;
use App\Services\PhoneNumberNormalizer;
use App\Services\RetellApiClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CallProcessingService
{
    private CostCalculator $costCalculator;
    private RetellApiClient $retellClient;

    public function __construct()
    {
        $this->costCalculator = new CostCalculator();
        $this->retellClient = new RetellApiClient();
    }

    /**
     * Handle call_started event
     */
    public function handleCallStarted(array $data): array
    {
        $callData = $data['call'] ?? $data;

        Log::info('📞 Call started - Creating/updating record', [
            'call_id' => $callData['call_id'] ?? null,
            'from' => $callData['from_number'] ?? null,
            'to' => $callData['to_number'] ?? null,
        ]);

        try {
            // Check if we already have this call
            $existingCall = Call::where('retell_call_id', $callData['call_id'] ?? null)->first();

            if ($existingCall) {
                // Update existing call status
                $existingCall->update([
                    'status' => 'ongoing',
                    'call_status' => 'ongoing',
                    'start_timestamp' => isset($callData['start_timestamp'])
                        ? Carbon::createFromTimestampMs($callData['start_timestamp'])
                        : now(),
                ]);

                Log::info('✅ Updated existing call to ongoing', ['call_id' => $existingCall->id]);

                return [
                    'success' => true,
                    'message' => 'Call status updated',
                    'call_id' => $existingCall->id,
                ];
            }

            // Determine phone number and agent
            $toNumber = $callData['to_number'] ?? null;
            $phoneNumber = null;

            if ($toNumber) {
                $normalizedTo = PhoneNumberNormalizer::normalize($toNumber) ?? $toNumber;
                $phoneNumber = PhoneNumber::where('number', $normalizedTo)
                    ->orWhere('number', $toNumber)
                    ->first();
            }

            $retellAgentId = $callData['agent_id'] ?? $callData['retell_agent_id'] ?? null;

            if (!$retellAgentId && $phoneNumber) {
                $retellAgentId = $phoneNumber->retell_agent_id;
            }

            $agentId = $phoneNumber?->agent_id;
            if (!$agentId && $retellAgentId) {
                $agent = RetellAgent::where('retell_agent_id', $retellAgentId)->first();
                $agentId = $agent?->id;
            }

            // Create new call record
            $call = Call::create([
                'retell_call_id' => $callData['call_id'] ?? null,
                'external_id' => $callData['call_id'] ?? null,
                'from_number' => $callData['from_number'] ?? 'unknown',
                'to_number' => $callData['to_number'] ?? null,
                'direction' => $callData['direction'] ?? 'inbound',
                'call_status' => 'ongoing',
                'status' => 'ongoing',
                'agent_id' => $agentId,
                'retell_agent_id' => $retellAgentId,
                'phone_number_id' => $phoneNumber ? $phoneNumber->id : null,
                'start_timestamp' => isset($callData['start_timestamp'])
                    ? Carbon::createFromTimestampMs($callData['start_timestamp'])
                    : now(),
                'company_id' => $phoneNumber ? $phoneNumber->company_id : 1,
            ]);

            Log::info('✅ Created new call record', [
                'call_id' => $call->id,
                'retell_call_id' => $call->retell_call_id,
            ]);

            return [
                'success' => true,
                'message' => 'Call record created',
                'call_id' => $call->id,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to handle call_started', [
                'error' => $e->getMessage(),
                'call_data' => $callData,
            ]);

            return [
                'success' => false,
                'error' => 'Failed to process call start',
            ];
        }
    }

    /**
     * Handle call_ended event
     */
    public function handleCallEnded(array $data): array
    {
        $callData = $data['call'] ?? $data;

        Log::info('📴 Call ended - Updating status', [
            'call_id' => $callData['call_id'] ?? null,
            'duration' => $callData['duration_ms'] ?? null,
        ]);

        try {
            $call = Call::where('retell_call_id', $callData['call_id'] ?? null)
                ->orWhere('external_id', $callData['call_id'] ?? null)
                ->first();

            if ($call) {
                $call->update([
                    'status' => 'completed',
                    'call_status' => 'completed',
                    'end_timestamp' => isset($callData['end_timestamp'])
                        ? Carbon::createFromTimestampMs($callData['end_timestamp'])
                        : now(),
                    'duration_ms' => $callData['duration_ms'] ?? null,
                    'duration_sec' => isset($callData['duration_ms']) ? round($callData['duration_ms'] / 1000) : null,
                    'disconnection_reason' => $callData['disconnection_reason'] ?? null,
                ]);

                // Calculate costs
                $this->costCalculator->updateCallCosts($call);

                Log::info('✅ Call ended and costs calculated', [
                    'call_id' => $call->id,
                    'duration_sec' => $call->duration_sec,
                    'base_cost' => $call->base_cost,
                ]);

                return [
                    'success' => true,
                    'message' => 'Call ended successfully',
                    'call_id' => $call->id,
                ];
            } else {
                // Create record if we don't have one
                $call = $this->createCallFromEndedEvent($callData);

                if ($call) {
                    $this->costCalculator->updateCallCosts($call);

                    return [
                        'success' => true,
                        'message' => 'Call record created from ended event',
                        'call_id' => $call->id,
                    ];
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to handle call_ended', [
                'error' => $e->getMessage(),
                'call_data' => $callData,
            ]);

            return [
                'success' => false,
                'error' => 'Failed to process call end',
            ];
        }

        return [
            'success' => false,
            'error' => 'Call not found',
        ];
    }

    /**
     * Handle call_analyzed event
     */
    public function handleCallAnalyzed(array $data): array
    {
        $callData = $data['call'] ?? $data;

        try {
            // Check if we already have this call
            $existingCall = Call::where('retell_call_id', $callData['call_id'] ?? null)->first();

            if ($existingCall) {
                Log::info('Call already exists, updating with latest data', ['call_id' => $existingCall->id]);
            }

            // Sync the call data using RetellApiClient
            $call = $this->retellClient->syncCallToDatabase($callData);

            if ($call) {
                Log::info('✅ Call successfully synced via webhook', [
                    'call_id' => $call->id,
                    'retell_call_id' => $call->retell_call_id,
                    'customer_id' => $call->customer_id,
                ]);

                return [
                    'success' => true,
                    'message' => 'Call analyzed event processed',
                    'call_id' => $call->id,
                ];
            }

        } catch (\Exception $e) {
            Log::error('Failed to sync call from webhook', [
                'error' => $e->getMessage(),
                'call_id' => $callData['call_id'] ?? null,
            ]);

            return [
                'success' => false,
                'error' => 'Failed to sync call',
            ];
        }

        return [
            'success' => false,
            'error' => 'Failed to process call analysis',
        ];
    }

    /**
     * Create call record from ended event
     */
    private function createCallFromEndedEvent(array $callData): ?Call
    {
        try {
            // 🔧 FIX 2026-02-18: Resolve company_id from agent_id instead of hardcoding.
            // Set company_id on model directly (not via mass assignment) because it's $guarded.
            $companyId = null;
            $agentId = $callData['agent_id'] ?? $callData['retell_agent_id'] ?? null;
            if ($agentId) {
                $agent = \App\Models\RetellAgent::where('agent_id', $agentId)->first();
                if ($agent) {
                    $companyId = $agent->company_id;
                }
            }

            // Fallback: resolve from phone number
            if (!$companyId && !empty($callData['to_number'])) {
                $phone = \App\Models\PhoneNumber::where('number', $callData['to_number'])
                    ->orWhere('phone_number', $callData['to_number'])
                    ->first();
                if ($phone) {
                    $companyId = $phone->company_id;
                }
            }

            $call = new Call();
            $call->fill([
                'retell_call_id' => $callData['call_id'] ?? null,
                'external_id' => $callData['call_id'] ?? null,
                'from_number' => $callData['from_number'] ?? 'unknown',
                'to_number' => $callData['to_number'] ?? null,
                'direction' => $callData['direction'] ?? 'inbound',
                'status' => 'completed',
                'call_status' => 'completed',
                'retell_agent_id' => $agentId,
                'start_timestamp' => isset($callData['start_timestamp'])
                    ? Carbon::createFromTimestampMs($callData['start_timestamp'])
                    : null,
                'end_timestamp' => isset($callData['end_timestamp'])
                    ? Carbon::createFromTimestampMs($callData['end_timestamp'])
                    : now(),
                'duration_ms' => $callData['duration_ms'] ?? null,
                'duration_sec' => isset($callData['duration_ms']) ? round($callData['duration_ms'] / 1000) : null,
                'disconnection_reason' => $callData['disconnection_reason'] ?? null,
            ]);
            $call->company_id = $companyId ?? 1;
            $call->save();

            return $call;
        } catch (\Exception $e) {
            Log::error('Failed to create call from ended event', [
                'error' => $e->getMessage(),
                'call_data' => $callData,
            ]);
            return null;
        }
    }
}