<?php

namespace App\Services\ConversationFlow;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Conversation Flow Deployment Service
 *
 * Deploys conversation flow nodes to Retell.ai via API
 */
class ConversationFlowDeploymentService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.retellai.base_url', 'https://api.retellai.com'), '/');
        $this->apiKey = config('services.retellai.api_key');
    }

    /**
     * Deploy conversation flow from node_graph.json to Retell.ai
     */
    public function deployFromNodeGraph(string $agentId): array
    {
        try {
            Log::info('🚀 Starting Conversation Flow deployment', ['agent_id' => $agentId]);

            // Step 1: Load node graph
            $nodeGraphPath = 'conversation_flow/graphs/node_graph.json';
            if (!Storage::disk('local')->exists($nodeGraphPath)) {
                throw new \Exception("Node graph not found at {$nodeGraphPath}");
            }

            $nodeGraph = json_decode(Storage::disk('local')->get($nodeGraphPath), true);
            Log::info('  ✓ Loaded node graph', [
                'total_nodes' => $nodeGraph['total_nodes'],
                'total_transitions' => $nodeGraph['total_transitions']
            ]);

            // Step 2: Get existing functions from current agent
            $existingFunctions = $this->getAgentFunctions($agentId);
            Log::info('  ✓ Retrieved existing functions', ['count' => count($existingFunctions)]);

            // Step 3: Transform to Retell API format
            $conversationFlowPayload = $this->transformToRetellFormat($nodeGraph, $existingFunctions);
            Log::info('  ✓ Transformed to Retell API format', ['nodes_count' => count($conversationFlowPayload['nodes'])]);

            // Step 4: Create conversation flow via API
            $conversationFlowId = $this->createConversationFlow($conversationFlowPayload);
            Log::info('  ✓ Created conversation flow', ['conversation_flow_id' => $conversationFlowId]);

            // Step 5: Link conversation flow to agent
            $this->linkConversationFlowToAgent($agentId, $conversationFlowId);
            Log::info('  ✓ Linked conversation flow to agent');

            Log::info('✅ Conversation Flow deployment completed successfully!');

            return [
                'success' => true,
                'conversation_flow_id' => $conversationFlowId,
                'agent_id' => $agentId,
                'nodes_deployed' => count($conversationFlowPayload['nodes']),
                'message' => 'Conversation Flow successfully deployed and linked to agent'
            ];

        } catch (\Exception $e) {
            Log::error('❌ Conversation Flow deployment failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }

    /**
     * Get functions from existing agent
     */
    private function getAgentFunctions(string $agentId): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json'
        ])->get("{$this->baseUrl}/get-agent/{$agentId}");

        if (!$response->successful()) {
            Log::warning('Could not fetch agent functions, using empty array', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return [];
        }

        $agent = $response->json();

        // Try to get functions from response_engine or fallback to tools
        $functions = [];

        if (isset($agent['response_engine']['llm_id'])) {
            // Single prompt agent - fetch LLM to get functions
            $llmId = $agent['response_engine']['llm_id'];
            $llmResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get("{$this->baseUrl}/get-retell-llm/{$llmId}");

            if ($llmResponse->successful()) {
                $llm = $llmResponse->json();
                $functions = $llm['general_tools'] ?? [];
            }
        }

        return $functions;
    }

    /**
     * Transform node graph to Retell API format
     */
    private function transformToRetellFormat(array $nodeGraph, array $existingFunctions): array
    {
        $nodes = [];
        $transitions = $nodeGraph['transitions'] ?? [];

        foreach ($nodeGraph['nodes'] as $nodeId => $node) {
            $transformedNode = $this->transformNode($node, $transitions);
            if ($transformedNode) {
                $nodes[] = $transformedNode;
            }
        }

        return [
            'start_speaker' => 'agent',
            'start_node_id' => 'node_01_initialization',
            'model_choice' => [
                'type' => 'cascading',
                'model' => 'gpt-4o-mini'  // Using corrected model name
            ],
            'model_temperature' => 0.3,
            'global_prompt' => $this->getGlobalPrompt(),
            'tools' => $this->transformTools($existingFunctions),
            'nodes' => $nodes,
            'begin_after_user_silence_ms' => 800
        ];
    }

    /**
     * Transform single node to Retell format
     */
    private function transformNode(array $node, array $allTransitions): ?array
    {
        $nodeId = $node['id'];
        $originalType = $node['type'];

        // Map ALL nodes to 'conversation' type with tool calls in instruction
        // This is simpler than managing tool_ids
        $transformedNode = [
            'id' => $nodeId,
            'type' => $originalType === 'end' ? 'end' : 'conversation'
        ];

        // Build instruction with tool calls if needed
        if ($originalType === 'function') {
            // Function node → Conversation node with tool calls
            $instruction = $node['prompt'] ?? $node['description'];

            // Add function call instructions
            if (isset($node['functions']) && is_array($node['functions'])) {
                $functionList = implode(' and ', $node['functions']);
                $instruction .= "\n\nCall these functions: {$functionList}";

                if (isset($node['execution_mode']) && $node['execution_mode'] === 'parallel') {
                    $instruction .= " (in parallel).";
                }
            }

            if (isset($node['function_name'])) {
                $instruction .= "\n\nCall function: {$node['function_name']}";

                if (isset($node['speak_during_execution']) && $node['speak_during_execution']) {
                    $instruction .= "\n\nWhile function executes, say: \"" . ($node['execution_message'] ?? 'Einen Moment bitte...') . "\"";
                }
            }

            $transformedNode['instruction'] = [
                'type' => 'prompt',
                'text' => $instruction
            ];

        } elseif ($originalType === 'logic' || $originalType === 'interaction') {
            // Logic/Interaction nodes → Conversation nodes
            $transformedNode['instruction'] = [
                'type' => 'prompt',
                'text' => $node['prompt'] ?? $node['description']
            ];
        } elseif ($originalType !== 'end') {
            // Default: conversation node
            $transformedNode['instruction'] = [
                'type' => 'prompt',
                'text' => $node['prompt'] ?? $node['description']
            ];
        }

        // Add edges (transitions)
        $edges = $this->buildEdges($nodeId, $allTransitions);
        if (!empty($edges)) {
            $transformedNode['edges'] = $edges;
        }

        return $transformedNode;
    }

    /**
     * Map our node types to Retell node types
     */
    private function mapNodeType(string $ourType): string
    {
        $mapping = [
            'function' => 'function',
            'interaction' => 'conversation',
            'logic' => 'branch',
            'end' => 'end'
        ];

        return $mapping[$ourType] ?? 'conversation';
    }

    /**
     * Build edges (transitions) for a node
     */
    private function buildEdges(string $nodeId, array $allTransitions): array
    {
        $edges = [];
        $edgeCounter = 1;

        foreach ($allTransitions as $transition) {
            if ($transition['from'] === $nodeId) {
                $edge = [
                    'id' => "edge_{$nodeId}_{$edgeCounter}",
                    'destination_node_id' => $transition['to']
                ];

                // Add condition if exists
                if (isset($transition['condition'])) {
                    $condition = $transition['condition'];

                    // Parse condition to determine if it's an equation or prompt
                    $parsedEquation = $this->parseEquationCondition($condition);

                    if ($parsedEquation) {
                        // Equation-based condition - format as Retell.ai expects
                        // Example: "{{customer_status}} == \"found\""
                        $equationString = $this->buildEquationString($parsedEquation);

                        $edge['transition_condition'] = [
                            'type' => 'equation',
                            'equations' => [$equationString]  // Must be array of equation strings
                        ];
                    } else {
                        // Prompt-based semantic condition
                        $edge['transition_condition'] = [
                            'type' => 'prompt',
                            'prompt' => $condition
                        ];
                    }
                }

                $edges[] = $edge;
                $edgeCounter++;
            }
        }

        return $edges;
    }

    /**
     * Parse equation condition string into components
     * Example: "customer_status == \"found\"" → ['left' => 'customer_status', 'operator' => '==', 'right' => 'found']
     */
    private function parseEquationCondition(string $condition): ?array
    {
        // List of operators to check (order matters - check longer operators first)
        $operators = ['==', '!=', '>=', '<=', '>', '<', 'contains', 'exists'];

        foreach ($operators as $operator) {
            if (strpos($condition, $operator) !== false) {
                $parts = explode($operator, $condition, 2);

                if (count($parts) === 2) {
                    $left = trim($parts[0]);
                    $right = trim($parts[1]);

                    // Remove quotes from right side if present
                    $right = trim($right, '"\'');

                    return [
                        'left' => $left,
                        'operator' => $operator,
                        'right' => $right
                    ];
                }
            }
        }

        return null; // Not an equation, must be a prompt
    }

    /**
     * Build equation string in Retell.ai format from parsed components
     * Example: ['left' => 'customer_status', 'operator' => '==', 'right' => 'found']
     *       → "{{customer_status}} == \"found\""
     */
    private function buildEquationString(array $parsedEquation): string
    {
        $left = $parsedEquation['left'];
        $operator = $parsedEquation['operator'];
        $right = $parsedEquation['right'];

        // Wrap variable in {{}} unless it already has them
        if (!str_starts_with($left, '{{')) {
            $left = "{{" . $left . "}}";
        }

        // Determine if right side should be quoted
        // Quote if it's not a number and not already a variable
        if (!is_numeric($right) && !str_starts_with($right, '{{')) {
            $right = '"' . $right . '"';
        }

        return "{$left} {$operator} {$right}";
    }

    /**
     * Get global prompt for all nodes
     */
    private function getGlobalPrompt(): string
    {
        return <<<PROMPT
# AskPro AI Appointment Booking Agent

## Core Rules
- NEVER invent dates, times, or availability
- ALWAYS use function results
- Ask clarifying questions when uncertain
- Use first name only (no Herr/Frau without explicit gender)
- Confirm important details before booking
- Be polite, professional, and efficient

## Date Parsing Rules
- "15.1" means 15th of CURRENT month, NOT January
- Use current_time_berlin() for reference
- Parse relative dates: "morgen" = tomorrow, "übermorgen" = day after tomorrow

## V85 Race Condition Protection
- STEP 1: collect_appointment_data with bestaetigung=false (check only)
- STEP 2: Get explicit user confirmation
- STEP 3: collect_appointment_data with bestaetigung=true (book)
- If race condition occurs, offer alternatives immediately

## Anti-Silence Rule
- Speak within 2 seconds of user utterance
- If processing, say "Einen Moment bitte..."
- Never let silence exceed 3 seconds
PROMPT;
    }

    /**
     * Transform existing functions to tools format
     */
    private function transformTools(array $existingFunctions): array
    {
        $tools = [];

        foreach ($existingFunctions as $function) {
            if (isset($function['name'])) {
                $tools[] = [
                    'type' => 'custom',
                    'name' => $function['name'],
                    'description' => $function['description'] ?? '',
                    'url' => $function['url'] ?? config('app.url') . '/api/retell/function-call',
                    'speak_after_execution' => $function['speak_after_execution'] ?? true,
                    'speak_during_execution' => $function['speak_during_execution'] ?? false,
                    'parameters' => $function['parameters'] ?? []
                ];
            }
        }

        return $tools;
    }

    /**
     * Create conversation flow via Retell API
     */
    private function createConversationFlow(array $payload): string
    {
        Log::info('Creating conversation flow via API', [
            'nodes_count' => count($payload['nodes']),
            'model' => $payload['model_choice']['model']
        ]);

        // Debug: Save payload to file for inspection
        Storage::disk('local')->put(
            'conversation_flow/debug/api_payload.json',
            json_encode($payload, JSON_PRETTY_PRINT)
        );
        Log::info('Saved API payload to storage/conversation_flow/debug/api_payload.json');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json'
        ])->post("{$this->baseUrl}/create-conversation-flow", $payload);

        if (!$response->successful()) {
            Log::error('Failed to create conversation flow', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception("Failed to create conversation flow: " . $response->body());
        }

        $result = $response->json();

        if (!isset($result['conversation_flow_id'])) {
            throw new \Exception("API response missing conversation_flow_id: " . json_encode($result));
        }

        return $result['conversation_flow_id'];
    }

    /**
     * Link conversation flow to agent
     */
    private function linkConversationFlowToAgent(string $agentId, string $conversationFlowId): void
    {
        Log::info('Linking conversation flow to agent', [
            'agent_id' => $agentId,
            'conversation_flow_id' => $conversationFlowId
        ]);

        $payload = [
            'response_engine' => [
                'type' => 'conversation-flow',
                'conversation_flow_id' => $conversationFlowId
            ]
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json'
        ])->patch("{$this->baseUrl}/update-agent/{$agentId}", $payload);

        if (!$response->successful()) {
            Log::error('Failed to link conversation flow to agent', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception("Failed to link conversation flow to agent: " . $response->body());
        }

        Log::info('Successfully linked conversation flow to agent');
    }

    /**
     * Update existing agent to use different conversation flow
     */
    public function updateAgentConversationFlow(string $agentId, string $conversationFlowId): array
    {
        try {
            Log::info('Updating agent conversation flow', [
                'agent_id' => $agentId,
                'conversation_flow_id' => $conversationFlowId
            ]);

            $this->linkConversationFlowToAgent($agentId, $conversationFlowId);

            return [
                'success' => true,
                'agent_id' => $agentId,
                'conversation_flow_id' => $conversationFlowId,
                'message' => 'Agent successfully updated to use new conversation flow'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to update agent conversation flow', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
