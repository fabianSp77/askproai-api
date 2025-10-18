<?php

namespace App\Services\Tracing;

use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Trace Visualization Service - Formats traces for UI display
 *
 * Converts trace data into visualization formats:
 * - Flame graph data (timeline of spans)
 * - Waterfall chart (parent-child relationships)
 * - Timeline view (chronological)
 * - Summary cards (key metrics)
 * - Performance profiles (hotspots)
 */
class TraceVisualizationService
{
    /**
     * Generate flame graph data
     *
     * Returns spans organized for flame graph visualization
     *
     * @param DistributedTracingService $tracingService Tracing service
     * @return array Flame graph data
     */
    public static function generateFlameGraph(DistributedTracingService $tracingService): array
    {
        try {
            $timeline = $tracingService->getTraceTimeline();
            $spans = $timeline['timeline'] ?? [];

            if (empty($spans)) {
                return [];
            }

            // Find min start time to use as baseline
            $minStartTime = min(array_map(fn($s) => $s['start_time'], $spans));

            // Format for flame graph
            $flameGraphData = array_map(function ($span) use ($minStartTime) {
                return [
                    'name' => $span['name'],
                    'span_id' => $span['span_id'],
                    'parent_span_id' => $span['parent_span_id'],
                    'kind' => $span['kind'],
                    'start_ms' => round(($span['start_time'] - $minStartTime) / 1000, 2),
                    'duration_ms' => $span['duration_ms'],
                    'status' => $span['status'],
                    'color' => self::getColorForStatus($span['status']),
                ];
            }, $spans);

            return [
                'trace_id' => $tracingService->getTraceId(),
                'type' => 'flame_graph',
                'data' => $flameGraphData,
                'total_duration_ms' => $tracingService->getTraceTree()['total_duration_ms'],
            ];

        } catch (Exception $e) {
            Log::warning("Failed to generate flame graph", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Generate waterfall chart data
     *
     * Shows parent-child span relationships
     *
     * @param DistributedTracingService $tracingService Tracing service
     * @return array Waterfall data
     */
    public static function generateWaterfall(DistributedTracingService $tracingService): array
    {
        try {
            $tree = $tracingService->getTraceTree();
            $spans = $tree['spans'] ?? [];

            $waterfall = [];
            self::buildWaterfallTree($spans, $waterfall, 0);

            return [
                'trace_id' => $tracingService->getTraceId(),
                'type' => 'waterfall',
                'spans' => $waterfall,
            ];

        } catch (Exception $e) {
            Log::warning("Failed to generate waterfall", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Recursively build waterfall tree
     *
     * @param array $spans Spans to process
     * @param array $waterfall Output waterfall array
     * @param int $depth Current depth
     */
    private static function buildWaterfallTree(array $spans, array &$waterfall, int $depth = 0): void
    {
        foreach ($spans as $span) {
            $waterfall[] = [
                'name' => $span['name'],
                'depth' => $depth,
                'duration_ms' => $span['duration_ms'],
                'status' => $span['status'],
                'kind' => $span['kind'],
                'has_children' => !empty($span['children']),
            ];

            if (!empty($span['children'])) {
                self::buildWaterfallTree($span['children'], $waterfall, $depth + 1);
            }
        }
    }

    /**
     * Generate timeline view
     *
     * Shows chronological order of spans
     *
     * @param DistributedTracingService $tracingService Tracing service
     * @return array Timeline data
     */
    public static function generateTimeline(DistributedTracingService $tracingService): array
    {
        try {
            $timeline = $tracingService->getTraceTimeline();
            $spans = $timeline['timeline'] ?? [];

            // Find min/max times
            $startTimes = array_map(fn($s) => $s['start_time'], $spans);
            $endTimes = array_map(fn($s) => $s['end_time'] ?? $s['start_time'], $spans);

            $minTime = min($startTimes);
            $maxTime = max($endTimes);

            $timelineData = array_map(function ($span) use ($minTime, $maxTime) {
                $duration = $maxTime - $minTime;

                return [
                    'name' => $span['name'],
                    'start_percent' => round((($span['start_time'] - $minTime) / $duration) * 100, 2),
                    'duration_percent' => round((($span['duration_ms'] ?? 0) * 1000 / $duration) * 100, 2),
                    'duration_ms' => $span['duration_ms'],
                    'status' => $span['status'],
                    'kind' => $span['kind'],
                ];
            }, $spans);

            return [
                'trace_id' => $tracingService->getTraceId(),
                'type' => 'timeline',
                'timeline' => $timelineData,
                'total_duration_ms' => ($maxTime - $minTime) / 1000,
            ];

        } catch (Exception $e) {
            Log::warning("Failed to generate timeline", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Generate summary cards
     *
     * Key metrics for quick overview
     *
     * @param DistributedTracingService $tracingService Tracing service
     * @return array Summary card data
     */
    public static function generateSummary(DistributedTracingService $tracingService): array
    {
        try {
            $tree = $tracingService->getTraceTree();
            $stats = $tracingService->getTraceStatistics();

            return [
                'trace_id' => $tracingService->getTraceId(),
                'cards' => [
                    [
                        'title' => 'Total Duration',
                        'value' => $tree['total_duration_ms'] . 'ms',
                        'type' => 'duration',
                    ],
                    [
                        'title' => 'Span Count',
                        'value' => $tree['span_count'],
                        'type' => 'count',
                    ],
                    [
                        'title' => 'Operation Types',
                        'value' => count($stats['by_kind'] ?? []),
                        'type' => 'types',
                    ],
                    [
                        'title' => 'Slowest Operation',
                        'value' => self::getSlowestOperation($tree),
                        'type' => 'operation',
                    ],
                ],
            ];

        } catch (Exception $e) {
            Log::warning("Failed to generate summary", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get slowest operation
     *
     * @param array $tree Trace tree
     * @return string Slowest operation name + duration
     */
    private static function getSlowestOperation(array $tree): string
    {
        $spans = $tree['spans'] ?? [];

        if (empty($spans)) {
            return 'N/A';
        }

        $slowest = null;
        $maxDuration = 0;

        $findSlowest = function ($spans) use (&$findSlowest, &$slowest, &$maxDuration) {
            foreach ($spans as $span) {
                if (($span['duration_ms'] ?? 0) > $maxDuration) {
                    $maxDuration = $span['duration_ms'];
                    $slowest = $span;
                }

                if (!empty($span['children'])) {
                    $findSlowest($span['children']);
                }
            }
        };

        $findSlowest($spans);

        return $slowest
            ? $slowest['name'] . ' (' . $slowest['duration_ms'] . 'ms)'
            : 'N/A';
    }

    /**
     * Generate performance profile
     *
     * Shows where time is spent
     *
     * @param DistributedTracingService $tracingService Tracing service
     * @return array Performance profile
     */
    public static function generatePerformanceProfile(DistributedTracingService $tracingService): array
    {
        try {
            $stats = $tracingService->getTraceStatistics();

            $profile = [
                'trace_id' => $tracingService->getTraceId(),
                'type' => 'performance_profile',
                'operations' => [],
            ];

            // Calculate percentage of total time for each operation type
            $totalTime = 0;
            $byKind = $stats['by_kind'] ?? [];

            foreach ($byKind as $kind => $kindStats) {
                $totalTime += $kindStats['total_duration_ms'] ?? 0;
            }

            foreach ($byKind as $kind => $kindStats) {
                $percentage = $totalTime > 0
                    ? round(($kindStats['total_duration_ms'] / $totalTime) * 100, 2)
                    : 0;

                $profile['operations'][] = [
                    'kind' => $kind,
                    'count' => $kindStats['count'],
                    'total_duration_ms' => $kindStats['total_duration_ms'],
                    'avg_duration_ms' => $kindStats['avg_duration_ms'],
                    'percentage_of_total' => $percentage,
                    'color' => self::getColorForKind($kind),
                ];
            }

            // Sort by percentage descending
            usort($profile['operations'], fn($a, $b) => $b['percentage_of_total'] <=> $a['percentage_of_total']);

            return $profile;

        } catch (Exception $e) {
            Log::warning("Failed to generate performance profile", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get color for span status
     *
     * @param string $status Span status
     * @return string Hex color
     */
    private static function getColorForStatus(string $status): string
    {
        return match ($status) {
            'OK' => '#28a745',        // Green
            'ERROR' => '#dc3545',     // Red
            'UNSET' => '#6c757d',     // Gray
            default => '#17a2b8',     // Blue
        };
    }

    /**
     * Get color for operation kind
     *
     * @param string $kind Span kind
     * @return string Hex color
     */
    private static function getColorForKind(string $kind): string
    {
        return match ($kind) {
            'SERVER' => '#007bff',    // Blue
            'CLIENT' => '#17a2b8',    // Cyan
            'PRODUCER' => '#28a745',  // Green
            'CONSUMER' => '#ffc107',  // Yellow
            'INTERNAL' => '#6c757d',  // Gray
            default => '#e83e8c',     // Pink
        };
    }

    /**
     * Generate trace export (JSON)
     *
     * @param DistributedTracingService $tracingService Tracing service
     * @return array JSON-serializable trace data
     */
    public static function exportTrace(DistributedTracingService $tracingService): array
    {
        return [
            'trace_id' => $tracingService->getTraceId(),
            'tree' => $tracingService->getTraceTree(),
            'timeline' => $tracingService->getTraceTimeline(),
            'statistics' => $tracingService->getTraceStatistics(),
            'flame_graph' => self::generateFlameGraph($tracingService),
            'waterfall' => self::generateWaterfall($tracingService),
            'timeline_view' => self::generateTimeline($tracingService),
            'summary' => self::generateSummary($tracingService),
            'performance_profile' => self::generatePerformanceProfile($tracingService),
            'exported_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Generate HTML report
     *
     * @param DistributedTracingService $tracingService Tracing service
     * @return string HTML report
     */
    public static function generateHtmlReport(DistributedTracingService $tracingService): string
    {
        try {
            $export = self::exportTrace($tracingService);
            $summary = $export['summary']['cards'] ?? [];

            $html = '<html><head><style>';
            $html .= 'body { font-family: Arial; margin: 20px; }';
            $html .= '.card { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 4px; }';
            $html .= '.title { font-size: 18px; font-weight: bold; margin-bottom: 10px; }';
            $html .= '.value { font-size: 24px; color: #0066cc; }';
            $html .= 'table { width: 100%; border-collapse: collapse; }';
            $html .= 'th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }';
            $html .= 'th { background-color: #f5f5f5; }';
            $html .= '</style></head><body>';

            $html .= '<h1>Trace Report: ' . $export['trace_id'] . '</h1>';

            $html .= '<h2>Summary</h2>';
            foreach ($summary as $card) {
                $html .= '<div class="card">';
                $html .= '<div class="title">' . htmlspecialchars($card['title']) . '</div>';
                $html .= '<div class="value">' . htmlspecialchars($card['value']) . '</div>';
                $html .= '</div>';
            }

            $html .= '<h2>Operations</h2>';
            $html .= '<table>';
            $html .= '<tr><th>Operation</th><th>Duration (ms)</th><th>Count</th><th>Average (ms)</th></tr>';

            foreach (($export['performance_profile']['operations'] ?? []) as $op) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($op['kind']) . '</td>';
                $html .= '<td>' . $op['total_duration_ms'] . '</td>';
                $html .= '<td>' . $op['count'] . '</td>';
                $html .= '<td>' . $op['avg_duration_ms'] . '</td>';
                $html .= '</tr>';
            }

            $html .= '</table>';
            $html .= '</body></html>';

            return $html;

        } catch (Exception $e) {
            Log::warning("Failed to generate HTML report", [
                'error' => $e->getMessage(),
            ]);
            return '<html><body>Error generating report</body></html>';
        }
    }
}
