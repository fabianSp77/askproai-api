<?php

$apiKey = getenv('NOTION_API_KEY') ?: 'YOUR_NOTION_API_KEY_HERE';
$parentPageId = getenv('NOTION_PARENT_PAGE_ID') ?: 'YOUR_PARENT_PAGE_ID_HERE';

// Notion API endpoint
$url = 'https://api.notion.com/v1/pages';

// Page content
$data = [
    'parent' => [
        'type' => 'page_id',
        'page_id' => $parentPageId
    ],
    'properties' => [
        'title' => [
            'title' => [
                [
                    'type' => 'text',
                    'text' => [
                        'content' => '🚀 Retell.ai MCP Migration Guide - AskProAI'
                    ]
                ]
            ]
        ]
    ],
    'children' => [
        // Callout Block
        [
            'object' => 'block',
            'type' => 'callout',
            'callout' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => '✅ Status: Production Ready | Version: 1.0.0 | Agent: agent_9a8202a740cd3120d96fcfda1e'
                        ]
                    ]
                ],
                'icon' => [
                    'emoji' => '🚀'
                ]
            ]
        ],
        // Divider
        [
            'object' => 'block',
            'type' => 'divider',
            'divider' => new stdClass()
        ],
        // Heading 1
        [
            'object' => 'block',
            'type' => 'heading_1',
            'heading_1' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => '📌 Quick Links'
                        ]
                    ]
                ]
            ]
        ],
        // Bullet List
        [
            'object' => 'block',
            'type' => 'bulleted_list_item',
            'bulleted_list_item' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => '⚡ 15-Minuten Quick Setup'
                        ]
                    ]
                ]
            ]
        ],
        [
            'object' => 'block',
            'type' => 'bulleted_list_item',
            'bulleted_list_item' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => '📋 Vollständige Anleitung'
                        ]
                    ]
                ]
            ]
        ],
        [
            'object' => 'block',
            'type' => 'bulleted_list_item',
            'bulleted_list_item' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => '🆘 Troubleshooting'
                        ]
                    }
                ]
            ]
        ],
        // Divider
        [
            'object' => 'block',
            'type' => 'divider',
            'divider' => new stdClass()
        ],
        // Heading 1
        [
            'object' => 'block',
            'type' => 'heading_1',
            'heading_1' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => '🎯 Was ist MCP?'
                        ]
                    ]
                ]
            ]
        ],
        // Paragraph
        [
            'object' => 'block',
            'type' => 'paragraph',
            'paragraph' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => 'MCP (Model Context Protocol) ermöglicht '
                        ]
                    ],
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => 'direkte Echtzeit-Kommunikation'
                        ],
                        'annotations' => [
                            'bold' => true
                        ]
                    },
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => ' zwischen Retell.ai und Ihrer Middleware - ohne Webhook-Verzögerungen!'
                        ]
                    ]
                ]
            ]
        ],
        // Performance Comparison
        [
            'object' => 'block',
            'type' => 'heading_2',
            'heading_2' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => 'Performance-Vergleich'
                        ]
                    }
                ]
            ]
        ],
        [
            'object' => 'block',
            'type' => 'paragraph',
            'paragraph' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => '🐌 Webhooks (Alt): 2-3 Sekunden Latenz, 95% Erfolgsrate'
                        ]
                    }
                ]
            ]
        ],
        [
            'object' => 'block',
            'type' => 'paragraph',
            'paragraph' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => '⚡ MCP (Neu): <500ms Latenz, 99%+ Erfolgsrate'
                        ]
                    }
                ]
            ]
        ],
        // Divider
        [
            'object' => 'block',
            'type' => 'divider',
            'divider' => new stdClass()
        ],
        // Quick Setup
        [
            'object' => 'block',
            'type' => 'heading_1',
            'heading_1' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => '⚡ Quick Setup (15 Minuten)'
                        ]
                    }
                ]
            ]
        ],
        // Step 1
        [
            'object' => 'block',
            'type' => 'heading_2',
            'heading_2' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => 'Schritt 1: Token generieren'
                        ]
                    }
                ]
            ]
        ],
        [
            'object' => 'block',
            'type' => 'code',
            'code' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => 'openssl rand -hex 32'
                        ]
                    }
                ],
                'language' => 'bash'
            ]
        ],
        // Step 2
        [
            'object' => 'block',
            'type' => 'heading_2',
            'heading_2' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => 'Schritt 2: Retell.ai konfigurieren'
                        ]
                    ]
                ]
            ]
        ],
        // MCP URL Callout
        [
            'object' => 'block',
            'type' => 'callout',
            'callout' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => '📋 Copy & Paste URL: '
                        ]
                    },
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => 'https://api.askproai.de/api/mcp/retell/tools'
                        ],
                        'annotations' => [
                            'code' => true
                        ]
                    }
                ],
                'icon' => [
                    'emoji' => '🔗'
                ]
            ]
        ],
        // Headers Code Block
        [
            'object' => 'block',
            'type' => 'code',
            'code' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => '{\n  "Authorization": "Bearer IHR_TOKEN_HIER",\n  "Content-Type": "application/json",\n  "X-Agent-ID": "{{agent_id}}",\n  "X-Call-ID": "{{call_id}}"\n}'
                        ]
                    }
                ],
                'language' => 'json'
            ]
        ],
        // Tools Section
        [
            'object' => 'block',
            'type' => 'heading_2',
            'heading_2' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => 'Schritt 3: Tools aktivieren (ALLE 5)'
                        ]
                    }
                ]
            ]
        ],
        [
            'object' => 'block',
            'type' => 'bulleted_list_item',
            'bulleted_list_item' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => '✅ getCurrentTimeBerlin'
                        ],
                        'annotations' => [
                            'code' => true
                        ]
                    }
                ]
            ]
        ],
        [
            'object' => 'block',
            'type' => 'bulleted_list_item',
            'bulleted_list_item' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => '✅ checkAvailableSlots'
                        ],
                        'annotations' => [
                            'code' => true
                        ]
                    }
                ]
            ]
        ],
        [
            'object' => 'block',
            'type' => 'bulleted_list_item',
            'bulleted_list_item' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => '✅ bookAppointment'
                        ],
                        'annotations' => [
                            'code' => true
                        ]
                    }
                ]
            ]
        ],
        [
            'object' => 'block',
            'type' => 'bulleted_list_item',
            'bulleted_list_item' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => '✅ getCustomerInfo'
                        ],
                        'annotations' => [
                            'code' => true
                        ]
                    }
                ]
            ]
        ],
        [
            'object' => 'block',
            'type' => 'bulleted_list_item',
            'bulleted_list_item' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => '✅ endCallSession'
                        ],
                        'annotations' => [
                            'code' => true
                        ]
                    }
                ]
            ]
        ],
        // System Prompt Changes
        [
            'object' => 'block',
            'type' => 'heading_2',
            'heading_2' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => 'Schritt 4: System Prompt anpassen'
                        ]
                    }
                ]
            ]
        ],
        [
            'object' => 'block',
            'type' => 'code',
            'code' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => "# ALT (deaktivieren):\nnutze die Funktion 'current_time_berlin'\nnutze die Funktion 'collect_appointment_data'\nnutze die Funktion 'end_call'\n\n# NEU (aktivieren):\nnutze das MCP Tool 'getCurrentTimeBerlin'\nnutze das MCP Tool 'bookAppointment'\nnutze das MCP Tool 'endCallSession'"
                        ]
                    }
                ],
                'language' => 'text'
            ]
        ],
        // Monitoring
        [
            'object' => 'block',
            'type' => 'divider',
            'divider' => new stdClass()
        ],
        [
            'object' => 'block',
            'type' => 'heading_1',
            'heading_1' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => '📊 Monitoring & Admin'
                        ]
                    ]
                ]
            ]
        ],
        [
            'object' => 'block',
            'type' => 'callout',
            'callout' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => '🔗 Admin Panel: https://api.askproai.de/admin/mcp-configuration'
                        ]
                    }
                ],
                'icon' => [
                    'emoji' => '📊'
                ]
            ]
        ],
        // Success Message
        [
            'object' => 'block',
            'type' => 'divider',
            'divider' => new stdClass()
        ],
        [
            'object' => 'block',
            'type' => 'callout',
            'callout' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => '🎉 Gratulation! Nach diesen Schritten haben Sie erfolgreich von Webhooks zu MCP migriert und erzielen 80% schnellere Response-Zeiten!'
                        ]
                    ]
                ],
                'icon' => [
                    'emoji' => '🎉'
                ],
                'color' => 'green_background'
            ]
        ]
    ]
];

// Make the API request
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json',
    'Notion-Version: 2022-06-28'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $result = json_decode($response, true);
    $pageId = $result['id'] ?? '';
    $pageUrl = $result['url'] ?? '';
    
    echo "\n✅ NOTION-SEITE ERFOLGREICH ERSTELLT!\n";
    echo "=====================================\n\n";
    echo "📄 Page ID: " . $pageId . "\n";
    echo "🔗 Notion URL: " . $pageUrl . "\n\n";
    
    // Generate direct links
    $cleanId = str_replace('-', '', $pageId);
    echo "🌐 Direkter Link:\n";
    echo "   https://www.notion.so/" . $cleanId . "\n\n";
    echo "🌐 Alternativer Link:\n";
    echo "   https://www.notion.so/Retell-ai-MCP-Migration-Guide-AskProAI-" . $cleanId . "\n\n";
    
    echo "✨ Die Dokumentation ist jetzt in Ihrem Notion Workspace verfügbar!\n\n";
    
} else {
    echo "\n❌ Fehler beim Erstellen der Notion-Seite\n";
    echo "HTTP Code: " . $httpCode . "\n";
    echo "Response: " . $response . "\n\n";
    
    $error = json_decode($response, true);
    if (isset($error['message'])) {
        echo "Error Message: " . $error['message'] . "\n";
    }
}