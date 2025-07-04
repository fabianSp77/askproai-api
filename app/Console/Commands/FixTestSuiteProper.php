<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FixTestSuiteProper extends Command
{
    protected $signature = 'tests:fix-phpunit-proper';
    protected $description = 'Properly fix PHPUnit compatibility issues in test files';

    public function handle()
    {
        $this->info('🔧 Properly fixing PHPUnit compatibility issues...');
        
        $testPath = base_path('tests');
        $files = File::allFiles($testPath);
        $fixedCount = 0;
        
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            
            $content = File::get($file->getPathname());
            $originalContent = $content;
            
            // Parse the file structure
            $lines = explode("\n", $content);
            $newLines = [];
            $phpTagFound = false;
            $namespaceFound = false;
            $useStatements = [];
            $classContent = [];
            $inClass = false;
            
            foreach ($lines as $line) {
                // Handle PHP opening tag
                if (preg_match('/^<\?php\s*$/', $line)) {
                    $phpTagFound = true;
                    $newLines[] = $line;
                    $newLines[] = ''; // Empty line after <?php
                    continue;
                }
                
                // Handle namespace
                if (!$namespaceFound && preg_match('/^namespace\s+/', $line)) {
                    $namespaceFound = true;
                    $newLines[] = $line;
                    $newLines[] = ''; // Empty line after namespace
                    continue;
                }
                
                // Collect use statements (but skip if they're inside the class)
                if (!$inClass && preg_match('/^use\s+/', $line)) {
                    // Skip duplicate PHPUnit use statements inside class
                    if (!preg_match('/^use\s+PHPUnit\\\\Framework\\\\Attributes\\\\Test;/', $line) || 
                        !in_array('use PHPUnit\Framework\Attributes\Test;', $useStatements)) {
                        $useStatements[] = trim($line);
                    }
                    continue;
                }
                
                // Detect class start
                if (preg_match('/^class\s+/', $line)) {
                    $inClass = true;
                    
                    // Add all use statements before the class
                    if (!in_array('use PHPUnit\Framework\Attributes\Test;', $useStatements)) {
                        $useStatements[] = 'use PHPUnit\Framework\Attributes\Test;';
                    }
                    
                    // Sort use statements
                    sort($useStatements);
                    
                    // Add use statements to output
                    foreach ($useStatements as $useStatement) {
                        $newLines[] = $useStatement;
                    }
                    
                    if (count($useStatements) > 0) {
                        $newLines[] = ''; // Empty line before class
                    }
                }
                
                // Skip use statements inside the class
                if ($inClass && preg_match('/^\s*use\s+PHPUnit\\\\Framework\\\\Attributes\\\\Test;/', $line)) {
                    continue;
                }
                
                // Add all other lines
                if (!preg_match('/^use\s+/', $line) || $inClass) {
                    $newLines[] = $line;
                }
            }
            
            // Reconstruct the file
            $newContent = implode("\n", $newLines);
            
            // Clean up multiple empty lines
            $newContent = preg_replace("/\n\n\n+/", "\n\n", $newContent);
            
            // Save if changed
            if ($newContent !== $originalContent) {
                File::put($file->getPathname(), $newContent);
                $this->info("Fixed: " . $file->getRelativePathname());
                $fixedCount++;
            }
        }
        
        $this->info("✅ Fixed $fixedCount test files");
        
        $this->info('');
        $this->info('🎯 Next steps:');
        $this->info('1. Run: php artisan test');
        $this->info('2. If still failing, check individual test errors');
    }
}