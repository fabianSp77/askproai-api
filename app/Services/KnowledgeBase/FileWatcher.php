<?php

namespace App\Services\KnowledgeBase;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Redis;

class FileWatcher
{
    protected array $watchPaths = [];
    protected $callback;
    protected bool $isWatching = false;
    
    /**
     * Watch paths for changes
     */
    public function watch(array $paths, callable $callback): void
    {
        $this->watchPaths = $paths;
        $this->callback = $callback;
        
        // Store watch configuration in Redis
        Redis::set('knowledge:file_watcher:config', json_encode([
            'paths' => $paths,
            'started_at' => now()->toIso8601String(),
        ]));
        
        // For production, we'll use a queue job that runs periodically
        $this->startPollingWatcher();
    }
    
    /**
     * Start polling-based file watcher
     */
    protected function startPollingWatcher(): void
    {
        if ($this->isWatching) {
            return;
        }
        
        $this->isWatching = true;
        
        // Dispatch a job that will check for file changes periodically
        dispatch(function () {
            $this->checkForChanges();
        })->delay(now()->addSeconds(10));
    }
    
    /**
     * Check for file changes
     */
    public function checkForChanges(): void
    {
        $lastCheck = Redis::get('knowledge:file_watcher:last_check');
        $lastCheckTime = $lastCheck ? strtotime($lastCheck) : strtotime('-1 hour');
        
        $changes = [];
        
        foreach ($this->watchPaths as $pattern) {
            $files = glob($pattern, GLOB_NOSORT);
            
            foreach ($files as $file) {
                if (!file_exists($file)) {
                    continue;
                }
                
                $mtime = filemtime($file);
                
                // Check if file was modified since last check
                if ($mtime > $lastCheckTime) {
                    $changes[] = [
                        'event' => 'modified',
                        'path' => $file,
                        'timestamp' => $mtime
                    ];
                }
            }
        }
        
        // Check for deleted files
        $previousFiles = Redis::smembers('knowledge:file_watcher:files');
        $currentFiles = [];
        
        foreach ($this->watchPaths as $pattern) {
            $files = glob($pattern, GLOB_NOSORT);
            $currentFiles = array_merge($currentFiles, $files);
        }
        
        $deletedFiles = array_diff($previousFiles, $currentFiles);
        foreach ($deletedFiles as $file) {
            $changes[] = [
                'event' => 'deleted',
                'path' => $file,
                'timestamp' => time()
            ];
        }
        
        // Update file list in Redis
        Redis::del('knowledge:file_watcher:files');
        if (!empty($currentFiles)) {
            Redis::sadd('knowledge:file_watcher:files', ...$currentFiles);
        }
        
        // Process changes
        foreach ($changes as $change) {
            try {
                if ($this->callback) {
                    call_user_func($this->callback, $change['event'], $change['path']);
                }
                
                // Broadcast change event
                $this->broadcastChange($change);
                
            } catch (\Exception $e) {
                Log::error('File watcher callback error', [
                    'change' => $change,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Update last check time
        Redis::set('knowledge:file_watcher:last_check', date('Y-m-d H:i:s'));
        
        // Log changes
        if (!empty($changes)) {
            Log::info('File watcher detected changes', [
                'changes_count' => count($changes),
                'changes' => $changes
            ]);
        }
        
        // Schedule next check if still watching
        if ($this->isWatching) {
            dispatch(function () {
                $this->checkForChanges();
            })->delay(now()->addSeconds(10));
        }
    }
    
    /**
     * Broadcast file change event
     */
    protected function broadcastChange(array $change): void
    {
        // Publish to Redis channel for real-time updates
        Redis::publish('knowledge:file_changes', json_encode([
            'event' => $change['event'],
            'path' => $change['path'],
            'timestamp' => $change['timestamp'],
            'relative_path' => str_replace(base_path() . '/', '', $change['path'])
        ]));
    }
    
    /**
     * Stop watching
     */
    public function stop(): void
    {
        $this->isWatching = false;
        Redis::del('knowledge:file_watcher:config');
        Log::info('File watcher stopped');
    }
    
    /**
     * Get watcher status
     */
    public function getStatus(): array
    {
        $config = Redis::get('knowledge:file_watcher:config');
        $lastCheck = Redis::get('knowledge:file_watcher:last_check');
        $fileCount = Redis::scard('knowledge:file_watcher:files');
        
        return [
            'is_watching' => $this->isWatching,
            'config' => $config ? json_decode($config, true) : null,
            'last_check' => $lastCheck,
            'monitored_files' => $fileCount,
        ];
    }
    
    /**
     * Use inotify for real-time file watching (Linux only)
     */
    protected function startInotifyWatcher(): void
    {
        if (PHP_OS_FAMILY !== 'Linux') {
            Log::warning('Inotify is only available on Linux, falling back to polling');
            $this->startPollingWatcher();
            return;
        }
        
        // Check if inotify-tools is installed
        $result = Process::run('which inotifywait');
        if (!$result->successful()) {
            Log::warning('inotify-tools not installed, falling back to polling');
            $this->startPollingWatcher();
            return;
        }
        
        // Build inotifywait command
        $paths = implode(' ', array_map('escapeshellarg', $this->watchPaths));
        $cmd = "inotifywait -mr --format '%w%f %e' -e modify -e create -e delete -e move {$paths}";
        
        // Start watching in background
        Process::forever()->start($cmd, function (string $type, string $output) {
            if ($type === Process::OUT) {
                $this->handleInotifyOutput($output);
            }
        });
        
        Log::info('Started inotify file watcher', ['paths' => $this->watchPaths]);
    }
    
    /**
     * Handle inotify output
     */
    protected function handleInotifyOutput(string $output): void
    {
        $lines = explode("\n", trim($output));
        
        foreach ($lines as $line) {
            if (empty($line)) continue;
            
            // Parse inotify output format: /path/to/file EVENT
            if (preg_match('/^(.+)\s+(CREATE|MODIFY|DELETE|MOVED_TO|MOVED_FROM)$/i', $line, $matches)) {
                $path = $matches[1];
                $event = strtolower($matches[2]);
                
                // Map inotify events to our events
                $eventMap = [
                    'create' => 'created',
                    'modify' => 'modified',
                    'delete' => 'deleted',
                    'moved_to' => 'created',
                    'moved_from' => 'deleted',
                ];
                
                $mappedEvent = $eventMap[$event] ?? 'modified';
                
                // Only process markdown files
                if (pathinfo($path, PATHINFO_EXTENSION) === 'md') {
                    try {
                        if ($this->callback) {
                            call_user_func($this->callback, $mappedEvent, $path);
                        }
                        
                        $this->broadcastChange([
                            'event' => $mappedEvent,
                            'path' => $path,
                            'timestamp' => time()
                        ]);
                        
                    } catch (\Exception $e) {
                        Log::error('Inotify callback error', [
                            'path' => $path,
                            'event' => $mappedEvent,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }
    }
    
    /**
     * Get list of monitored files
     */
    public function getMonitoredFiles(): array
    {
        $files = [];
        
        foreach ($this->watchPaths as $pattern) {
            $matches = glob($pattern, GLOB_NOSORT);
            $files = array_merge($files, $matches);
        }
        
        return array_unique($files);
    }
}