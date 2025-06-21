<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ContinuousImprovement\ImprovementEngine;

class ImprovementAnalyzeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'improvement:analyze
                            {--module= : Specific module to analyze (performance, bottlenecks, patterns, all)}
                            {--apply= : Apply specific optimization by ID}
                            {--monitor : Start continuous monitoring}
                            {--report : Generate detailed report}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze system performance and suggest improvements';

    protected ImprovementEngine $improvementEngine;

    /**
     * Create a new command instance.
     */
    public function __construct(ImprovementEngine $improvementEngine)
    {
        parent::__construct();
        $this->improvementEngine = $improvementEngine;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔬 Starting System Analysis...');
        
        try {
            // Apply optimization if specified
            if ($optimizationId = $this->option('apply')) {
                return $this->applyOptimization($optimizationId);
            }
            
            // Start monitoring if requested
            if ($this->option('monitor')) {
                return $this->startMonitoring();
            }
            
            // Run analysis
            $analysis = $this->improvementEngine->analyze();
            
            // Display results
            $this->displayAnalysisResults($analysis);
            
            // Generate report if requested
            if ($this->option('report')) {
                $this->generateReport($analysis);
            }
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('❌ Analysis failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Display analysis results
     */
    protected function displayAnalysisResults(array $analysis): void
    {
        // Performance overview
        if (isset($analysis['performance'])) {
            $this->displayPerformanceOverview($analysis['performance']);
        }
        
        // Bottlenecks
        if (!empty($analysis['bottlenecks'])) {
            $this->displayBottlenecks($analysis['bottlenecks']);
        }
        
        // Patterns
        if (!empty($analysis['patterns'])) {
            $this->displayPatterns($analysis['patterns']);
        }
        
        // Optimizations
        if (!empty($analysis['optimizations'])) {
            $this->displayOptimizations($analysis['optimizations']);
        }
        
        // Predictions
        if (!empty($analysis['predictions'])) {
            $this->displayPredictions($analysis['predictions']);
        }
        
        // Recommendations
        if (!empty($analysis['recommendations'])) {
            $this->displayRecommendations($analysis['recommendations']);
        }
    }
    
    /**
     * Display performance overview
     */
    protected function displayPerformanceOverview(array $performance): void
    {
        $this->info('📊 Performance Overview:');
        
        // Response times
        if (isset($performance['response_times'])) {
            $avg = $performance['response_times']['average'] ?? 0;
            $status = $avg < 1000 ? '✅' : ($avg < 3000 ? '⚠️' : '❌');
            $this->line("  {$status} Average Response Time: {$avg}ms");
        }
        
        // Error rates
        if (isset($performance['error_rates'])) {
            $rate = $performance['error_rates']['rate'] ?? 0;
            $status = $rate < 0.01 ? '✅' : ($rate < 0.05 ? '⚠️' : '❌');
            $percentage = round($rate * 100, 2);
            $this->line("  {$status} Error Rate: {$percentage}%");
        }
        
        // Throughput
        if (isset($performance['throughput'])) {
            $rps = $performance['throughput']['rps'] ?? 0;
            $this->line("  📈 Throughput: {$rps} requests/second");
        }
        
        $this->newLine();
    }
    
    /**
     * Display bottlenecks
     */
    protected function displayBottlenecks(array $bottlenecks): void
    {
        $this->info('🚧 Bottlenecks Detected:');
        $this->newLine();
        
        foreach ($bottlenecks as $type => $items) {
            if (empty($items)) continue;
            
            $this->line("  " . ucfirst($type) . " Bottlenecks:");
            
            foreach ($items as $key => $bottleneck) {
                $severity = $bottleneck['severity'] ?? 'unknown';
                $icon = match($severity) {
                    'high' => '🔴',
                    'medium' => '🟡',
                    'low' => '🟢',
                    default => '⚪'
                };
                
                $this->line("    {$icon} {$key}");
                $this->line("       Impact: {$bottleneck['impact']}");
                $this->line("       Solution: {$bottleneck['solution']}");
            }
            
            $this->newLine();
        }
    }
    
    /**
     * Display patterns
     */
    protected function displayPatterns(array $patterns): void
    {
        $this->info('📈 Patterns Detected:');
        
        $patternCount = 0;
        foreach ($patterns as $category => $items) {
            if (!empty($items)) {
                $patternCount += count($items);
            }
        }
        
        if ($patternCount === 0) {
            $this->line('  No significant patterns detected.');
        } else {
            $this->line("  Found {$patternCount} patterns across {count($patterns)} categories");
            
            // Show most significant patterns
            if (!empty($patterns['temporal'])) {
                $this->line("  - Peak usage times identified");
            }
            if (!empty($patterns['errors'])) {
                $this->line("  - Error patterns detected");
            }
            if (!empty($patterns['performance'])) {
                $this->line("  - Performance degradation patterns found");
            }
        }
        
        $this->newLine();
    }
    
    /**
     * Display optimizations
     */
    protected function displayOptimizations(array $optimizations): void
    {
        $this->info('💡 Suggested Optimizations:');
        $this->newLine();
        
        $count = 0;
        foreach ($optimizations as $type => $items) {
            if (empty($items)) continue;
            
            $this->line("  " . ucfirst(str_replace('_', ' ', $type)) . ":");
            
            foreach (array_slice($items, 0, 3) as $optimization) {
                $count++;
                $priority = $optimization['priority'] ?? 'medium';
                $priorityIcon = match($priority) {
                    'high' => '🔴',
                    'medium' => '🟡',
                    'low' => '🟢',
                    default => '⚪'
                };
                
                $this->line("    {$priorityIcon} [{$optimization['id']}] {$optimization['type']}");
                
                if (isset($optimization['suggestion'])) {
                    $this->line("       {$optimization['suggestion']}");
                }
                
                if (isset($optimization['estimated_improvement'])) {
                    $this->line("       Estimated improvement: {$optimization['estimated_improvement']}");
                }
            }
        }
        
        if ($count > 3) {
            $this->newLine();
            $this->line("  ... and " . ($count - 3) . " more optimizations available");
        }
        
        $this->newLine();
        $this->info("To apply an optimization, run: php artisan improvement:analyze --apply=<optimization_id>");
        $this->newLine();
    }
    
    /**
     * Display predictions
     */
    protected function displayPredictions(array $predictions): void
    {
        if (empty($predictions)) {
            return;
        }
        
        $this->info('🔮 Predictions:');
        $this->newLine();
        
        foreach ($predictions as $prediction) {
            $risk = $prediction['risk'] ?? 0;
            $riskLevel = $risk > 0.8 ? 'High' : ($risk > 0.5 ? 'Medium' : 'Low');
            $icon = $risk > 0.8 ? '🔴' : ($risk > 0.5 ? '🟡' : '🟢');
            
            $this->line("  {$icon} " . ($prediction['type'] ?? 'Unknown'));
            $this->line("     Risk Level: {$riskLevel} (" . round($risk * 100) . "%)");
            
            if (isset($prediction['description'])) {
                $this->line("     " . $prediction['description']);
            }
            
            if (isset($prediction['timeframe'])) {
                $this->line("     Timeframe: " . $prediction['timeframe']);
            }
            
            $this->newLine();
        }
    }
    
    /**
     * Display recommendations
     */
    protected function displayRecommendations(array $recommendations): void
    {
        $this->info('🎯 Top Recommendations:');
        $this->newLine();
        
        foreach (array_slice($recommendations, 0, 5) as $index => $rec) {
            $number = $index + 1;
            $priorityIcon = match($rec['priority'] ?? 'medium') {
                'high' => '🔴',
                'medium' => '🟡',
                'low' => '🟢',
                default => '⚪'
            };
            
            $this->line("{$number}. {$priorityIcon} {$rec['title']}");
            $this->line("   {$rec['description']}");
            
            if (!empty($rec['actions'])) {
                $this->line("   Actions:");
                foreach (array_slice($rec['actions'], 0, 3) as $action) {
                    $this->line("   - {$action}");
                }
            }
            
            if (isset($rec['estimated_impact'])) {
                $this->line("   Impact: {$rec['estimated_impact']}");
            }
            
            if (isset($rec['effort'])) {
                $this->line("   Effort: {$rec['effort']}");
            }
            
            $this->newLine();
        }
    }
    
    /**
     * Apply a specific optimization
     */
    protected function applyOptimization(string $optimizationId): int
    {
        $this->info("Applying optimization: {$optimizationId}");
        
        if (!$this->confirm('Are you sure you want to apply this optimization?')) {
            $this->info('Optimization cancelled.');
            return Command::SUCCESS;
        }
        
        try {
            $result = $this->improvementEngine->applyOptimization($optimizationId);
            
            if ($result['status'] === 'success') {
                $this->info('✅ Optimization applied successfully!');
                
                if (isset($result['impact'])) {
                    $this->newLine();
                    $this->info('Impact:');
                    foreach ($result['impact'] as $metric => $change) {
                        $this->line("  {$metric}: {$change}%");
                    }
                }
            } else {
                $this->error('❌ Optimization failed: ' . ($result['error'] ?? 'Unknown error'));
            }
            
            return $result['status'] === 'success' ? Command::SUCCESS : Command::FAILURE;
            
        } catch (\Exception $e) {
            $this->error('Failed to apply optimization: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Start continuous monitoring
     */
    protected function startMonitoring(): int
    {
        $this->info('📡 Starting continuous monitoring...');
        $this->line('Press Ctrl+C to stop monitoring');
        $this->newLine();
        
        while (true) {
            try {
                // Track metrics
                $metrics = $this->improvementEngine->trackMetrics();
                
                // Display key metrics
                $this->displayMonitoringMetrics($metrics);
                
                // Wait for next interval
                $interval = config('improvement-engine.monitoring.interval', 300);
                sleep($interval);
                
            } catch (\Exception $e) {
                $this->error('Monitoring error: ' . $e->getMessage());
                sleep(60); // Wait before retrying
            }
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Display monitoring metrics
     */
    protected function displayMonitoringMetrics(array $metrics): void
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        $this->line("[{$timestamp}] System Metrics:");
        
        // Performance metrics
        if (isset($metrics['performance'])) {
            $perf = $metrics['performance'];
            $this->line("  Response Time: " . ($perf['response_times']['api'] ?? 'N/A') . "ms");
            $this->line("  Throughput: " . ($perf['throughput']['requests_per_second'] ?? 'N/A') . " req/s");
        }
        
        // Resource metrics
        if (isset($metrics['resources'])) {
            $res = $metrics['resources'];
            $this->line("  CPU: " . round($res['cpu']['usage'] ?? 0) . "%");
            $this->line("  Memory: " . round($res['memory']['usage'] ?? 0) . "%");
        }
        
        // Business metrics
        if (isset($metrics['business'])) {
            $biz = $metrics['business'];
            $this->line("  Appointments Created: " . ($biz['appointments']['created'] ?? 0));
            $this->line("  Active Calls: " . ($biz['calls']['total'] ?? 0));
        }
        
        $this->newLine();
    }
    
    /**
     * Generate detailed report
     */
    protected function generateReport(array $analysis): void
    {
        $this->info('📄 Generating detailed report...');
        
        $filename = 'improvement_report_' . now()->format('Y_m_d_His') . '.json';
        $path = storage_path('app/improvement-engine/reports/' . $filename);
        
        // Ensure directory exists
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Write report
        file_put_contents($path, json_encode($analysis, JSON_PRETTY_PRINT));
        
        $this->info("Report saved to: {$path}");
    }
}