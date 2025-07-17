#!/usr/bin/env php
<?php
/**
 * Import ALL Documentation to Notion
 * 
 * Prerequisites:
 * 1. Set NOTION_API_KEY in .env
 * 2. Set NOTION_PARENT_PAGE_ID in .env
 * 3. Run: php import-all-to-notion.php
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

class NotionImporter 
{
    private $apiKey;
    private $parentPageId;
    private $baseUrl = 'https://api.notion.com/v1';
    private $importedCount = 0;
    private $failedCount = 0;

    public function __construct()
    {
        $this->apiKey = config('services.notion.api_key') ?? env('NOTION_API_KEY');
        $this->parentPageId = config('services.notion.parent_page_id') ?? env('NOTION_PARENT_PAGE_ID');
        
        if (!$this->apiKey || !$this->parentPageId) {
            echo "Debug: API Key = " . ($this->apiKey ? 'SET' : 'NOT SET') . "\n";
            echo "Debug: Parent Page ID = " . ($this->parentPageId ? 'SET' : 'NOT SET') . "\n";
            throw new Exception("Please set NOTION_API_KEY and NOTION_PARENT_PAGE_ID in .env file");
        }
    }

    public function run()
    {
        echo "🚀 Starting Notion Import...\n";
        echo "===========================\n\n";
        
        // 1. Create main structure
        $mainStructure = $this->createMainStructure();
        
        // 2. Import all documentation
        $this->importHelpCenterDocs($mainStructure['help_center_id']);
        $this->importTechnicalDocs($mainStructure['technical_id']);
        $this->importErrorPatterns($mainStructure['technical_id']);
        $this->importRootDocs($mainStructure['developer_id']);
        
        // 3. Summary
        echo "\n✅ Import Complete!\n";
        echo "==================\n";
        echo "Imported: {$this->importedCount} documents\n";
        echo "Failed: {$this->failedCount} documents\n";
        echo "\nCheck your Notion workspace!\n";
    }

    private function createMainStructure()
    {
        echo "Creating main structure...\n";
        
        $structure = [
            'main' => $this->createPage($this->parentPageId, 'AskProAI Documentation Hub', 
                "# 🚀 AskProAI Documentation\n\nComplete documentation for AskProAI platform."),
            
            'technical_id' => null,
            'help_center_id' => null,
            'developer_id' => null,
            'business_id' => null,
        ];
        
        // Create sub-sections
        $structure['technical_id'] = $this->createPage($structure['main'], 
            '🔧 Technical Documentation', 
            "API Reference, Integration Guides, Error Patterns, and technical specifications.");
            
        $structure['help_center_id'] = $this->createPage($structure['main'], 
            '📚 Customer Help Center', 
            "Hilfe und Anleitungen für Endkunden.");
            
        $structure['developer_id'] = $this->createPage($structure['main'], 
            '👨‍💻 Developer Resources', 
            "Setup guides, best practices, and development documentation.");
            
        $structure['business_id'] = $this->createPage($structure['main'], 
            '💼 Business Documentation', 
            "Business processes, onboarding, and operational guides.");
        
        return $structure;
    }

    private function importHelpCenterDocs($parentId)
    {
        echo "\nImporting Help Center Documentation...\n";
        
        $categories = [
            'account' => '👤 Account Management',
            'appointments' => '📅 Termine',
            'billing' => '💳 Abrechnung',
            'faq' => '❓ Häufige Fragen',
            'getting-started' => '🚀 Erste Schritte',
            'troubleshooting' => '🔧 Fehlerbehebung'
        ];
        
        $basePath = base_path('resources/docs/help-center');
        
        foreach ($categories as $folder => $title) {
            $categoryId = $this->createPage($parentId, $title, "");
            
            $files = glob("$basePath/$folder/*.md");
            foreach ($files as $file) {
                $this->importMarkdownFile($file, $categoryId);
            }
        }
    }

    private function importTechnicalDocs($parentId)
    {
        echo "\nImporting Technical Documentation...\n";
        
        // Import main technical docs
        $techDocs = [
            'ERROR_PATTERNS.md',
            'TROUBLESHOOTING_DECISION_TREE.md',
            'RETELL_WEBHOOK_FIX_*.md',
            'DEPLOYMENT_CHECKLIST.md',
            'INTEGRATION_HEALTH_MONITOR.md'
        ];
        
        foreach ($techDocs as $pattern) {
            $files = glob(base_path($pattern));
            foreach ($files as $file) {
                if (file_exists($file)) {
                    $this->importMarkdownFile($file, $parentId);
                }
            }
        }
    }

    private function importErrorPatterns($parentId)
    {
        echo "\nImporting Error Pattern Catalog...\n";
        
        $errorPageId = $this->createPage($parentId, '🚨 Error Pattern Catalog', 
            "Complete catalog of error patterns with automated solutions.");
        
        // Get errors from database
        $errors = \App\Models\ErrorCatalog::with(['solutions', 'tags'])->get();
        
        foreach ($errors as $error) {
            $content = $this->formatErrorPattern($error);
            $this->createPage($errorPageId, "{$error->error_code}: {$error->title}", $content);
        }
    }

    private function importRootDocs($parentId)
    {
        echo "\nImporting Root Documentation...\n";
        
        $rootDocs = [
            'CLAUDE.md' => '🤖 Claude Integration Guide',
            'README.md' => '📖 Project Overview',
            'BEST_PRACTICES_IMPLEMENTATION.md' => '✨ Best Practices',
            'DEVELOPMENT_PROCESS_2025.md' => '🔄 Development Process'
        ];
        
        foreach ($rootDocs as $file => $title) {
            $filePath = base_path($file);
            if (file_exists($filePath)) {
                $content = File::get($filePath);
                $this->createPage($parentId, $title, $content);
            }
        }
    }

    private function importMarkdownFile($filePath, $parentId)
    {
        try {
            $filename = basename($filePath, '.md');
            $title = str_replace('-', ' ', ucwords($filename));
            $content = File::get($filePath);
            
            echo "  Importing: $title... ";
            
            $pageId = $this->createPage($parentId, $title, $content);
            
            if ($pageId) {
                echo "✅\n";
                $this->importedCount++;
            } else {
                echo "❌\n";
                $this->failedCount++;
            }
        } catch (\Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
            $this->failedCount++;
        }
    }

    private function createPage($parentId, $title, $content)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Notion-Version' => '2022-06-28'
            ])->post($this->baseUrl . '/pages', [
                'parent' => ['page_id' => str_replace('-', '', $parentId)],
                'properties' => [
                    'title' => [
                        'title' => [
                            ['text' => ['content' => $title]]
                        ]
                    ]
                ],
                'children' => $this->markdownToNotionBlocks($content)
            ]);
            
            if ($response->successful()) {
                return $response->json()['id'];
            } else {
                throw new Exception($response->json()['message'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            echo "Failed to create page '$title': " . $e->getMessage() . "\n";
            return null;
        }
    }

    private function markdownToNotionBlocks($markdown)
    {
        $blocks = [];
        $lines = explode("\n", $markdown);
        $currentCodeBlock = null;
        
        foreach ($lines as $line) {
            // Code blocks
            if (preg_match('/^```(.*)$/', $line, $matches)) {
                if ($currentCodeBlock === null) {
                    $currentCodeBlock = [
                        'language' => $matches[1] ?: 'plain text',
                        'lines' => []
                    ];
                } else {
                    // End code block
                    $blocks[] = [
                        'type' => 'code',
                        'code' => [
                            'rich_text' => [[
                                'type' => 'text',
                                'text' => ['content' => implode("\n", $currentCodeBlock['lines'])]
                            ]],
                            'language' => $currentCodeBlock['language']
                        ]
                    ];
                    $currentCodeBlock = null;
                }
                continue;
            }
            
            if ($currentCodeBlock !== null) {
                $currentCodeBlock['lines'][] = $line;
                continue;
            }
            
            // Headers
            if (preg_match('/^(#{1,3})\s+(.+)$/', $line, $matches)) {
                $level = strlen($matches[1]);
                $type = ['heading_1', 'heading_2', 'heading_3'][$level - 1];
                
                $blocks[] = [
                    'type' => $type,
                    $type => [
                        'rich_text' => [[
                            'type' => 'text',
                            'text' => ['content' => $matches[2]]
                        ]]
                    ]
                ];
                continue;
            }
            
            // Bullet points
            if (preg_match('/^[-*]\s+(.+)$/', $line, $matches)) {
                $blocks[] = [
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
            
            // Numbered lists
            if (preg_match('/^\d+\.\s+(.+)$/', $line, $matches)) {
                $blocks[] = [
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
            
            // Regular paragraphs
            if (trim($line) !== '') {
                $blocks[] = [
                    'type' => 'paragraph',
                    'paragraph' => [
                        'rich_text' => [[
                            'type' => 'text',
                            'text' => ['content' => $line]
                        ]]
                    ]
                ];
            }
        }
        
        return $blocks;
    }

    private function formatErrorPattern($error)
    {
        $content = "# {$error->error_code}: {$error->title}\n\n";
        $content .= "**Severity**: {$error->severity}\n";
        $content .= "**Category**: {$error->category}\n\n";
        
        $content .= "## Description\n{$error->description}\n\n";
        
        if ($error->symptoms) {
            $content .= "## Symptoms\n{$error->symptoms}\n\n";
        }
        
        if ($error->root_causes) {
            $content .= "## Root Causes\n";
            foreach ($error->root_causes as $cause => $desc) {
                $content .= "- **{$cause}**: {$desc}\n";
            }
            $content .= "\n";
        }
        
        if ($error->solutions->count() > 0) {
            $content .= "## Solutions\n";
            foreach ($error->solutions as $solution) {
                $content .= "### {$solution->title}\n";
                $content .= "{$solution->description}\n\n";
                
                if ($solution->code_snippet) {
                    $content .= "```\n{$solution->code_snippet}\n```\n\n";
                }
            }
        }
        
        return $content;
    }
}

// Run the importer
try {
    $importer = new NotionImporter();
    $importer->run();
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nPlease follow these steps:\n";
    echo "1. Set NOTION_API_KEY in .env\n";
    echo "2. Set NOTION_PARENT_PAGE_ID in .env\n";
    echo "3. Run: php artisan config:cache\n";
    echo "4. Try again: php import-all-to-notion.php\n";
    exit(1);
}