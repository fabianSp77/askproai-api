<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$notionApiKey = env('NOTION_API_KEY');
$notionVersion = '2022-06-28';

// Parent page ID for Technical Documentation (you'll need to provide this)
$parentPageId = '9e3ebdb4-1abc-4c94-b95d-f8e7db0d8b9f'; // Replace with actual parent page ID

// Documentation structure
$documentationStructure = [
    'Cal.com Integration' => [
        'files' => [
            'CALCOM_V2_API_DOCUMENTATION.md' => 'Cal.com V2 API Documentation',
            'CALCOM_MCP_SERVER_API.md' => 'Cal.com MCP Server API',
            'docs/notion-ready/CALCOM_INTEGRATION_GUIDE.md' => 'Cal.com Integration Guide',
            'docs/notion-ready/CALCOM_WEBHOOK_SETUP.md' => 'Cal.com Webhook Setup',
            'docs/notion-ready/CALCOM_ERROR_HANDLING.md' => 'Cal.com Error Handling',
            'docs/notion-ready/CALCOM_BOOKING_FLOW.md' => 'Cal.com Booking Flow',
            'docs/notion-ready/CALCOM_MONITORING.md' => 'Cal.com Monitoring',
            'docs/notion-ready/CALCOM_TROUBLESHOOTING.md' => 'Cal.com Troubleshooting'
        ]
    ],
    'Email System' => [
        'files' => [
            'docs/notion-ready/EMAIL_SYSTEM_COMPLETE.md' => 'Email System Complete Guide',
            'docs/notion-ready/EMAIL_CONFIGURATION.md' => 'Email Configuration',
            'docs/notion-ready/EMAIL_TEMPLATES.md' => 'Email Templates',
            'docs/notion-ready/EMAIL_TROUBLESHOOTING.md' => 'Email Troubleshooting'
        ]
    ],
    'CI/CD Pipeline' => [
        'files' => [
            'CI_CD_PIPELINE_DOCUMENTATION.md' => 'CI/CD Pipeline Documentation',
            'CI_CD_BEST_PRACTICES.md' => 'CI/CD Best Practices',
            'DEPLOYMENT_TROUBLESHOOTING_GUIDE.md' => 'Deployment Troubleshooting',
            'DEVELOPER_WORKFLOW_GUIDE.md' => 'Developer Workflow Guide'
        ]
    ],
    'Infrastructure Guide' => [
        'files' => [
            'DEVOPS_MANUAL.md' => 'DevOps Manual',
            'docs/notion-ready/INFRASTRUCTURE_ARCHITECTURE.md' => 'Infrastructure Architecture',
            'docs/notion-ready/SERVER_CONFIGURATION.md' => 'Server Configuration',
            'docs/notion-ready/SECURITY_HARDENING.md' => 'Security Hardening',
            'EMERGENCY_PROCEDURES.md' => 'Emergency Procedures'
        ]
    ],
    'Queue & Horizon' => [
        'files' => [
            'docs/notion-ready/QUEUE_HORIZON_GUIDE.md' => 'Queue & Horizon Complete Guide',
            'docs/notion-ready/QUEUE_CONFIGURATION.md' => 'Queue Configuration',
            'docs/notion-ready/HORIZON_MONITORING.md' => 'Horizon Monitoring',
            'QUEUE_MCP_IMPLEMENTATION_COMPLETE.md' => 'Queue MCP Implementation'
        ]
    ],
    'Documentation Standards' => [
        'files' => [
            'DOCUMENTATION_VISUAL_HIERARCHY.md' => 'Documentation Visual Hierarchy',
            'DOCUMENTATION_FRAMEWORK.md' => 'Documentation Framework',
            'docs/templates/INTEGRATION_TEMPLATE.md' => 'Integration Template',
            'docs/templates/SERVICE_TEMPLATE.md' => 'Service Template',
            'docs/templates/TROUBLESHOOTING_TEMPLATE.md' => 'Troubleshooting Template',
            'docs/templates/API_DOCUMENTATION_TEMPLATE.md' => 'API Documentation Template'
        ]
    ]
];

function createNotionPage($title, $content, $parentId, $apiKey, $version) {
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $apiKey,
        'Content-Type' => 'application/json',
        'Notion-Version' => $version
    ])->post('https://api.notion.com/v1/pages', [
        'parent' => [
            'type' => 'page_id',
            'page_id' => $parentId
        ],
        'properties' => [
            'title' => [
                [
                    'type' => 'text',
                    'text' => [
                        'content' => $title
                    ]
                ]
            ]
        ],
        'children' => convertMarkdownToNotionBlocks($content)
    ]);

    if ($response->successful()) {
        $data = $response->json();
        return [
            'success' => true,
            'url' => $data['url'] ?? 'https://notion.so/' . str_replace('-', '', $data['id'])
        ];
    }

    return [
        'success' => false,
        'error' => $response->body()
    ];
}

function convertMarkdownToNotionBlocks($markdown) {
    $blocks = [];
    $lines = explode("\n", $markdown);
    $currentCodeBlock = null;

    foreach ($lines as $line) {
        // Handle code blocks
        if (preg_match('/^```(.*)$/', $line, $matches)) {
            if ($currentCodeBlock === null) {
                $currentCodeBlock = [
                    'language' => $matches[1] ?: 'plain text',
                    'content' => []
                ];
            } else {
                // End of code block
                $blocks[] = [
                    'object' => 'block',
                    'type' => 'code',
                    'code' => [
                        'rich_text' => [[
                            'type' => 'text',
                            'text' => [
                                'content' => implode("\n", $currentCodeBlock['content'])
                            ]
                        ]],
                        'language' => $currentCodeBlock['language']
                    ]
                ];
                $currentCodeBlock = null;
            }
            continue;
        }

        // If in code block, add line to content
        if ($currentCodeBlock !== null) {
            $currentCodeBlock['content'][] = $line;
            continue;
        }

        // Handle headers
        if (preg_match('/^(#{1,3})\s+(.+)$/', $line, $matches)) {
            $level = strlen($matches[1]);
            $text = $matches[2];
            $type = $level === 1 ? 'heading_1' : ($level === 2 ? 'heading_2' : 'heading_3');
            
            $blocks[] = [
                'object' => 'block',
                'type' => $type,
                $type => [
                    'rich_text' => [[
                        'type' => 'text',
                        'text' => ['content' => $text]
                    ]]
                ]
            ];
            continue;
        }

        // Handle bullet points
        if (preg_match('/^[-*]\s+(.+)$/', $line, $matches)) {
            $blocks[] = [
                'object' => 'block',
                'type' => 'bulleted_list_item',
                'bulleted_list_item' => [
                    'rich_text' => [[
                        'type' => 'text',
                        'text' => ['content' => $matches[1]]
                    ]]
                ]
            ];
            continue;
        }

        // Handle numbered lists
        if (preg_match('/^\d+\.\s+(.+)$/', $line, $matches)) {
            $blocks[] = [
                'object' => 'block',
                'type' => 'numbered_list_item',
                'numbered_list_item' => [
                    'rich_text' => [[
                        'type' => 'text',
                        'text' => ['content' => $matches[1]]
                    ]]
                ]
            ];
            continue;
        }

        // Skip empty lines
        if (trim($line) === '') {
            continue;
        }

        // Regular paragraph
        $blocks[] = [
            'object' => 'block',
            'type' => 'paragraph',
            'paragraph' => [
                'rich_text' => [[
                    'type' => 'text',
                    'text' => ['content' => $line]
                ]]
            ]
        ];
    }

    return $blocks;
}

// Main import process
echo "Starting Notion documentation import...\n\n";

$results = [];

foreach ($documentationStructure as $section => $data) {
    echo "=== Importing section: $section ===\n";
    
    // Create section page
    $sectionResult = createNotionPage($section, "# $section\n\nDocumentation for $section", $parentPageId, $notionApiKey, $notionVersion);
    
    if (!$sectionResult['success']) {
        echo "Failed to create section page: $section\n";
        echo "Error: " . ($sectionResult['error'] ?? 'Unknown error') . "\n";
        continue;
    }
    
    $sectionPageId = str_replace('https://www.notion.so/', '', $sectionResult['url']);
    $sectionPageId = str_replace('-', '', $sectionPageId);
    
    echo "Created section page: $section\n";
    echo "URL: " . $sectionResult['url'] . "\n";
    
    // Import files in this section
    foreach ($data['files'] as $filePath => $title) {
        $fullPath = base_path($filePath);
        
        if (!file_exists($fullPath)) {
            $fullPath = base_path('docs/' . $filePath);
            if (!file_exists($fullPath)) {
                echo "  - Skipping $title (file not found: $filePath)\n";
                continue;
            }
        }
        
        $content = file_get_contents($fullPath);
        $result = createNotionPage($title, $content, $sectionPageId, $notionApiKey, $notionVersion);
        
        if ($result['success']) {
            echo "  ✓ Imported: $title\n";
            echo "    URL: " . $result['url'] . "\n";
            $results[$section][$title] = $result['url'];
        } else {
            echo "  ✗ Failed: $title\n";
            echo "    Error: " . ($result['error'] ?? 'Unknown error') . "\n";
        }
        
        // Rate limiting
        sleep(0.5);
    }
    
    echo "\n";
}

// Summary report
echo "\n=== IMPORT SUMMARY ===\n\n";

foreach ($results as $section => $pages) {
    echo "$section:\n";
    foreach ($pages as $title => $url) {
        echo "  - $title: $url\n";
    }
    echo "\n";
}

echo "Import complete!\n";