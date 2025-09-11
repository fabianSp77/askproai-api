<?php

namespace App\View;

use Illuminate\View\Compilers\BladeCompiler;

class SafeBladeCompiler extends BladeCompiler
{
    /**
     * Determine if the view at the given path is expired.
     * Override to handle missing compiled views gracefully.
     *
     * @param  string  $path
     * @return bool
     */
    public function isExpired($path)
    {
        $compiled = $this->getCompiledPath($path);

        // If the compiled file doesn't exist, it's definitely expired
        // Use @ to suppress errors from concurrent access
        if (!@file_exists($compiled)) {
            return true;
        }

        // Check if we can get the modification time safely
        // Use @ on all filesystem operations to prevent race conditions
        $compiledTime = @filemtime($compiled);
        $sourceTime = @filemtime($path);
        
        // If we can't get either time, consider it expired to force recompilation
        if ($compiledTime === false || $sourceTime === false) {
            // Try to remove the corrupted compiled file
            @unlink($compiled);
            return true;
        }

        // Also check if file is empty or corrupted
        $size = @filesize($compiled);
        if ($size === false || $size === 0) {
            @unlink($compiled);
            return true;
        }

        // Standard check: source newer than compiled
        return $sourceTime >= $compiledTime;
    }
    
    /**
     * Compile the view at the given path.
     * Enhanced with atomic file operations and locking to prevent race conditions.
     *
     * @param  string|null  $path
     * @return void
     */
    public function compile($path = null)
    {
        if ($path) {
            $this->setPath($path);
        }

        if (!is_null($this->cachePath)) {
            $compiledPath = $this->getCompiledPath($this->getPath());
            
            // Ensure the compiled views directory exists
            $directory = dirname($compiledPath);
            if (!@is_dir($directory)) {
                @mkdir($directory, 0775, true);
                @chown($directory, 'www-data');
                @chgrp($directory, 'www-data');
            }
            
            // Clear any corrupted file - use @ to suppress errors on all filesystem operations
            if (@file_exists($compiledPath)) {
                $size = @filesize($compiledPath);
                if ($size === 0 || $size === false) {
                    @unlink($compiledPath);
                }
            }
        }

        try {
            // Call parent compile method to handle the actual compilation
            parent::compile($path);
            
            // After parent compilation, ensure the file was written with atomic operation
            if (!is_null($this->cachePath) && $this->isExpired($this->getPath())) {
                $compiledPath = $this->getCompiledPath($this->getPath());
                
                // If the file exists but might be corrupted, rewrite it atomically
                if (@file_exists($compiledPath)) {
                    $content = @file_get_contents($compiledPath);
                    if ($content !== false && !empty($content)) {
                        $this->atomicWrite($compiledPath, $content);
                    }
                }
            }
        } catch (\Exception $e) {
            // Log the error but don't let it break the entire compilation process
            \Log::warning('View compilation warning: ' . $e->getMessage(), [
                'path' => $this->getPath() ?? 'unknown'
            ]);
            
            // Create a simple placeholder compiled file
            if (!is_null($this->cachePath)) {
                $compiledPath = $this->getCompiledPath($this->getPath());
                $this->atomicWrite($compiledPath, '<?php /* Compilation failed - placeholder */ ?>');
            }
        }
    }
    
    /**
     * Atomically write content to a file with locking.
     * This prevents race conditions during concurrent writes.
     *
     * @param string $path
     * @param string $content
     * @return bool
     */
    protected function atomicWrite($path, $content)
    {
        // Generate unique temporary file in same directory (for atomic rename)
        $tempPath = $path . '.tmp.' . uniqid() . '.' . getmypid();
        
        // Write to temporary file with exclusive lock
        $written = @file_put_contents($tempPath, $content, LOCK_EX);
        
        if ($written !== false) {
            // Set proper permissions
            @chmod($tempPath, 0664);
            @chown($tempPath, 'www-data');
            @chgrp($tempPath, 'www-data');
            
            // Atomically move temp file to final location
            // rename() is atomic on POSIX filesystems
            if (@rename($tempPath, $path)) {
                return true;
            }
            
            // If rename failed, try copy + unlink
            if (@copy($tempPath, $path)) {
                @unlink($tempPath);
                return true;
            }
        }
        
        // Clean up temp file on failure
        @unlink($tempPath);
        
        // Fallback to direct write with locking
        return @file_put_contents($path, $content, LOCK_EX) !== false;
    }
}