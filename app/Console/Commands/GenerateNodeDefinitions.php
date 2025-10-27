<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateNodeDefinitions extends Command
{
    protected $signature = 'conversation-flow:generate-node-guide';
    protected $description = 'Generate step-by-step guide for creating nodes in Retell Dashboard';

    public function handle()
    {
        $this->info('🎯 Generating Node Creation Guide for Retell Dashboard');
        $this->newLine();

        // Load node graph
        $nodeGraphPath = 'conversation_flow/graphs/node_graph.json';
        if (!Storage::disk('local')->exists($nodeGraphPath)) {
            $this->error("Node graph not found!");
            return Command::FAILURE;
        }

        $nodeGraph = json_decode(Storage::disk('local')->get($nodeGraphPath), true);

        // Generate markdown guide
        $guide = $this->generateGuide($nodeGraph);

        // Save guide
        Storage::disk('local')->put(
            'conversation_flow/DASHBOARD_SETUP_GUIDE.md',
            $guide
        );

        $guidePath = storage_path('app/private/conversation_flow/DASHBOARD_SETUP_GUIDE.md');

        $this->info('✅ Guide generated successfully!');
        $this->newLine();
        $this->info('📄 Location: ' . $guidePath);
        $this->newLine();
        $this->info('Next: Open the guide and follow the steps to create nodes in Retell Dashboard');

        // Show preview
        $this->newLine();
        $this->line(substr($guide, 0, 500) . '...');

        return Command::SUCCESS;
    }

    private function generateGuide(array $nodeGraph): string
    {
        $md = "# Retell Dashboard - Conversation Flow Setup Guide\n\n";
        $md .= "**Generated**: " . now()->toIso8601String() . "\n";
        $md .= "**Agent ID**: agent_616d645570ae613e421edb98e7\n";
        $md .= "**Total Nodes**: 17\n";
        $md .= "**Model**: gpt-4o-mini\n\n";
        $md .= "---\n\n";

        $md .= "## Quick Start\n\n";
        $md .= "1. Go to: https://dashboard.retellai.com/agents/agent_616d645570ae613e421edb98e7\n";
        $md .= "2. Click \"Edit\" (if Single Prompt) or start with Conversation Flow\n";
        $md .= "3. Follow steps below to create each node\n\n";
        $md .= "---\n\n";

        $md .= "## Global Settings\n\n";
        $md .= "### Model Configuration\n";
        $md .= "- **Model**: gpt-4o-mini\n";
        $md .= "- **Temperature**: 0.3\n";
        $md .= "- **Start Speaker**: Agent\n";
        $md .= "- **Start Node**: node_01_initialization\n\n";

        $md .= "### Global Prompt\n";
        $md .= "```\n";
        $md .= $this->getGlobalPrompt();
        $md .= "\n```\n\n";
        $md .= "---\n\n";

        $md .= "## Node Definitions\n\n";

        $nodeCounter = 1;
        foreach ($nodeGraph['nodes'] as $nodeId => $node) {
            $md .= "### Node {$nodeCounter}: {$node['name']}\n\n";
            $md .= "**ID**: `{$nodeId}`\n";
            $md .= "**Type**: Conversation\n\n";

            $md .= "**Instruction/Prompt**:\n```\n";
            $md .= $node['prompt'] ?? $node['description'];
            $md .= "\n```\n\n";

            // Add transitions
            $transitions = $this->getNodeTransitions($nodeId, $nodeGraph['transitions']);
            if (!empty($transitions)) {
                $md .= "**Transitions (Edges)**:\n\n";
                foreach ($transitions as $idx => $trans) {
                    $md .= ($idx + 1) . ". → `{$trans['to']}`\n";
                    $md .= "   - Condition: {$trans['condition']}\n";
                }
                $md .= "\n";
            }

            $md .= "---\n\n";
            $nodeCounter++;
        }

        $md .= "## Important Notes\n\n";
        $md .= "- **Node 01**: Must call `current_time_berlin` and `check_customer` functions\n";
        $md .= "- **Node 08**: Calls `collect_appointment_data` with `bestaetigung=false`\n";
        $md .= "- **Node 09c**: Calls `collect_appointment_data` with `bestaetigung=true`\n";
        $md .= "- **Node 15**: Handles race conditions from V85\n\n";

        $md .= "## Function Configuration\n\n";
        $md .= "Make sure these functions are configured in your agent:\n";
        $md .= "- `current_time_berlin`\n";
        $md .= "- `check_customer`\n";
        $md .= "- `collect_appointment_data`\n\n";

        return $md;
    }

    private function getNodeTransitions(string $nodeId, array $allTransitions): array
    {
        $result = [];
        foreach ($allTransitions as $trans) {
            if ($trans['from'] === $nodeId) {
                $result[] = $trans;
            }
        }
        return $result;
    }

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

## Date Parsing Rules
- "15.1" means 15th of CURRENT month, NOT January
- Parse relative dates: "morgen" = tomorrow

## V85 Race Condition Protection
- STEP 1: collect_appointment_data with bestaetigung=false (check only)
- STEP 2: Get explicit user confirmation
- STEP 3: collect_appointment_data with bestaetigung=true (book)

## Anti-Silence Rule
- Speak within 2 seconds
- Never let silence exceed 3 seconds
PROMPT;
    }
}
