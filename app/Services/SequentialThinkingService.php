<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Sequential Thinking Service
 * 
 * Provides structured problem-solving and sequential analysis capabilities
 * Mimics the functionality of a sequential-thinking agent
 */
class SequentialThinkingService
{
    protected array $thinkingSteps = [];
    protected array $context = [];
    protected string $currentProblem = '';
    
    /**
     * Analyze a complex problem using sequential thinking
     */
    public function analyzeProblem(string $problem, array $context = []): array
    {
        $this->currentProblem = $problem;
        $this->context = $context;
        $this->thinkingSteps = [];
        
        // Step 1: Problem Decomposition
        $decomposition = $this->decomposeProblem($problem);
        $this->addStep('Problem Decomposition', $decomposition);
        
        // Step 2: Identify Dependencies
        $dependencies = $this->identifyDependencies($decomposition);
        $this->addStep('Dependency Analysis', $dependencies);
        
        // Step 3: Prioritize Steps
        $prioritized = $this->prioritizeSteps($decomposition, $dependencies);
        $this->addStep('Step Prioritization', $prioritized);
        
        // Step 4: Generate Action Plan
        $actionPlan = $this->generateActionPlan($prioritized);
        $this->addStep('Action Plan', $actionPlan);
        
        // Step 5: Risk Analysis
        $risks = $this->analyzeRisks($actionPlan);
        $this->addStep('Risk Analysis', $risks);
        
        // Step 6: Success Metrics
        $metrics = $this->defineSuccessMetrics($actionPlan);
        $this->addStep('Success Metrics', $metrics);
        
        return $this->formatResults();
    }
    
    /**
     * Break down a complex problem into manageable components
     */
    protected function decomposeProblem(string $problem): array
    {
        $components = [];
        
        // Identify main objective
        $components['objective'] = $this->extractObjective($problem);
        
        // Identify constraints
        $components['constraints'] = $this->extractConstraints($problem);
        
        // Identify resources
        $components['resources'] = $this->extractResources($problem);
        
        // Break into sub-problems
        $components['sub_problems'] = $this->extractSubProblems($problem);
        
        return $components;
    }
    
    /**
     * Extract the main objective from the problem statement
     */
    protected function extractObjective(string $problem): string
    {
        // Analyze problem for key action words and goals
        $keywords = ['implement', 'fix', 'create', 'optimize', 'analyze', 'solve', 'build', 'design'];
        
        foreach ($keywords as $keyword) {
            if (stripos($problem, $keyword) !== false) {
                // Extract the sentence containing the keyword
                $sentences = preg_split('/[.!?]+/', $problem);
                foreach ($sentences as $sentence) {
                    if (stripos($sentence, $keyword) !== false) {
                        return trim($sentence);
                    }
                }
            }
        }
        
        // Fallback: use first sentence as objective
        $sentences = preg_split('/[.!?]+/', $problem);
        return trim($sentences[0] ?? $problem);
    }
    
    /**
     * Extract constraints from the problem
     */
    protected function extractConstraints(string $problem): array
    {
        $constraints = [];
        
        // Time constraints
        if (preg_match('/(\d+)\s*(hours?|days?|weeks?|minutes?)/', $problem, $matches)) {
            $constraints[] = "Time constraint: {$matches[0]}";
        }
        
        // Technical constraints
        $techKeywords = ['must', 'should', 'cannot', 'avoid', 'require', 'need'];
        foreach ($techKeywords as $keyword) {
            if (stripos($problem, $keyword) !== false) {
                $pattern = "/.*\b{$keyword}\b[^.!?]*/i";
                if (preg_match($pattern, $problem, $matches)) {
                    $constraints[] = "Requirement: " . trim($matches[0]);
                }
            }
        }
        
        // Context constraints
        if (!empty($this->context)) {
            foreach ($this->context as $key => $value) {
                if (is_string($value) || is_numeric($value)) {
                    $constraints[] = "Context: {$key} = {$value}";
                }
            }
        }
        
        return array_unique($constraints);
    }
    
    /**
     * Extract available resources
     */
    protected function extractResources(string $problem): array
    {
        $resources = [];
        
        // Technical resources
        $techResources = ['Laravel', 'PHP', 'MySQL', 'Redis', 'Filament', 'Livewire', 'Alpine.js', 'Tailwind'];
        foreach ($techResources as $resource) {
            if (stripos($problem, $resource) !== false) {
                $resources[] = $resource;
            }
        }
        
        // File/code resources mentioned
        if (preg_match_all('/([A-Z]\w+(?:Resource|Controller|Service|Model|Page))/', $problem, $matches)) {
            foreach ($matches[1] as $match) {
                $resources[] = "Class: {$match}";
            }
        }
        
        // Database tables
        if (preg_match_all('/`?(\w+)`?\s+table/', $problem, $matches)) {
            foreach ($matches[1] as $table) {
                $resources[] = "Table: {$table}";
            }
        }
        
        return array_unique($resources);
    }
    
    /**
     * Break problem into sub-problems
     */
    protected function extractSubProblems(string $problem): array
    {
        $subProblems = [];
        
        // Split by common separators
        $parts = preg_split('/\band\b|\bfür\b|\bmit\b|\boder\b/i', $problem);
        
        foreach ($parts as $i => $part) {
            $part = trim($part);
            if (strlen($part) > 20) { // Significant sub-problem
                $subProblems[] = [
                    'id' => $i + 1,
                    'description' => $part,
                    'complexity' => $this->estimateComplexity($part)
                ];
            }
        }
        
        // If no sub-problems found, create them based on verbs
        if (empty($subProblems)) {
            $verbs = ['implement', 'fix', 'create', 'analyze', 'optimize', 'test', 'validate'];
            $i = 1;
            foreach ($verbs as $verb) {
                if (stripos($problem, $verb) !== false) {
                    $subProblems[] = [
                        'id' => $i++,
                        'description' => ucfirst($verb) . " the required functionality",
                        'complexity' => 'medium'
                    ];
                }
            }
        }
        
        return $subProblems;
    }
    
    /**
     * Estimate complexity of a task
     */
    protected function estimateComplexity(string $task): string
    {
        $complexKeywords = ['complex', 'difficult', 'challenge', 'optimize', 'refactor', 'architecture'];
        $simpleKeywords = ['simple', 'basic', 'add', 'remove', 'fix', 'update'];
        
        $complexCount = 0;
        $simpleCount = 0;
        
        foreach ($complexKeywords as $keyword) {
            if (stripos($task, $keyword) !== false) {
                $complexCount++;
            }
        }
        
        foreach ($simpleKeywords as $keyword) {
            if (stripos($task, $keyword) !== false) {
                $simpleCount++;
            }
        }
        
        if ($complexCount > $simpleCount) {
            return 'high';
        } elseif ($simpleCount > $complexCount) {
            return 'low';
        }
        
        return 'medium';
    }
    
    /**
     * Identify dependencies between components
     */
    protected function identifyDependencies(array $decomposition): array
    {
        $dependencies = [];
        $subProblems = $decomposition['sub_problems'] ?? [];
        
        // Simple dependency detection based on order and complexity
        for ($i = 0; $i < count($subProblems); $i++) {
            $current = $subProblems[$i];
            $deps = [];
            
            // Higher complexity items often depend on lower complexity ones
            for ($j = 0; $j < $i; $j++) {
                if ($subProblems[$j]['complexity'] === 'low') {
                    $deps[] = $subProblems[$j]['id'];
                }
            }
            
            $dependencies[$current['id']] = [
                'depends_on' => $deps,
                'blocks' => [], // Will be filled in reverse
                'parallel_possible' => empty($deps)
            ];
        }
        
        // Fill in what each task blocks
        foreach ($dependencies as $id => $dep) {
            foreach ($dep['depends_on'] as $depId) {
                if (isset($dependencies[$depId])) {
                    $dependencies[$depId]['blocks'][] = $id;
                }
            }
        }
        
        return $dependencies;
    }
    
    /**
     * Prioritize steps based on dependencies and complexity
     */
    protected function prioritizeSteps(array $decomposition, array $dependencies): array
    {
        $prioritized = [];
        $subProblems = $decomposition['sub_problems'] ?? [];
        
        // Sort by dependencies first, then by complexity
        usort($subProblems, function($a, $b) use ($dependencies) {
            $aDeps = count($dependencies[$a['id']]['depends_on'] ?? []);
            $bDeps = count($dependencies[$b['id']]['depends_on'] ?? []);
            
            if ($aDeps !== $bDeps) {
                return $aDeps - $bDeps; // Fewer dependencies = higher priority
            }
            
            // If same dependencies, prioritize by complexity (simpler first)
            $complexityOrder = ['low' => 1, 'medium' => 2, 'high' => 3];
            return ($complexityOrder[$a['complexity']] ?? 2) - ($complexityOrder[$b['complexity']] ?? 2);
        });
        
        foreach ($subProblems as $i => $problem) {
            $prioritized[] = [
                'priority' => $i + 1,
                'task' => $problem,
                'dependencies' => $dependencies[$problem['id']] ?? [],
                'estimated_time' => $this->estimateTime($problem['complexity'])
            ];
        }
        
        return $prioritized;
    }
    
    /**
     * Estimate time for a task based on complexity
     */
    protected function estimateTime(string $complexity): string
    {
        return match($complexity) {
            'low' => '15-30 minutes',
            'medium' => '1-2 hours',
            'high' => '2-4 hours',
            default => '1 hour'
        };
    }
    
    /**
     * Generate an actionable plan
     */
    protected function generateActionPlan(array $prioritized): array
    {
        $plan = [
            'phases' => [],
            'total_estimated_time' => '',
            'parallel_opportunities' => [],
            'critical_path' => []
        ];
        
        // Group tasks into phases
        $currentPhase = [];
        $phaseNum = 1;
        
        foreach ($prioritized as $item) {
            if (empty($item['dependencies']['depends_on'])) {
                // Can be done in parallel
                $currentPhase[] = $item;
            } else {
                // New phase needed
                if (!empty($currentPhase)) {
                    $plan['phases'][] = [
                        'number' => $phaseNum++,
                        'tasks' => $currentPhase,
                        'parallel' => count($currentPhase) > 1
                    ];
                    $currentPhase = [];
                }
                $currentPhase[] = $item;
            }
        }
        
        // Add remaining phase
        if (!empty($currentPhase)) {
            $plan['phases'][] = [
                'number' => $phaseNum,
                'tasks' => $currentPhase,
                'parallel' => count($currentPhase) > 1
            ];
        }
        
        // Identify parallel opportunities
        foreach ($plan['phases'] as $phase) {
            if ($phase['parallel']) {
                $plan['parallel_opportunities'][] = "Phase {$phase['number']}: " . 
                    count($phase['tasks']) . " tasks can be done in parallel";
            }
        }
        
        // Identify critical path (longest dependency chain)
        $criticalPath = [];
        foreach ($prioritized as $item) {
            if (count($item['dependencies']['blocks'] ?? []) === 0) {
                // This is an end task
                $criticalPath[] = $item['task']['description'];
            }
        }
        $plan['critical_path'] = $criticalPath;
        
        // Calculate total time
        $totalMinutes = 0;
        foreach ($prioritized as $item) {
            // Extract minimum time from estimate
            if (preg_match('/(\d+)/', $item['estimated_time'], $matches)) {
                $totalMinutes += intval($matches[1]);
            }
        }
        
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        $plan['total_estimated_time'] = "{$hours} hours {$minutes} minutes (sequential)";
        
        return $plan;
    }
    
    /**
     * Analyze potential risks
     */
    protected function analyzeRisks(array $actionPlan): array
    {
        $risks = [];
        
        // Time risks
        if (str_contains($actionPlan['total_estimated_time'], 'hours')) {
            preg_match('/(\d+)\s*hours/', $actionPlan['total_estimated_time'], $matches);
            if (isset($matches[1]) && intval($matches[1]) > 4) {
                $risks[] = [
                    'type' => 'time',
                    'level' => 'high',
                    'description' => 'Task may take longer than a single work session',
                    'mitigation' => 'Consider breaking into multiple sessions or delegating parts'
                ];
            }
        }
        
        // Dependency risks
        foreach ($actionPlan['phases'] as $phase) {
            if (count($phase['tasks']) > 3) {
                $risks[] = [
                    'type' => 'complexity',
                    'level' => 'medium',
                    'description' => "Phase {$phase['number']} has many parallel tasks",
                    'mitigation' => 'Ensure clear coordination if multiple people involved'
                ];
            }
        }
        
        // Critical path risks
        if (count($actionPlan['critical_path']) > 0) {
            $risks[] = [
                'type' => 'dependency',
                'level' => 'medium',
                'description' => 'Critical path tasks must be completed for project success',
                'mitigation' => 'Prioritize and monitor critical path tasks closely'
            ];
        }
        
        // Technical risks based on resources
        $resources = $this->context['resources'] ?? [];
        if (in_array('database', array_map('strtolower', $resources))) {
            $risks[] = [
                'type' => 'technical',
                'level' => 'medium',
                'description' => 'Database changes may affect production',
                'mitigation' => 'Test thoroughly in development, consider migrations'
            ];
        }
        
        return $risks;
    }
    
    /**
     * Define success metrics
     */
    protected function defineSuccessMetrics(array $actionPlan): array
    {
        $metrics = [];
        
        // Completion metrics
        $totalTasks = 0;
        foreach ($actionPlan['phases'] as $phase) {
            $totalTasks += count($phase['tasks']);
        }
        
        $metrics[] = [
            'name' => 'Task Completion',
            'target' => "100% ({$totalTasks}/{$totalTasks} tasks)",
            'measurement' => 'Count of completed tasks'
        ];
        
        // Time metrics
        $metrics[] = [
            'name' => 'Time to Completion',
            'target' => $actionPlan['total_estimated_time'],
            'measurement' => 'Actual time vs estimated'
        ];
        
        // Quality metrics
        $metrics[] = [
            'name' => 'Quality Assurance',
            'target' => 'All tests passing',
            'measurement' => 'Test suite results'
        ];
        
        // Performance metrics
        if (stripos($this->currentProblem, 'performance') !== false || 
            stripos($this->currentProblem, 'optimize') !== false) {
            $metrics[] = [
                'name' => 'Performance Improvement',
                'target' => '20-50% improvement',
                'measurement' => 'Benchmark before/after'
            ];
        }
        
        // User satisfaction
        if (stripos($this->currentProblem, 'user') !== false || 
            stripos($this->currentProblem, 'ux') !== false) {
            $metrics[] = [
                'name' => 'User Satisfaction',
                'target' => 'Positive feedback',
                'measurement' => 'User testing or feedback'
            ];
        }
        
        return $metrics;
    }
    
    /**
     * Add a thinking step
     */
    protected function addStep(string $name, $data): void
    {
        $this->thinkingSteps[] = [
            'step' => count($this->thinkingSteps) + 1,
            'name' => $name,
            'data' => $data,
            'timestamp' => now()->toIso8601String()
        ];
    }
    
    /**
     * Format the results
     */
    protected function formatResults(): array
    {
        return [
            'problem' => $this->currentProblem,
            'analysis_timestamp' => now()->toIso8601String(),
            'thinking_process' => $this->thinkingSteps,
            'summary' => $this->generateSummary(),
            'recommendations' => $this->generateRecommendations()
        ];
    }
    
    /**
     * Generate a summary of the analysis
     */
    protected function generateSummary(): array
    {
        $summary = [];
        
        // Extract key points from each step
        foreach ($this->thinkingSteps as $step) {
            switch ($step['name']) {
                case 'Problem Decomposition':
                    $summary['objective'] = $step['data']['objective'] ?? 'Not identified';
                    $summary['constraints_count'] = count($step['data']['constraints'] ?? []);
                    $summary['sub_problems_count'] = count($step['data']['sub_problems'] ?? []);
                    break;
                    
                case 'Action Plan':
                    $summary['phases_count'] = count($step['data']['phases'] ?? []);
                    $summary['estimated_time'] = $step['data']['total_estimated_time'] ?? 'Unknown';
                    $summary['has_parallel_tasks'] = !empty($step['data']['parallel_opportunities']);
                    break;
                    
                case 'Risk Analysis':
                    $summary['risks_identified'] = count($step['data'] ?? []);
                    $highRisks = array_filter($step['data'] ?? [], fn($r) => ($r['level'] ?? '') === 'high');
                    $summary['high_risks'] = count($highRisks);
                    break;
                    
                case 'Success Metrics':
                    $summary['metrics_defined'] = count($step['data'] ?? []);
                    break;
            }
        }
        
        return $summary;
    }
    
    /**
     * Generate recommendations based on analysis
     */
    protected function generateRecommendations(): array
    {
        $recommendations = [];
        
        // Based on action plan
        $actionPlan = null;
        foreach ($this->thinkingSteps as $step) {
            if ($step['name'] === 'Action Plan') {
                $actionPlan = $step['data'];
                break;
            }
        }
        
        if ($actionPlan) {
            // Parallel execution recommendation
            if (!empty($actionPlan['parallel_opportunities'])) {
                $recommendations[] = [
                    'priority' => 'high',
                    'type' => 'efficiency',
                    'recommendation' => 'Leverage parallel execution opportunities',
                    'details' => implode('; ', $actionPlan['parallel_opportunities'])
                ];
            }
            
            // Time management
            if (str_contains($actionPlan['total_estimated_time'], 'hours')) {
                preg_match('/(\d+)\s*hours/', $actionPlan['total_estimated_time'], $matches);
                if (isset($matches[1]) && intval($matches[1]) > 2) {
                    $recommendations[] = [
                        'priority' => 'medium',
                        'type' => 'planning',
                        'recommendation' => 'Plan for multiple work sessions',
                        'details' => 'Task requires ' . $actionPlan['total_estimated_time'] . ' - consider splitting across sessions'
                    ];
                }
            }
        }
        
        // Based on risks
        $risks = null;
        foreach ($this->thinkingSteps as $step) {
            if ($step['name'] === 'Risk Analysis') {
                $risks = $step['data'];
                break;
            }
        }
        
        if ($risks) {
            $highRisks = array_filter($risks, fn($r) => ($r['level'] ?? '') === 'high');
            if (!empty($highRisks)) {
                foreach ($highRisks as $risk) {
                    $recommendations[] = [
                        'priority' => 'high',
                        'type' => 'risk_mitigation',
                        'recommendation' => 'Address high-risk item',
                        'details' => $risk['mitigation'] ?? $risk['description']
                    ];
                }
            }
        }
        
        // General recommendations
        $recommendations[] = [
            'priority' => 'medium',
            'type' => 'quality',
            'recommendation' => 'Implement iterative testing',
            'details' => 'Test after each phase completion to catch issues early'
        ];
        
        $recommendations[] = [
            'priority' => 'low',
            'type' => 'documentation',
            'recommendation' => 'Document decisions and changes',
            'details' => 'Keep track of implementation choices for future reference'
        ];
        
        return $recommendations;
    }
    
    /**
     * Execute a specific thinking strategy
     */
    public function executeStrategy(string $strategy, array $inputs): array
    {
        return match($strategy) {
            'decompose' => $this->decomposeProblem($inputs['problem'] ?? ''),
            'prioritize' => $this->prioritizeSteps(
                $inputs['decomposition'] ?? [],
                $inputs['dependencies'] ?? []
            ),
            'analyze_risks' => $this->analyzeRisks($inputs['action_plan'] ?? []),
            'define_metrics' => $this->defineSuccessMetrics($inputs['action_plan'] ?? []),
            default => $this->analyzeProblem($inputs['problem'] ?? '', $inputs['context'] ?? [])
        };
    }
}