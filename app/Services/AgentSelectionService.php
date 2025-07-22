<?php

namespace App\Services;

use App\Models\AgentAssignment;
use App\Models\Company;
use App\Models\Customer;
use App\Models\RetellAgent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AgentSelectionService
{
    /**
     * Select the best agent for a given context.
     */
    public function selectAgent(array $context): ?RetellAgent
    {
        $companyId = $context['company_id'] ?? null;

        if (! $companyId) {
            Log::warning('No company_id provided for agent selection');

            return null;
        }

        // Try cache first
        $cacheKey = $this->buildCacheKey($context);
        $cachedAgent = Cache::get($cacheKey);

        if ($cachedAgent) {
            Log::info('Using cached agent selection', [
                'agent_id' => $cachedAgent->id,
                'cache_key' => $cacheKey,
            ]);

            return $cachedAgent;
        }

        // Get all active agents for company
        $agents = RetellAgent::where('company_id', $companyId)
            ->active()
            ->with('assignments')
            ->get();

        if ($agents->isEmpty()) {
            Log::warning('No active agents found for company', ['company_id' => $companyId]);

            return null;
        }

        // If only one agent, return it
        if ($agents->count() === 1) {
            return $this->cacheAndReturn($agents->first(), $cacheKey);
        }

        // Score each agent based on assignments
        $scoredAgents = $this->scoreAgents($agents, $context);

        // Get the highest scoring agent
        $selectedAgent = $scoredAgents->sortByDesc('score')->first()['agent'];

        // Handle A/B testing if applicable
        $selectedAgent = $this->handleABTesting($selectedAgent, $scoredAgents, $context);

        return $this->cacheAndReturn($selectedAgent, $cacheKey);
    }

    /**
     * Score agents based on their assignments and context.
     */
    protected function scoreAgents(Collection $agents, array $context): Collection
    {
        return $agents->map(function (RetellAgent $agent) use ($context) {
            $score = 0;
            $matchedAssignments = [];

            // Base score from agent priority
            $score += $agent->priority;

            // Check each assignment
            foreach ($agent->assignments as $assignment) {
                if (! $assignment->isCurrentlyActive()) {
                    continue;
                }

                if ($assignment->matchesCriteria($context)) {
                    $assignmentScore = $assignment->getScore($context);
                    $score += $assignmentScore;
                    $matchedAssignments[] = [
                        'id' => $assignment->id,
                        'type' => $assignment->assignment_type,
                        'score' => $assignmentScore,
                    ];
                }
            }

            // Bonus for default agent if no specific matches
            if ($agent->is_default && empty($matchedAssignments)) {
                $score += 5;
            }

            // Bonus for performance
            if ($agent->success_rate > 80) {
                $score += 3;
            }

            // Bonus for language match
            if (isset($context['language']) && $agent->language === $context['language']) {
                $score += 10;
            }

            // Bonus for type match
            if (isset($context['purpose'])) {
                $typeBonus = $this->getTypeBonus($agent->type, $context['purpose']);
                $score += $typeBonus;
            }

            return [
                'agent' => $agent,
                'score' => $score,
                'matched_assignments' => $matchedAssignments,
            ];
        });
    }

    /**
     * Get bonus score for agent type matching purpose.
     */
    protected function getTypeBonus(string $agentType, string $purpose): int
    {
        $typeMapping = [
            'appointment_booking' => RetellAgent::TYPE_APPOINTMENTS,
            'appointment_reminder' => RetellAgent::TYPE_APPOINTMENTS,
            'sales_outreach' => RetellAgent::TYPE_SALES,
            'follow_up' => RetellAgent::TYPE_SALES,
            'customer_support' => RetellAgent::TYPE_SUPPORT,
            'feedback_collection' => RetellAgent::TYPE_SUPPORT,
        ];

        $expectedType = $typeMapping[$purpose] ?? RetellAgent::TYPE_GENERAL;

        if ($agentType === $expectedType) {
            return 15;
        }

        if ($agentType === RetellAgent::TYPE_GENERAL) {
            return 5; // General agents can handle anything
        }

        return 0;
    }

    /**
     * Handle A/B testing logic.
     */
    protected function handleABTesting(RetellAgent $selectedAgent, Collection $scoredAgents, array $context): RetellAgent
    {
        // Check if any agents are in A/B test mode
        $testAgents = $scoredAgents->filter(function ($item) {
            $agent = $item['agent'];

            return $agent->assignments->contains(function ($assignment) {
                return $assignment->is_test && $assignment->isCurrentlyActive();
            });
        });

        if ($testAgents->isEmpty()) {
            return $selectedAgent;
        }

        // Determine which agent to use based on traffic percentage
        foreach ($testAgents as $testItem) {
            $agent = $testItem['agent'];
            $testAssignment = $agent->assignments->first(function ($assignment) {
                return $assignment->is_test && $assignment->isCurrentlyActive();
            });

            if ($testAssignment && $this->shouldUseTestAgent($testAssignment, $context)) {
                Log::info('Using test agent for A/B testing', [
                    'test_agent_id' => $agent->id,
                    'original_agent_id' => $selectedAgent->id,
                    'traffic_percentage' => $testAssignment->traffic_percentage,
                ]);

                return $agent;
            }
        }

        return $selectedAgent;
    }

    /**
     * Determine if test agent should be used based on traffic percentage.
     */
    protected function shouldUseTestAgent(AgentAssignment $testAssignment, array $context): bool
    {
        $trafficPercentage = $testAssignment->traffic_percentage ?? 50;

        // Use consistent hashing based on customer ID for consistent assignment
        if (isset($context['customer_id'])) {
            $hash = crc32($context['customer_id'] . $testAssignment->id);

            return ($hash % 100) < $trafficPercentage;
        }

        // Random assignment for anonymous calls
        return rand(1, 100) <= $trafficPercentage;
    }

    /**
     * Build cache key for agent selection.
     */
    protected function buildCacheKey(array $context): string
    {
        $keyParts = [
            'agent_selection',
            $context['company_id'],
            $context['purpose'] ?? 'general',
            $context['service_id'] ?? 'none',
            $context['branch_id'] ?? 'none',
            $context['language'] ?? 'default',
        ];

        return implode(':', $keyParts);
    }

    /**
     * Cache and return agent.
     */
    protected function cacheAndReturn(?RetellAgent $agent, string $cacheKey): ?RetellAgent
    {
        if ($agent) {
            // Cache for 5 minutes
            Cache::put($cacheKey, $agent, 300);
        }

        return $agent;
    }

    /**
     * Get agent by ID with fallback to default.
     */
    public function getAgentById(int $companyId, string $agentId): ?RetellAgent
    {
        $agent = RetellAgent::where('company_id', $companyId)
            ->where('retell_agent_id', $agentId)
            ->active()
            ->first();

        if (! $agent) {
            // Fallback to default agent
            $agent = RetellAgent::where('company_id', $companyId)
                ->where('is_default', true)
                ->active()
                ->first();
        }

        return $agent;
    }

    /**
     * Import agents from Retell.ai API.
     */
    public function importAgentsFromRetell(Company $company): Collection
    {
        $retellService = app(RetellV2Service::class);
        $importedAgents = collect();

        try {
            // Get agents from Retell API
            $retellAgents = $retellService->listAgents();

            foreach ($retellAgents as $retellAgent) {
                // Check if agent already exists
                $agent = RetellAgent::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'retell_agent_id' => $retellAgent['agent_id'],
                    ],
                    [
                        'name' => $retellAgent['agent_name'] ?? 'Unnamed Agent',
                        'description' => $retellAgent['description'] ?? null,
                        'language' => $retellAgent['language'] ?? 'en',
                        'voice_settings' => $retellAgent['voice'] ?? [],
                        'prompt_settings' => [
                            'prompt' => $retellAgent['prompt'] ?? null,
                            'response_format' => $retellAgent['response_format'] ?? null,
                        ],
                        'is_active' => true,
                        'type' => $this->detectAgentType($retellAgent),
                    ]
                );

                $importedAgents->push($agent);
            }

            // Set default agent if none exists
            if ($importedAgents->isNotEmpty() && ! $company->agents()->where('is_default', true)->exists()) {
                $importedAgents->first()->update(['is_default' => true]);
            }

            Log::info('Imported agents from Retell', [
                'company_id' => $company->id,
                'count' => $importedAgents->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to import agents from Retell', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $importedAgents;
    }

    /**
     * Detect agent type from Retell agent data.
     */
    protected function detectAgentType(array $retellAgent): string
    {
        $prompt = strtolower($retellAgent['prompt'] ?? '');
        $name = strtolower($retellAgent['agent_name'] ?? '');

        if (str_contains($prompt, 'appointment') || str_contains($name, 'appointment')) {
            return RetellAgent::TYPE_APPOINTMENTS;
        }

        if (str_contains($prompt, 'sales') || str_contains($name, 'sales')) {
            return RetellAgent::TYPE_SALES;
        }

        if (str_contains($prompt, 'support') || str_contains($name, 'support')) {
            return RetellAgent::TYPE_SUPPORT;
        }

        return RetellAgent::TYPE_GENERAL;
    }
}
