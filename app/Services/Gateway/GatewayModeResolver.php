<?php

namespace App\Services\Gateway;

use App\Models\Call;
use App\Models\PolicyConfiguration;
use App\Models\RetellTranscriptSegment;
use Illuminate\Support\Facades\Log;

/**
 * GatewayModeResolver - Determines call routing mode
 *
 * Resolves call routing to 'appointment', 'service_desk', or 'hybrid' mode
 * based on company policy configuration and feature flags.
 *
 * Architecture:
 * - IN-HANDLER resolver (not middleware)
 * - Called within RetellFunctionCallHandler after extractCallIdLayered()
 * - Performance: ~2-5ms (policy cache hit)
 * - Fallback strategy: feature_flag → company_policy → default_mode
 *
 * @package App\Services\Gateway
 */
class GatewayModeResolver
{
    /**
     * Resolve gateway mode for a given call ID
     *
     * Resolution Strategy:
     * 1. Check feature flag (gateway.mode_enabled)
     * 2. Get call from database by retell_call_id
     * 3. Check company gateway_mode policy (cached)
     * 4. Return policy mode or default
     *
     * @param string $callId Retell call ID
     * @return string Gateway mode (appointment|service_desk|hybrid)
     */
    public function resolve(string $callId): string
    {
        // 1. Feature flag check - fast fail if disabled
        if (!config('gateway.mode_enabled', false)) {
            return 'appointment';
        }

        // 2. Resolve call to company
        $call = Call::where('retell_call_id', $callId)->first();

        if (!$call) {
            Log::warning('[GatewayModeResolver] Call not found', [
                'call_id' => $callId,
                'fallback_mode' => config('gateway.default_mode'),
            ]);

            return config('gateway.default_mode', 'appointment');
        }

        // 2b. CRIT-002: Validate company context exists (multi-tenancy)
        if (!$call->company) {
            Log::error('[GatewayModeResolver] CRIT-002: Company context missing', [
                'call_id' => $callId,
                'company_id' => $call->company_id,
                'reason' => 'Company deleted or not found',
            ]);

            throw new \RuntimeException('CRIT-002: Tenant context required');
        }

        // 3. Get company gateway policy (cached - 5 min TTL)
        $policy = PolicyConfiguration::getCachedPolicy(
            $call->company,
            PolicyConfiguration::POLICY_TYPE_GATEWAY_MODE
        );

        // 4. Extract mode from policy config
        $mode = $policy?->config['mode'] ?? config('gateway.default_mode', 'appointment');

        // 5. Validate mode against allowed modes
        if (!in_array($mode, config('gateway.modes', ['appointment']))) {
            Log::error('[GatewayModeResolver] Invalid mode in policy', [
                'call_id' => $callId,
                'company_id' => $call->company_id,
                'invalid_mode' => $mode,
                'fallback_mode' => config('gateway.default_mode'),
            ]);

            return config('gateway.default_mode', 'appointment');
        }

        // 6. Hybrid mode - detect intent from initial utterance
        if ($mode === 'hybrid') {
            Log::info('[GatewayModeResolver] Hybrid mode - detecting intent', [
                'call_id' => $callId,
                'company_id' => $call->company_id,
            ]);

            $utterance = $this->getInitialUtterance($callId);

            if ($utterance) {
                $intentService = app(IntentDetectionService::class);
                $mode = $intentService->determineMode($utterance, $call->company_id);

                Log::info('[GatewayModeResolver] Intent detected', [
                    'call_id' => $callId,
                    'detected_mode' => $mode,
                    'utterance_preview' => substr($utterance, 0, 50),
                ]);
            } else {
                // No utterance yet, use fallback
                $mode = config('gateway.hybrid.fallback_mode', 'appointment');

                Log::info('[GatewayModeResolver] No utterance available, using fallback', [
                    'call_id' => $callId,
                    'fallback_mode' => $mode,
                ]);
            }
        }

        Log::info('[GatewayModeResolver] Mode resolved', [
            'call_id' => $callId,
            'company_id' => $call->company_id,
            'mode' => $mode,
            'from_policy' => $policy !== null,
        ]);

        return $mode;
    }

    /**
     * Get initial user utterance from call transcript
     *
     * Retrieves the first user message from the transcript for intent detection.
     * Falls back to cache if transcript segments are not yet available.
     *
     * @param string $callId Retell call ID
     * @return string|null Initial user utterance
     */
    private function getInitialUtterance(string $callId): ?string
    {
        // Try cache first (for early function calls)
        $cached = cache()->get("intent:utterance:{$callId}");
        if ($cached) {
            Log::debug('[GatewayModeResolver] Utterance from cache', [
                'call_id' => $callId,
            ]);
            return $cached;
        }

        // Try to get from transcript segments
        $call = Call::where('retell_call_id', $callId)->first();

        if (!$call || !$call->retell_call_session_id) {
            Log::debug('[GatewayModeResolver] No call session yet', [
                'call_id' => $callId,
            ]);
            return null;
        }

        // Get first user utterance from transcript
        $firstUserSegment = RetellTranscriptSegment::where('call_session_id', $call->retell_call_session_id)
            ->where('role', 'user')
            ->orderBy('segment_sequence')
            ->first();

        if ($firstUserSegment) {
            Log::debug('[GatewayModeResolver] Utterance from transcript', [
                'call_id' => $callId,
                'segment_id' => $firstUserSegment->id,
            ]);

            // Cache for future lookups (5 minutes)
            cache()->put("intent:utterance:{$callId}", $firstUserSegment->text, 300);

            return $firstUserSegment->text;
        }

        Log::debug('[GatewayModeResolver] No utterance available yet', [
            'call_id' => $callId,
        ]);

        return null;
    }
}
