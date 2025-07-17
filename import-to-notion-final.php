<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Notion configuration
$notionApiKey = env('NOTION_API_KEY');
$notionVersion = '2022-06-28';

// You need to create a parent page in Notion first and get its ID
// The ID is the last part of the URL when viewing the page
echo "=== NOTION DOCUMENTATION IMPORT ===\n\n";
echo "IMPORTANT: You need to:\n";
echo "1. Create a parent page in Notion called 'AskProAI Technical Documentation'\n";
echo "2. Get the page ID from the URL (last 32 characters after the dash)\n";
echo "3. Update the \$parentPageId variable in this script\n\n";

// REPLACE THIS WITH YOUR ACTUAL PARENT PAGE ID
$parentPageId = 'YOUR-PARENT-PAGE-ID-HERE';

if ($parentPageId === 'YOUR-PARENT-PAGE-ID-HERE') {
    echo "ERROR: Please update the \$parentPageId variable with your actual Notion page ID!\n";
    exit(1);
}

// Documentation structure
$importStructure = [
    'Cal.com Integration' => [
        'description' => 'Complete Cal.com integration documentation',
        'files' => [
            'docs/CALCOM_V2_API_DOCUMENTATION.md',
            'docs/CALCOM_MCP_SERVER_API.md',
            'docs/notion-ready/CALCOM_INTEGRATION_GUIDE.md',
            'docs/notion-ready/CALCOM_WEBHOOK_SETUP.md',
            'docs/notion-ready/CALCOM_ERROR_HANDLING.md',
            'docs/notion-ready/CALCOM_BOOKING_FLOW.md',
            'docs/notion-ready/CALCOM_MONITORING.md',
            'docs/notion-ready/CALCOM_TROUBLESHOOTING.md'
        ]
    ],
    'Email System' => [
        'description' => 'Email system configuration and templates',
        'files' => [
            'docs/notion-ready/EMAIL_SYSTEM_COMPLETE.md',
            'docs/notion-ready/EMAIL_CONFIGURATION.md',
            'docs/notion-ready/EMAIL_TEMPLATES.md',
            'docs/notion-ready/EMAIL_TROUBLESHOOTING.md'
        ]
    ],
    'CI/CD Pipeline' => [
        'description' => 'Continuous Integration and Deployment',
        'files' => [
            'docs/CI_CD_PIPELINE_DOCUMENTATION.md',
            'docs/CI_CD_BEST_PRACTICES.md',
            'docs/DEPLOYMENT_TROUBLESHOOTING_GUIDE.md',
            'docs/DEVELOPER_WORKFLOW_GUIDE.md'
        ]
    ],
    'Infrastructure' => [
        'description' => 'Server and infrastructure documentation',
        'files' => [
            'docs/DEVOPS_MANUAL.md',
            'docs/notion-ready/INFRASTRUCTURE_ARCHITECTURE.md',
            'docs/notion-ready/SERVER_CONFIGURATION.md',
            'docs/notion-ready/SECURITY_HARDENING.md',
            'docs/EMERGENCY_PROCEDURES.md'
        ]
    ],
    'Queue & Horizon' => [
        'description' => 'Queue system and Laravel Horizon',
        'files' => [
            'docs/notion-ready/QUEUE_HORIZON_GUIDE.md',
            'docs/notion-ready/QUEUE_CONFIGURATION.md',
            'docs/notion-ready/HORIZON_MONITORING.md',
            'docs/QUEUE_MCP_IMPLEMENTATION_COMPLETE.md'
        ]
    ],
    'Documentation Standards' => [
        'description' => 'Documentation templates and standards',
        'files' => [
            'docs/DOCUMENTATION_VISUAL_HIERARCHY.md',
            'docs/DOCUMENTATION_FRAMEWORK.md',
            'docs/templates/INTEGRATION_TEMPLATE.md',
            'docs/templates/SERVICE_TEMPLATE.md',
            'docs/templates/TROUBLESHOOTING_TEMPLATE.md',
            'docs/templates/API_DOCUMENTATION_TEMPLATE.md'
        ]
    ]
];

// Summary of what will be imported
echo "Will import the following sections:\n\n";
$totalFiles = 0;
foreach ($importStructure as $section => $data) {
    $fileCount = count($data['files']);
    $totalFiles += $fileCount;
    echo "- $section ($fileCount files)\n";
}
echo "\nTotal files to import: $totalFiles\n\n";

// Show the import command to use with the NotionMCPServer
echo "To import using the NotionMCPServer, use these commands:\n\n";

foreach ($importStructure as $section => $data) {
    echo "// Import $section\n";
    echo "php artisan mcp:notion create-page \\\n";
    echo "  --parent=\"$parentPageId\" \\\n";
    echo "  --title=\"$section\" \\\n";
    echo "  --content=\"{$data['description']}\"\n\n";
    
    foreach ($data['files'] as $file) {
        $title = basename($file, '.md');
        $title = str_replace(['_', '-'], ' ', $title);
        $title = Str::title($title);
        
        echo "php artisan mcp:notion import-markdown \\\n";
        echo "  --file=\"$file\" \\\n";
        echo "  --parent=\"<$section-page-id>\" \\\n";
        echo "  --title=\"$title\"\n";
    }
    echo "\n";
}

// Alternative: Direct API approach
echo "\n=== ALTERNATIVE: Direct API Import ===\n\n";
echo "If MCP commands are not available, run this script:\n\n";

foreach ($importStructure as $section => $data) {
    echo "Section: $section\n";
    echo "Files:\n";
    foreach ($data['files'] as $file) {
        if (file_exists(base_path($file))) {
            echo "  ✓ $file\n";
        } else {
            echo "  ✗ $file (NOT FOUND)\n";
        }
    }
    echo "\n";
}

// Create a summary JSON file
$summaryData = [
    'generated_at' => now()->toIso8601String(),
    'parent_page_id' => $parentPageId,
    'total_sections' => count($importStructure),
    'total_files' => $totalFiles,
    'sections' => array_map(function($section, $data) {
        return [
            'name' => $section,
            'description' => $data['description'],
            'file_count' => count($data['files']),
            'files' => array_map(function($file) {
                return [
                    'path' => $file,
                    'exists' => file_exists(base_path($file)),
                    'size' => file_exists(base_path($file)) ? filesize(base_path($file)) : 0
                ];
            }, $data['files'])
        ];
    }, array_keys($importStructure), $importStructure)
];

file_put_contents(
    base_path('docs/notion-import-summary.json'),
    json_encode($summaryData, JSON_PRETTY_PRINT)
);

echo "\nSummary saved to: docs/notion-import-summary.json\n";
echo "\nNext steps:\n";
echo "1. Create a parent page in Notion\n";
echo "2. Update the \$parentPageId in this script\n";
echo "3. Run the MCP commands above or use the Notion API directly\n";