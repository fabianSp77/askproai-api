<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ConversationFlowController extends Controller
{
    /**
     * Download complete conversation flow as JSON for Retell import
     */
    public function downloadJson()
    {
        $nodeGraphPath = 'conversation_flow/graphs/node_graph.json';

        if (!Storage::disk('local')->exists($nodeGraphPath)) {
            abort(404, 'Conversation flow not found. Please generate it first.');
        }

        $nodeGraph = json_decode(Storage::disk('local')->get($nodeGraphPath), true);

        // Transform to Retell import format
        $retellFormat = $this->transformToRetellImportFormat($nodeGraph);

        $filename = 'retell_conversation_flow_' . date('Y-m-d_His') . '.json';

        return response()->json($retellFormat, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Download setup guide
     */
    public function downloadGuide()
    {
        $guidePath = 'conversation_flow/DASHBOARD_SETUP_GUIDE.md';

        if (!Storage::disk('local')->exists($guidePath)) {
            abort(404, 'Setup guide not found.');
        }

        $content = Storage::disk('local')->get($guidePath);
        $filename = 'Retell_Setup_Guide_' . date('Y-m-d') . '.md';

        return response($content, 200, [
            'Content-Type' => 'text/markdown',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * View reports dashboard
     */
    public function viewReports()
    {
        $reports = [
            'research_validation' => null,
            'baseline_analysis' => null,
            'migration_report' => null,
        ];

        // Load research validation
        if (Storage::disk('local')->exists('conversation_flow/reports/research_validation_report.md')) {
            $reports['research_validation'] = Storage::disk('local')->get('conversation_flow/reports/research_validation_report.md');
        }

        // Load baseline analysis
        if (Storage::disk('local')->exists('conversation_flow/reports/baseline_analysis.md')) {
            $reports['baseline_analysis'] = Storage::disk('local')->get('conversation_flow/reports/baseline_analysis.md');
        }

        // Load migration report
        if (Storage::disk('local')->exists('conversation_flow/reports/MIGRATION_AGENT_REPORT.md')) {
            $reports['migration_report'] = Storage::disk('local')->get('conversation_flow/reports/MIGRATION_AGENT_REPORT.md');
        }

        return view('conversation-flow.reports', compact('reports'));
    }

    /**
     * Transform node graph to Retell import format
     */
    private function transformToRetellImportFormat(array $nodeGraph): array
    {
        $nodes = [];

        foreach ($nodeGraph['nodes'] as $nodeId => $node) {
            $transformedNode = [
                'id' => $nodeId,
                'name' => $node['name'],
                'type' => 'conversation',
                'instruction' => [
                    'type' => 'prompt',
                    'text' => $node['prompt'] ?? $node['description']
                ],
                'edges' => []
            ];

            // Add transitions
            foreach ($nodeGraph['transitions'] as $transition) {
                if ($transition['from'] === $nodeId) {
                    $edge = [
                        'destination_node_id' => $transition['to'],
                        'condition' => $transition['condition']
                    ];
                    $transformedNode['edges'][] = $edge;
                }
            }

            $nodes[] = $transformedNode;
        }

        return [
            'version' => '1.0',
            'generated_at' => now()->toIso8601String(),
            'agent_type' => 'conversation_flow',
            'model' => 'gpt-4o-mini',
            'temperature' => 0.3,
            'start_node_id' => 'node_01_initialization',
            'start_speaker' => 'agent',
            'global_prompt' => $this->getGlobalPrompt(),
            'nodes' => $nodes,
            'metadata' => [
                'total_nodes' => count($nodes),
                'total_transitions' => count($nodeGraph['transitions']),
                'designed_for' => 'AskPro AI Appointment Booking',
                'expected_improvements' => [
                    'success_rate' => '52.1% → 83% (+26 pp)',
                    'scenario_4' => '25% → 85% (+60 pp)',
                    'hallucinations' => '-70%',
                    'revenue_monthly' => '+€3,360'
                ]
            ]
        ];
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
}
