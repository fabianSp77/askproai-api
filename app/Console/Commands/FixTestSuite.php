<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FixTestSuite extends Command
{
    protected $signature = 'tests:fix-phpunit';
    protected $description = 'Fix PHPUnit compatibility issues in test files';

    public function handle()
    {
        $this->info('🔧 Fixing PHPUnit compatibility issues...');
        
        $testPath = base_path('tests');
        $files = File::allFiles($testPath);
        $fixedCount = 0;
        
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            
            $content = File::get($file->getPathname());
            $originalContent = $content;
            
            // Check if file has misplaced use statements inside class
            if (preg_match('/class\s+\w+.*?\{.*?use\s+PHPUnit\\\\Framework\\\\Attributes\\\\Test;/s', $content)) {
                $this->info("Fixing: " . $file->getRelativePathname());
                
                // Remove all use statements inside the class
                $content = preg_replace('/^(\s*)use\s+PHPUnit\\\\Framework\\\\Attributes\\\\Test;\s*$/m', '', $content);
                
                // Add the use statement at the top if not already there
                if (!preg_match('/^use\s+PHPUnit\\\\Framework\\\\Attributes\\\\Test;/m', $content)) {
                    // Find the position after namespace and other use statements
                    if (preg_match('/^((?:namespace\s+[^;]+;\s*)?(?:use\s+[^;]+;\s*)*)(.*)$/s', $content, $matches)) {
                        $header = trim($matches[1]);
                        $rest = $matches[2];
                        
                        // Add PHPUnit use statement
                        if ($header) {
                            $content = $header . "\nuse PHPUnit\\Framework\\Attributes\\Test;\n" . $rest;
                        } else {
                            $content = "<?php\n\nuse PHPUnit\\Framework\\Attributes\\Test;\n" . substr($content, 5);
                        }
                    }
                }
                
                // Save the fixed file
                if ($content !== $originalContent) {
                    File::put($file->getPathname(), $content);
                    $fixedCount++;
                }
            }
        }
        
        $this->info("✅ Fixed $fixedCount test files");
        
        // Also fix any backup files that might be causing issues
        $this->fixBackupFiles();
        
        $this->info('');
        $this->info('🎯 Next steps:');
        $this->info('1. Run: php artisan test');
        $this->info('2. If still failing, check for database migration issues');
        $this->info('3. Ensure PHPUnit configuration is correct in phpunit.xml');
    }
    
    private function fixBackupFiles()
    {
        $this->info('Cleaning up backup files...');
        
        // Remove backup files that might interfere
        $backupPattern = base_path('tests/**/*.backup.*');
        $backupFiles = glob($backupPattern, GLOB_BRACE);
        
        foreach ($backupFiles as $file) {
            if (File::exists($file)) {
                File::delete($file);
                $this->info("Removed backup: " . basename($file));
            }
        }
    }
}