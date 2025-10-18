<?php

namespace App\Services\Tracing;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Request Trace Collector - Aggregates and analyzes traces
 *
 * Collects distributed traces and provides:
 * - Trace aggregation (combining spans from multiple services)
 * - Performance analysis (bottleneck detection)
 * - Error analysis (failure patterns)
 * - Trend analysis (performance degradation)
 * - Flame graph data (for visualization)
 */
class RequestTraceCollector
{
    /**
     * Collect trace data for analysis
     *
     * @param DistributedTracingService $tracingService Tracing service with spans
     * @return array Collected trace data
     */
    public static function collectTrace(DistributedTracingService $tracingService): array
    {
        try {
            $traceId = $tracingService->getTraceId();
            $tree = $tracingService->getTraceTree();
            $timeline = $tracingService->getTraceTimeline();
            $stats = $tracingService->getTraceStatistics();

            $traceData = [
                'trace_id' => $traceId,
                'timestamp' => now()->toIso8601String(),
                'tree' => $tree,
                'timeline' => $timeline['timeline'] ?? [],
                'statistics' => $stats,
                'analysis' => self::analyzeTrace($tracingService),
            ];

            // Store collected trace
            $tracesKey = 'traces:collected';
            $traces = Cache::get($tracesKey, []);
            $traces[] = $traceData;

            // Keep last 10000 traces
            if (count($traces) > 10000) {
                $traces = array_slice($traces, -10000);
            }

            Cache::put($tracesKey, $traces, 86400 * 7);  // 7 days

            return $traceData;

        } catch (Exception $e) {
            Log::warning("Failed to collect trace", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Analyze trace for performance issues
     *
     * @param DistributedTracingService $tracingService Tracing service
     * @return array Analysis results
     */
    private static function analyzeTrace(DistributedTracingService $tracingService): array
    {
        try {
            $tree = $tracingService->getTraceTree();
            $timeline = $tracingService->getTraceTimeline()['timeline'] ?? [];

            $analysis = [
                'total_duration_ms' => $tree['total_duration_ms'],
                'span_count' => $tree['span_count'],
                'bottlenecks' => self::identifyBottlenecks($timeline),
                'errors' => self::findErrors($timeline),
                'parallelism' => self::analyzeParallelism($timeline),
                'recommendations' => [],
            ];

            // Generate recommendations
            if (!empty($analysis['bottlenecks'])) {
                $analysis['recommendations'][] = "Slow operations detected - consider optimization";
            }

            if (!empty($analysis['errors'])) {
                $analysis['recommendations'][] = "Errors occurred in trace - review error handling";
            }

            return $analysis;

        } catch (Exception $e) {
            Log::debug("Failed to analyze trace", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Identify bottlenecks (slowest operations)
     *
     * @param array $spans Timeline of spans
     * @return array Bottleneck operations
     */
    private static function identifyBottlenecks(array $spans): array
    {
        if (empty($spans)) {
            return [];
        }

        // Sort by duration
        usort($spans, fn($a, $b) => ($b['duration_ms'] ?? 0) <=> ($a['duration_ms'] ?? 0));

        // Top 5 slowest
        return array_slice(array_map(function ($span) {
            return [
                'name' => $span['name'],
                'duration_ms' => $span['duration_ms'],
                'kind' => $span['kind'],
            ];
        }, $spans), 0, 5);
    }

    /**
     * Find errors in trace
     *
     * @param array $spans Timeline of spans
     * @return array Error spans
     */
    private static function findErrors(array $spans): array
    {
        return array_filter(array_map(function ($span) {
            if ($span['status'] === 'ERROR') {
                return [
                    'span_name' => $span['name'],
                    'error_events' => array_filter($span['events'] ?? [], fn($e) => $e['name'] === 'exception'),
                ];
            }
            return null;
        }, $spans));
    }

    /**
     * Analyze parallelism (concurrent vs sequential)
     *
     * @param array $spans Timeline of spans
     * @return array Parallelism analysis
     */
    private static function analyzeParallelism(array $spans): array
    {
        if (empty($spans)) {
            return ['concurrent_spans' => 0, 'sequential_depth' => 0];
        }

        $maxConcurrent = 0;
        $maxDepth = 0;
        $depthStack = [];

        foreach ($spans as $span) {
            $startTime = $span['start_time'];
            $endTime = $span['end_time'] ?? $span['start_time'];

            // Add to active spans
            $depthStack[$span['span_id']] = [
                'start' => $startTime,
                'end' => $endTime,
                'depth' => $span['parent_span_id'] ? 1 : 0,
            ];

            // Count currently active spans at this time
            $active = array_filter($depthStack, fn($s) => $s['start'] <= $startTime && $s['end'] >= $startTime);
            $maxConcurrent = max($maxConcurrent, count($active));

            // Track max depth
            if (isset($depthStack[$span['span_id']])) {
                $maxDepth = max($maxDepth, $depthStack[$span['span_id']]['depth']);
            }

            // Cleanup ended spans
            $depthStack = array_filter($depthStack, fn($s) => $s['end'] >= $startTime);
        }

        return [
            'concurrent_spans' => $maxConcurrent,
            'sequential_depth' => $maxDepth,
            'parallelism_score' => $maxConcurrent > 1 ? 'good' : 'low',
        ];
    }

    /**
     * Get traces matching criteria
     *
     * @param array $criteria Search criteria
     * @return array Matching traces
     */
    public static function getTraces(array $criteria = []): array
    {
        try {
            $tracesKey = 'traces:collected';
            $traces = Cache::get($tracesKey, []);

            if (empty($traces)) {
                return [];
            }

            // Filter by criteria
            if (isset($criteria['min_duration_ms'])) {
                $traces = array_filter($traces, fn($t) =>
                    ($t['tree']['total_duration_ms'] ?? 0) >= $criteria['min_duration_ms']
                );
            }

            if (isset($criteria['has_errors'])) {
                $traces = array_filter($traces, fn($t) =>
                    !empty($t['analysis']['errors'])
                );
            }

            if (isset($criteria['limit'])) {
                $traces = array_slice($traces, -$criteria['limit']);
            }

            return array_values($traces);

        } catch (Exception $e) {
            Log::warning("Failed to get traces", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get slow traces (performance issues)
     *
     * @param int $thresholdMs Threshold in milliseconds
     * @param int $limit Number of traces to return
     * @return array Slow traces
     */
    public static function getSlowTraces(int $thresholdMs = 1000, int $limit = 20): array
    {
        return self::getTraces([
            'min_duration_ms' => $thresholdMs,
            'limit' => $limit,
        ]);
    }

    /**
     * Get error traces
     *
     * @param int $limit Number of traces to return
     * @return array Traces with errors
     */
    public static function getErrorTraces(int $limit = 20): array
    {
        return self::getTraces([
            'has_errors' => true,
            'limit' => $limit,
        ]);
    }

    /**
     * Get trace performance distribution
     *
     * Groups traces by duration ranges
     *
     * @return array Performance distribution
     */
    public static function getPerformanceDistribution(): array
    {
        try {
            $tracesKey = 'traces:collected';
            $traces = Cache::get($tracesKey, []);

            $distribution = [
                'very_fast' => 0,      // < 100ms
                'fast' => 0,           // 100-500ms
                'normal' => 0,         // 500-1000ms
                'slow' => 0,           // 1000-5000ms
                'very_slow' => 0,      // > 5000ms
            ];

            foreach ($traces as $trace) {
                $duration = $trace['tree']['total_duration_ms'] ?? 0;

                if ($duration < 100) $distribution['very_fast']++;
                elseif ($duration < 500) $distribution['fast']++;
                elseif ($duration < 1000) $distribution['normal']++;
                elseif ($duration < 5000) $distribution['slow']++;
                else $distribution['very_slow']++;
            }

            return [
                'total_traces' => count($traces),
                'distribution' => $distribution,
                'average_duration_ms' => count($traces) > 0
                    ? array_sum(array_map(fn($t) => $t['tree']['total_duration_ms'] ?? 0, $traces)) / count($traces)
                    : 0,
            ];

        } catch (Exception $e) {
            Log::warning("Failed to get performance distribution", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get span kind distribution
     *
     * @return array Span kind statistics
     */
    public static function getSpanKindDistribution(): array
    {
        try {
            $tracesKey = 'traces:collected';
            $traces = Cache::get($tracesKey, []);

            $distribution = [];

            foreach ($traces as $trace) {
                $stats = $trace['statistics'] ?? [];
                foreach ($stats as $kind => $kindStats) {
                    if (!isset($distribution[$kind])) {
                        $distribution[$kind] = [];
                    }

                    $distribution[$kind][] = $kindStats['avg_duration_ms'];
                }
            }

            // Calculate averages
            $result = [];
            foreach ($distribution as $kind => $durations) {
                $result[$kind] = [
                    'count' => count($durations),
                    'avg_duration_ms' => round(array_sum($durations) / count($durations), 2),
                    'min_duration_ms' => min($durations),
                    'max_duration_ms' => max($durations),
                ];
            }

            return $result;

        } catch (Exception $e) {
            Log::warning("Failed to get span kind distribution", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get trace trends (over time)
     *
     * @param int $bucketSizeSeconds Bucket size for aggregation
     * @return array Trends data
     */
    public static function getTraceTrends(int $bucketSizeSeconds = 300): array
    {
        try {
            $tracesKey = 'traces:collected';
            $traces = Cache::get($tracesKey, []);

            $buckets = [];

            foreach ($traces as $trace) {
                $timestamp = strtotime($trace['timestamp']);
                $bucket = floor($timestamp / $bucketSizeSeconds) * $bucketSizeSeconds;

                if (!isset($buckets[$bucket])) {
                    $buckets[$bucket] = [
                        'count' => 0,
                        'total_duration' => 0,
                        'errors' => 0,
                    ];
                }

                $buckets[$bucket]['count']++;
                $buckets[$bucket]['total_duration'] += $trace['tree']['total_duration_ms'] ?? 0;
                $buckets[$bucket]['errors'] += count($trace['analysis']['errors'] ?? []);
            }

            // Calculate averages
            $trends = [];
            foreach ($buckets as $bucket => $data) {
                $trends[] = [
                    'bucket' => $bucket,
                    'timestamp' => date('c', $bucket),
                    'trace_count' => $data['count'],
                    'avg_duration_ms' => round($data['total_duration'] / $data['count'], 2),
                    'error_count' => $data['errors'],
                ];
            }

            return array_values(array_sort($trends, fn($t) => $t['bucket']));

        } catch (Exception $e) {
            Log::warning("Failed to get trace trends", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Clear old traces
     *
     * @return array Cleanup results
     */
    public static function cleanup(): array
    {
        try {
            $tracesKey = 'traces:collected';
            Cache::forget($tracesKey);

            Log::info("Traces cleaned up");

            return ['status' => 'success'];

        } catch (Exception $e) {
            Log::warning("Failed to cleanup traces", [
                'error' => $e->getMessage(),
            ]);
            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }
}
