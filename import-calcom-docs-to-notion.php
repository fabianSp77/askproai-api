<?php

// Import Cal.com documentation to Notion
// This script creates a comprehensive Cal.com documentation structure in Notion

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Notion API configuration
$notionApiKey = env('NOTION_API_KEY', 'secret_YourNotionIntegrationTokenHere');
$notionVersion = '2022-06-28';

// The parent page ID where we'll create the Cal.com documentation
// This should be under Technical Docs → Integrations
$parentPageId = 'YOUR_PARENT_PAGE_ID'; // Replace with actual parent page ID

// Function to create a Notion page
function createNotionPage($apiKey, $version, $parentId, $title, $content = [], $icon = null) {
    $response = Http::withHeaders([
        'Authorization' => "Bearer {$apiKey}",
        'Notion-Version' => $version,
        'Content-Type' => 'application/json',
    ])->post('https://api.notion.com/v1/pages', [
        'parent' => [
            'type' => 'page_id',
            'page_id' => $parentId,
        ],
        'icon' => $icon ?: [
            'type' => 'emoji',
            'emoji' => '📅',
        ],
        'properties' => [
            'title' => [
                'title' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => $title,
                        ],
                    ],
                ],
            ],
        ],
        'children' => $content,
    ]);

    if ($response->successful()) {
        return $response->json()['id'];
    } else {
        echo "Failed to create page '{$title}': " . $response->body() . "\n";
        return null;
    }
}

// Function to convert markdown to Notion blocks
function markdownToNotionBlocks($markdown) {
    $blocks = [];
    $lines = explode("\n", $markdown);
    $currentCodeBlock = null;
    $currentList = [];
    $currentListType = null;
    
    foreach ($lines as $line) {
        // Code block handling
        if (preg_match('/^```(\w*)$/', $line, $matches)) {
            if ($currentCodeBlock !== null) {
                // End code block
                $blocks[] = [
                    'type' => 'code',
                    'code' => [
                        'rich_text' => [[
                            'type' => 'text',
                            'text' => ['content' => implode("\n", $currentCodeBlock['lines'])],
                        ]],
                        'language' => $currentCodeBlock['language'] ?: 'plain text',
                    ],
                ];
                $currentCodeBlock = null;
            } else {
                // Start code block
                $currentCodeBlock = [
                    'language' => $matches[1] ?: 'plain text',
                    'lines' => [],
                ];
            }
            continue;
        }
        
        if ($currentCodeBlock !== null) {
            $currentCodeBlock['lines'][] = $line;
            continue;
        }
        
        // Headers
        if (preg_match('/^#{1,3}\s+(.+)$/', $line, $matches)) {
            $level = strlen(explode(' ', $line)[0]);
            $headerType = $level === 1 ? 'heading_1' : ($level === 2 ? 'heading_2' : 'heading_3');
            $blocks[] = [
                'type' => $headerType,
                $headerType => [
                    'rich_text' => [[
                        'type' => 'text',
                        'text' => ['content' => $matches[1]],
                    ]],
                ],
            ];
            continue;
        }
        
        // Bulleted lists
        if (preg_match('/^[-*]\s+(.+)$/', $line, $matches)) {
            $currentList[] = $matches[1];
            $currentListType = 'bulleted_list_item';
            continue;
        }
        
        // Numbered lists
        if (preg_match('/^\d+\.\s+(.+)$/', $line, $matches)) {
            $currentList[] = $matches[1];
            $currentListType = 'numbered_list_item';
            continue;
        }
        
        // If we have a list and hit a non-list line, output the list
        if (!empty($currentList) && !preg_match('/^[\s]*[-*\d]+\.?\s+/', $line)) {
            foreach ($currentList as $item) {
                $blocks[] = [
                    'type' => $currentListType,
                    $currentListType => [
                        'rich_text' => [[
                            'type' => 'text',
                            'text' => ['content' => $item],
                        ]],
                    ],
                ];
            }
            $currentList = [];
            $currentListType = null;
        }
        
        // Regular paragraphs
        if (trim($line) !== '') {
            $blocks[] = [
                'type' => 'paragraph',
                'paragraph' => [
                    'rich_text' => [[
                        'type' => 'text',
                        'text' => ['content' => $line],
                    ]],
                ],
            ];
        }
    }
    
    // Handle any remaining list items
    if (!empty($currentList)) {
        foreach ($currentList as $item) {
            $blocks[] = [
                'type' => $currentListType,
                $currentListType => [
                    'rich_text' => [[
                        'type' => 'text',
                        'text' => ['content' => $item],
                    ]],
                ],
            ];
        }
    }
    
    return $blocks;
}

// Read documentation files
$docs = [
    'Integration Guide' => file_get_contents('/var/www/api-gateway/CALCOM_INTEGRATION_GUIDE.md'),
    'V2 API Reference' => file_get_contents('/var/www/api-gateway/CALCOM_V2_API_REFERENCE.md'),
    'V1 to V2 Migration' => file_get_contents('/var/www/api-gateway/CALCOM_V1_TO_V2_MIGRATION_GUIDE.md'),
    'Webhook Configuration' => file_get_contents('/var/www/api-gateway/CALCOM_WEBHOOK_GUIDE.md'),
    'Event Type Management' => file_get_contents('/var/www/api-gateway/CALCOM_EVENT_TYPES_GUIDE.md'),
    'Troubleshooting Guide' => file_get_contents('/var/www/api-gateway/CALCOM_TROUBLESHOOTING_GUIDE.md'),
    'Operations Manual' => file_get_contents('/var/www/api-gateway/CALCOM_OPERATIONS_MANUAL.md'),
    'Quick Reference' => file_get_contents('/var/www/api-gateway/CALCOM_QUICK_REFERENCE.md'),
];

// Create main Cal.com page
echo "Creating main Cal.com Integration page...\n";

$mainPageContent = [
    [
        'type' => 'heading_1',
        'heading_1' => [
            'rich_text' => [[
                'type' => 'text',
                'text' => ['content' => '📅 Cal.com Integration'],
            ]],
        ],
    ],
    [
        'type' => 'callout',
        'callout' => [
            'rich_text' => [[
                'type' => 'text',
                'text' => ['content' => 'Complete documentation for Cal.com V2 API integration in AskProAI platform'],
            ]],
            'icon' => ['emoji' => '✅'],
            'color' => 'green_background',
        ],
    ],
    [
        'type' => 'heading_2',
        'heading_2' => [
            'rich_text' => [[
                'type' => 'text',
                'text' => ['content' => 'Current Status'],
            ]],
        ],
    ],
    [
        'type' => 'bulleted_list_item',
        'bulleted_list_item' => [
            'rich_text' => [[
                'type' => 'text',
                'text' => ['content' => '✅ API Version: V2 (fully migrated from V1)'],
            ]],
        ],
    ],
    [
        'type' => 'bulleted_list_item',
        'bulleted_list_item' => [
            'rich_text' => [[
                'type' => 'text',
                'text' => ['content' => '✅ Production Ready: Yes'],
            ]],
        ],
    ],
    [
        'type' => 'bulleted_list_item',
        'bulleted_list_item' => [
            'rich_text' => [[
                'type' => 'text',
                'text' => ['content' => '✅ Multi-tenant Support: Yes'],
            ]],
        ],
    ],
    [
        'type' => 'bulleted_list_item',
        'bulleted_list_item' => [
            'rich_text' => [[
                'type' => 'text',
                'text' => ['content' => '✅ Webhook Integration: Active'],
            ]],
        ],
    ],
    [
        'type' => 'bulleted_list_item',
        'bulleted_list_item' => [
            'rich_text' => [[
                'type' => 'text',
                'text' => ['content' => '✅ Circuit Breaker: Enabled'],
            ]],
        ],
    ],
    [
        'type' => 'heading_2',
        'heading_2' => [
            'rich_text' => [[
                'type' => 'text',
                'text' => ['content' => 'Quick Links'],
            ]],
        ],
    ],
    [
        'type' => 'bulleted_list_item',
        'bulleted_list_item' => [
            'rich_text' => [[
                'type' => 'text',
                'text' => ['content' => 'Integration Guide - Complete setup and configuration'],
            ]],
        ],
    ],
    [
        'type' => 'bulleted_list_item',
        'bulleted_list_item' => [
            'rich_text' => [[
                'type' => 'text',
                'text' => ['content' => 'V2 API Reference - All endpoints and methods'],
            ]],
        ],
    ],
    [
        'type' => 'bulleted_list_item',
        'bulleted_list_item' => [
            'rich_text' => [[
                'type' => 'text',
                'text' => ['content' => 'Troubleshooting - Common issues and solutions'],
            ]],
        ],
    ],
    [
        'type' => 'bulleted_list_item',
        'bulleted_list_item' => [
            'rich_text' => [[
                'type' => 'text',
                'text' => ['content' => 'Quick Reference - Essential commands and queries'],
            ]],
        ],
    ],
    [
        'type' => 'heading_2',
        'heading_2' => [
            'rich_text' => [[
                'type' => 'text',
                'text' => ['content' => 'Critical Information'],
            ]],
        ],
    ],
    [
        'type' => 'code',
        'code' => [
            'rich_text' => [[
                'type' => 'text',
                'text' => ['content' => "# Environment Variables\nDEFAULT_CALCOM_API_KEY=cal_live_xxxxxxxxxxxxxx\nDEFAULT_CALCOM_TEAM_SLUG=your-team-slug\nCALCOM_WEBHOOK_SECRET=your-webhook-secret\n\n# Webhook URL\nhttps://api.askproai.de/api/webhooks/calcom\n\n# API Endpoint\nhttps://api.cal.com/v2"],
            ]],
            'language' => 'bash',
        ],
    ],
];

$mainPageId = createNotionPage($notionApiKey, $notionVersion, $parentPageId, '📅 Cal.com Integration', $mainPageContent);

if (!$mainPageId) {
    die("Failed to create main page. Please check your Notion API key and parent page ID.\n");
}

echo "Main page created with ID: {$mainPageId}\n";

// Create sub-pages
$pageUrls = [];

foreach ($docs as $title => $content) {
    echo "Creating page: {$title}...\n";
    
    // Convert markdown content to Notion blocks
    $blocks = markdownToNotionBlocks($content);
    
    // Limit blocks to prevent API errors (Notion has a limit)
    $blocks = array_slice($blocks, 0, 100);
    
    $pageId = createNotionPage($notionApiKey, $notionVersion, $mainPageId, $title, $blocks);
    
    if ($pageId) {
        $pageUrls[$title] = "https://www.notion.so/{$pageId}";
        echo "✓ Created: {$title}\n";
    } else {
        echo "✗ Failed: {$title}\n";
    }
    
    // Small delay to avoid rate limiting
    sleep(1);
}

// Output results
echo "\n=== Cal.com Documentation Import Complete ===\n";
echo "Main Page: https://www.notion.so/{$mainPageId}\n";
echo "\nSub-pages:\n";
foreach ($pageUrls as $title => $url) {
    echo "- {$title}: {$url}\n";
}

echo "\n⚠️ IMPORTANT: Replace 'YOUR_PARENT_PAGE_ID' with the actual Notion page ID where you want to create the documentation.\n";
echo "To find the parent page ID:\n";
echo "1. Open the parent page in Notion\n";
echo "2. Copy the page ID from the URL (the part after the page name)\n";
echo "3. Update the \$parentPageId variable in this script\n";