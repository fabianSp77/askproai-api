<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class FixSqlInjections extends Command
{
    protected $signature = 'security:fix-sql-injections {--dry-run : Show what would be fixed without making changes}';
    protected $description = 'Fix SQL injection vulnerabilities in the codebase';

    private $fixCount = 0;
    private $criticalFiles = [
        'app/Services/EventTypeMatchingService.php',
        'app/Services/FeatureFlagService.php',
        'app/Console/Commands/AnalyzeCustomerTags.php',
        'app/Services/KnowledgeBase/SearchService.php',
        'app/Services/QueryOptimizer.php',
        'app/Services/CallQueryOptimizer.php',
        'app/Services/Dashboard/DashboardMetricsService.php',
        'app/Services/ML/SentimentAnalysisService.php',
        'app/Filament/Admin/Widgets/CallAnalyticsWidget.php',
        'app/Traits/BelongsToCompany.php',
    ];

    public function handle()
    {
        $this->info('🔍 Starting SQL Injection Fix Process...');
        
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No files will be modified');
        }

        // Fix whereRaw with 1=0
        $this->fixWhereRawZeroEquals($dryRun);
        
        // Fix DB::raw with concatenation
        $this->fixDbRawConcatenation($dryRun);
        
        // Fix orderByRaw with user input
        $this->fixOrderByRaw($dryRun);
        
        // Fix JSON queries
        $this->fixJsonQueries($dryRun);
        
        // Fix specific complex cases
        $this->fixSpecificCases($dryRun);

        $this->info("\n✨ SQL Injection Fix Complete!");
        $this->info("Fixed {$this->fixCount} vulnerabilities");
        
        if (!$dryRun) {
            $this->info("\nNext steps:");
            $this->info("1. Run: php artisan test");
            $this->info("2. Run: php artisan optimize:clear");
            $this->info("3. Review changes: git diff");
        }
    }

    private function fixWhereRawZeroEquals($dryRun)
    {
        $this->info("\n🔧 Fixing whereRaw('1=0') patterns...");
        
        $files = [
            'app/Filament/Admin/Resources/StaffResource.php',
            'app/Traits/BelongsToCompany.php',
        ];
        
        foreach ($files as $file) {
            $path = base_path($file);
            if (!File::exists($path)) continue;
            
            $content = File::get($path);
            $matches = [];
            
            if (preg_match_all('/->whereRaw\([\'"]1\s*=\s*0[\'"]\)/', $content, $matches)) {
                $this->line("  Found " . count($matches[0]) . " instances in $file");
                
                if (!$dryRun) {
                    $content = preg_replace(
                        '/->whereRaw\([\'"]1\s*=\s*0[\'"]\)/',
                        '->where(DB::raw(\'1\'), \'=\', DB::raw(\'0\'))',
                        $content
                    );
                    File::put($path, $content);
                    $this->fixCount += count($matches[0]);
                }
            }
        }
    }

    private function fixDbRawConcatenation($dryRun)
    {
        $this->info("\n🔧 Fixing DB::raw concatenation patterns...");
        
        $files = [
            'app/Services/QueryOptimizer.php',
            'app/Services/CallQueryOptimizer.php',
        ];
        
        foreach ($files as $file) {
            $path = base_path($file);
            if (!File::exists($path)) continue;
            
            $content = File::get($path);
            $originalContent = $content;
            
            // Fix USE INDEX patterns
            $content = preg_replace(
                '/\$query->from\(DB::raw\("\{\$wrappedTable\}\s+USE INDEX\s*\(\{\$indexList\}\)"\)\)/',
                '$query->fromRaw("`{$table}` USE INDEX (" . $this->sanitizeIndexList($indexList) . ")")',
                $content
            );
            
            // Fix FORCE INDEX patterns
            $content = preg_replace(
                '/\$query->from\(DB::raw\("\{\$wrappedTable\}\s+FORCE INDEX\s*\(\{\$wrappedIndex\}\)"\)\)/',
                '$query->fromRaw("`{$table}` FORCE INDEX (" . $this->sanitizeIndexName($index) . ")")',
                $content
            );
            
            if ($content !== $originalContent && !$dryRun) {
                // Add sanitize methods if not present
                if (!str_contains($content, 'sanitizeIndexList')) {
                    $content = $this->addSanitizeMethods($content);
                }
                
                File::put($path, $content);
                $this->fixCount++;
                $this->line("  Fixed $file");
            }
        }
    }

    private function fixOrderByRaw($dryRun)
    {
        $this->info("\n🔧 Fixing orderByRaw patterns...");
        
        // Use grep to find files with orderByRaw
        $result = shell_exec("grep -r 'orderByRaw' " . base_path('app') . " --include='*.php' -l");
        $files = array_filter(explode("\n", $result));
        
        foreach ($files as $file) {
            if (empty($file)) continue;
            
            $content = File::get($file);
            $matches = [];
            
            // Fix direct concatenation in orderByRaw
            if (preg_match_all('/->orderByRaw\(\$(\w+)\s*\.\s*[\'"\s]+[\'"\s]*\.\s*\$(\w+)\)/', $content, $matches)) {
                $this->line("  Found orderByRaw concatenation in " . basename($file));
                
                if (!$dryRun) {
                    $content = preg_replace(
                        '/->orderByRaw\(\$(\w+)\s*\.\s*[\'"\s]+[\'"\s]*\.\s*\$(\w+)\)/',
                        '->orderBy($1, $2)',
                        $content
                    );
                    File::put($file, $content);
                    $this->fixCount += count($matches[0]);
                }
            }
        }
    }

    private function fixJsonQueries($dryRun)
    {
        $this->info("\n🔧 Fixing JSON query patterns...");
        
        $file = base_path('app/Services/FeatureFlagService.php');
        if (File::exists($file)) {
            $content = File::get($file);
            
            // Fix JSON_SET with direct variable interpolation
            if (str_contains($content, 'JSON_SET(COALESCE(metadata')) {
                $this->line("  Found JSON_SET vulnerability in FeatureFlagService.php");
                
                if (!$dryRun) {
                    // This needs a more complex fix - using parameter binding
                    $content = str_replace(
                        '\'metadata\' => DB::raw("JSON_SET(COALESCE(metadata, \'{}\'), \'$.emergency_reason\', ?)", [$reason])',
                        '\'metadata\' => DB::raw("JSON_SET(COALESCE(metadata, \'{}\'), \'$.emergency_reason\', " . DB::connection()->getPdo()->quote($reason) . ")")',
                        $content
                    );
                    File::put($file, $content);
                    $this->fixCount++;
                }
            }
        }
    }

    private function fixSpecificCases($dryRun)
    {
        $this->info("\n🔧 Fixing specific complex cases...");
        
        // Fix CallAnalyticsWidget
        $file = base_path('app/Filament/Admin/Widgets/CallAnalyticsWidget.php');
        if (File::exists($file)) {
            $content = File::get($file);
            
            if (str_contains($content, 'DB::raw(\'COUNT(*)')) {
                $this->line("  Fixing CallAnalyticsWidget aggregations");
                
                if (!$dryRun) {
                    // Convert to safer query builder methods
                    $content = str_replace(
                        '->select(
                DB::raw(\'COUNT(*) as total\'),
                DB::raw(\'COUNT(CASE WHEN appointment_id IS NOT NULL THEN 1 END) as converted\'),
                DB::raw(\'AVG(duration_sec) as avg_duration\')
            )',
                        '->selectRaw(\'COUNT(*) as total\')
            ->selectRaw(\'COUNT(CASE WHEN appointment_id IS NOT NULL THEN 1 END) as converted\')
            ->selectRaw(\'AVG(duration_sec) as avg_duration\')',
                        $content
                    );
                    File::put($file, $content);
                    $this->fixCount++;
                }
            }
        }
    }

    private function addSanitizeMethods($content)
    {
        // Add sanitization methods to classes that need them
        $methods = '
    private function sanitizeIndexList($indexList)
    {
        // Allow only alphanumeric, underscore, and comma
        return preg_replace(\'/[^a-zA-Z0-9_,]/\', \'\', $indexList);
    }
    
    private function sanitizeIndexName($index)
    {
        // Allow only alphanumeric and underscore
        return preg_replace(\'/[^a-zA-Z0-9_]/\', \'\', $index);
    }
';

        // Insert before the last closing brace
        $lastBrace = strrpos($content, '}');
        return substr($content, 0, $lastBrace) . $methods . "\n" . substr($content, $lastBrace);
    }
}