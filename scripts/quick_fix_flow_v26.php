#!/usr/bin/env php
<?php

/**
 * Quick Fix - Retell Conversation Flow V26
 * Adds alternative selection → booking flow with CORRECT schema
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Http;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$apiKey = config('services.retellai.api_key');
$baseUrl = config('services.retellai.base_url');
$conversationFlowId = 'conversation_flow_a58405e3f67a';

echo "🚀 Fetching current flow...\n";
$response = Http::withHeaders([
    'Authorization' => "Bearer {$apiKey}",
])->get("{$baseUrl}/get-conversation-flow/{$conversationFlowId}");

$flow = $response->json();
echo "✅ Flow V{$flow['version']} fetched\n\n";

// Add new extract node with CORRECT schema
$extractNode = [
    'id' => 'node_extract_alternative_selection',
    'name' => 'Alternative extrahieren',
    'type' => 'extract_dynamic_variables',  // ✅ PLURAL!
    'display_position' => ['x' => 3050, 'y' => -20],
    'variables' => [  // ✅ 'variables', not 'dynamic_variables'
        [
            'type' => 'text',
            'name' => 'selected_alternative_time',
            'description' => 'Die vom Kunden gewählte alternative Uhrzeit (z.B. "06:55", "14:30")'
        ]
    ],
    'edges' => [
        [
            'id' => 'edge_extract_to_confirm',
            'destination_node_id' => 'node_confirm_alternative',
            'transition_condition' => [
                'type' => 'equation',
                'equations' => [  // ✅ PLURAL!
                    [
                        'left' => 'selected_alternative_time',
                        'operator' => 'exists'
                    ]
                ]
            ]
        ]
    ]
];

// Add confirm node
$confirmNode = [
    'id' => 'node_confirm_alternative',
    'name' => 'Alternative bestätigen',
    'type' => 'conversation',
    'display_position' => ['x' => 3400, 'y' => -20],
    'instruction' => [
        'type' => 'static',
        'text' => 'Perfekt! Einen Moment, ich buche das für Sie...'
    ],
    'edges' => [
        [
            'id' => 'edge_confirm_to_book',
            'destination_node_id' => 'func_book_appointment',
            'transition_condition' => [
                'type' => 'equation',
                'equations' => [
                    [
                        'left' => 'selected_alternative_time',
                        'operator' => 'exists'
                    ]
                ]
            ]
        ]
    ]
];

// Find and update node_present_result
$presentNode = collect($flow['nodes'])->firstWhere('id', 'node_present_result');
$presentNode['edges'] = array_merge(
    [
        [
            'id' => 'edge_present_to_extract',
            'destination_node_id' => 'node_extract_alternative_selection',
            'transition_condition' => [
                'type' => 'prompt',
                'prompt' => 'User selected one of the presented alternative time slots (e.g., "Um 06:55", "Den ersten Termin", "14:30")'
            ]
        ]
    ],
    $presentNode['edges']
);

// Update func_book_appointment parameter mapping
$bookNode = collect($flow['nodes'])->firstWhere('id', 'func_book_appointment');
$bookNode['parameters']['uhrzeit'] = '{{selected_alternative_time | appointment_time}}';

// Build updated flow
$updatedFlow = $flow;
$updatedFlow['nodes'] = collect($flow['nodes'])
    ->map(function($node) use ($presentNode, $bookNode) {
        if ($node['id'] === 'node_present_result') return $presentNode;
        if ($node['id'] === 'func_book_appointment') return $bookNode;
        return $node;
    })
    ->push($extractNode)
    ->push($confirmNode)
    ->values()
    ->toArray();

$updatedFlow['version'] = $flow['version'] + 1;

echo "📦 Updating flow to V{$updatedFlow['version']}...\n";
echo "   Added nodes: node_extract_alternative_selection, node_confirm_alternative\n";
echo "   Modified: node_present_result, func_book_appointment\n\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer {$apiKey}",
    'Content-Type' => 'application/json',
])->patch("{$baseUrl}/update-conversation-flow/{$conversationFlowId}", $updatedFlow);

if (!$response->successful()) {
    echo "❌ Failed: HTTP {$response->status()}\n";
    echo $response->body() . "\n";
    exit(1);
}

echo "✅ Flow updated successfully to V{$updatedFlow['version']}!\n\n";
echo "🔄 Next: Publish agent to activate V{$updatedFlow['version']}\n";
