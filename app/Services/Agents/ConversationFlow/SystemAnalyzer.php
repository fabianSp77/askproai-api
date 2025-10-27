<?php

namespace App\Services\Agents\ConversationFlow;

use App\Services\Retell\RetellAgentManagementService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * System Analyzer for Conversation Flow Migration
 *
 * Analyzes current system state to establish baseline metrics
 * before migration to Conversation Flow.
 */
class SystemAnalyzer
{
    private RetellAgentManagementService $retellService;

    public function __construct(RetellAgentManagementService $retellService)
    {
        $this->retellService = $retellService;
    }

    /**
     * Analyze current system state
     */
    public function analyze(): array
    {
        Log::info('Starting system analysis for Conversation Flow migration');

        $analysis = [
            'timestamp' => now()->toIso8601String(),
            'current_agent_config' => $this->analyzeCurrentAgentConfig(),
            'call_scenarios' => $this->analyzeCallScenarios(),
            'existing_functions' => $this->analyzeExistingFunctions(),
            'pain_points' => $this->identifyPainPoints(),
            'baseline_metrics' => $this->calculateBaselineMetrics()
        ];

        $this->saveAnalysis($analysis);

        return $analysis;
    }

    /**
     * Analyze current agent configuration
     */
    private function analyzeCurrentAgentConfig(): array
    {
        $liveAgent = $this->retellService->getLiveAgent();

        if (!$liveAgent) {
            Log::warning('Could not fetch live agent configuration');
            return [
                'status' => 'unavailable',
                'message' => 'Could not fetch current agent from Retell.ai'
            ];
        }

        return [
            'agent_id' => $liveAgent['agent_id'] ?? null,
            'agent_name' => $liveAgent['agent_name'] ?? null,
            'response_engine' => [
                'type' => $liveAgent['response_engine']['type'] ?? null,
                'llm_id' => $liveAgent['response_engine']['llm_id'] ?? null,
                'version' => $liveAgent['llm_version'] ?? null
            ],
            'model' => $liveAgent['llm_model'] ?? null,
            'voice' => [
                'voice_id' => $liveAgent['voice_id'] ?? null,
                'voice_temperature' => $liveAgent['voice_temperature'] ?? null,
                'voice_speed' => $liveAgent['voice_speed'] ?? null
            ],
            'settings' => [
                'interruption_sensitivity' => $liveAgent['interruption_sensitivity'] ?? null,
                'enable_backchannel' => $liveAgent['enable_backchannel'] ?? null,
                'backchannel_frequency' => $liveAgent['backchannel_frequency'] ?? null,
                'responsiveness' => $liveAgent['responsiveness'] ?? null
            ],
            'prompt' => [
                'type' => 'single_prompt',
                'length' => isset($liveAgent['agent_prompt']) ? strlen($liveAgent['agent_prompt']) : 0,
                'begin_message' => $liveAgent['begin_message'] ?? null
            ],
            'functions' => [
                'count' => isset($liveAgent['functions']) ? count($liveAgent['functions']) : 0,
                'names' => isset($liveAgent['functions']) ? array_column($liveAgent['functions'], 'name') : []
            ]
        ];
    }

    /**
     * Analyze call scenarios from documentation
     */
    private function analyzeCallScenarios(): array
    {
        // Data extracted from RETELL_CALL_FLOWS_COMPLETE_2025-10-11.md
        return [
            'scenario_1' => [
                'name' => 'MIT NUMMER + BEKANNT',
                'description' => 'Known customer calling with phone number',
                'frequency' => 0.65, // 15/23 calls
                'success_rate' => 0.60, // 9/15 successful
                'average_duration_seconds' => 82,
                'typical_flow' => [
                    'check_customer() finds customer',
                    'Personalized greeting',
                    'Collect appointment preferences',
                    'Check availability',
                    'Book appointment'
                ],
                'issues' => [
                    '40% failure rate',
                    'Race conditions',
                    'Cache collisions',
                    'Some calls take too long'
                ],
                'priority' => 'MEDIUM'
            ],
            'scenario_2' => [
                'name' => 'MIT NUMMER + UNBEKANNT',
                'description' => 'New customer calling with phone number',
                'frequency' => 0.00, // No data yet
                'success_rate' => null,
                'expected_success_rate' => 0.85,
                'average_duration_seconds' => null,
                'expected_duration_seconds' => 50,
                'typical_flow' => [
                    'check_customer() returns new_customer',
                    'Generic greeting',
                    'Collect name during booking flow',
                    'Check availability',
                    'Book appointment'
                ],
                'issues' => [
                    'No data available yet',
                    'Needs testing'
                ],
                'priority' => 'LOW'
            ],
            'scenario_3' => [
                'name' => 'ANONYM + BEKANNT',
                'description' => 'Known customer calling with anonymous number',
                'frequency' => 0.09, // 2/23 calls
                'success_rate' => 1.00, // 2/2 successful
                'average_duration_seconds' => 188,
                'typical_flow' => [
                    'check_customer() returns anonymous',
                    'User mentions name proactively',
                    'Long pause (17s silence)',
                    'Datum parsing error',
                    'Eventually books successfully'
                ],
                'issues' => [
                    'CRITICAL: 188s duration (too long!)',
                    '17s silence gap',
                    'Datum parsing errors ("15.1" → Januar statt Oktober)',
                    'Verbotene Phrasen ("Herr Schuster")'
                ],
                'priority' => 'HIGH'
            ],
            'scenario_4' => [
                'name' => 'ANONYM + UNBEKANNT',
                'description' => 'New/unknown customer calling with anonymous number',
                'frequency' => 0.35, // 8/23 calls
                'success_rate' => 0.25, // Only 2/8 successful!
                'abandon_rate' => 0.75, // 6/8 abandoned!
                'average_duration_seconds' => 35, // Short because abandoned
                'typical_flow' => [
                    'check_customer() returns anonymous',
                    'begin_message too long (3s)',
                    'User responds immediately (10s)',
                    'Functions run late (16-18s)',
                    'SILENCE - agent does not respond',
                    'User says "Hallo?" (22s)',
                    'User hangs up frustrated (30s)'
                ],
                'issues' => [
                    '🚨 CRITICAL: 75% abandon rate!',
                    'Race condition: functions vs user input',
                    'No Anti-Silence rule in V77 prompt',
                    'Agent blocks without specific date',
                    'Impatient users (20-30s tolerance)'
                ],
                'priority' => 'CRITICAL'
            ]
        ];
    }

    /**
     * Analyze existing functions
     */
    private function analyzeExistingFunctions(): array
    {
        return [
            'check_customer' => [
                'description' => 'Check if customer exists by phone number',
                'parameters' => ['call_id'],
                'returns' => ['status', 'customer_id', 'customer_name', 'phone'],
                'performance' => 'Fast (<500ms)',
                'reliability' => 'High (with V85 multi-tenancy fix)',
                'critical' => true
            ],
            'current_time_berlin' => [
                'description' => 'Get current date/time in Berlin timezone',
                'parameters' => [],
                'returns' => ['weekday', 'date', 'time', 'iso_date', 'week_number'],
                'performance' => 'Very Fast (<200ms)',
                'reliability' => 'High',
                'critical' => true,
                'note' => 'Essential for relative date parsing'
            ],
            'list_services' => [
                'description' => 'Get available services for company',
                'parameters' => [],
                'returns' => ['services' => ['id', 'name', 'duration', 'price']],
                'performance' => 'Fast (<500ms)',
                'reliability' => 'High',
                'critical' => false
            ],
            'collect_appointment_data' => [
                'description' => 'Check availability OR book appointment (2-step process)',
                'parameters' => [
                    'call_id',
                    'service_id',
                    'name',
                    'datum',
                    'uhrzeit',
                    'dienstleistung',
                    'bestaetigung', // false = check only, true = book
                    'email' // optional
                ],
                'returns' => ['success', 'status', 'message', 'alternatives', 'appointment_id'],
                'performance' => 'Variable (1-5s depending on cache)',
                'reliability' => 'High (with V85 race condition handling)',
                'critical' => true,
                'issues' => [
                    'Timeout currently 10000ms (too high)',
                    'Cache invalidation needed after booking'
                ]
            ],
            'cancel_appointment' => [
                'description' => 'Cancel existing appointment',
                'parameters' => ['call_id', 'appointment_id'],
                'returns' => ['success', 'message'],
                'performance' => 'Fast (<1s)',
                'reliability' => 'High (with V85 same-call policy)',
                'critical' => false
            ],
            'reschedule_appointment' => [
                'description' => 'Reschedule existing appointment',
                'parameters' => ['call_id', 'appointment_id', 'neues_datum', 'neue_uhrzeit'],
                'returns' => ['success', 'message', 'fee'],
                'performance' => 'Fast (<2s)',
                'reliability' => 'High (with V85 availability check)',
                'critical' => false
            ]
        ];
    }

    /**
     * Identify pain points from current system
     */
    private function identifyPainPoints(): array
    {
        return [
            [
                'category' => 'HALLUCINATIONS',
                'severity' => 'HIGH',
                'description' => 'Agent invents dates and times',
                'examples' => [
                    '"15.1" interpreted as January instead of current month',
                    'Agent suggests times without checking availability',
                    'Wrong weekday assignment'
                ],
                'frequency' => 'Occurs in ~15-20% of calls',
                'impact' => 'Double bookings, wrong appointments, customer frustration',
                'root_cause' => 'Single Prompt LLM has too much freedom, no validation constraints',
                'solution' => 'Conversation Flow with dedicated validation nodes'
            ],
            [
                'category' => 'RACE CONDITIONS',
                'severity' => 'HIGH',
                'description' => 'Slot taken between check and booking',
                'examples' => [
                    'User confirms, booking fails because slot now taken',
                    'Agent offers time that is actually unavailable'
                ],
                'frequency' => 'Occurs in ~10% of calls',
                'impact' => 'Failed bookings, frustrated users, lost revenue',
                'root_cause' => 'Time gap between availability check (bestaetigung=false) and booking (bestaetigung=true)',
                'solution' => 'V85 double-check + Conversation Flow with immediate retry node'
            ],
            [
                'category' => 'SILENCE_GAPS',
                'severity' => 'CRITICAL',
                'description' => '17s silence causes user abandonment',
                'examples' => [
                    'User asks question at 10s',
                    'Functions still running (16-18s)',
                    'Agent does not respond',
                    'User says "Hallo?" at 22s',
                    'User hangs up at 30s'
                ],
                'frequency' => 'Occurs in 75% of Szenario 4 calls',
                'impact' => '75% abandon rate in Szenario 4 (35% of all calls!)',
                'root_cause' => 'begin_message too long (3s) → functions run late → user speaks before functions complete → no Anti-Silence rule',
                'solution' => 'Conversation Flow: short greeting, parallel functions, Anti-Silence node'
            ],
            [
                'category' => 'VERBOTENE_PHRASEN',
                'severity' => 'MEDIUM',
                'description' => 'Agent uses forbidden phrases despite prompt rules',
                'examples' => [
                    '"Herr Schuster" (forbidden without gender)',
                    '"Technisches Problem" (forbidden phrase)',
                    '"Das System funktioniert nicht" (forbidden)'
                ],
                'frequency' => 'Occurs in ~5-10% of calls',
                'impact' => 'Unprofessional, reduces trust',
                'root_cause' => 'Single Prompt: LLM sometimes ignores rules buried in long prompt',
                'solution' => 'Conversation Flow: Node-specific prompts are shorter and more focused'
            ],
            [
                'category' => 'CACHE_COLLISIONS',
                'severity' => 'MEDIUM',
                'description' => 'Availability cache returns stale data',
                'examples' => [
                    'Agent offers 8:00 as "free"',
                    'But 8:00 was booked 2 hours ago',
                    'Cache not invalidated after booking'
                ],
                'frequency' => 'Occurs occasionally',
                'impact' => 'Double bookings possible',
                'root_cause' => 'Cache invalidation not triggered by Cal.com webhook',
                'solution' => 'Already fixed in V85, but Conversation Flow makes this more explicit'
            ],
            [
                'category' => 'LONG_DURATION',
                'severity' => 'MEDIUM',
                'description' => 'Szenario 3 takes 188s (too long)',
                'examples' => [
                    'Anonymous caller provides name',
                    '17s silence gap',
                    'Datum parsing errors',
                    'Multiple clarifications needed'
                ],
                'frequency' => 'Affects 9% of calls (Szenario 3)',
                'impact' => 'Poor UX, customer frustration',
                'root_cause' => 'Multiple issues compound: silence + hallucination + verbose prompts',
                'solution' => 'Conversation Flow: Fast name collection (Node 5), no silence gaps, better validation'
            ]
        ];
    }

    /**
     * Calculate baseline metrics
     */
    private function calculateBaselineMetrics(): array
    {
        $scenarios = $this->analyzeCallScenarios();

        // Calculate weighted average success rate
        $totalFrequency = 0;
        $weightedSuccess = 0;
        $totalDuration = 0;
        $totalCalls = 0;

        foreach ($scenarios as $key => $scenario) {
            if ($scenario['frequency'] > 0 && $scenario['success_rate'] !== null) {
                $totalFrequency += $scenario['frequency'];
                $weightedSuccess += $scenario['frequency'] * $scenario['success_rate'];

                if ($scenario['average_duration_seconds']) {
                    $totalDuration += $scenario['frequency'] * $scenario['average_duration_seconds'];
                    $totalCalls += $scenario['frequency'];
                }
            }
        }

        $overallSuccessRate = $totalFrequency > 0 ? $weightedSuccess / $totalFrequency : 0;
        $overallAvgDuration = $totalCalls > 0 ? $totalDuration / $totalCalls : 0;

        return [
            'overall_success_rate' => round($overallSuccessRate * 100, 1) . '%',
            'overall_success_rate_decimal' => round($overallSuccessRate, 3),
            'average_call_duration_seconds' => round($overallAvgDuration, 1),
            'critical_scenario' => 'scenario_4',
            'critical_scenario_success_rate' => '25%',
            'critical_scenario_abandon_rate' => '75%',
            'estimated_revenue_loss_per_month' => '€3,360',
            'reasoning' => 'Szenario 4 represents 35% of calls with only 25% success. Fixing this could add ~6 successful bookings/day.',
            'target_metrics' => [
                'overall_success_rate' => '83%',
                'scenario_1_success_rate' => '80%',
                'scenario_2_success_rate' => '85%',
                'scenario_3_success_rate' => '100%',
                'scenario_3_duration' => '45s',
                'scenario_4_success_rate' => '85%',
                'average_call_duration' => '48s'
            ]
        ];
    }

    /**
     * Save analysis to storage
     */
    private function saveAnalysis(array $analysis): void
    {
        $markdown = $this->convertToMarkdown($analysis);

        Storage::disk('local')->put(
            'conversation_flow/reports/baseline_analysis.md',
            $markdown
        );

        Storage::disk('local')->put(
            'conversation_flow/reports/baseline_analysis.json',
            json_encode($analysis, JSON_PRETTY_PRINT)
        );

        Log::info('System baseline analysis saved');
    }

    /**
     * Convert analysis to markdown
     */
    private function convertToMarkdown(array $analysis): string
    {
        $md = "# System Baseline Analysis - Conversation Flow Migration\n\n";
        $md .= "**Generated**: " . $analysis['timestamp'] . "\n";
        $md .= "**Purpose**: Establish baseline metrics before Conversation Flow migration\n\n";
        $md .= "---\n\n";

        // Current Agent Config
        $md .= "## Current Agent Configuration\n\n";
        $config = $analysis['current_agent_config'];

        if (isset($config['status']) && $config['status'] === 'unavailable') {
            $md .= "⚠️ Could not fetch live agent configuration\n\n";
        } else {
            $md .= "- **Agent ID**: " . ($config['agent_id'] ?? 'N/A') . "\n";
            $md .= "- **Agent Name**: " . ($config['agent_name'] ?? 'N/A') . "\n";
            $md .= "- **Model**: " . ($config['model'] ?? 'N/A') . "\n";
            $md .= "- **Prompt Type**: " . ($config['prompt']['type'] ?? 'N/A') . "\n";
            $md .= "- **Prompt Length**: " . ($config['prompt']['length'] ?? 0) . " characters\n";
            $md .= "- **Functions**: " . ($config['functions']['count'] ?? 0) . " configured\n\n";
        }

        // Call Scenarios
        $md .= "## Call Scenarios Analysis\n\n";
        foreach ($analysis['call_scenarios'] as $key => $scenario) {
            $md .= "### " . $scenario['name'] . "\n\n";
            $md .= "**Description**: " . $scenario['description'] . "\n\n";
            $md .= "**Frequency**: " . ($scenario['frequency'] * 100) . "%\n\n";

            if ($scenario['success_rate'] !== null) {
                $md .= "**Success Rate**: " . ($scenario['success_rate'] * 100) . "%\n\n";
            } else {
                $md .= "**Success Rate**: Not available\n\n";
            }

            if ($scenario['average_duration_seconds']) {
                $md .= "**Avg Duration**: " . $scenario['average_duration_seconds'] . "s\n\n";
            }

            $md .= "**Priority**: " . $scenario['priority'] . "\n\n";

            if (!empty($scenario['issues'])) {
                $md .= "**Issues**:\n";
                foreach ($scenario['issues'] as $issue) {
                    $md .= "- " . $issue . "\n";
                }
                $md .= "\n";
            }

            $md .= "---\n\n";
        }

        // Pain Points
        $md .= "## 🚨 Identified Pain Points\n\n";
        foreach ($analysis['pain_points'] as $painPoint) {
            $md .= "### " . $painPoint['category'] . " (Severity: " . $painPoint['severity'] . ")\n\n";
            $md .= $painPoint['description'] . "\n\n";
            $md .= "**Frequency**: " . $painPoint['frequency'] . "\n\n";
            $md .= "**Impact**: " . $painPoint['impact'] . "\n\n";
            $md .= "**Root Cause**: " . $painPoint['root_cause'] . "\n\n";
            $md .= "**Solution**: " . $painPoint['solution'] . "\n\n";
            $md .= "---\n\n";
        }

        // Baseline Metrics
        $md .= "## Baseline Metrics\n\n";
        $metrics = $analysis['baseline_metrics'];
        $md .= "### Current Performance\n\n";
        $md .= "- **Overall Success Rate**: " . $metrics['overall_success_rate'] . "\n";
        $md .= "- **Average Call Duration**: " . $metrics['average_call_duration_seconds'] . "s\n";
        $md .= "- **Critical Scenario**: " . $metrics['critical_scenario'] . "\n";
        $md .= "- **Critical Success Rate**: " . $metrics['critical_scenario_success_rate'] . "\n";
        $md .= "- **Critical Abandon Rate**: " . $metrics['critical_scenario_abandon_rate'] . " 🚨\n\n";

        $md .= "### Target Metrics (Post-Migration)\n\n";
        foreach ($metrics['target_metrics'] as $key => $value) {
            $md .= "- " . ucfirst(str_replace('_', ' ', $key)) . ": " . $value . "\n";
        }
        $md .= "\n";

        $md .= "### Revenue Impact\n\n";
        $md .= "**Estimated Monthly Loss**: " . $metrics['estimated_revenue_loss_per_month'] . "\n\n";
        $md .= $metrics['reasoning'] . "\n\n";

        return $md;
    }
}
