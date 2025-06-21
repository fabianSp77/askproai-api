<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Finder\Finder;

class FileWatcherService
{
    protected KnowledgeBaseService $knowledgeService;
    protected array $watchPaths;
    protected string $cacheKey = 'knowledge_file_watcher';
    protected int $checkInterval = 60; // seconds
    
    public function __construct(KnowledgeBaseService $knowledgeService)
    {
        $this->knowledgeService = $knowledgeService;
        $this->watchPaths = config('knowledge.watch_paths', [
            base_path('*.md'),
            base_path('docs'),
            base_path('resources/docs'),
        ]);
    }
    
    /**
     * Check for file changes and re-index if needed
     */
    public function checkForChanges(): array
    {
        $currentState = $this->getCurrentFileState();
        $previousState = Cache::get($this->cacheKey, []);
        
        $changes = [
            'added' => [],
            'modified' => [],
            'deleted' => [],
        ];
        
        // Check for new and modified files
        foreach ($currentState as $path => $mtime) {
            if (!isset($previousState[$path])) {
                $changes['added'][] = $path;
            } elseif ($previousState[$path] !== $mtime) {
                $changes['modified'][] = $path;
            }
        }
        
        // Check for deleted files
        foreach ($previousState as $path => $mtime) {
            if (!isset($currentState[$path])) {
                $changes['deleted'][] = $path;
            }
        }
        
        // Process changes
        if ($this->hasChanges($changes)) {
            $this->processChanges($changes);
            Cache::put($this->cacheKey, $currentState, now()->addDays(30));
        }
        
        return $changes;
    }
    
    /**
     * Get current state of all watched files
     */
    protected function getCurrentFileState(): array
    {
        $state = [];
        
        foreach ($this->watchPaths as $path) {
            if (is_dir($path)) {
                $finder = new Finder();
                $finder->files()
                    ->in($path)
                    ->name(['*.md', '*.markdown'])
                    ->sortByName();
                    
                foreach ($finder as $file) {
                    $state[$file->getRealPath()] = $file->getMTime();
                }
            } elseif (is_file($path)) {
                $state[$path] = filemtime($path);
            } else {
                // Handle glob pattern
                $files = glob($path);
                foreach ($files as $file) {
                    if (is_file($file)) {
                        $state[$file] = filemtime($file);
                    }
                }
            }
        }
        
        return $state;
    }
    
    /**
     * Check if there are any changes
     */
    protected function hasChanges(array $changes): bool
    {
        return !empty($changes['added']) || 
               !empty($changes['modified']) || 
               !empty($changes['deleted']);
    }
    
    /**
     * Process file changes
     */
    protected function processChanges(array $changes): void
    {
        Log::info('Processing knowledge base file changes', [
            'added' => count($changes['added']),
            'modified' => count($changes['modified']),
            'deleted' => count($changes['deleted']),
        ]);
        
        // Process added and modified files
        $filesToIndex = array_merge($changes['added'], $changes['modified']);
        foreach ($filesToIndex as $file) {
            try {
                $this->knowledgeService->indexFile($file);
            } catch (\Exception $e) {
                Log::error('Failed to index file', [
                    'file' => $file,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        // Process deleted files
        foreach ($changes['deleted'] as $file) {
            try {
                $this->handleDeletedFile($file);
            } catch (\Exception $e) {
                Log::error('Failed to handle deleted file', [
                    'file' => $file,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
    
    /**
     * Handle a deleted file
     */
    protected function handleDeletedFile(string $filePath): void
    {
        $relativePath = $this->getRelativePath($filePath);
        
        // Find and soft delete the document
        $document = \App\Models\KnowledgeDocument::where('file_path', $relativePath)->first();
        if ($document) {
            // Update status instead of deleting
            $document->update([
                'status' => 'archived',
                'metadata' => array_merge($document->metadata ?? [], [
                    'archived_at' => now()->toIso8601String(),
                    'archived_reason' => 'file_deleted',
                ]),
            ]);
            
            Log::info('Archived deleted document', [
                'document_id' => $document->id,
                'file_path' => $relativePath,
            ]);
        }
    }
    
    /**
     * Get relative path from base path
     */
    protected function getRelativePath(string $filePath): string
    {
        $basePath = base_path();
        if (str_starts_with($filePath, $basePath)) {
            return ltrim(substr($filePath, strlen($basePath)), '/\\');
        }
        return $filePath;
    }
    
    /**
     * Force re-index all files
     */
    public function forceReindex(): array
    {
        Cache::forget($this->cacheKey);
        return $this->knowledgeService->discoverAndIndexDocuments($this->watchPaths);
    }
    
    /**
     * Get watcher status
     */
    public function getStatus(): array
    {
        $state = Cache::get($this->cacheKey, []);
        
        return [
            'enabled' => true,
            'paths' => $this->watchPaths,
            'files_tracked' => count($state),
            'last_check' => Cache::get($this->cacheKey . '_last_check'),
            'check_interval' => $this->checkInterval,
        ];
    }
    
    /**
     * Set last check time
     */
    public function setLastCheck(): void
    {
        Cache::put($this->cacheKey . '_last_check', now(), now()->addDays(1));
    }
}