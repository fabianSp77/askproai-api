<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SequentialThinkingService;

class SequentialThinkingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'think:sequential 
                            {problem : The problem or task to analyze}
                            {--context=* : Additional context as key=value pairs}
                            {--strategy=analyze : The thinking strategy to use (analyze, decompose, prioritize, risks, metrics)}
                            {--format=detailed : Output format (detailed, summary, json)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze problems using sequential thinking methodology';

    protected SequentialThinkingService $thinkingService;

    /**
     * Create a new command instance.
     */
    public function __construct(SequentialThinkingService $thinkingService)
    {
        parent::__construct();
        $this->thinkingService = $thinkingService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $problem = $this->argument('problem');
        $contextOptions = $this->option('context');
        $strategy = $this->option('strategy');
        $format = $this->option('format');
        
        // Parse context options
        $context = $this->parseContext($contextOptions);
        
        $this->info("🧠 Sequential Thinking Analysis");
        $this->info("================================");
        $this->newLine();
        
        $this->comment("Problem: {$problem}");
        if (!empty($context)) {
            $this->comment("Context: " . json_encode($context));
        }
        $this->comment("Strategy: {$strategy}");
        $this->newLine();
        
        try {
            // Execute the thinking process
            $result = $this->executeThinking($problem, $context, $strategy);
            
            // Display results based on format
            switch ($format) {
                case 'json':
                    $this->line(json_encode($result, JSON_PRETTY_PRINT));
                    break;
                    
                case 'summary':
                    $this->displaySummary($result);
                    break;
                    
                case 'detailed':
                default:
                    $this->displayDetailed($result);
                    break;
            }
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Error during analysis: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Parse context options into array
     */
    protected function parseContext(array $contextOptions): array
    {
        $context = [];
        
        foreach ($contextOptions as $option) {
            if (str_contains($option, '=')) {
                [$key, $value] = explode('=', $option, 2);
                $context[trim($key)] = trim($value);
            }
        }
        
        return $context;
    }
    
    /**
     * Execute the thinking process based on strategy
     */
    protected function executeThinking(string $problem, array $context, string $strategy): array
    {
        switch ($strategy) {
            case 'decompose':
                return [
                    'strategy' => 'decompose',
                    'result' => $this->thinkingService->executeStrategy('decompose', [
                        'problem' => $problem,
                        'context' => $context
                    ])
                ];
                
            case 'prioritize':
                // First decompose, then prioritize
                $decomposition = $this->thinkingService->executeStrategy('decompose', [
                    'problem' => $problem,
                    'context' => $context
                ]);
                
                return [
                    'strategy' => 'prioritize',
                    'result' => $this->thinkingService->executeStrategy('prioritize', [
                        'decomposition' => $decomposition,
                        'dependencies' => []
                    ])
                ];
                
            case 'risks':
                // Full analysis to get action plan, then focus on risks
                $fullAnalysis = $this->thinkingService->analyzeProblem($problem, $context);
                $actionPlan = null;
                
                foreach ($fullAnalysis['thinking_process'] as $step) {
                    if ($step['name'] === 'Action Plan') {
                        $actionPlan = $step['data'];
                        break;
                    }
                }
                
                return [
                    'strategy' => 'risks',
                    'result' => $this->thinkingService->executeStrategy('analyze_risks', [
                        'action_plan' => $actionPlan
                    ])
                ];
                
            case 'metrics':
                // Full analysis to get action plan, then define metrics
                $fullAnalysis = $this->thinkingService->analyzeProblem($problem, $context);
                $actionPlan = null;
                
                foreach ($fullAnalysis['thinking_process'] as $step) {
                    if ($step['name'] === 'Action Plan') {
                        $actionPlan = $step['data'];
                        break;
                    }
                }
                
                return [
                    'strategy' => 'metrics',
                    'result' => $this->thinkingService->executeStrategy('define_metrics', [
                        'action_plan' => $actionPlan
                    ])
                ];
                
            case 'analyze':
            default:
                return $this->thinkingService->analyzeProblem($problem, $context);
        }
    }
    
    /**
     * Display summary format
     */
    protected function displaySummary(array $result): void
    {
        if (isset($result['summary'])) {
            $this->info("📊 Analysis Summary");
            $this->info("==================");
            
            foreach ($result['summary'] as $key => $value) {
                $label = str_replace('_', ' ', ucfirst($key));
                $this->line("• {$label}: " . (is_bool($value) ? ($value ? 'Yes' : 'No') : $value));
            }
            
            $this->newLine();
        }
        
        if (isset($result['recommendations'])) {
            $this->info("💡 Top Recommendations");
            $this->info("=====================");
            
            $highPriority = array_filter($result['recommendations'], fn($r) => $r['priority'] === 'high');
            
            foreach ($highPriority as $rec) {
                $this->warn("⚠️  " . $rec['recommendation']);
                $this->line("   " . $rec['details']);
            }
        }
    }
    
    /**
     * Display detailed format
     */
    protected function displayDetailed(array $result): void
    {
        // Special handling for strategy-specific results
        if (isset($result['strategy'])) {
            $this->displayStrategyResult($result['strategy'], $result['result']);
            return;
        }
        
        // Full analysis display
        if (isset($result['thinking_process'])) {
            foreach ($result['thinking_process'] as $step) {
                $this->info("Step {$step['step']}: {$step['name']}");
                $this->info(str_repeat('-', strlen("Step {$step['step']}: {$step['name']}")));
                
                $this->displayStepData($step['data']);
                $this->newLine();
            }
        }
        
        // Display summary
        if (isset($result['summary'])) {
            $this->displaySummary($result);
        }
        
        // Display recommendations
        if (isset($result['recommendations'])) {
            $this->info("📋 All Recommendations");
            $this->info("=====================");
            
            $grouped = [];
            foreach ($result['recommendations'] as $rec) {
                $grouped[$rec['priority']][] = $rec;
            }
            
            foreach (['high', 'medium', 'low'] as $priority) {
                if (isset($grouped[$priority])) {
                    $this->comment(ucfirst($priority) . " Priority:");
                    foreach ($grouped[$priority] as $rec) {
                        $icon = match($priority) {
                            'high' => '🔴',
                            'medium' => '🟡',
                            'low' => '🟢',
                            default => '⚪'
                        };
                        $this->line("{$icon} {$rec['recommendation']}");
                        $this->line("   {$rec['details']}");
                    }
                    $this->newLine();
                }
            }
        }
    }
    
    /**
     * Display strategy-specific results
     */
    protected function displayStrategyResult(string $strategy, $result): void
    {
        switch ($strategy) {
            case 'decompose':
                $this->info("🔍 Problem Decomposition");
                $this->info("=======================");
                $this->displayStepData($result);
                break;
                
            case 'prioritize':
                $this->info("📝 Prioritized Tasks");
                $this->info("===================");
                
                foreach ($result as $item) {
                    $this->comment("Priority {$item['priority']}:");
                    $this->line("  Task: {$item['task']['description']}");
                    $this->line("  Complexity: {$item['task']['complexity']}");
                    $this->line("  Estimated Time: {$item['estimated_time']}");
                    
                    if (!empty($item['dependencies']['depends_on'])) {
                        $this->line("  Dependencies: " . implode(', ', $item['dependencies']['depends_on']));
                    }
                    $this->newLine();
                }
                break;
                
            case 'risks':
                $this->info("⚠️  Risk Analysis");
                $this->info("================");
                
                foreach ($result as $risk) {
                    $icon = match($risk['level']) {
                        'high' => '🔴',
                        'medium' => '🟡',
                        'low' => '🟢',
                        default => '⚪'
                    };
                    
                    $this->line("{$icon} {$risk['type']} Risk ({$risk['level']})");
                    $this->line("   Description: {$risk['description']}");
                    $this->line("   Mitigation: {$risk['mitigation']}");
                    $this->newLine();
                }
                break;
                
            case 'metrics':
                $this->info("📊 Success Metrics");
                $this->info("=================");
                
                foreach ($result as $metric) {
                    $this->comment($metric['name']);
                    $this->line("  Target: {$metric['target']}");
                    $this->line("  How to Measure: {$metric['measurement']}");
                    $this->newLine();
                }
                break;
        }
    }
    
    /**
     * Display step data recursively
     */
    protected function displayStepData($data, int $indent = 0): void
    {
        $prefix = str_repeat('  ', $indent);
        
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $this->comment("{$prefix}{$key}:");
                    $this->displayStepData($value, $indent + 1);
                } else {
                    $this->line("{$prefix}• {$key}: " . (is_bool($value) ? ($value ? 'Yes' : 'No') : $value));
                }
            }
        } else {
            $this->line("{$prefix}" . $data);
        }
    }
}