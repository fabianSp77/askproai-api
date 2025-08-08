<?php

$apiKey = getenv('NOTION_API_KEY') ?: 'YOUR_NOTION_API_KEY_HERE';
$parentPageId = getenv('NOTION_PARENT_PAGE_ID') ?: 'YOUR_PARENT_PAGE_ID_HERE';

$url = 'https://api.notion.com/v1/pages';

$data = [
    'parent' => [
        'type' => 'page_id',
        'page_id' => $parentPageId
    ],
    'properties' => [
        'title' => [
            'title' => [[
                'type' => 'text',
                'text' => ['content' => '🚀 Retell.ai MCP Migration Guide - AskProAI']
            ]]
        ]
    ],
    'children' => [
        [
            'object' => 'block',
            'type' => 'callout',
            'callout' => [
                'rich_text' => [[
                    'type' => 'text',
                    'text' => ['content' => '✅ Production Ready | Agent: agent_9a8202a740cd3120d96fcfda1e']
                ]],
                'icon' => ['emoji' => '🚀']
            ]
        ],
        [
            'object' => 'block',
            'type' => 'heading_1',
            'heading_1' => [
                'rich_text' => [[
                    'type' => 'text',
                    'text' => ['content' => '⚡ 15-Minuten Quick Setup']
                ]]
            ]
        ],
        [
            'object' => 'block',
            'type' => 'paragraph',
            'paragraph' => [
                'rich_text' => [[
                    'type' => 'text',
                    'text' => ['content' => 'Diese Anleitung zeigt Ihnen, wie Sie in 15 Minuten von Webhooks zu MCP migrieren.']
                ]]
            ]
        ],
        [
            'object' => 'block',
            'type' => 'heading_2',
            'heading_2' => [
                'rich_text' => [[
                    'type' => 'text',
                    'text' => ['content' => '1️⃣ Token generieren']
                ]]
            ]
        ],
        [
            'object' => 'block',
            'type' => 'code',
            'code' => [
                'rich_text' => [[
                    'type' => 'text',
                    'text' => ['content' => 'openssl rand -hex 32']
                ]],
                'language' => 'bash'
            ]
        ],
        [
            'object' => 'block',
            'type' => 'heading_2',
            'heading_2' => [
                'rich_text' => [[
                    'type' => 'text',
                    'text' => ['content' => '2️⃣ Retell.ai konfigurieren']
                ]]
            ]
        ],
        [
            'object' => 'block',
            'type' => 'callout',
            'callout' => [
                'rich_text' => [[
                    'type' => 'text',
                    'text' => ['content' => '📋 MCP URL: https://api.askproai.de/api/mcp/retell/tools']
                ]],
                'icon' => ['emoji' => '🔗']
            ]
        ],
        [
            'object' => 'block',
            'type' => 'code',
            'code' => [
                'rich_text' => [[
                    'type' => 'text',
                    'text' => ['content' => '{"Authorization": "Bearer IHR_TOKEN", "Content-Type": "application/json", "X-Agent-ID": "{{agent_id}}", "X-Call-ID": "{{call_id}}"}']
                ]],
                'language' => 'json'
            ]
        ],
        [
            'object' => 'block',
            'type' => 'heading_2',
            'heading_2' => [
                'rich_text' => [[
                    'type' => 'text',
                    'text' => ['content' => '3️⃣ Tools aktivieren (ALLE 5)']
                ]]
            ]
        ],
        [
            'object' => 'block',
            'type' => 'bulleted_list_item',
            'bulleted_list_item' => [
                'rich_text' => [[
                    'type' => 'text',
                    'text' => ['content' => '✅ getCurrentTimeBerlin'],
                    'annotations' => ['code' => true]
                ]]
            ]
        ],
        [
            'object' => 'block',
            'type' => 'bulleted_list_item',
            'bulleted_list_item' => [
                'rich_text' => [[
                    'type' => 'text',
                    'text' => ['content' => '✅ checkAvailableSlots'],
                    'annotations' => ['code' => true]
                ]]
            ]
        ],
        [
            'object' => 'block',
            'type' => 'bulleted_list_item',
            'bulleted_list_item' => [
                'rich_text' => [[
                    'type' => 'text',
                    'text' => ['content' => '✅ bookAppointment'],
                    'annotations' => ['code' => true]
                ]]
            ]
        ],
        [
            'object' => 'block',
            'type' => 'bulleted_list_item',
            'bulleted_list_item' => [
                'rich_text' => [[
                    'type' => 'text',
                    'text' => ['content' => '✅ getCustomerInfo'],
                    'annotations' => ['code' => true]
                ]]
            ]
        ],
        [
            'object' => 'block',
            'type' => 'bulleted_list_item',
            'bulleted_list_item' => [
                'rich_text' => [[
                    'type' => 'text',
                    'text' => ['content' => '✅ endCallSession'],
                    'annotations' => ['code' => true]
                ]]
            ]
        ],
        [
            'object' => 'block',
            'type' => 'heading_2',
            'heading_2' => [
                'rich_text' => [[
                    'type' => 'text',
                    'text' => ['content' => '4️⃣ System Prompt anpassen']
                ]]
            ]
        ],
        [
            'object' => 'block',
            'type' => 'code',
            'code' => [
                'rich_text' => [[
                    'type' => 'text',
                    'text' => ['content' => "ALT: nutze die Funktion 'current_time_berlin'\nNEU: nutze das MCP Tool 'getCurrentTimeBerlin'"]
                ]],
                'language' => 'plain text'
            ]
        ],
        [
            'object' => 'block',
            'type' => 'divider',
            'divider' => new stdClass()
        ],
        [
            'object' => 'block',
            'type' => 'heading_1',
            'heading_1' => [
                'rich_text' => [[
                    'type' => 'text',
                    'text' => ['content' => '📊 Performance Vergleich']
                ]]
            ]
        ],
        [
            'object' => 'block',
            'type' => 'paragraph',
            'paragraph' => [
                'rich_text' => [[
                    'type' => 'text',
                    'text' => ['content' => '🐌 Webhook (Alt): 2-3 Sekunden Latenz']
                ]]
            ]
        ],
        [
            'object' => 'block',
            'type' => 'paragraph',
            'paragraph' => [
                'rich_text' => [[
                    'type' => 'text',
                    'text' => ['content' => '⚡ MCP (Neu): <500ms Latenz (80% schneller!)']
                ]]
            ]
        ],
        [
            'object' => 'block',
            'type' => 'heading_1',
            'heading_1' => [
                'rich_text' => [[
                    'type' => 'text',
                    'text' => ['content' => '🔗 Admin & Monitoring']
                ]]
            ]
        ],
        [
            'object' => 'block',
            'type' => 'callout',
            'callout' => [
                'rich_text' => [[
                    'type' => 'text',
                    'text' => ['content' => '📊 Admin Panel: https://api.askproai.de/admin/mcp-configuration']
                ]],
                'icon' => ['emoji' => '🔗']
            ]
        ],
        [
            'object' => 'block',
            'type' => 'divider',
            'divider' => new stdClass()
        ],
        [
            'object' => 'block',
            'type' => 'callout',
            'callout' => [
                'rich_text' => [[
                    'type' => 'text',
                    'text' => ['content' => '🎉 Gratulation! Nach diesen Schritten läuft Ihr Agent mit MCP - 80% schneller als vorher!']
                ]],
                'icon' => ['emoji' => '🎉'],
                'color' => 'green_background'
            ]
        ]
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json',
    'Notion-Version: 2022-06-28'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($httpCode === 200) {
    $result = json_decode($response, true);
    $pageId = $result['id'] ?? '';
    $pageUrl = $result['url'] ?? '';
    
    echo "\n✅ NOTION-SEITE ERFOLGREICH ERSTELLT!\n";
    echo "=====================================\n\n";
    echo "📄 Page ID: " . $pageId . "\n";
    echo "🔗 Notion URL: " . $pageUrl . "\n\n";
    
    $cleanId = str_replace('-', '', $pageId);
    echo "🌐 DIREKTER LINK:\n";
    echo "   https://www.notion.so/" . $cleanId . "\n\n";
    
} else {
    echo "\n❌ Fehler beim Erstellen der Notion-Seite\n";
    echo "HTTP Code: " . $httpCode . "\n";
    if ($error) {
        echo "CURL Error: " . $error . "\n";
    }
    echo "Response: " . $response . "\n\n";
    
    $errorData = json_decode($response, true);
    if (isset($errorData['message'])) {
        echo "Error Message: " . $errorData['message'] . "\n";
    }
}