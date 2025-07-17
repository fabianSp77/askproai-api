#!/usr/bin/env php
<?php

/**
 * Notion Documentation Sync Script
 * 
 * Automatically syncs documentation with Notion database when code changes
 * Updates status indicators, timestamps, and creates backups
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class NotionDocSync
{
    private string $notionApiKey;
    private string $databaseId;
    private array $changedFiles = [];
    private array $docMapping = [];
    
    public function __construct()
    {
        $this->notionApiKey = env('NOTION_API_KEY');
        $this->databaseId = env('NOTION_DATABASE_ID');
        $this->loadDocMapping();
    }
    
    /**
     * Main sync process
     */
    public function sync(): void
    {
        echo "🔄 Starting Notion documentation sync...\n";
        
        // 1. Detect changed files
        $this->detectChangedFiles();
        
        // 2. Create backup
        $this->createBackup();
        
        // 3. Update documentation for changed files
        foreach ($this->changedFiles as $file) {
            $this->updateDocumentation($file);
        }
        
        // 4. Update system status indicators
        $this->updateSystemStatus();
        
        // 5. Update last sync timestamp
        $this->updateLastSync();
        
        echo "✅ Sync completed successfully!\n";
    }
    
    /**
     * Detect files changed in last commit
     */
    private function detectChangedFiles(): void
    {
        $output = shell_exec('git diff --name-only HEAD~1 HEAD');
        $this->changedFiles = array_filter(explode("\n", $output));
        
        echo "📝 Found " . count($this->changedFiles) . " changed files\n";
    }
    
    /**
     * Load mapping between code files and documentation
     */
    private function loadDocMapping(): void
    {
        $mappingFile = __DIR__ . '/doc-mapping.json';
        if (file_exists($mappingFile)) {
            $this->docMapping = json_decode(file_get_contents($mappingFile), true);
        }
    }
    
    /**
     * Update documentation for a specific file
     */
    private function updateDocumentation(string $file): void
    {
        // Skip if not in mapping
        if (!isset($this->docMapping[$file])) {
            return;
        }
        
        $notionPageId = $this->docMapping[$file]['notion_page_id'];
        $docType = $this->docMapping[$file]['type'];
        
        echo "📄 Updating documentation for: $file\n";
        
        // Extract relevant information based on file type
        $updates = [];
        
        if (str_ends_with($file, '.php')) {
            $updates = $this->extractPhpDocumentation($file);
        } elseif (str_starts_with($file, 'routes/')) {
            $updates = $this->extractRouteDocumentation($file);
        } elseif (str_starts_with($file, 'database/migrations/')) {
            $updates = $this->extractMigrationDocumentation($file);
        }
        
        // Update Notion page
        $this->updateNotionPage($notionPageId, $updates);
    }
    
    /**
     * Extract documentation from PHP files
     */
    private function extractPhpDocumentation(string $file): array
    {
        $content = file_get_contents($file);
        $updates = [];
        
        // Extract class documentation
        if (preg_match('/class\s+(\w+)/', $content, $matches)) {
            $updates['class_name'] = $matches[1];
        }
        
        // Extract methods
        preg_match_all('/public\s+function\s+(\w+)\s*\(([^)]*)\)/', $content, $matches);
        $updates['methods'] = array_map(function($method, $params) {
            return [
                'name' => $method,
                'parameters' => $params,
            ];
        }, $matches[1], $matches[2]);
        
        // Extract dependencies
        preg_match_all('/use\s+([^;]+);/', $content, $matches);
        $updates['dependencies'] = $matches[1];
        
        $updates['last_modified'] = Carbon::now()->toIso8601String();
        
        return $updates;
    }
    
    /**
     * Extract route documentation
     */
    private function extractRouteDocumentation(string $file): array
    {
        $content = file_get_contents($file);
        $updates = [];
        
        // Extract routes
        preg_match_all('/Route::(get|post|put|patch|delete)\s*\(\s*[\'"]([^\'"]*)[\'"]\s*,/', $content, $matches);
        
        $updates['routes'] = array_map(function($method, $path) {
            return [
                'method' => strtoupper($method),
                'path' => $path,
            ];
        }, $matches[1], $matches[2]);
        
        $updates['last_modified'] = Carbon::now()->toIso8601String();
        
        return $updates;
    }
    
    /**
     * Extract migration documentation
     */
    private function extractMigrationDocumentation(string $file): array
    {
        $content = file_get_contents($file);
        $updates = [];
        
        // Extract table operations
        preg_match_all('/Schema::(create|table)\s*\(\s*[\'"](\w+)[\'"]/', $content, $matches);
        $updates['tables'] = array_unique($matches[2]);
        
        // Extract columns
        preg_match_all('/\$table->(\w+)\s*\(\s*[\'"](\w+)[\'"]/', $content, $matches);
        $updates['columns'] = array_map(function($type, $name) {
            return [
                'type' => $type,
                'name' => $name,
            ];
        }, $matches[1], $matches[2]);
        
        $updates['last_modified'] = Carbon::now()->toIso8601String();
        
        return $updates;
    }
    
    /**
     * Update Notion page with extracted documentation
     */
    private function updateNotionPage(string $pageId, array $updates): void
    {
        $url = "https://api.notion.com/v1/pages/{$pageId}";
        
        $properties = [];
        
        // Convert updates to Notion properties format
        if (isset($updates['last_modified'])) {
            $properties['Last Modified'] = [
                'date' => [
                    'start' => $updates['last_modified']
                ]
            ];
        }
        
        if (isset($updates['class_name'])) {
            $properties['Title'] = [
                'title' => [
                    [
                        'text' => [
                            'content' => $updates['class_name']
                        ]
                    ]
                ]
            ];
        }
        
        // Add methods as rich text
        if (isset($updates['methods'])) {
            $methodText = implode("\n", array_map(function($method) {
                return "• {$method['name']}({$method['parameters']})";
            }, $updates['methods']));
            
            $properties['Methods'] = [
                'rich_text' => [
                    [
                        'text' => [
                            'content' => $methodText
                        ]
                    ]
                ]
            ];
        }
        
        $payload = [
            'properties' => $properties
        ];
        
        $this->makeNotionRequest('PATCH', $url, $payload);
    }
    
    /**
     * Update system status indicators
     */
    private function updateSystemStatus(): void
    {
        echo "📊 Updating system status indicators...\n";
        
        $statuses = [
            'database' => $this->checkDatabaseStatus(),
            'redis' => $this->checkRedisStatus(),
            'horizon' => $this->checkHorizonStatus(),
            'retell' => $this->checkRetellStatus(),
            'calcom' => $this->checkCalcomStatus(),
        ];
        
        // Update status page in Notion
        $this->updateNotionStatusPage($statuses);
    }
    
    /**
     * Check database status
     */
    private function checkDatabaseStatus(): array
    {
        try {
            \DB::connection()->getPdo();
            return ['status' => 'operational', 'message' => 'Database is running'];
        } catch (\Exception $e) {
            return ['status' => 'down', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Check Redis status
     */
    private function checkRedisStatus(): array
    {
        try {
            \Redis::ping();
            return ['status' => 'operational', 'message' => 'Redis is running'];
        } catch (\Exception $e) {
            return ['status' => 'down', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Check Horizon status
     */
    private function checkHorizonStatus(): array
    {
        $status = shell_exec('php artisan horizon:status');
        if (str_contains($status, 'Horizon is running')) {
            return ['status' => 'operational', 'message' => 'Horizon is running'];
        } else {
            return ['status' => 'down', 'message' => 'Horizon is not running'];
        }
    }
    
    /**
     * Check Retell API status
     */
    private function checkRetellStatus(): array
    {
        try {
            $retellService = app(\App\Services\RetellV2Service::class);
            $agents = $retellService->listAgents();
            return ['status' => 'operational', 'message' => 'Retell API is accessible'];
        } catch (\Exception $e) {
            return ['status' => 'down', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Check Cal.com API status
     */
    private function checkCalcomStatus(): array
    {
        try {
            $calcomService = app(\App\Services\CalcomV2Service::class);
            $user = $calcomService->getMe();
            return ['status' => 'operational', 'message' => 'Cal.com API is accessible'];
        } catch (\Exception $e) {
            return ['status' => 'down', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Create backup of documentation
     */
    private function createBackup(): void
    {
        echo "💾 Creating documentation backup...\n";
        
        $backupDir = storage_path('documentation-backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $backupFile = "{$backupDir}/notion-backup-{$timestamp}.json";
        
        // Export all pages from Notion database
        $pages = $this->exportNotionDatabase();
        
        file_put_contents($backupFile, json_encode($pages, JSON_PRETTY_PRINT));
        
        echo "✅ Backup created: $backupFile\n";
        
        // Clean old backups (keep last 30)
        $this->cleanOldBackups($backupDir);
    }
    
    /**
     * Export all pages from Notion database
     */
    private function exportNotionDatabase(): array
    {
        $url = "https://api.notion.com/v1/databases/{$this->databaseId}/query";
        $pages = [];
        $hasMore = true;
        $startCursor = null;
        
        while ($hasMore) {
            $payload = [];
            if ($startCursor) {
                $payload['start_cursor'] = $startCursor;
            }
            
            $response = $this->makeNotionRequest('POST', $url, $payload);
            $pages = array_merge($pages, $response['results']);
            
            $hasMore = $response['has_more'] ?? false;
            $startCursor = $response['next_cursor'] ?? null;
        }
        
        return $pages;
    }
    
    /**
     * Clean old backup files
     */
    private function cleanOldBackups(string $backupDir): void
    {
        $files = glob("{$backupDir}/notion-backup-*.json");
        
        if (count($files) > 30) {
            // Sort by modification time
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Remove oldest files
            $filesToDelete = array_slice($files, 0, count($files) - 30);
            foreach ($filesToDelete as $file) {
                unlink($file);
            }
        }
    }
    
    /**
     * Update last sync timestamp
     */
    private function updateLastSync(): void
    {
        $syncFile = storage_path('notion-last-sync.json');
        $data = [
            'timestamp' => Carbon::now()->toIso8601String(),
            'files_synced' => count($this->changedFiles),
        ];
        
        file_put_contents($syncFile, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    /**
     * Make request to Notion API
     */
    private function makeNotionRequest(string $method, string $url, array $data = []): array
    {
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->notionApiKey,
            'Content-Type: application/json',
            'Notion-Version: 2022-06-28',
        ]);
        
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Notion API error: {$response}");
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Update Notion status page
     */
    private function updateNotionStatusPage(array $statuses): void
    {
        $statusPageId = env('NOTION_STATUS_PAGE_ID');
        if (!$statusPageId) {
            return;
        }
        
        $blocks = [];
        
        // Add header
        $blocks[] = [
            'object' => 'block',
            'type' => 'heading_2',
            'heading_2' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => '🔄 System Status'
                        ]
                    ]
                ]
            ]
        ];
        
        // Add status for each service
        foreach ($statuses as $service => $status) {
            $emoji = $status['status'] === 'operational' ? '✅' : '❌';
            
            $blocks[] = [
                'object' => 'block',
                'type' => 'paragraph',
                'paragraph' => [
                    'rich_text' => [
                        [
                            'type' => 'text',
                            'text' => [
                                'content' => "{$emoji} {$service}: {$status['message']}"
                            ]
                        ]
                    ]
                ]
            ];
        }
        
        // Add last updated timestamp
        $blocks[] = [
            'object' => 'block',
            'type' => 'paragraph',
            'paragraph' => [
                'rich_text' => [
                    [
                        'type' => 'text',
                        'text' => [
                            'content' => '⏰ Last updated: ' . Carbon::now()->format('Y-m-d H:i:s')
                        ]
                    ]
                ]
            ]
        ];
        
        // Update page content
        $url = "https://api.notion.com/v1/blocks/{$statusPageId}/children";
        $this->makeNotionRequest('PATCH', $url, ['children' => $blocks]);
    }
}

// Run sync if executed directly
if (php_sapi_name() === 'cli') {
    $sync = new NotionDocSync();
    $sync->sync();
}