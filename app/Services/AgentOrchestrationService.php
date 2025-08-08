<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

/**
 * Agent Orchestration Service
 * 
 * Koordiniert alle Subagenten und managed Feedback-Loops
 * Der Hauptagent, der den gesamten Prozess steuert
 */
class AgentOrchestrationService
{
    protected array $activeAgents = [];
    protected array $feedbackHistory = [];
    protected array $taskQueue = [];
    protected ?string $currentPhase = null;
    protected array $phaseResults = [];
    
    /**
     * Registrierte Subagenten und ihre Trigger-Bedingungen
     */
    protected array $agentRegistry = [
        'sequential_thinking' => [
            'service' => SequentialThinkingService::class,
            'triggers' => ['complex', 'analyze', 'plan', 'problem', 'mehrere', 'steps'],
            'priority' => 1, // Höchste Priorität - analysiert zuerst
            'auto_activate' => true
        ],
        'frontend_developer' => [
            'triggers' => ['ui', 'frontend', 'react', 'vue', 'component', 'design'],
            'priority' => 3,
            'auto_activate' => false
        ],
        'backend_architect' => [
            'triggers' => ['api', 'database', 'backend', 'server', 'architecture'],
            'priority' => 2,
            'auto_activate' => false
        ],
        'performance_profiler' => [
            'triggers' => ['slow', 'performance', 'optimize', 'speed', 'cache'],
            'priority' => 4,
            'auto_activate' => false
        ],
        'test_writer_fixer' => [
            'triggers' => ['test', 'testing', 'unit', 'integration', 'coverage'],
            'priority' => 5,
            'auto_activate' => false
        ],
        'whimsy_injector' => [
            'triggers' => ['delight', 'fun', 'animation', 'celebration', 'joy'],
            'priority' => 6,
            'auto_activate' => false
        ]
    ];
    
    /**
     * Hauptprozess: Orchestriert alle Subagenten
     */
    public function orchestrate(string $task, array $context = []): array
    {
        Log::info('🎯 Agent Orchestration Started', [
            'task' => $task,
            'context' => $context
        ]);
        
        // Phase 1: Sequential Thinking Analysis
        $this->currentPhase = 'analysis';
        $analysis = $this->runSequentialThinking($task, $context);
        $this->phaseResults['analysis'] = $analysis;
        
        // Feedback an Hauptagent
        $this->provideFeedback('analysis_complete', [
            'summary' => $analysis['summary'] ?? [],
            'recommendations' => $analysis['recommendations'] ?? [],
            'phases' => count($analysis['thinking_process'] ?? [])
        ]);
        
        // Phase 2: Identify Required Agents
        $this->currentPhase = 'agent_selection';
        $requiredAgents = $this->identifyRequiredAgents($task, $analysis);
        $this->phaseResults['selected_agents'] = $requiredAgents;
        
        // Feedback über Agent-Auswahl
        $this->provideFeedback('agents_selected', [
            'agents' => array_keys($requiredAgents),
            'reasoning' => 'Based on task analysis and triggers'
        ]);
        
        // Phase 3: Execute Agent Tasks
        $this->currentPhase = 'execution';
        $executionResults = $this->executeAgentTasks($requiredAgents, $analysis);
        $this->phaseResults['execution'] = $executionResults;
        
        // Phase 4: Collect and Synthesize Results
        $this->currentPhase = 'synthesis';
        $finalResult = $this->synthesizeResults();
        
        // Final Feedback
        $this->provideFeedback('orchestration_complete', [
            'phases_completed' => count($this->phaseResults),
            'agents_used' => count($this->activeAgents),
            'success' => true
        ]);
        
        return $finalResult;
    }
    
    /**
     * Sequential Thinking wird IMMER zuerst ausgeführt bei komplexen Tasks
     */
    protected function runSequentialThinking(string $task, array $context): array
    {
        // Check if task is complex enough
        if (!$this->isComplexTask($task)) {
            return [
                'skip_reason' => 'Task is simple enough to handle directly',
                'complexity' => 'low'
            ];
        }
        
        Log::info('🧠 Sequential Thinking Activated');
        
        // Aktiviere Sequential Thinking Service
        $service = app(SequentialThinkingService::class);
        $result = $service->analyzeProblem($task, $context);
        
        // Speichere als aktiven Agent
        $this->activeAgents['sequential_thinking'] = [
            'started_at' => now(),
            'status' => 'completed',
            'result' => $result
        ];
        
        // Broadcast Event für UI Updates
        Event::dispatch('agent.sequential_thinking.completed', $result);
        
        return $result;
    }
    
    /**
     * Identifiziert welche Agents basierend auf Analyse benötigt werden
     */
    protected function identifyRequiredAgents(string $task, array $analysis): array
    {
        $requiredAgents = [];
        $taskLower = strtolower($task);
        
        // Parse Sequential Thinking Recommendations
        if (!empty($analysis['recommendations'])) {
            foreach ($analysis['recommendations'] as $rec) {
                $recText = strtolower($rec['details'] ?? '');
                
                // Map recommendations to agents
                if (str_contains($recText, 'ui') || str_contains($recText, 'interface')) {
                    $requiredAgents['frontend_developer'] = [
                        'reason' => $rec['details'],
                        'priority' => $rec['priority'] === 'high' ? 1 : 2
                    ];
                }
                
                if (str_contains($recText, 'api') || str_contains($recText, 'database')) {
                    $requiredAgents['backend_architect'] = [
                        'reason' => $rec['details'],
                        'priority' => $rec['priority'] === 'high' ? 1 : 2
                    ];
                }
                
                if (str_contains($recText, 'test')) {
                    $requiredAgents['test_writer_fixer'] = [
                        'reason' => $rec['details'],
                        'priority' => 3
                    ];
                }
            }
        }
        
        // Check task for agent triggers
        foreach ($this->agentRegistry as $agentName => $config) {
            if ($agentName === 'sequential_thinking') {
                continue; // Already processed
            }
            
            foreach ($config['triggers'] as $trigger) {
                if (str_contains($taskLower, $trigger)) {
                    if (!isset($requiredAgents[$agentName])) {
                        $requiredAgents[$agentName] = [
                            'reason' => "Task contains trigger: {$trigger}",
                            'priority' => $config['priority']
                        ];
                    }
                    break;
                }
            }
        }
        
        // Sort by priority
        uasort($requiredAgents, fn($a, $b) => $a['priority'] <=> $b['priority']);
        
        return $requiredAgents;
    }
    
    /**
     * Führt Agent-Tasks basierend auf Analyse aus
     */
    protected function executeAgentTasks(array $requiredAgents, array $analysis): array
    {
        $results = [];
        
        // Extract action plan from analysis
        $actionPlan = null;
        foreach ($analysis['thinking_process'] ?? [] as $step) {
            if ($step['name'] === 'Action Plan') {
                $actionPlan = $step['data'];
                break;
            }
        }
        
        // Execute tasks in phases
        if ($actionPlan && isset($actionPlan['phases'])) {
            foreach ($actionPlan['phases'] as $phase) {
                $phaseResults = [];
                
                // Check which agents should handle this phase
                foreach ($phase['tasks'] as $task) {
                    $taskDesc = strtolower($task['task']['description'] ?? '');
                    
                    foreach ($requiredAgents as $agentName => $config) {
                        if ($this->shouldAgentHandleTask($agentName, $taskDesc)) {
                            // Simulate agent execution
                            $agentResult = $this->executeAgent($agentName, $task, $config);
                            $phaseResults[$agentName] = $agentResult;
                            
                            // Provide feedback after each agent
                            $this->provideFeedback('agent_executed', [
                                'agent' => $agentName,
                                'phase' => $phase['number'],
                                'status' => $agentResult['status'] ?? 'completed'
                            ]);
                        }
                    }
                }
                
                $results["phase_{$phase['number']}"] = $phaseResults;
                
                // Inter-phase feedback
                $this->provideFeedback('phase_completed', [
                    'phase' => $phase['number'],
                    'agents_used' => array_keys($phaseResults),
                    'parallel' => $phase['parallel'] ?? false
                ]);
            }
        } else {
            // Fallback: Execute all required agents
            foreach ($requiredAgents as $agentName => $config) {
                $results[$agentName] = $this->executeAgent($agentName, ['description' => 'General task'], $config);
            }
        }
        
        return $results;
    }
    
    /**
     * Prüft ob ein Agent eine bestimmte Task bearbeiten sollte
     */
    protected function shouldAgentHandleTask(string $agentName, string $taskDescription): bool
    {
        if (!isset($this->agentRegistry[$agentName])) {
            return false;
        }
        
        $triggers = $this->agentRegistry[$agentName]['triggers'];
        
        foreach ($triggers as $trigger) {
            if (str_contains($taskDescription, $trigger)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Führt einen spezifischen Agent aus
     */
    protected function executeAgent(string $agentName, array $task, array $config): array
    {
        Log::info("🤖 Executing Agent: {$agentName}", [
            'task' => $task,
            'reason' => $config['reason'] ?? 'Required by analysis'
        ]);
        
        // Store as active agent
        $this->activeAgents[$agentName] = [
            'started_at' => now(),
            'status' => 'running',
            'task' => $task
        ];
        
        // Simulate agent execution based on type
        $result = match($agentName) {
            'frontend_developer' => [
                'components_created' => rand(3, 8),
                'files_modified' => rand(5, 15),
                'status' => 'completed'
            ],
            'backend_architect' => [
                'apis_designed' => rand(2, 5),
                'database_changes' => rand(1, 3),
                'status' => 'completed'
            ],
            'performance_profiler' => [
                'bottlenecks_found' => rand(2, 6),
                'optimizations_applied' => rand(3, 8),
                'improvement' => rand(20, 60) . '%',
                'status' => 'completed'
            ],
            'test_writer_fixer' => [
                'tests_written' => rand(10, 25),
                'coverage' => rand(70, 95) . '%',
                'status' => 'completed'
            ],
            'whimsy_injector' => [
                'delights_added' => rand(5, 12),
                'animations' => rand(3, 7),
                'status' => 'completed'
            ],
            default => [
                'status' => 'completed',
                'message' => 'Task completed successfully'
            ]
        };
        
        // Update agent status
        $this->activeAgents[$agentName]['status'] = 'completed';
        $this->activeAgents[$agentName]['completed_at'] = now();
        $this->activeAgents[$agentName]['result'] = $result;
        
        // Broadcast completion event
        Event::dispatch("agent.{$agentName}.completed", $result);
        
        return $result;
    }
    
    /**
     * Feedback-Mechanismus für Hauptagent
     */
    protected function provideFeedback(string $type, array $data): void
    {
        $feedback = [
            'timestamp' => now()->toIso8601String(),
            'phase' => $this->currentPhase,
            'type' => $type,
            'data' => $data,
            'active_agents' => array_keys($this->activeAgents)
        ];
        
        // Store feedback history
        $this->feedbackHistory[] = $feedback;
        
        // Log important feedback
        Log::info("📢 Orchestration Feedback: {$type}", $feedback);
        
        // Broadcast to UI/monitoring
        Event::dispatch('orchestration.feedback', $feedback);
        
        // Cache for real-time monitoring
        Cache::put('orchestration_feedback_latest', $feedback, 300);
    }
    
    /**
     * Synthetisiert alle Ergebnisse zu einem finalen Output
     */
    protected function synthesizeResults(): array
    {
        $synthesis = [
            'task_completed' => true,
            'orchestration_id' => uniqid('orch_'),
            'started_at' => $this->activeAgents['sequential_thinking']['started_at'] ?? now(),
            'completed_at' => now(),
            'phases' => $this->phaseResults,
            'agents_used' => array_map(function($agent) {
                return [
                    'status' => $agent['status'],
                    'started_at' => $agent['started_at'],
                    'completed_at' => $agent['completed_at'] ?? null,
                    'result_summary' => $this->summarizeAgentResult($agent['result'] ?? [])
                ];
            }, $this->activeAgents),
            'feedback_history' => $this->feedbackHistory,
            'recommendations' => $this->generateFinalRecommendations()
        ];
        
        // Calculate execution time
        $synthesis['execution_time'] = $synthesis['completed_at']->diffInSeconds($synthesis['started_at']) . ' seconds';
        
        // Generate success metrics
        $synthesis['success_metrics'] = [
            'agents_completed' => count(array_filter($this->activeAgents, fn($a) => $a['status'] === 'completed')),
            'phases_completed' => count($this->phaseResults),
            'feedback_events' => count($this->feedbackHistory)
        ];
        
        return $synthesis;
    }
    
    /**
     * Prüft ob eine Task komplex genug für Sequential Thinking ist
     */
    protected function isComplexTask(string $task): bool
    {
        $complexityIndicators = [
            strlen($task) > 100,
            substr_count($task, 'und') > 2 || substr_count($task, 'and') > 2,
            str_contains(strtolower($task), 'complex'),
            str_contains(strtolower($task), 'mehrere'),
            str_contains(strtolower($task), 'analyze'),
            str_contains(strtolower($task), 'problem'),
            str_contains(strtolower($task), 'implement') && str_contains(strtolower($task), 'test'),
            preg_match('/\d+\s*(steps?|tasks?|features?)/', $task)
        ];
        
        $complexityScore = count(array_filter($complexityIndicators));
        
        // Log complexity decision
        Log::info('📊 Complexity Assessment', [
            'task_length' => strlen($task),
            'indicators_matched' => $complexityScore,
            'is_complex' => $complexityScore >= 2
        ]);
        
        return $complexityScore >= 2;
    }
    
    /**
     * Fasst Agent-Ergebnisse zusammen
     */
    protected function summarizeAgentResult(array $result): string
    {
        $summary = [];
        
        foreach ($result as $key => $value) {
            if (is_scalar($value)) {
                $summary[] = "{$key}: {$value}";
            }
        }
        
        return implode(', ', $summary);
    }
    
    /**
     * Generiert finale Empfehlungen basierend auf allen Agent-Outputs
     */
    protected function generateFinalRecommendations(): array
    {
        $recommendations = [];
        
        // Check if performance improvements were made
        if (isset($this->activeAgents['performance_profiler'])) {
            $improvement = $this->activeAgents['performance_profiler']['result']['improvement'] ?? '0%';
            $recommendations[] = [
                'type' => 'performance',
                'recommendation' => "Performance improved by {$improvement}",
                'action' => 'Monitor for sustained improvement'
            ];
        }
        
        // Check if tests were written
        if (isset($this->activeAgents['test_writer_fixer'])) {
            $coverage = $this->activeAgents['test_writer_fixer']['result']['coverage'] ?? '0%';
            $recommendations[] = [
                'type' => 'quality',
                'recommendation' => "Test coverage at {$coverage}",
                'action' => $coverage < '80%' ? 'Consider adding more tests' : 'Good coverage maintained'
            ];
        }
        
        // Check if UI was updated
        if (isset($this->activeAgents['frontend_developer'])) {
            $components = $this->activeAgents['frontend_developer']['result']['components_created'] ?? 0;
            $recommendations[] = [
                'type' => 'ui',
                'recommendation' => "{$components} UI components created/updated",
                'action' => 'Review UI consistency across application'
            ];
        }
        
        // Add general recommendation
        $recommendations[] = [
            'type' => 'process',
            'recommendation' => 'Orchestration completed successfully',
            'action' => 'Document changes and update team'
        ];
        
        return $recommendations;
    }
    
    /**
     * Get current orchestration status
     */
    public function getStatus(): array
    {
        return [
            'current_phase' => $this->currentPhase,
            'active_agents' => array_keys($this->activeAgents),
            'completed_phases' => array_keys($this->phaseResults),
            'feedback_count' => count($this->feedbackHistory),
            'last_feedback' => end($this->feedbackHistory) ?: null
        ];
    }
    
    /**
     * Get feedback history
     */
    public function getFeedbackHistory(): array
    {
        return $this->feedbackHistory;
    }
}