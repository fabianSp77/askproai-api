<?php

namespace App\Services\Testing;

/**
 * Flow Validation Engine
 *
 * Validates Retell conversation flows for:
 * - Required function nodes
 * - Logical consistency
 * - Dead-end detection
 * - Infinite loop prevention
 *
 * Purpose: Catch flow configuration errors BEFORE deployment.
 */
class FlowValidationEngine
{
    private array $validationRules = [];
    private array $errors = [];
    private array $warnings = [];

    public function __construct()
    {
        $this->initializeRules();
    }

    /**
     * Validate a flow configuration.
     *
     * @param array|null $flowConfig Flow configuration
     * @return FlowValidationResult Validation result
     */
    public function validateFlow(?array $flowConfig): FlowValidationResult
    {
        $this->reset();

        if (!$flowConfig) {
            $this->addError('Flow configuration is null');
            return $this->buildResult();
        }

        // Run all validation rules
        $this->validateStructure($flowConfig);
        $this->validateNodes($flowConfig);
        $this->validateEdges($flowConfig);
        $this->validateFunctionNodes($flowConfig);
        $this->validateTransitions($flowConfig);
        $this->detectDeadEnds($flowConfig);
        $this->detectInfiniteLoops($flowConfig);

        return $this->buildResult();
    }

    /**
     * Validate basic flow structure.
     *
     * @param array $flowConfig Flow configuration
     * @return void
     */
    private function validateStructure(array $flowConfig): void
    {
        if (!isset($flowConfig['nodes']) || !is_array($flowConfig['nodes'])) {
            $this->addError('Flow must have a "nodes" array');
        }

        if (!isset($flowConfig['edges']) || !is_array($flowConfig['edges'])) {
            $this->addError('Flow must have an "edges" array');
        }

        if (empty($flowConfig['nodes'])) {
            $this->addError('Flow has no nodes');
        }
    }

    /**
     * Validate node configurations.
     *
     * @param array $flowConfig Flow configuration
     * @return void
     */
    private function validateNodes(array $flowConfig): void
    {
        if (!isset($flowConfig['nodes'])) {
            return;
        }

        $hasStartNode = false;

        foreach ($flowConfig['nodes'] as $index => $node) {
            // Check required fields
            if (!isset($node['id'])) {
                $this->addError("Node at index {$index} missing 'id' field");
                continue;
            }

            $nodeId = $node['id'];

            if (!isset($node['type'])) {
                $this->addWarning("Node '{$nodeId}' missing 'type' field");
            }

            // Check for start node
            if (($node['id'] ?? '') === 'begin' || ($node['type'] ?? '') === 'start') {
                $hasStartNode = true;
            }

            // Validate node-specific requirements
            if (($node['type'] ?? '') === 'function_call') {
                $this->validateFunctionNode($node);
            }
        }

        if (!$hasStartNode) {
            $this->addError('Flow has no start node (id="begin" or type="start")');
        }
    }

    /**
     * Validate a single function node.
     *
     * @param array $node Function node
     * @return void
     */
    private function validateFunctionNode(array $node): void
    {
        $nodeId = $node['id'] ?? 'unknown';

        if (!isset($node['data']['name'])) {
            $this->addError("Function node '{$nodeId}' missing function name");
        }

        // Check critical configuration
        if (!($node['data']['wait_for_result'] ?? false)) {
            $this->addWarning(
                "Function node '{$nodeId}' does not wait for result - function calls may be unreliable"
            );
        }

        if (!($node['data']['speak_during_execution'] ?? false)) {
            $this->addWarning(
                "Function node '{$nodeId}' does not speak during execution - may cause dead air"
            );
        }
    }

    /**
     * Validate edges (transitions).
     *
     * @param array $flowConfig Flow configuration
     * @return void
     */
    private function validateEdges(array $flowConfig): void
    {
        if (!isset($flowConfig['edges']) || !isset($flowConfig['nodes'])) {
            return;
        }

        $nodeIds = array_column($flowConfig['nodes'], 'id');

        foreach ($flowConfig['edges'] as $index => $edge) {
            // Check required fields
            if (!isset($edge['source'])) {
                $this->addError("Edge at index {$index} missing 'source' field");
                continue;
            }

            if (!isset($edge['target'])) {
                $this->addError("Edge at index {$index} missing 'target' field");
                continue;
            }

            // Check source node exists
            if (!in_array($edge['source'], $nodeIds)) {
                $this->addError("Edge source '{$edge['source']}' references non-existent node");
            }

            // Check target node exists
            if (!in_array($edge['target'], $nodeIds)) {
                $this->addError("Edge target '{$edge['target']}' references non-existent node");
            }

            // Check for self-loops
            if ($edge['source'] === $edge['target']) {
                $this->addWarning("Edge from '{$edge['source']}' to itself (self-loop) - may cause infinite loop");
            }
        }
    }

    /**
     * Validate function nodes exist for critical functions.
     *
     * @param array $flowConfig Flow configuration
     * @return void
     */
    private function validateFunctionNodes(array $flowConfig): void
    {
        if (!isset($flowConfig['nodes'])) {
            return;
        }

        $criticalFunctions = [
            'check_availability' => 'CRITICAL: No check_availability function node found',
            'book_appointment' => 'WARNING: No book_appointment function node found',
        ];

        foreach ($criticalFunctions as $functionName => $errorMessage) {
            $found = false;

            foreach ($flowConfig['nodes'] as $node) {
                if (
                    ($node['type'] ?? '') === 'function_call' &&
                    stripos($node['data']['name'] ?? '', $functionName) !== false
                ) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                if (strpos($errorMessage, 'CRITICAL') !== false) {
                    $this->addError($errorMessage);
                } else {
                    $this->addWarning($errorMessage);
                }
            }
        }
    }

    /**
     * Validate transitions are logically consistent.
     *
     * @param array $flowConfig Flow configuration
     * @return void
     */
    private function validateTransitions(array $flowConfig): void
    {
        if (!isset($flowConfig['nodes']) || !isset($flowConfig['edges'])) {
            return;
        }

        // Build adjacency list
        $outgoing = [];
        foreach ($flowConfig['edges'] as $edge) {
            $source = $edge['source'] ?? null;
            if ($source) {
                if (!isset($outgoing[$source])) {
                    $outgoing[$source] = [];
                }
                $outgoing[$source][] = $edge;
            }
        }

        // Check each node has at least one outgoing edge (except end nodes)
        foreach ($flowConfig['nodes'] as $node) {
            $nodeId = $node['id'];
            $nodeType = $node['type'] ?? '';

            // Skip explicit end nodes
            if ($nodeType === 'end' || $nodeId === 'end') {
                continue;
            }

            if (!isset($outgoing[$nodeId]) || empty($outgoing[$nodeId])) {
                $this->addWarning("Node '{$nodeId}' has no outgoing edges (dead end)");
            }
        }
    }

    /**
     * Detect dead-end nodes (no outgoing edges).
     *
     * @param array $flowConfig Flow configuration
     * @return void
     */
    private function detectDeadEnds(array $flowConfig): void
    {
        if (!isset($flowConfig['nodes']) || !isset($flowConfig['edges'])) {
            return;
        }

        $nodeIds = array_column($flowConfig['nodes'], 'id');
        $nodesWithOutgoing = array_unique(array_column($flowConfig['edges'], 'source'));
        $deadEnds = array_diff($nodeIds, $nodesWithOutgoing);

        // Filter out intentional end nodes
        $deadEnds = array_filter($deadEnds, function($nodeId) {
            return !in_array($nodeId, ['end', 'END', 'exit', 'EXIT']);
        });

        if (!empty($deadEnds)) {
            $this->addWarning(
                'Found ' . count($deadEnds) . ' potential dead-end nodes: ' . implode(', ', $deadEnds)
            );
        }
    }

    /**
     * Detect infinite loops.
     *
     * @param array $flowConfig Flow configuration
     * @return void
     */
    private function detectInfiniteLoops(array $flowConfig): void
    {
        if (!isset($flowConfig['nodes']) || !isset($flowConfig['edges'])) {
            return;
        }

        // Build adjacency list
        $graph = [];
        foreach ($flowConfig['edges'] as $edge) {
            $source = $edge['source'] ?? null;
            $target = $edge['target'] ?? null;

            if ($source && $target) {
                if (!isset($graph[$source])) {
                    $graph[$source] = [];
                }
                $graph[$source][] = $target;
            }
        }

        // Detect cycles using DFS
        $visited = [];
        $recursionStack = [];

        foreach (array_keys($graph) as $node) {
            if (!isset($visited[$node])) {
                if ($this->hasCycleDFS($node, $graph, $visited, $recursionStack)) {
                    $this->addWarning("Potential infinite loop detected involving node '{$node}'");
                }
            }
        }
    }

    /**
     * DFS helper for cycle detection.
     *
     * @param string $node Current node
     * @param array $graph Adjacency list
     * @param array &$visited Visited nodes
     * @param array &$recursionStack Recursion stack
     * @return bool True if cycle detected
     */
    private function hasCycleDFS(string $node, array $graph, array &$visited, array &$recursionStack): bool
    {
        $visited[$node] = true;
        $recursionStack[$node] = true;

        if (isset($graph[$node])) {
            foreach ($graph[$node] as $neighbor) {
                if (!isset($visited[$neighbor])) {
                    if ($this->hasCycleDFS($neighbor, $graph, $visited, $recursionStack)) {
                        return true;
                    }
                } elseif (isset($recursionStack[$neighbor]) && $recursionStack[$neighbor]) {
                    return true; // Cycle detected
                }
            }
        }

        $recursionStack[$node] = false;
        return false;
    }

    /**
     * Initialize validation rules.
     *
     * @return void
     */
    private function initializeRules(): void
    {
        $this->validationRules = [
            'structure' => true,
            'nodes' => true,
            'edges' => true,
            'function_nodes' => true,
            'transitions' => true,
            'dead_ends' => true,
            'infinite_loops' => true,
        ];
    }

    /**
     * Add validation error.
     *
     * @param string $error Error message
     * @return void
     */
    private function addError(string $error): void
    {
        $this->errors[] = $error;
    }

    /**
     * Add validation warning.
     *
     * @param string $warning Warning message
     * @return void
     */
    private function addWarning(string $warning): void
    {
        $this->warnings[] = $warning;
    }

    /**
     * Build validation result.
     *
     * @return FlowValidationResult Validation result
     */
    private function buildResult(): FlowValidationResult
    {
        return new FlowValidationResult(
            isValid: empty($this->errors),
            errors: $this->errors,
            warnings: $this->warnings
        );
    }

    /**
     * Reset validation state.
     *
     * @return void
     */
    private function reset(): void
    {
        $this->errors = [];
        $this->warnings = [];
    }
}

/**
 * Flow Validation Result
 */
class FlowValidationResult
{
    public function __construct(
        public bool $isValid,
        public array $errors = [],
        public array $warnings = []
    ) {}

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function getAllIssues(): array
    {
        return array_merge(
            array_map(fn($e) => ['type' => 'error', 'message' => $e], $this->errors),
            array_map(fn($w) => ['type' => 'warning', 'message' => $w], $this->warnings)
        );
    }

    public function toArray(): array
    {
        return [
            'is_valid' => $this->isValid,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'error_count' => count($this->errors),
            'warning_count' => count($this->warnings),
        ];
    }
}
