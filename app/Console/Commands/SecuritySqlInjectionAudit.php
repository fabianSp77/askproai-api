<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SecuritySqlInjectionAudit extends Command
{
    protected $signature = 'security:audit-sql-injection {--fix : Attempt to auto-fix issues}';
    protected $description = 'Audit codebase for potential SQL injection vulnerabilities';
    
    private $vulnerabilities = [];
    private $filesScanned = 0;
    private $issuesFound = 0;
    
    private $dangerousPatterns = [
        // Raw queries without parameter binding
        '/->whereRaw\s*\([^,)]+\$[^,)]+\)/' => 'whereRaw with variable concatenation',
        '/->selectRaw\s*\([^,)]+\$[^,)]+\)/' => 'selectRaw with variable concatenation',
        '/->orderByRaw\s*\([^,)]+\$[^,)]+\)/' => 'orderByRaw with variable concatenation',
        '/DB::raw\s*\([^)]*\$[^)]+\)/' => 'DB::raw with variable concatenation',
        
        // LIKE queries with unescaped user input
        '/LIKE\s*[\'"]%[\'"].*\.\s*\$/' => 'LIKE query with unescaped concatenation',
        '/LIKE.*\$[^,\]]+%/' => 'LIKE query with potential injection',
        
        // Direct SQL statements
        '/DB::statement\s*\([^)]*\$/' => 'DB::statement with variables',
        '/DB::select\s*\([^)]*\$/' => 'DB::select with variables',
        '/DB::insert\s*\([^)]*\$/' => 'DB::insert with variables',
        '/DB::update\s*\([^)]*\$/' => 'DB::update with variables',
        '/DB::delete\s*\([^)]*\$/' => 'DB::delete with variables',
        
        // Dynamic table/column names
        '/->from\s*\(\s*\$/' => 'Dynamic table name',
        '/->table\s*\(\s*\$/' => 'Dynamic table name',
        '/->join\s*\([^,]+\$/' => 'Dynamic join table',
    ];
    
    private $safePatterns = [
        // These patterns indicate safe usage
        '/->whereRaw\s*\([^,]+,\s*\[/' => 'whereRaw with parameter binding',
        '/->selectRaw\s*\([^,]+,\s*\[/' => 'selectRaw with parameter binding',
        '/->orderByRaw\s*\([^,]+,\s*\[/' => 'orderByRaw with parameter binding',
        '/SafeQueryHelper::/' => 'Using SafeQueryHelper',
        '/\?\s*[\'"],\s*\[/' => 'Using placeholder with binding',
    ];

    public function handle()
    {
        $this->info('Starting SQL injection vulnerability audit...');
        
        $directories = [
            app_path(),
            base_path('routes'),
        ];
        
        foreach ($directories as $directory) {
            $this->scanDirectory($directory);
        }
        
        $this->displayResults();
        
        if ($this->option('fix')) {
            $this->attemptAutoFix();
        }
        
        return $this->issuesFound > 0 ? 1 : 0;
    }
    
    private function scanDirectory($directory)
    {
        $files = File::allFiles($directory);
        
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            
            // Skip vendor, storage, and test files
            if (str_contains($file->getPathname(), 'vendor/') || 
                str_contains($file->getPathname(), 'storage/') ||
                str_contains($file->getPathname(), 'tests/')) {
                continue;
            }
            
            $this->scanFile($file->getPathname());
        }
    }
    
    private function scanFile($filePath)
    {
        $this->filesScanned++;
        $content = File::get($filePath);
        $lines = explode("\n", $content);
        
        foreach ($lines as $lineNumber => $line) {
            // Skip comments
            if (preg_match('/^\s*\/\//', $line) || preg_match('/^\s*\*/', $line)) {
                continue;
            }
            
            // Check for dangerous patterns
            foreach ($this->dangerousPatterns as $pattern => $description) {
                if (preg_match($pattern, $line)) {
                    // Check if it's actually safe
                    $isSafe = false;
                    foreach ($this->safePatterns as $safePattern => $_) {
                        if (preg_match($safePattern, $line)) {
                            $isSafe = true;
                            break;
                        }
                    }
                    
                    if (!$isSafe) {
                        $this->addVulnerability($filePath, $lineNumber + 1, $line, $description);
                    }
                }
            }
            
            // Check for specific vulnerable method calls
            if (preg_match('/->where\s*\(\s*[\'"][^\'",]+[\'"],\s*[\'"]LIKE[\'"],\s*[\'"]%[\'"].*\.\s*\$/', $line)) {
                $this->addVulnerability($filePath, $lineNumber + 1, $line, 'Unescaped LIKE query');
            }
        }
    }
    
    private function addVulnerability($file, $line, $code, $type)
    {
        $this->issuesFound++;
        $this->vulnerabilities[] = [
            'file' => str_replace(base_path() . '/', '', $file),
            'line' => $line,
            'code' => trim($code),
            'type' => $type,
        ];
    }
    
    private function displayResults()
    {
        $this->info("\nAudit Results:");
        $this->info("Files scanned: {$this->filesScanned}");
        $this->info("Issues found: {$this->issuesFound}");
        
        if ($this->issuesFound > 0) {
            $this->error("\nPotential SQL injection vulnerabilities found:");
            
            $grouped = collect($this->vulnerabilities)->groupBy('file');
            
            foreach ($grouped as $file => $issues) {
                $this->warn("\n{$file}:");
                foreach ($issues as $issue) {
                    $this->line("  Line {$issue['line']}: {$issue['type']}");
                    $this->line("  Code: " . \Str::limit($issue['code'], 80));
                }
            }
            
            $this->info("\nRecommendations:");
            $this->info("1. Use parameter binding: ->whereRaw('column = ?', [\$value])");
            $this->info("2. Use SafeQueryHelper for LIKE queries: SafeQueryHelper::whereLike(\$query, 'column', \$value)");
            $this->info("3. Escape special characters in LIKE: SafeQueryHelper::escapeLike(\$value)");
            $this->info("4. Use Query Builder methods instead of raw SQL when possible");
            $this->info("5. Validate and sanitize all user input");
        } else {
            $this->info("\nNo SQL injection vulnerabilities detected!");
        }
    }
    
    private function attemptAutoFix()
    {
        if ($this->issuesFound === 0) {
            return;
        }
        
        $this->info("\nAttempting to auto-fix issues...");
        
        if (!$this->confirm('This will modify your files. Do you want to continue?')) {
            return;
        }
        
        $fixed = 0;
        
        foreach ($this->vulnerabilities as $vulnerability) {
            $filePath = base_path($vulnerability['file']);
            $content = File::get($filePath);
            $lines = explode("\n", $content);
            $line = $lines[$vulnerability['line'] - 1];
            
            // Simple auto-fix for common patterns
            $newLine = $line;
            
            // Fix unescaped LIKE queries
            if (preg_match('/->where\s*\(\s*([\'"][^\'",]+[\'"])\s*,\s*[\'"]LIKE[\'"],\s*[\'"]%[\'"].*\.\s*\$([^)]+)\s*\.\s*[\'"]%[\'"]/', $line, $matches)) {
                $column = $matches[1];
                $variable = $matches[2];
                $newLine = preg_replace(
                    '/->where\s*\([^)]+\)/',
                    "->where(function(\$q) use ({$variable}) { SafeQueryHelper::whereLike(\$q, {$column}, {$variable}); })",
                    $line
                );
                
                // Add import if not present
                if (!str_contains($content, 'use App\Helpers\SafeQueryHelper;')) {
                    $content = preg_replace(
                        '/namespace\s+[^;]+;/',
                        "$0\n\nuse App\Helpers\SafeQueryHelper;",
                        $content
                    );
                }
            }
            
            if ($newLine !== $line) {
                $lines[$vulnerability['line'] - 1] = $newLine;
                File::put($filePath, implode("\n", $lines));
                $fixed++;
                $this->info("Fixed: {$vulnerability['file']}:{$vulnerability['line']}");
            }
        }
        
        $this->info("\nAuto-fixed {$fixed} issues.");
        
        if ($fixed < $this->issuesFound) {
            $this->warn("Some issues require manual intervention.");
        }
    }
}