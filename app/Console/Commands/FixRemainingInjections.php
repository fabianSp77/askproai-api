<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FixRemainingInjections extends Command
{
    protected $signature = 'security:fix-remaining-injections {--dry-run : Show what would be changed without making changes}';
    protected $description = 'Fix remaining SQL injection vulnerabilities';

    private $fixes = [
        // DatabaseMCPServer.php fixes
        [
            'file' => 'app/Services/MCP/DatabaseMCPServer.php',
            'pattern' => '/DB::select\("SHOW COLUMNS FROM " \. self::quoteIdentifier\(\$table\)\)/',
            'replacement' => 'DB::select("SHOW COLUMNS FROM ?", [$this->sanitizeTableName($table)])',
            'line' => 255
        ],
        [
            'file' => 'app/Services/MCP/DatabaseMCPServer.php',
            'pattern' => '/DB::select\("SHOW INDEX FROM " \. self::quoteIdentifier\(\$table\)\)/',
            'replacement' => 'DB::select("SHOW INDEX FROM ?", [$this->sanitizeTableName($table)])',
            'line' => 270
        ],
        
        // PhoneNumberResolver.php fixes
        [
            'file' => 'app/Services/PhoneNumberResolver.php',
            'pattern' => '/->whereRaw\("number LIKE \?", \[\$phonePattern \. \'%\'\]\)/',
            'replacement' => '->where(\'number\', \'LIKE\', $phonePattern . \'%\')',
            'line' => 267
        ],
        [
            'file' => 'app/Services/PhoneNumberResolver.php',
            'pattern' => '/->whereRaw\("phone LIKE \?", \[\$phonePattern \. \'%\'\]\)/',
            'replacement' => '->where(\'phone\', \'LIKE\', $phonePattern . \'%\')',
            'line' => 461
        ],
        
        // FindDuplicates.php fixes
        [
            'file' => 'app/Filament/Admin/Resources/CustomerResource/Pages/FindDuplicates.php',
            'pattern' => '/->whereRaw\(\'LOWER\(name\) = \?\', \[strtolower\(\$customer->name\)\]\)/',
            'replacement' => '->whereRaw(\'LOWER(name) = LOWER(?)\', [$customer->name])',
            'line' => 41
        ],
        [
            'file' => 'app/Filament/Admin/Resources/CustomerResource/Pages/FindDuplicates.php',
            'pattern' => '/->whereRaw\(\'LOWER\(email\) = \?\', \[strtolower\(\$customer->email\)\]\)/',
            'replacement' => '->whereRaw(\'LOWER(email) = LOWER(?)\', [$customer->email])',
            'line' => 53
        ],
    ];

    public function handle()
    {
        $this->info('Starting SQL injection fixes...');
        
        $fixedCount = 0;
        $errorCount = 0;
        
        foreach ($this->fixes as $fix) {
            $filePath = base_path($fix['file']);
            
            if (!File::exists($filePath)) {
                $this->error("File not found: {$fix['file']}");
                $errorCount++;
                continue;
            }
            
            $content = File::get($filePath);
            $originalContent = $content;
            
            // Apply fix
            $content = preg_replace($fix['pattern'], $fix['replacement'], $content, -1, $count);
            
            if ($count > 0) {
                if ($this->option('dry-run')) {
                    $this->line("Would fix in {$fix['file']} at line {$fix['line']}:");
                    $this->info("  Old: " . trim($fix['pattern'], '/'));
                    $this->info("  New: {$fix['replacement']}");
                } else {
                    File::put($filePath, $content);
                    $this->info("Fixed in {$fix['file']} at line {$fix['line']}");
                }
                $fixedCount++;
            }
        }
        
        // Add sanitizeTableName method to DatabaseMCPServer if needed
        if (!$this->option('dry-run')) {
            $this->addSanitizeTableNameMethod();
        }
        
        $this->info("\nSummary:");
        $this->info("Fixed: $fixedCount vulnerabilities");
        if ($errorCount > 0) {
            $this->error("Errors: $errorCount");
        }
        
        if ($this->option('dry-run')) {
            $this->warn("This was a dry run. No files were modified.");
        }
        
        return $errorCount > 0 ? 1 : 0;
    }
    
    private function addSanitizeTableNameMethod()
    {
        $filePath = base_path('app/Services/MCP/DatabaseMCPServer.php');
        $content = File::get($filePath);
        
        // Check if method already exists
        if (strpos($content, 'private function sanitizeTableName') !== false) {
            return;
        }
        
        // Add method before the last closing brace
        $methodCode = '
    /**
     * Sanitize table name to prevent SQL injection
     */
    private function sanitizeTableName(string $table): string
    {
        // Only allow alphanumeric, underscore, and dot (for database.table notation)
        if (!preg_match(\'/^[a-zA-Z0-9_\.]+$/\', $table)) {
            throw new \InvalidArgumentException(\'Invalid table name\');
        }
        
        // Additional check against known table whitelist
        $allowedTables = array_merge(
            Schema::getConnection()->getDoctrineSchemaManager()->listTableNames(),
            [\'information_schema.columns\', \'information_schema.statistics\']
        );
        
        if (!in_array($table, $allowedTables)) {
            throw new \InvalidArgumentException(\'Table not in whitelist\');
        }
        
        return $table;
    }
';
        
        // Insert before the last closing brace
        $lastBracePos = strrpos($content, '}');
        $content = substr($content, 0, $lastBracePos) . $methodCode . "\n" . substr($content, $lastBracePos);
        
        File::put($filePath, $content);
        $this->info('Added sanitizeTableName method to DatabaseMCPServer.php');
    }
}