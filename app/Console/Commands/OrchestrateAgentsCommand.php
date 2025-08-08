<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AgentOrchestrationService;

class OrchestrateAgentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agents:orchestrate 
                            {task : The task to orchestrate}
                            {--context=* : Additional context as key=value pairs}
                            {--watch : Watch real-time feedback}
                            {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Orchestrate multiple agents to complete a complex task';

    protected AgentOrchestrationService $orchestrator;

    /**
     * Create a new command instance.
     */
    public function __construct(AgentOrchestrationService $orchestrator)
    {
        parent::__construct();
        $this->orchestrator = $orchestrator;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $task = $this->argument('task');
        $contextOptions = $this->option('context');
        $watch = $this->option('watch');
        $json = $this->option('json');
        
        // Parse context
        $context = [];
        foreach ($contextOptions as $option) {
            if (str_contains($option, '=')) {
                [$key, $value] = explode('=', $option, 2);
                $context[trim($key)] = trim($value);
            }
        }
        
        $this->info('🎯 Agent Orchestration System');
        $this->info('==============================');
        $this->newLine();
        
        $this->comment("Task: {$task}");
        if (!empty($context)) {
            $this->comment("Context: " . json_encode($context));
        }
        $this->newLine();
        
        // Set up real-time feedback listener if requested
        if ($watch) {
            $this->listenForFeedback();
        }
        
        try {
            $this->info('🚀 Starting orchestration...');
            $this->newLine();
            
            // Run orchestration
            $result = $this->orchestrator->orchestrate($task, $context);
            
            // Display results
            if ($json) {
                $this->line(json_encode($result, JSON_PRETTY_PRINT));
            } else {
                $this->displayResults($result);
            }
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Orchestration failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Display orchestration results
     */
    protected function displayResults(array $result): void
    {
        // Summary
        $this->info('✅ Orchestration Complete');
        $this->info('========================');
        $this->newLine();
        
        // Execution metrics
        $this->comment('📊 Execution Metrics:');
        $this->line("• Orchestration ID: {$result['orchestration_id']}");
        $this->line("• Execution Time: {$result['execution_time']}");
        $this->line("• Agents Used: {$result['success_metrics']['agents_completed']}");
        $this->line("• Phases Completed: {$result['success_metrics']['phases_completed']}");
        $this->line("• Feedback Events: {$result['success_metrics']['feedback_events']}");
        $this->newLine();
        
        // Agents used
        $this->comment('🤖 Agents Executed:');
        foreach ($result['agents_used'] as $agentName => $agentData) {
            $status = $agentData['status'] === 'completed' ? '✅' : '⏳';
            $this->line("{$status} {$agentName}");
            if (!empty($agentData['result_summary'])) {
                $this->line("   {$agentData['result_summary']}");
            }
        }
        $this->newLine();
        
        // Phase results
        if (isset($result['phases']['analysis'])) {
            $this->comment('📋 Analysis Phase:');
            $analysis = $result['phases']['analysis'];
            
            if (isset($analysis['summary'])) {
                foreach ($analysis['summary'] as $key => $value) {
                    $label = str_replace('_', ' ', ucfirst($key));
                    $displayValue = is_bool($value) ? ($value ? 'Yes' : 'No') : $value;
                    $this->line("• {$label}: {$displayValue}");
                }
            }
            $this->newLine();
        }
        
        // Execution phases
        if (isset($result['phases']['execution'])) {
            $this->comment('⚡ Execution Phases:');
            foreach ($result['phases']['execution'] as $phase => $phaseData) {
                $this->line("Phase: {$phase}");
                foreach ($phaseData as $agent => $agentResult) {
                    $this->line("  • {$agent}: {$agentResult['status']}");
                }
            }
            $this->newLine();
        }
        
        // Recommendations
        if (!empty($result['recommendations'])) {
            $this->comment('💡 Recommendations:');
            foreach ($result['recommendations'] as $rec) {
                $icon = match($rec['type']) {
                    'performance' => '⚡',
                    'quality' => '✨',
                    'ui' => '🎨',
                    'process' => '📋',
                    default => '•'
                };
                $this->line("{$icon} {$rec['recommendation']}");
                $this->line("   → {$rec['action']}");
            }
            $this->newLine();
        }
        
        // Feedback history summary
        $this->comment('📢 Feedback Timeline:');
        $feedbackTypes = array_count_values(array_column($result['feedback_history'], 'type'));
        foreach ($feedbackTypes as $type => $count) {
            $this->line("• {$type}: {$count} events");
        }
    }
    
    /**
     * Listen for real-time feedback events
     */
    protected function listenForFeedback(): void
    {
        // Register event listener for feedback
        \Event::listen('orchestration.feedback', function ($feedback) {
            $icon = match($feedback['type']) {
                'analysis_complete' => '🧠',
                'agents_selected' => '🤖',
                'agent_executed' => '⚡',
                'phase_completed' => '✅',
                'orchestration_complete' => '🎯',
                default => '📢'
            };
            
            $this->info("{$icon} [{$feedback['phase']}] {$feedback['type']}");
            
            if (!empty($feedback['data'])) {
                foreach ($feedback['data'] as $key => $value) {
                    if (is_array($value)) {
                        $this->line("   {$key}: " . json_encode($value));
                    } else {
                        $this->line("   {$key}: {$value}");
                    }
                }
            }
            $this->newLine();
        });
    }
}