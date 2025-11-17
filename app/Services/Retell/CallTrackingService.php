<?php

namespace App\Services\Retell;

use App\Models\RetellCallSession;
use App\Models\RetellCallEvent;
use App\Models\RetellFunctionTrace;
use App\Models\RetellTranscriptSegment;
use App\Models\RetellErrorLog;
use App\Services\Tracing\RequestCorrelationService;
use App\Helpers\LogSanitizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CallTrackingService
{
    public function __construct(
        private RequestCorrelationService $correlationService,
        private LogSanitizer $sanitizer
    ) {}

    /**
     * Start a new call session tracking.
     *
     * @param array $data Call initialization data from Retell
     * @return RetellCallSession
     */
    public function startCallSession(array $data): RetellCallSession
    {
        return DB::transaction(function () use ($data) {
            $session = RetellCallSession::create([
                'id' => Str::uuid(),
                'call_id' => $data['call_id'],
                'company_id' => $data['company_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'branch_id' => $data['branch_id'] ?? null,
                'phone_number' => $data['phone_number'] ?? null,
                'branch_name' => $data['branch_name'] ?? null,
                'agent_id' => $data['agent_id'] ?? null,
                'agent_version' => $data['agent_version'] ?? null,
                'started_at' => now(),
                'call_status' => 'in_progress',
                'conversation_flow_id' => $data['conversation_flow_id'] ?? null,
                'metadata' => $data['metadata'] ?? [],
            ]);

            // Cache the active session for fast lookup
            $this->cacheActiveSession($session);

            Log::info('📞 Call session started', [
                'call_id' => $session->call_id,
                'session_id' => $session->id,
                'company_id' => $session->company_id,
                'customer_id' => $session->customer_id,
                'branch_id' => $session->branch_id,
                'phone_number' => $session->phone_number,
            ]);

            return $session;
        });
    }

    /**
     * Track a function call (USER'S #1 PRIORITY FEATURE).
     *
     * Captures EVERY function call with:
     * - Input parameters
     * - Execution timing
     * - Output results
     * - Errors
     *
     * @param string $callId Retell call ID
     * @param string $functionName Function being called
     * @param array $arguments Input parameters
     * @param string|null $correlationId Optional correlation ID
     * @return RetellFunctionTrace
     */
    public function trackFunctionCall(
        string $callId,
        string $functionName,
        array $arguments,
        ?string $correlationId = null
    ): RetellFunctionTrace {
        $session = $this->getOrCreateSession($callId);

        return DB::transaction(function () use ($session, $functionName, $arguments, $correlationId) {
            // Generate correlation ID if not provided
            $correlationId = $correlationId ?? $this->correlationService->getId();

            // Sanitize input params for PII protection
            $sanitizedArgs = $this->sanitizer->sanitize($arguments);

            // Calculate call offset
            $callOffsetMs = $session->getCallOffsetMs(now());

            // Create event record
            $event = $session->events()->create([
                'correlation_id' => $correlationId,
                'event_type' => 'function_call',
                'occurred_at' => now(),
                'call_offset_ms' => $callOffsetMs,
                'function_name' => $functionName,
                'function_arguments' => $sanitizedArgs,
                'function_status' => 'pending',
            ]);

            // Create function trace (optimized for debugging)
            $trace = RetellFunctionTrace::create([
                'call_session_id' => $session->id,
                'event_id' => $event->id,
                'correlation_id' => $correlationId,
                'function_name' => $functionName,
                'execution_sequence' => $session->function_call_count + 1,
                'started_at' => now(),
                'status' => 'pending',
                'input_params' => $sanitizedArgs,
            ]);

            // Update session counters
            $session->increment('function_call_count');
            $session->increment('total_events');

            Log::info('🔧 Function call tracked', [
                'call_id' => $session->call_id,
                'function' => $functionName,
                'sequence' => $trace->execution_sequence,
                'correlation_id' => $correlationId,
                'trace_id' => $trace->id,
            ]);

            return $trace;
        });
    }

    /**
     * Record function execution result.
     *
     * @param int $traceId Function trace ID
     * @param array $response Function output
     * @param string $status 'success' or 'error'
     * @param array|null $error Error details if failed
     * @return void
     */
    public function recordFunctionResponse(
        int $traceId,
        array $response,
        string $status = 'success',
        ?array $error = null
    ): void {
        $trace = RetellFunctionTrace::findOrFail($traceId);
        $session = $trace->callSession;

        DB::transaction(function () use ($trace, $session, $response, $status, $error) {
            // Calculate duration
            $durationMs = now()->diffInMilliseconds($trace->started_at);

            // Sanitize output for PII protection
            $sanitizedResponse = $this->sanitizer->sanitize($response);
            $sanitizedError = $error ? $this->sanitizer->sanitize($error) : null;

            // Update function trace
            $trace->update([
                'completed_at' => now(),
                'duration_ms' => $durationMs,
                'output_result' => $sanitizedResponse,
                'status' => $status,
                'error_details' => $sanitizedError,
            ]);

            // Update related event
            $trace->event->update([
                'function_response' => $sanitizedResponse,
                'response_time_ms' => $durationMs,
                'function_status' => $status,
            ]);

            // Update session metrics
            $this->updateSessionMetrics($session);

            // Log error if failed
            if ($status === 'error' && $error) {
                $this->logError($session, $trace, $error);
            }

            Log::info($status === 'success' ? '✅ Function completed' : '❌ Function failed', [
                'call_id' => $session->call_id,
                'function' => $trace->function_name,
                'duration_ms' => $durationMs,
                'status' => $status,
                'trace_id' => $trace->id,
            ]);
        });
    }

    /**
     * Record transcript segment with timeline correlation.
     *
     * @param string $callId Retell call ID
     * @param string $role 'agent' or 'user'
     * @param string $text Transcript text
     * @param array $metadata Additional metadata
     * @return RetellTranscriptSegment
     */
    public function recordTranscript(
        string $callId,
        string $role,
        string $text,
        array $metadata = []
    ): RetellTranscriptSegment {
        $session = $this->getOrCreateSession($callId);

        return DB::transaction(function () use ($session, $role, $text, $metadata) {
            $callOffsetMs = $session->getCallOffsetMs(now());

            // Create event
            $event = $session->events()->create([
                'event_type' => 'transcript',
                'occurred_at' => now(),
                'call_offset_ms' => $callOffsetMs,
                'transcript_text' => $text,
                'transcript_role' => $role,
            ]);

            // Create transcript segment
            $segment = RetellTranscriptSegment::create([
                'call_session_id' => $session->id,
                'event_id' => $event->id,
                'occurred_at' => now(),
                'call_offset_ms' => $callOffsetMs,
                'segment_sequence' => $session->transcript_segment_count + 1,
                'role' => $role,
                'text' => $text,
                'word_count' => str_word_count($text),
                'metadata' => $metadata,
            ]);

            // Update session counters
            $session->increment('transcript_segment_count');
            $session->increment('total_events');

            return $segment;
        });
    }

    /**
     * Record flow transition.
     *
     * @param string $callId Retell call ID
     * @param string $fromNode Previous node
     * @param string $toNode New node
     * @param string|null $trigger What triggered the transition
     * @return RetellCallEvent
     */
    public function recordFlowTransition(
        string $callId,
        string $fromNode,
        string $toNode,
        ?string $trigger = null
    ): RetellCallEvent {
        $session = $this->getOrCreateSession($callId);

        return DB::transaction(function () use ($session, $fromNode, $toNode, $trigger) {
            $callOffsetMs = $session->getCallOffsetMs(now());

            $event = $session->events()->create([
                'event_type' => 'flow_transition',
                'occurred_at' => now(),
                'call_offset_ms' => $callOffsetMs,
                'from_node' => $fromNode,
                'to_node' => $toNode,
                'transition_trigger' => $trigger,
            ]);

            // Update session current node
            $session->update(['current_flow_node' => $toNode]);
            $session->increment('total_events');

            Log::info('🔄 Flow transition', [
                'call_id' => $session->call_id,
                'from' => $fromNode,
                'to' => $toNode,
                'trigger' => $trigger,
            ]);

            return $event;
        });
    }

    /**
     * End a call session.
     *
     * @param string $callId Retell call ID
     * @param array $data Final call data
     * @return RetellCallSession
     */
    public function endCallSession(string $callId, array $data = []): RetellCallSession
    {
        $session = $this->getSession($callId);

        DB::transaction(function () use ($session, $data) {
            $durationMs = now()->diffInMilliseconds($session->started_at);

            $session->update([
                'ended_at' => now(),
                'duration_ms' => $durationMs,
                'call_status' => $data['status'] ?? 'completed',
                'disconnection_reason' => $data['disconnection_reason'] ?? null,
                'metadata' => array_merge($session->metadata ?? [], $data['metadata'] ?? []),
            ]);

            // Update final metrics
            $this->updateSessionMetrics($session);

            // Remove from cache
            Cache::forget($this->getSessionCacheKey($callId));

            Log::info('🏁 Call session ended', [
                'call_id' => $session->call_id,
                'duration_ms' => $durationMs,
                'function_calls' => $session->function_call_count,
                'errors' => $session->error_count,
            ]);
        });

        return $session->fresh();
    }

    /**
     * Get call timeline with all events correlated.
     *
     * @param string $callId Retell call ID
     * @return array Timeline of events
     */
    public function getCallTimeline(string $callId): array
    {
        $session = $this->getSession($callId);

        return $session->events()
            ->orderBy('occurred_at')
            ->get()
            ->map(function ($event) {
                return [
                    'timestamp' => $event->occurred_at,
                    'offset_ms' => $event->call_offset_ms,
                    'type' => $event->event_type,
                    'data' => $this->extractEventData($event),
                ];
            })
            ->toArray();
    }

    /**
     * Get function call chain for a call.
     *
     * @param string $callId Retell call ID
     * @return array Function traces in execution order
     */
    public function getFunctionCallChain(string $callId): array
    {
        $session = $this->getSession($callId);

        return $session->functionTraces()
            ->inSequence()
            ->get()
            ->map(function ($trace) {
                return [
                    'sequence' => $trace->execution_sequence,
                    'function' => $trace->function_name,
                    'started_at' => $trace->started_at,
                    'duration_ms' => $trace->duration_ms,
                    'status' => $trace->status,
                    'input' => $trace->input_params,
                    'output' => $trace->output_result,
                    'error' => $trace->error_details,
                ];
            })
            ->toArray();
    }

    /**
     * Get error summary for a call.
     *
     * @param string $callId Retell call ID
     * @return array Error details
     */
    public function getErrorSummary(string $callId): array
    {
        $session = $this->getSession($callId);

        $errors = $session->errors()->get();

        return [
            'total_errors' => $errors->count(),
            'critical_errors' => $errors->where('severity', 'critical')->count(),
            'booking_failures' => $errors->where('booking_failed', true)->count(),
            'call_terminating_errors' => $errors->where('call_terminated', true)->count(),
            'unresolved_errors' => $errors->where('resolved', false)->count(),
            'errors' => $errors->map(function ($error) {
                return [
                    'code' => $error->error_code,
                    'type' => $error->error_type,
                    'severity' => $error->severity,
                    'message' => $error->error_message,
                    'function' => $error->affected_function,
                    'occurred_at' => $error->occurred_at,
                ];
            })->toArray(),
        ];
    }

    /**
     * Get or create session (handles missing sessions gracefully).
     */
    private function getOrCreateSession(string $callId): RetellCallSession
    {
        try {
            return $this->getSession($callId);
        } catch (\Exception $e) {
            Log::warning('Session not found, creating new one', ['call_id' => $callId]);
            return $this->startCallSession(['call_id' => $callId]);
        }
    }

    /**
     * Get session by call ID (cached).
     */
    private function getSession(string $callId): RetellCallSession
    {
        $cacheKey = $this->getSessionCacheKey($callId);

        return Cache::remember($cacheKey, 3600, function () use ($callId) {
            return RetellCallSession::where('call_id', $callId)->firstOrFail();
        });
    }

    /**
     * Cache active session for fast lookup.
     */
    private function cacheActiveSession(RetellCallSession $session): void
    {
        $cacheKey = $this->getSessionCacheKey($session->call_id);
        Cache::put($cacheKey, $session, 3600); // 1 hour TTL
    }

    /**
     * Get cache key for session.
     */
    private function getSessionCacheKey(string $callId): string
    {
        return "retell:call_session:{$callId}";
    }

    /**
     * Update session performance metrics.
     */
    private function updateSessionMetrics(RetellCallSession $session): void
    {
        $traces = $session->functionTraces()
            ->whereNotNull('duration_ms')
            ->get();

        if ($traces->isNotEmpty()) {
            $session->update([
                'avg_response_time_ms' => (int) $traces->avg('duration_ms'),
                'max_response_time_ms' => (int) $traces->max('duration_ms'),
                'min_response_time_ms' => (int) $traces->min('duration_ms'),
            ]);
        }
    }

    /**
     * Log error to error table.
     */
    private function logError(RetellCallSession $session, RetellFunctionTrace $trace, array $error): void
    {
        $severity = $this->determineSeverity($error);
        $errorType = $this->determineErrorType($error);

        RetellErrorLog::create([
            'call_session_id' => $session->id,
            'event_id' => $trace->event_id,
            'function_trace_id' => $trace->id,
            'error_code' => $error['code'] ?? 'unknown',
            'error_type' => $errorType,
            'severity' => $severity,
            'occurred_at' => now(),
            'call_offset_ms' => $session->getCallOffsetMs(now()),
            'error_message' => $error['message'] ?? 'Unknown error',
            'stack_trace' => $error['trace'] ?? null,
            'error_context' => $this->sanitizer->sanitize($error),
            'call_terminated' => $error['call_terminated'] ?? false,
            'booking_failed' => $error['booking_failed'] ?? false,
            'affected_function' => $trace->function_name,
        ]);

        $session->increment('error_count');
    }

    /**
     * Determine error severity.
     */
    private function determineSeverity(array $error): string
    {
        if (isset($error['severity'])) {
            return $error['severity'];
        }

        if ($error['call_terminated'] ?? false || $error['booking_failed'] ?? false) {
            return 'critical';
        }

        return 'medium';
    }

    /**
     * Determine error type.
     */
    private function determineErrorType(array $error): string
    {
        if (isset($error['type'])) {
            return $error['type'];
        }

        $code = $error['code'] ?? '';

        if (str_contains($code, 'api_')) {
            return 'api_error';
        }

        if (str_contains($code, 'validation_')) {
            return 'validation_error';
        }

        return 'function_error';
    }

    /**
     * Extract event data for timeline.
     */
    private function extractEventData(RetellCallEvent $event): array
    {
        return match ($event->event_type) {
            'function_call' => [
                'function' => $event->function_name,
                'arguments' => $event->function_arguments,
                'response' => $event->function_response,
                'duration_ms' => $event->response_time_ms,
                'status' => $event->function_status,
            ],
            'transcript' => [
                'role' => $event->transcript_role,
                'text' => $event->transcript_text,
            ],
            'flow_transition' => [
                'from' => $event->from_node,
                'to' => $event->to_node,
                'trigger' => $event->transition_trigger,
            ],
            'error' => [
                'code' => $event->error_code,
                'message' => $event->error_message,
                'context' => $event->error_context,
            ],
            default => [],
        };
    }
}
