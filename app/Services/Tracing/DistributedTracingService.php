<?php

namespace App\Services\Tracing;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Distributed Tracing Service - OpenTelemetry-based request tracing
 *
 * Creates spans for each operation:
 * - HTTP request (entry span)
 * - Saga steps
 * - External API calls
 * - Database queries
 * - Cache operations
 * - Circuit breaker evaluations
 *
 * Each span tracks:
 * - Start/end time
 * - Duration
 * - Status (success/error)
 * - Attributes (service name, operation name, etc.)
 * - Logs (events during span)
 * - Parent span (for hierarchy)
 *
 * Enables detailed request flow visualization
 */
class DistributedTracingService
{
    /**
     * Current trace ID (shared across all spans in request)
     */
    private string $traceId;

    /**
     * Root span context
     */
    private array $rootSpan;

    /**
     * Active spans stack (for parent-child relationships)
     */
    private array $spanStack = [];

    public function __construct(?string $traceId = null)
    {
        $this->traceId = $traceId ?? $this->generateTraceId();
        $this->rootSpan = $this->createRootSpan();
    }

    /**
     * Generate unique trace ID
     *
     * @return string Trace ID (128-bit hex)
     */
    private function generateTraceId(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Generate unique span ID
     *
     * @return string Span ID (64-bit hex)
     */
    private function generateSpanId(): string
    {
        return bin2hex(random_bytes(8));
    }

    /**
     * Create root span for this request
     *
     * @return array Root span
     */
    private function createRootSpan(): array
    {
        return [
            'trace_id' => $this->traceId,
            'span_id' => $this->generateSpanId(),
            'parent_span_id' => null,
            'name' => 'root',
            'kind' => 'INTERNAL',
            'start_time' => $this->microtime(),
            'end_time' => null,
            'duration_ms' => null,
            'status' => 'UNSET',
            'attributes' => [
                'http.method' => request()->method(),
                'http.url' => request()->fullUrl(),
                'http.client_ip' => request()->ip(),
            ],
            'events' => [],
            'links' => [],
        ];
    }

    /**
     * Get current time in microseconds
     *
     * @return float Timestamp in microseconds
     */
    private function microtime(): float
    {
        return microtime(true) * 1000000;
    }

    /**
     * Start a new span
     *
     * @param string $name Span name
     * @param array $attributes Span attributes
     * @param string $kind Span kind (INTERNAL, SERVER, CLIENT, PRODUCER, CONSUMER)
     * @return string Span ID
     */
    public function startSpan(
        string $name,
        array $attributes = [],
        string $kind = 'INTERNAL'
    ): string {
        $parentSpan = end($this->spanStack) ?: $this->rootSpan;

        $span = [
            'trace_id' => $this->traceId,
            'span_id' => $this->generateSpanId(),
            'parent_span_id' => $parentSpan['span_id'],
            'name' => $name,
            'kind' => $kind,
            'start_time' => $this->microtime(),
            'end_time' => null,
            'duration_ms' => null,
            'status' => 'UNSET',
            'attributes' => $attributes,
            'events' => [],
            'links' => [],
        ];

        $this->spanStack[] = $span;
        $this->storeSpan($span);

        Log::debug("Span started", [
            'trace_id' => $this->traceId,
            'span_id' => $span['span_id'],
            'name' => $name,
            'parent' => $span['parent_span_id'],
        ]);

        return $span['span_id'];
    }

    /**
     * End current span
     *
     * @param string $spanId Span ID to end
     * @param string $status Status (OK, ERROR, UNSET)
     * @param ?Exception $exception Exception if failed
     */
    public function endSpan(
        string $spanId,
        string $status = 'OK',
        ?Exception $exception = null
    ): void {
        try {
            // Find span in stack
            $spanKey = null;
            foreach ($this->spanStack as $key => $span) {
                if ($span['span_id'] === $spanId) {
                    $spanKey = $key;
                    break;
                }
            }

            if ($spanKey === null) {
                Log::warning("Span not found", ['span_id' => $spanId]);
                return;
            }

            $span = $this->spanStack[$spanKey];
            $now = $this->microtime();

            $span['end_time'] = $now;
            $span['duration_ms'] = round(($now - $span['start_time']) / 1000, 2);
            $span['status'] = $exception ? 'ERROR' : $status;

            if ($exception) {
                $span['events'][] = [
                    'name' => 'exception',
                    'timestamp' => $now,
                    'attributes' => [
                        'exception.type' => get_class($exception),
                        'exception.message' => $exception->getMessage(),
                        'exception.stacktrace' => $exception->getTraceAsString(),
                    ],
                ];
            }

            // Update span
            $this->spanStack[$spanKey] = $span;
            $this->storeSpan($span);

            // Remove from active stack
            array_pop($this->spanStack);

            Log::debug("Span ended", [
                'trace_id' => $this->traceId,
                'span_id' => $spanId,
                'duration_ms' => $span['duration_ms'],
                'status' => $span['status'],
            ]);

        } catch (Exception $e) {
            Log::warning("Failed to end span", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Add event to current span
     *
     * Events represent something that happened during span
     *
     * @param string $eventName Event name
     * @param array $attributes Event attributes
     */
    public function addEvent(string $eventName, array $attributes = []): void
    {
        try {
            $span = end($this->spanStack);
            if (!$span) {
                return;
            }

            $event = [
                'name' => $eventName,
                'timestamp' => $this->microtime(),
                'attributes' => $attributes,
            ];

            $span['events'][] = $event;
            $this->updateSpan($span['span_id'], $span);

        } catch (Exception $e) {
            Log::debug("Failed to add event", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Add attribute to current span
     *
     * @param string $key Attribute key
     * @param mixed $value Attribute value
     */
    public function addAttribute(string $key, mixed $value): void
    {
        try {
            $span = end($this->spanStack);
            if (!$span) {
                return;
            }

            $span['attributes'][$key] = $value;
            $this->updateSpan($span['span_id'], $span);

        } catch (Exception $e) {
            Log::debug("Failed to add attribute", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Record exception on current span
     *
     * @param Exception $exception Exception to record
     */
    public function recordException(Exception $exception): void
    {
        try {
            $this->addEvent('exception', [
                'exception.type' => get_class($exception),
                'exception.message' => $exception->getMessage(),
                'exception.stacktrace' => $exception->getTraceAsString(),
            ]);

            $span = end($this->spanStack);
            if ($span) {
                $span['status'] = 'ERROR';
                $this->updateSpan($span['span_id'], $span);
            }

        } catch (Exception $e) {
            Log::debug("Failed to record exception", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Store span in cache
     *
     * @param array $span Span data
     */
    private function storeSpan(array $span): void
    {
        try {
            $spansKey = "trace:{$this->traceId}:spans";
            $spans = Cache::get($spansKey, []);
            $spans[] = $span;

            Cache::put($spansKey, $spans, 86400);  // 24 hours

        } catch (Exception $e) {
            Log::debug("Failed to store span", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update existing span
     *
     * @param string $spanId Span ID
     * @param array $span Updated span data
     */
    private function updateSpan(string $spanId, array $span): void
    {
        try {
            $spansKey = "trace:{$this->traceId}:spans";
            $spans = Cache::get($spansKey, []);

            // Find and update span
            foreach ($spans as &$s) {
                if ($s['span_id'] === $spanId) {
                    $s = $span;
                    break;
                }
            }

            Cache::put($spansKey, $spans, 86400);

        } catch (Exception $e) {
            Log::debug("Failed to update span", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get trace tree (spans organized by parent-child)
     *
     * @return array Trace tree with hierarchy
     */
    public function getTraceTree(): array
    {
        try {
            $spansKey = "trace:{$this->traceId}:spans";
            $spans = Cache::get($spansKey, []);

            // Build tree structure
            $tree = [];
            $spanIndex = [];

            // Index spans by ID for quick lookup
            foreach ($spans as $span) {
                $spanIndex[$span['span_id']] = $span;
                $span['children'] = [];

                if ($span['parent_span_id'] === null) {
                    $tree[] = $span;
                }
            }

            // Attach children to parents
            foreach ($spans as $span) {
                if ($span['parent_span_id'] !== null) {
                    if (isset($spanIndex[$span['parent_span_id']])) {
                        $spanIndex[$span['parent_span_id']]['children'][] = $span;
                    }
                }
            }

            return [
                'trace_id' => $this->traceId,
                'span_count' => count($spans),
                'total_duration_ms' => $this->calculateTotalDuration($spans),
                'spans' => $tree,
            ];

        } catch (Exception $e) {
            Log::warning("Failed to get trace tree", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Calculate total trace duration
     *
     * @param array $spans All spans
     * @return float|null Total duration in ms
     */
    private function calculateTotalDuration(array $spans): ?float
    {
        if (empty($spans)) {
            return null;
        }

        $startTimes = array_map(fn($s) => $s['start_time'], $spans);
        $endTimes = array_map(fn($s) => $s['end_time'] ?? $s['start_time'], $spans);

        $minStart = min($startTimes);
        $maxEnd = max($endTimes);

        return round(($maxEnd - $minStart) / 1000, 2);
    }

    /**
     * Get trace timeline (spans in chronological order)
     *
     * @return array Spans ordered by start time
     */
    public function getTraceTimeline(): array
    {
        try {
            $spansKey = "trace:{$this->traceId}:spans";
            $spans = Cache::get($spansKey, []);

            // Sort by start time
            usort($spans, fn($a, $b) => $a['start_time'] <=> $b['start_time']);

            return [
                'trace_id' => $this->traceId,
                'timeline' => $spans,
            ];

        } catch (Exception $e) {
            Log::warning("Failed to get trace timeline", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get trace statistics
     *
     * @return array Trace metrics
     */
    public function getTraceStatistics(): array
    {
        try {
            $spansKey = "trace:{$this->traceId}:spans";
            $spans = Cache::get($spansKey, []);

            if (empty($spans)) {
                return [];
            }

            // Group by span kind
            $byKind = [];
            foreach ($spans as $span) {
                $kind = $span['kind'];
                if (!isset($byKind[$kind])) {
                    $byKind[$kind] = [];
                }
                $byKind[$kind][] = $span;
            }

            // Calculate statistics
            $statistics = [];
            foreach ($byKind as $kind => $kindSpans) {
                $durations = array_map(fn($s) => $s['duration_ms'] ?? 0, $kindSpans);

                $statistics[$kind] = [
                    'count' => count($kindSpans),
                    'total_duration_ms' => array_sum($durations),
                    'avg_duration_ms' => round(array_sum($durations) / count($durations), 2),
                    'max_duration_ms' => max($durations),
                    'min_duration_ms' => min($durations),
                ];
            }

            return [
                'trace_id' => $this->traceId,
                'total_spans' => count($spans),
                'by_kind' => $statistics,
            ];

        } catch (Exception $e) {
            Log::warning("Failed to get trace statistics", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get trace ID
     *
     * @return string Trace ID
     */
    public function getTraceId(): string
    {
        return $this->traceId;
    }

    /**
     * Get root span
     *
     * @return array Root span
     */
    public function getRootSpan(): array
    {
        return $this->rootSpan;
    }
}
