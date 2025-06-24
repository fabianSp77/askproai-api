<?php

// Test MCP webhook processing with call_analyzed event

// Use the actual webhook structure from Retell
$webhookData = [
    'event' => 'call_analyzed',
    'call' => [
        'call_id' => 'call_eb0b1d152db747dc21249f3996b',
        'from_number' => '+491604366218',
        'to_number' => '+493083793369',
        'call_analysis' => [
            'call_successful' => true,
            'custom_analysis_data' => [
                '_datum__termin' => 23062023,
                '_uhrzeit__termin' => 14,
                '_name' => 'Marc Schuster'
            ]
        ],
        'transcript_with_tool_calls' => [
            [
                'name' => 'collect_appointment_data',
                'arguments' => json_encode([
                    'datum' => '23.06.',
                    'uhrzeit' => '14:00',
                    'name' => 'Marc Schuster',
                    'dienstleistung' => 'Termin'
                ])
            ]
        ]
    ]
];

// Send to webhook endpoint
$ch = curl_init('http://localhost/api/retell/mcp-webhook');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($webhookData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Correlation-Id: ' . uniqid('test-', true)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";