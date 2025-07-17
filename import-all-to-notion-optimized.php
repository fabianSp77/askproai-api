#!/usr/bin/env php
<?php
/**
 * Optimized Notion Import with API Limits
 * 
 * Handles:
 * - 100 blocks per page limit
 * - 2000 chars per code block
 * - Language whitelist
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

class OptimizedNotionImporter 
{
    private $apiKey;
    private $parentPageId;
    private $baseUrl = 'https://api.notion.com/v1';
    private $importedCount = 0;
    private $failedCount = 0;
    
    // Notion API limits
    private const MAX_BLOCKS_PER_PAGE = 100;
    private const MAX_CHARS_PER_CODE_BLOCK = 2000;
    
    // Allowed code languages
    private const ALLOWED_LANGUAGES = [
        'bash', 'shell', 'javascript', 'typescript', 'php', 'python', 
        'json', 'yaml', 'xml', 'sql', 'markdown', 'plain text'
    ];

    public function __construct()
    {
        $this->apiKey = config('services.notion.api_key');
        $this->parentPageId = config('services.notion.parent_page_id');
        
        if (!$this->apiKey || !$this->parentPageId) {
            throw new Exception("Please set NOTION_API_KEY and NOTION_PARENT_PAGE_ID in .env file");
        }
    }

    public function run()
    {
        echo "🚀 Starting Optimized Notion Import...\n";
        echo "=====================================\n\n";
        
        // 1. Create main structure
        $mainStructure = $this->createMainStructure();
        
        // 2. Import all documentation
        $this->importHelpCenterDocs($mainStructure['help_center_id']);
        $this->importErrorPatterns($mainStructure['technical_id']);
        $this->importTechnicalDocs($mainStructure['technical_id']);
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
            'main' => $this->createPage($this->parentPageId, '🚀 AskProAI Documentation Hub', 
                "Complete documentation for AskProAI platform."),
            
            'technical_id' => null,
            'help_center_id' => null,
            'developer_id' => null,
            'business_id' => null,
        ];
        
        if (!$structure['main']) {
            throw new Exception("Failed to create main documentation page");
        }
        
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
            
            if (!$categoryId) continue;
            
            $files = glob("$basePath/$folder/*.md");
            foreach ($files as $file) {
                $this->importMarkdownFile($file, $categoryId);
            }
        }
    }

    private function importErrorPatterns($parentId)
    {
        echo "\nImporting Error Pattern Catalog...\n";
        
        $errorPageId = $this->createPage($parentId, '🚨 Error Pattern Catalog', 
            "Complete catalog of error patterns with automated solutions.");
        
        if (!$errorPageId) return;
        
        // Get errors from database
        $errors = \App\Models\ErrorCatalog::with(['solutions', 'tags'])->get();
        
        foreach ($errors as $error) {
            $content = $this->formatErrorPatternOptimized($error);
            $this->createPageWithContent($errorPageId, "{$error->error_code}: {$error->title}", $content);
        }
    }

    private function importTechnicalDocs($parentId)
    {
        echo "\nImporting Technical Documentation...\n";
        
        $techDocs = [
            'ERROR_PATTERNS.md' => '🚨 Error Patterns Reference',
            'TROUBLESHOOTING_DECISION_TREE.md' => '🔍 Troubleshooting Guide',
            'DEPLOYMENT_CHECKLIST.md' => '🚀 Deployment Checklist',
            'INTEGRATION_HEALTH_MONITOR.md' => '📊 Integration Health Monitor'
        ];
        
        foreach ($techDocs as $file => $title) {
            $filePath = base_path($file);
            if (file_exists($filePath)) {
                $this->importMarkdownFile($filePath, $parentId, $title);
            }
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
                $this->importMarkdownFile($filePath, $parentId, $title);
            }
        }
    }

    private function importMarkdownFile($filePath, $parentId, $title = null)
    {
        try {
            $filename = basename($filePath, '.md');
            if (!$title) {
                $title = str_replace(['-', '_'], ' ', ucwords($filename));
            }
            
            $content = File::get($filePath);
            
            echo "  Importing: $title... ";
            
            $success = $this->createPageWithContent($parentId, $title, $content);
            
            if ($success) {
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

    private function createPageWithContent($parentId, $title, $content)
    {
        $blocks = $this->markdownToOptimizedNotionBlocks($content);
        
        // If content is too large, split into multiple pages
        if (count($blocks) > self::MAX_BLOCKS_PER_PAGE) {
            return $this->createMultiPageDocument($parentId, $title, $blocks);
        }
        
        return $this->createPage($parentId, $title, '', $blocks);
    }

    private function createMultiPageDocument($parentId, $title, $blocks)
    {
        // Create main page
        $mainPageId = $this->createPage($parentId, $title, 
            "This document is split into multiple pages due to size constraints.");
        
        if (!$mainPageId) return false;
        
        // Split blocks into chunks
        $chunks = array_chunk($blocks, self::MAX_BLOCKS_PER_PAGE - 10); // Leave room for navigation
        
        foreach ($chunks as $index => $chunk) {
            $pageTitle = $title . " (Part " . ($index + 1) . ")";
            $this->createPage($mainPageId, $pageTitle, '', $chunk);
        }
        
        return true;
    }

    private function createPage($parentId, $title, $content = '', $blocks = null)
    {
        try {
            if (!$blocks && $content) {
                $blocks = $this->markdownToOptimizedNotionBlocks($content);
            } elseif (!$blocks) {
                $blocks = [];
            }
            
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
                'children' => $blocks
            ]);
            
            if ($response->successful()) {
                return $response->json()['id'];
            } else {
                $error = $response->json();
                throw new Exception($error['message'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            echo "Failed to create page '$title': " . $e->getMessage() . "\n";
            return null;
        }
    }

    private function markdownToOptimizedNotionBlocks($markdown)
    {
        $blocks = [];
        $lines = explode("\n", $markdown);
        $currentCodeBlock = null;
        
        foreach ($lines as $line) {
            // Handle code blocks
            if (preg_match('/^```(.*)$/', $line, $matches)) {
                if ($currentCodeBlock === null) {
                    $lang = $matches[1] ?: 'plain text';
                    // Map unsupported languages
                    if ($lang === 'cmd' || $lang === 'dos') $lang = 'shell';
                    if (!in_array($lang, self::ALLOWED_LANGUAGES)) $lang = 'plain text';
                    
                    $currentCodeBlock = [
                        'language' => $lang,
                        'lines' => []
                    ];
                } else {
                    // End code block
                    $codeContent = implode("\n", $currentCodeBlock['lines']);
                    
                    // Split long code blocks
                    if (strlen($codeContent) > self::MAX_CHARS_PER_CODE_BLOCK) {
                        $chunks = str_split($codeContent, self::MAX_CHARS_PER_CODE_BLOCK);
                        foreach ($chunks as $i => $chunk) {
                            $blocks[] = [
                                'type' => 'code',
                                'code' => [
                                    'rich_text' => [[
                                        'type' => 'text',
                                        'text' => ['content' => $chunk]
                                    ]],
                                    'language' => $currentCodeBlock['language']
                                ]
                            ];
                            if ($i < count($chunks) - 1) {
                                $blocks[] = [
                                    'type' => 'paragraph',
                                    'paragraph' => [
                                        'rich_text' => [[
                                            'type' => 'text',
                                            'text' => ['content' => '... (continued)']
                                        ]]
                                    ]
                                ];
                            }
                        }
                    } else {
                        $blocks[] = [
                            'type' => 'code',
                            'code' => [
                                'rich_text' => [[
                                    'type' => 'text',
                                    'text' => ['content' => $codeContent]
                                ]],
                                'language' => $currentCodeBlock['language']
                            ]
                        ];
                    }
                    $currentCodeBlock = null;
                }
                continue;
            }
            
            if ($currentCodeBlock !== null) {
                $currentCodeBlock['lines'][] = $line;
                continue;
            }
            
            // Skip empty lines if we're at block limit
            if (trim($line) === '' && count($blocks) >= self::MAX_BLOCKS_PER_PAGE - 5) {
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
                            'text' => ['content' => mb_substr($matches[2], 0, 100)]
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
                            'text' => ['content' => mb_substr($matches[1], 0, 200)]
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
                            'text' => ['content' => mb_substr($line, 0, 2000)]
                        ]]
                    ]
                ];
            }
            
            // Stop if we're near the limit
            if (count($blocks) >= self::MAX_BLOCKS_PER_PAGE - 2) {
                $blocks[] = [
                    'type' => 'paragraph',
                    'paragraph' => [
                        'rich_text' => [[
                            'type' => 'text',
                            'text' => ['content' => '... (content truncated due to size limits)']
                        ]]
                    ]
                ];
                break;
            }
        }
        
        return $blocks;
    }

    private function formatErrorPatternOptimized($error)
    {
        $content = "# {$error->error_code}: {$error->title}\n\n";
        $content .= "**Severity**: {$error->severity}\n";
        $content .= "**Category**: {$error->category}\n\n";
        
        $content .= "## Description\n{$error->description}\n\n";
        
        if ($error->symptoms) {
            $content .= "## Symptoms\n{$error->symptoms}\n\n";
        }
        
        if ($error->root_causes && count($error->root_causes) > 0) {
            $content .= "## Root Causes\n";
            $causes = array_slice($error->root_causes, 0, 5); // Limit causes
            foreach ($causes as $cause => $desc) {
                $content .= "- **{$cause}**: {$desc}\n";
            }
            $content .= "\n";
        }
        
        if ($error->solutions->count() > 0) {
            $content .= "## Solutions\n";
            foreach ($error->solutions->take(3) as $solution) { // Limit solutions
                $content .= "### {$solution->title}\n";
                $content .= mb_substr($solution->description, 0, 200) . "\n\n";
                
                if ($solution->code_snippet && strlen($solution->code_snippet) < 500) {
                    $content .= "```\n" . mb_substr($solution->code_snippet, 0, 500) . "\n```\n\n";
                }
            }
        }
        
        return $content;
    }
}

// Run the importer
try {
    $importer = new OptimizedNotionImporter();
    $importer->run();
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}