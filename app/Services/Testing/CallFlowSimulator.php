<?php

namespace App\Services\Testing;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * Call Flow Simulator
 *
 * Simulates Retell AI call flows WITHOUT making external API calls.
 * Loads agent and flow configurations, executes node transitions,
 * and validates function execution.
 *
 * Purpose: Test call flows internally before deploying to production.
 */
class CallFlowSimulator
{
    private ?array $agentConfig = null;
    private ?array $flowConfig = null;
    private array $callState = [];
    private array $executionLog = [];
    private MockFunctionExecutor $functionExecutor;
    private FlowValidationEngine $validator;

    public function __construct(
        MockFunctionExecutor $functionExecutor,
        FlowValidationEngine $validator
    ) {
        $this->functionExecutor = $functionExecutor;
        $this->validator = $validator;
    }

    /**
     * Simulate a complete call from start to finish.
     *
     * @param array $scenario Call scenario definition
     * @return CallSimulationResult
     */
    public function simulateCall(array $scenario): CallSimulationResult
    {
        $this->reset();

        Log::info('🎬 Starting call simulation', [
            'scenario' => $scenario['name'] ?? 'unnamed',
        ]);

        // Load configurations
        if (isset($scenario['agent_version'])) {
            $this->loadAgentConfig($scenario['agent_version']);
        }

        if (isset($scenario['flow_file'])) {
            $this->loadFlowFromFile($scenario['flow_file']);
        } elseif (isset($scenario['flow_id'])) {
            $this->loadFlowConfig($scenario['flow_id']);
        }

        // Validate flow before simulation
        $validationResult = $this->validator->validateFlow($this->flowConfig);
        if (!$validationResult->isValid) {
            return new CallSimulationResult(
                success: false,
                error: 'Flow validation failed',
                validationErrors: $validationResult->getErrors(),
                executionLog: $this->executionLog
            );
        }

        // Initialize call state
        $this->callState = [
            'current_node' => 'begin',
            'variables' => $scenario['variables'] ?? [],
            'customer' => $scenario['customer'] ?? null,
            'timestamp' => now(),
            'functions_called' => [],
            'transitions' => [],
        ];

        // Execute simulation
        try {
            $this->executeFlow($scenario['user_inputs'] ?? []);

            return new CallSimulationResult(
                success: true,
                callState: $this->callState,
                executionLog: $this->executionLog,
                functionsCalled: $this->callState['functions_called'],
                transitionPath: $this->callState['transitions']
            );
        } catch (\Exception $e) {
            return new CallSimulationResult(
                success: false,
                error: $e->getMessage(),
                callState: $this->callState,
                executionLog: $this->executionLog
            );
        }
    }

    /**
     * Load agent configuration from Retell API or cache.
     *
     * @param int $version Agent version number
     * @return array Agent configuration
     */
    public function loadAgentConfig(int $version): array
    {
        $this->log('info', "Loading agent config for V{$version}");

        // Try to load from cache/snapshot first
        $snapshotPath = storage_path("testing/agent_snapshots/agent_v{$version}.json");
        if (file_exists($snapshotPath)) {
            $this->agentConfig = json_decode(file_get_contents($snapshotPath), true);
            $this->log('success', "Loaded agent V{$version} from snapshot");
            return $this->agentConfig;
        }

        // Load from Retell API (requires RETELL_API_KEY in env)
        if (!config('services.retell.api_key')) {
            throw new \Exception('RETELL_API_KEY not configured');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.retell.api_key'),
        ])->get("https://api.retellai.com/get-agent/" . config('services.retell.agent_id'));

        if (!$response->successful()) {
            throw new \Exception("Failed to load agent config: " . $response->body());
        }

        $this->agentConfig = $response->json();
        $this->log('success', "Loaded agent V{$version} from API");

        return $this->agentConfig;
    }

    /**
     * Load flow configuration from Retell API.
     *
     * @param string $flowId Flow ID
     * @return array Flow configuration
     */
    public function loadFlowConfig(string $flowId): array
    {
        $this->log('info', "Loading flow config: {$flowId}");

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.retell.api_key'),
        ])->get("https://api.retellai.com/get-conversation-flow/{$flowId}");

        if (!$response->successful()) {
            throw new \Exception("Failed to load flow config: " . $response->body());
        }

        $this->flowConfig = $response->json();
        $this->log('success', "Loaded flow: {$flowId}");

        return $this->flowConfig;
    }

    /**
     * Load flow from local JSON file.
     *
     * @param string $filePath Path to flow JSON file
     * @return array Flow configuration
     */
    public function loadFlowFromFile(string $filePath): array
    {
        $this->log('info', "Loading flow from file: {$filePath}");

        if (!file_exists($filePath)) {
            throw new \Exception("Flow file not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        $this->flowConfig = json_decode($content, true);

        if (!$this->flowConfig) {
            throw new \Exception("Invalid JSON in flow file: {$filePath}");
        }

        $this->log('success', "Loaded flow from file");

        return $this->flowConfig;
    }

    /**
     * Execute the flow with given user inputs.
     *
     * @param array $userInputs Array of user inputs/intents
     * @return void
     */
    private function executeFlow(array $userInputs): void
    {
        $currentNode = $this->findNode('begin');
        $inputIndex = 0;
        $maxIterations = 100; // Prevent infinite loops
        $iteration = 0;

        while ($currentNode && $iteration < $maxIterations) {
            $iteration++;

            $this->log('node', "Entering node: {$currentNode['id']}", [
                'type' => $currentNode['type'] ?? 'unknown',
                'name' => $currentNode['data']['name'] ?? $currentNode['id'],
            ]);

            // Execute node based on type
            $nextNodeId = $this->executeNode($currentNode, $userInputs[$inputIndex] ?? null);

            if ($nextNodeId === null) {
                $this->log('complete', 'Flow completed (no next node)');
                break;
            }

            // Record transition
            $this->callState['transitions'][] = [
                'from' => $currentNode['id'],
                'to' => $nextNodeId,
                'timestamp' => now(),
            ];

            $currentNode = $this->findNode($nextNodeId);
            $inputIndex++;
        }

        if ($iteration >= $maxIterations) {
            $this->log('warning', 'Flow terminated: max iterations reached');
        }
    }

    /**
     * Execute a single node and return next node ID.
     *
     * @param array $node Node configuration
     * @param mixed $userInput User input/intent
     * @return string|null Next node ID
     */
    private function executeNode(array $node, $userInput = null): ?string
    {
        $nodeType = $node['type'] ?? 'unknown';

        return match ($nodeType) {
            'function_call' => $this->executeFunctionNode($node),
            'response' => $this->executeResponseNode($node, $userInput),
            'condition' => $this->executeConditionNode($node),
            'start' => $this->executeStartNode($node),
            default => $this->executeGenericNode($node, $userInput),
        };
    }

    /**
     * Execute function call node.
     *
     * @param array $node Function node configuration
     * @return string|null Next node ID
     */
    private function executeFunctionNode(array $node): ?string
    {
        $functionName = $node['data']['name'] ?? 'unknown_function';

        $this->log('function_call', "Calling function: {$functionName}", [
            'speak_during' => $node['data']['speak_during_execution'] ?? false,
            'wait_for_result' => $node['data']['wait_for_result'] ?? false,
        ]);

        // Execute mock function
        $result = $this->functionExecutor->execute(
            $functionName,
            $this->callState['variables']
        );

        // Record function call
        $this->callState['functions_called'][] = [
            'name' => $functionName,
            'timestamp' => now(),
            'input' => $this->callState['variables'],
            'output' => $result,
        ];

        // Update call state with function result
        if (isset($result['variables'])) {
            $this->callState['variables'] = array_merge(
                $this->callState['variables'],
                $result['variables']
            );
        }

        $this->log('function_result', "Function {$functionName} returned", [
            'success' => $result['success'] ?? false,
        ]);

        // Find next node (usually single outgoing edge)
        return $this->getNextNodeFromEdges($node['id']);
    }

    /**
     * Execute response node.
     *
     * @param array $node Response node configuration
     * @param mixed $userInput User input
     * @return string|null Next node ID
     */
    private function executeResponseNode(array $node, $userInput = null): ?string
    {
        $this->log('response', 'Agent response node', [
            'has_user_input' => $userInput !== null,
        ]);

        // Simulate user input processing
        if ($userInput) {
            $this->callState['variables']['last_user_input'] = $userInput;
        }

        return $this->getNextNodeFromEdges($node['id']);
    }

    /**
     * Execute condition node (evaluate transitions).
     *
     * @param array $node Condition node configuration
     * @return string|null Next node ID
     */
    private function executeConditionNode(array $node): ?string
    {
        $this->log('condition', 'Evaluating condition node');

        // Find all outgoing edges
        $edges = $this->getOutgoingEdges($node['id']);

        // Evaluate each edge's condition
        foreach ($edges as $edge) {
            if ($this->evaluateEdgeCondition($edge)) {
                $this->log('condition_met', "Condition met, transitioning to: {$edge['target']}");
                return $edge['target'];
            }
        }

        // Default to first edge if no condition matched
        if (!empty($edges)) {
            $this->log('condition_default', "No condition met, using default edge");
            return $edges[0]['target'];
        }

        return null;
    }

    /**
     * Execute start node.
     *
     * @param array $node Start node
     * @return string|null Next node ID
     */
    private function executeStartNode(array $node): ?string
    {
        $this->log('start', 'Flow started');
        return $this->getNextNodeFromEdges($node['id']);
    }

    /**
     * Execute generic/unknown node type.
     *
     * @param array $node Node configuration
     * @param mixed $userInput User input
     * @return string|null Next node ID
     */
    private function executeGenericNode(array $node, $userInput = null): ?string
    {
        $this->log('generic', "Generic node: {$node['id']}");
        return $this->getNextNodeFromEdges($node['id']);
    }

    /**
     * Find node by ID in flow config.
     *
     * @param string $nodeId Node ID
     * @return array|null Node configuration
     */
    private function findNode(string $nodeId): ?array
    {
        if (!isset($this->flowConfig['nodes'])) {
            return null;
        }

        foreach ($this->flowConfig['nodes'] as $node) {
            if ($node['id'] === $nodeId) {
                return $node;
            }
        }

        return null;
    }

    /**
     * Get next node ID from edges.
     *
     * @param string $fromNodeId Source node ID
     * @return string|null Target node ID
     */
    private function getNextNodeFromEdges(string $fromNodeId): ?string
    {
        $edges = $this->getOutgoingEdges($fromNodeId);

        if (empty($edges)) {
            return null;
        }

        // Return first edge's target (simple case)
        return $edges[0]['target'] ?? null;
    }

    /**
     * Get all outgoing edges from a node.
     *
     * @param string $nodeId Node ID
     * @return array Edges
     */
    private function getOutgoingEdges(string $nodeId): array
    {
        if (!isset($this->flowConfig['edges'])) {
            return [];
        }

        $outgoing = [];
        foreach ($this->flowConfig['edges'] as $edge) {
            if ($edge['source'] === $nodeId) {
                $outgoing[] = $edge;
            }
        }

        return $outgoing;
    }

    /**
     * Evaluate edge condition.
     *
     * @param array $edge Edge configuration
     * @return bool True if condition is met
     */
    private function evaluateEdgeCondition(array $edge): bool
    {
        // Simplified condition evaluation
        // In real implementation, would evaluate expressions against callState

        if (!isset($edge['data']['condition'])) {
            return true; // No condition = always true
        }

        $condition = $edge['data']['condition'];

        // Simple variable equality check
        if (preg_match('/(\w+)\s*==\s*["\']([^"\']+)["\']/', $condition, $matches)) {
            $variable = $matches[1];
            $expectedValue = $matches[2];

            return ($this->callState['variables'][$variable] ?? null) === $expectedValue;
        }

        // Default: assume condition is met
        return true;
    }

    /**
     * Check if a function should be called based on context.
     *
     * @param string $functionName Function name
     * @param array $context Call context
     * @return bool True if function should be called
     */
    public function shouldCallFunction(string $functionName, array $context): bool
    {
        // Find function node in flow
        if (!isset($this->flowConfig['nodes'])) {
            return false;
        }

        foreach ($this->flowConfig['nodes'] as $node) {
            if (
                ($node['type'] ?? '') === 'function_call' &&
                ($node['data']['name'] ?? '') === $functionName
            ) {
                return true; // Function node exists in flow
            }
        }

        return false;
    }

    /**
     * Validate function execution requirements.
     *
     * @param string $functionName Function name
     * @return ValidationResult
     */
    public function validateFunctionExecution(string $functionName): ValidationResult
    {
        $errors = [];

        // Check if function node exists
        $hasNode = $this->shouldCallFunction($functionName, []);
        if (!$hasNode) {
            $errors[] = "Function '{$functionName}' has no explicit function_call node in flow";
        }

        // Check configuration
        $functionNode = $this->findFunctionNode($functionName);
        if ($functionNode) {
            if (!($functionNode['data']['wait_for_result'] ?? false)) {
                $errors[] = "Function '{$functionName}' does not wait for result (unreliable)";
            }
        }

        return new ValidationResult(
            isValid: empty($errors),
            errors: $errors
        );
    }

    /**
     * Find function node by function name.
     *
     * @param string $functionName Function name
     * @return array|null Function node
     */
    private function findFunctionNode(string $functionName): ?array
    {
        if (!isset($this->flowConfig['nodes'])) {
            return null;
        }

        foreach ($this->flowConfig['nodes'] as $node) {
            if (
                ($node['type'] ?? '') === 'function_call' &&
                ($node['data']['name'] ?? '') === $functionName
            ) {
                return $node;
            }
        }

        return null;
    }

    /**
     * Reset simulator state.
     *
     * @return void
     */
    private function reset(): void
    {
        $this->callState = [];
        $this->executionLog = [];
    }

    /**
     * Log simulation event.
     *
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context
     * @return void
     */
    private function log(string $level, string $message, array $context = []): void
    {
        $logEntry = [
            'timestamp' => now()->format('H:i:s.v'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];

        $this->executionLog[] = $logEntry;

        Log::debug("[CallSimulator] {$message}", $context);
    }

    /**
     * Get execution log.
     *
     * @return array Execution log
     */
    public function getExecutionLog(): array
    {
        return $this->executionLog;
    }

    /**
     * Get call state.
     *
     * @return array Call state
     */
    public function getCallState(): array
    {
        return $this->callState;
    }
}

/**
 * Call Simulation Result
 */
class CallSimulationResult
{
    public function __construct(
        public bool $success,
        public ?array $callState = null,
        public array $executionLog = [],
        public array $functionsCalled = [],
        public array $transitionPath = [],
        public ?string $error = null,
        public array $validationErrors = []
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'call_state' => $this->callState,
            'execution_log' => $this->executionLog,
            'functions_called' => $this->functionsCalled,
            'transition_path' => $this->transitionPath,
            'error' => $this->error,
            'validation_errors' => $this->validationErrors,
        ];
    }

    public function getFunctionNames(): array
    {
        return array_column($this->functionsCalled, 'name');
    }

    public function wasFunctionCalled(string $functionName): bool
    {
        return in_array($functionName, $this->getFunctionNames());
    }

    public function getTransitionCount(): int
    {
        return count($this->transitionPath);
    }
}

/**
 * Validation Result
 */
class ValidationResult
{
    public function __construct(
        public bool $isValid,
        public array $errors = []
    ) {}
}
