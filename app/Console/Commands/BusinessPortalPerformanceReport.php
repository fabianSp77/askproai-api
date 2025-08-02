<?php

namespace App\Console\Commands;

use App\Services\BusinessPortalPerformanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class BusinessPortalPerformanceReport extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'performance:business-portal-report 
                            {--timeframe=last_24_hours : Time frame for the report}
                            {--email= : Email address to send the report to}
                            {--format=table : Output format (table, json, csv)}
                            {--export= : Export to file path}';

    /**
     * The console command description.
     */
    protected $description = 'Generate a comprehensive Business Portal performance report';

    protected BusinessPortalPerformanceService $performanceService;

    public function __construct(BusinessPortalPerformanceService $performanceService)
    {
        parent::__construct();
        $this->performanceService = $performanceService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $timeframe = $this->option('timeframe');
        $format = $this->option('format');
        
        $this->info("Generating Business Portal Performance Report for {$timeframe}...");
        
        // Generate the performance report
        $data = $this->performanceService->getPortalDashboardData($timeframe);
        $healthScore = $this->performanceService->getHealthScore($timeframe);
        
        // Display the report based on format
        switch ($format) {
            case 'json':
                $this->displayJsonReport($data, $healthScore);
                break;
            case 'csv':
                $this->displayCsvReport($data, $healthScore);
                break;
            default:
                $this->displayTableReport($data, $healthScore);
                break;
        }
        
        // Export to file if requested
        if ($exportPath = $this->option('export')) {
            $this->exportReport($data, $healthScore, $exportPath, $format);
        }
        
        // Send email if requested
        if ($email = $this->option('email')) {
            $this->sendEmailReport($data, $healthScore, $email);
        }
        
        return Command::SUCCESS;
    }

    /**
     * Display performance report in table format
     */
    protected function displayTableReport(array $data, array $healthScore): void
    {
        // Performance Overview
        $this->newLine();
        $this->info('🎯 Performance Health Score: ' . $healthScore['overall_score'] . '% (' . $healthScore['grade'] . ')');
        $this->newLine();
        
        // Overview metrics
        $overview = $data['overview'] ?? [];
        $overviewData = [
            ['Metric', 'Value', 'Status'],
            ['Total Requests', number_format($overview['total_requests'] ?? 0), '📊'],
            ['Avg Response Time', ($overview['avg_response_time'] ?? 0) . 'ms', $this->getStatusIcon($overview['avg_response_time'] ?? 0, 200)],
            ['P95 Response Time', ($overview['p95_response_time'] ?? 0) . 'ms', $this->getStatusIcon($overview['p95_response_time'] ?? 0, 400)],
            ['Error Rate', ($overview['error_rate'] ?? 0) . '%', $this->getStatusIcon($overview['error_rate'] ?? 0, 1, true)],
            ['SLA Compliance', ($overview['sla_compliance_percentage'] ?? 0) . '%', $this->getStatusIcon($overview['sla_compliance_percentage'] ?? 0, 99)],
            ['Uptime', ($overview['uptime_percentage'] ?? 0) . '%', $this->getStatusIcon($overview['uptime_percentage'] ?? 0, 99.9)],
        ];
        
        $this->table($overviewData[0], array_slice($overviewData, 1));

        // SLA Compliance by Endpoint
        if (!empty($data['sla_compliance'])) {
            $this->newLine();
            $this->info('📈 SLA Compliance by Endpoint');
            
            $slaData = [['Endpoint', 'Target', 'Actual', 'Compliance', 'Status']];
            foreach ($data['sla_compliance'] as $endpoint => $compliance) {
                $slaData[] = [
                    str_replace('/business', '', $endpoint),
                    $compliance['target_ms'] . 'ms',
                    $compliance['avg_response_time'] . 'ms',
                    $compliance['compliance_percentage'] . '%',
                    $this->getSLAStatusIcon($compliance['status']),
                ];
            }
            
            $this->table($slaData[0], array_slice($slaData, 1));
        }

        // Top Performing/Underperforming Endpoints
        if (!empty($data['endpoint_performance'])) {
            $this->newLine();
            $this->info('🏆 Top Endpoints by Response Time');
            
            $endpointData = [['Endpoint', 'Requests', 'Avg Time', 'P95 Time', 'Errors']];
            foreach (array_slice($data['endpoint_performance'], 0, 8) as $endpoint) {
                $endpointData[] = [
                    str_replace('/business', '', $endpoint['endpoint']),
                    number_format($endpoint['requests']),
                    $endpoint['avg_time'] . 'ms',
                    $endpoint['p95_time'] . 'ms',
                    $endpoint['error_count'],
                ];
            }
            
            $this->table($endpointData[0], array_slice($endpointData, 1));
        }

        // Active Alerts
        if (!empty($data['active_alerts'])) {
            $this->newLine();
            $this->error('🚨 Active Performance Alerts: ' . count($data['active_alerts']));
            
            $alertData = [['Severity', 'Endpoint', 'Issue', 'Breach %']];
            foreach ($data['active_alerts'] as $alert) {
                $alertData[] = [
                    strtoupper($alert['severity']),
                    str_replace('/business', '', $alert['endpoint']),
                    $alert['actual_ms'] . 'ms (target: ' . $alert['target_ms'] . 'ms)',
                    '+' . $alert['breach_percentage'] . '%',
                ];
            }
            
            $this->table($alertData[0], array_slice($alertData, 1));
        } else {
            $this->newLine();
            $this->info('✅ No active performance alerts');
        }

        // Health Score Breakdown
        if (!empty($healthScore['factors'])) {
            $this->newLine();
            $this->info('🏥 Health Score Breakdown');
            
            $factorData = [['Factor', 'Score', 'Weight', 'Impact']];
            foreach ($healthScore['factors'] as $factor => $data) {
                $factorData[] = [
                    ucfirst(str_replace('_', ' ', $factor)),
                    round($data['score'], 1) . '%',
                    $data['weight'] . '%',
                    $this->getHealthImpactIcon($data['score']),
                ];
            }
            
            $this->table($factorData[0], array_slice($factorData, 1));
        }

        // Recommendations
        if (!empty($healthScore['recommendations'])) {
            $this->newLine();
            $this->info('💡 Performance Recommendations');
            foreach ($healthScore['recommendations'] as $recommendation) {
                $this->line('  • ' . $recommendation);
            }
        }
    }

    /**
     * Display performance report in JSON format
     */
    protected function displayJsonReport(array $data, array $healthScore): void
    {
        $report = [
            'timestamp' => now()->toIso8601String(),
            'timeframe' => $this->option('timeframe'),
            'health_score' => $healthScore,
            'performance_data' => $data,
        ];
        
        $this->line(json_encode($report, JSON_PRETTY_PRINT));
    }

    /**
     * Display performance report in CSV format
     */
    protected function displayCsvReport(array $data, array $healthScore): void
    {
        // CSV headers
        $this->line('timestamp,metric,value,status');
        
        $timestamp = now()->toIso8601String();
        $overview = $data['overview'] ?? [];
        
        // Output key metrics as CSV
        $metrics = [
            'health_score' => $healthScore['overall_score'],
            'total_requests' => $overview['total_requests'] ?? 0,
            'avg_response_time' => $overview['avg_response_time'] ?? 0,
            'p95_response_time' => $overview['p95_response_time'] ?? 0,
            'error_rate' => $overview['error_rate'] ?? 0,
            'sla_compliance' => $overview['sla_compliance_percentage'] ?? 0,
            'uptime' => $overview['uptime_percentage'] ?? 0,
            'active_alerts' => count($data['active_alerts'] ?? []),
        ];
        
        foreach ($metrics as $metric => $value) {
            $status = $this->getMetricStatus($metric, $value);
            $this->line("{$timestamp},{$metric},{$value},{$status}");
        }
    }

    /**
     * Export report to file
     */
    protected function exportReport(array $data, array $healthScore, string $path, string $format): void
    {
        $content = '';
        
        switch ($format) {
            case 'json':
                $report = [
                    'timestamp' => now()->toIso8601String(),
                    'timeframe' => $this->option('timeframe'),
                    'health_score' => $healthScore,
                    'performance_data' => $data,
                ];
                $content = json_encode($report, JSON_PRETTY_PRINT);
                break;
                
            case 'csv':
                $content = "timestamp,metric,value,status\n";
                $timestamp = now()->toIso8601String();
                $overview = $data['overview'] ?? [];
                
                $metrics = [
                    'health_score' => $healthScore['overall_score'],
                    'total_requests' => $overview['total_requests'] ?? 0,
                    'avg_response_time' => $overview['avg_response_time'] ?? 0,
                    'error_rate' => $overview['error_rate'] ?? 0,
                    'sla_compliance' => $overview['sla_compliance_percentage'] ?? 0,
                ];
                
                foreach ($metrics as $metric => $value) {
                    $status = $this->getMetricStatus($metric, $value);
                    $content .= "{$timestamp},{$metric},{$value},{$status}\n";
                }
                break;
                
            default:
                $content = $this->generateTextReport($data, $healthScore);
                break;
        }
        
        file_put_contents($path, $content);
        $this->info("Report exported to: {$path}");
    }

    /**
     * Send email report
     */
    protected function sendEmailReport(array $data, array $healthScore, string $email): void
    {
        try {
            // TODO: Implement email template and send
            $this->info("Email report would be sent to: {$email}");
        } catch (\Exception $e) {
            $this->error("Failed to send email report: " . $e->getMessage());
        }
    }

    /**
     * Helper methods for status icons and formatting
     */
    protected function getStatusIcon(float $value, float $threshold, bool $inverse = false): string
    {
        if ($inverse) {
            return $value <= $threshold ? '✅' : ($value <= $threshold * 2 ? '⚠️' : '❌');
        }
        return $value <= $threshold ? '✅' : ($value <= $threshold * 1.5 ? '⚠️' : '❌');
    }

    protected function getSLAStatusIcon(string $status): string
    {
        return match($status) {
            'green' => '✅',
            'yellow' => '⚠️', 
            'red' => '❌',
            default => '❓',
        };
    }

    protected function getHealthImpactIcon(float $score): string
    {
        if ($score >= 90) return '🟢';
        if ($score >= 75) return '🟡';
        if ($score >= 60) return '🟠';
        return '🔴';
    }

    protected function getMetricStatus(string $metric, float $value): string
    {
        $thresholds = [
            'health_score' => ['good' => 90, 'warning' => 75],
            'avg_response_time' => ['good' => 200, 'warning' => 400],
            'error_rate' => ['good' => 1, 'warning' => 2],
            'sla_compliance' => ['good' => 99, 'warning' => 95],
        ];
        
        if (!isset($thresholds[$metric])) {
            return 'unknown';
        }
        
        $threshold = $thresholds[$metric];
        
        if (in_array($metric, ['error_rate'])) {
            // Lower is better
            if ($value <= $threshold['good']) return 'good';
            if ($value <= $threshold['warning']) return 'warning';
            return 'critical';
        } else {
            // Higher is better
            if ($value >= $threshold['good']) return 'good';
            if ($value >= $threshold['warning']) return 'warning';
            return 'critical';
        }
    }

    protected function generateTextReport(array $data, array $healthScore): string
    {
        $report = "Business Portal Performance Report\n";
        $report .= "Generated: " . now()->toDateTimeString() . "\n";
        $report .= "Timeframe: " . $this->option('timeframe') . "\n\n";
        
        $report .= "Health Score: " . $healthScore['overall_score'] . "% (" . $healthScore['grade'] . ")\n\n";
        
        $overview = $data['overview'] ?? [];
        $report .= "Overview:\n";
        $report .= "- Total Requests: " . number_format($overview['total_requests'] ?? 0) . "\n";
        $report .= "- Avg Response Time: " . ($overview['avg_response_time'] ?? 0) . "ms\n";
        $report .= "- Error Rate: " . ($overview['error_rate'] ?? 0) . "%\n";
        $report .= "- SLA Compliance: " . ($overview['sla_compliance_percentage'] ?? 0) . "%\n";
        $report .= "- Active Alerts: " . count($data['active_alerts'] ?? []) . "\n";
        
        return $report;
    }
}