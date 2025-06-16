<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EagerLoadingAnalyzer;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DetectN1QueriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'optimize:detect-n1 
                            {--path= : Specific path to analyze}
                            {--model= : Analyze a specific model}
                            {--fix : Suggest fixes for detected issues}
                            {--report : Generate detailed report}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detect N+1 query problems in the codebase';

    protected EagerLoadingAnalyzer $analyzer;
    protected array $findings = [];
    
    public function __construct(EagerLoadingAnalyzer $analyzer)
    {
        parent::__construct();
        $this->analyzer = $analyzer;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Detecting N+1 queries in the codebase...');
        
        if ($model = $this->option('model')) {
            $this->analyzeModel($model);
        } elseif ($path = $this->option('path')) {
            $this->analyzePath($path);
        } else {
            $this->analyzeFullCodebase();
        }
        
        $this->displayFindings();
        
        if ($this->option('fix')) {
            $this->suggestFixes();
        }
        
        if ($this->option('report')) {
            $this->generateReport();
        }
        
        return 0;
    }
    
    /**
     * Analyze a specific model
     */
    protected function analyzeModel(string $modelClass): void
    {
        if (!class_exists($modelClass)) {
            $modelClass = "App\\Models\\{$modelClass}";
        }
        
        if (!class_exists($modelClass)) {
            $this->error("Model class {$modelClass} not found");
            return;
        }
        
        $this->info("Analyzing model: {$modelClass}");
        
        $analysis = $this->analyzer->analyzeModel($modelClass);
        $this->findings['models'][$modelClass] = $analysis;
        
        // Analyze usages of this model
        $this->analyzeModelUsages($modelClass);
    }
    
    /**
     * Analyze files in a specific path
     */
    protected function analyzePath(string $path): void
    {
        $files = File::allFiles($path);
        
        $this->withProgressBar($files, function ($file) {
            $this->analyzeFile($file->getPathname());
        });
    }
    
    /**
     * Analyze the full codebase
     */
    protected function analyzeFullCodebase(): void
    {
        $this->info('Analyzing Models...');
        $this->analyzeModels();
        
        $this->info('Analyzing Controllers...');
        $this->analyzeControllers();
        
        $this->info('Analyzing Repositories...');
        $this->analyzeRepositories();
        
        $this->info('Analyzing Filament Resources...');
        $this->analyzeFilamentResources();
        
        $this->info('Analyzing Blade Views...');
        $this->analyzeBladeViews();
    }
    
    /**
     * Analyze all models
     */
    protected function analyzeModels(): void
    {
        $modelFiles = File::files(app_path('Models'));
        
        foreach ($modelFiles as $file) {
            $className = 'App\\Models\\' . $file->getFilenameWithoutExtension();
            
            if (class_exists($className)) {
                $analysis = $this->analyzer->analyzeModel($className);
                $this->findings['models'][$className] = $analysis;
            }
        }
    }
    
    /**
     * Analyze controllers for N+1 patterns
     */
    protected function analyzeControllers(): void
    {
        $controllerFiles = File::allFiles(app_path('Http/Controllers'));
        
        foreach ($controllerFiles as $file) {
            $issues = $this->analyzeFileForN1Patterns($file->getPathname());
            if (!empty($issues)) {
                $this->findings['controllers'][$file->getRelativePathname()] = $issues;
            }
        }
    }
    
    /**
     * Analyze repositories
     */
    protected function analyzeRepositories(): void
    {
        if (!is_dir(app_path('Repositories'))) {
            return;
        }
        
        $repositoryFiles = File::allFiles(app_path('Repositories'));
        
        foreach ($repositoryFiles as $file) {
            $issues = $this->analyzeFileForN1Patterns($file->getPathname());
            if (!empty($issues)) {
                $this->findings['repositories'][$file->getRelativePathname()] = $issues;
            }
        }
    }
    
    /**
     * Analyze Filament resources
     */
    protected function analyzeFilamentResources(): void
    {
        $resourcePaths = [
            app_path('Filament/Resources'),
            app_path('Filament/Admin/Resources'),
        ];
        
        foreach ($resourcePaths as $path) {
            if (!is_dir($path)) {
                continue;
            }
            
            $resourceFiles = File::allFiles($path);
            
            foreach ($resourceFiles as $file) {
                $issues = $this->analyzeFilamentResource($file->getPathname());
                if (!empty($issues)) {
                    $this->findings['filament'][$file->getRelativePathname()] = $issues;
                }
            }
        }
    }
    
    /**
     * Analyze Blade views
     */
    protected function analyzeBladeViews(): void
    {
        $viewFiles = File::allFiles(resource_path('views'));
        
        foreach ($viewFiles as $file) {
            if ($file->getExtension() === 'php') {
                $issues = $this->analyzeBladeFile($file->getPathname());
                if (!empty($issues)) {
                    $this->findings['views'][$file->getRelativePathname()] = $issues;
                }
            }
        }
    }
    
    /**
     * Analyze a file for N+1 patterns
     */
    protected function analyzeFileForN1Patterns(string $filepath): array
    {
        $content = File::get($filepath);
        $issues = [];
        
        // Pattern 1: Loops with relationship access
        if (preg_match_all('/foreach\s*\([^)]+\)\s*{[^}]*->((?!with|load)[a-zA-Z_]+)(?:\(\))?[^}]*}/s', $content, $matches)) {
            foreach ($matches[1] as $match) {
                if ($this->looksLikeRelationship($match)) {
                    $issues[] = [
                        'type' => 'loop_relationship_access',
                        'relationship' => $match,
                        'severity' => 'high',
                        'line' => $this->getLineNumber($content, $match),
                    ];
                }
            }
        }
        
        // Pattern 2: Missing eager loading in queries
        if (preg_match_all('/(\w+)::(?:all|get|find|paginate)\(\)/', $content, $matches)) {
            foreach ($matches[0] as $index => $match) {
                $model = $matches[1][$index];
                if ($this->isEloquentModel($model) && !$this->hasEagerLoading($content, $match)) {
                    $issues[] = [
                        'type' => 'missing_eager_loading',
                        'model' => $model,
                        'severity' => 'medium',
                        'line' => $this->getLineNumber($content, $match),
                    ];
                }
            }
        }
        
        // Pattern 3: whereHas without with
        if (preg_match_all('/->whereHas\([\'"](\w+)[\'"]/', $content, $matches)) {
            foreach ($matches[1] as $relationship) {
                if (!preg_match("/->with\(['\"]?{$relationship}['\"]?\)/", $content)) {
                    $issues[] = [
                        'type' => 'wherehas_without_with',
                        'relationship' => $relationship,
                        'severity' => 'medium',
                        'line' => $this->getLineNumber($content, "whereHas('{$relationship}'"),
                    ];
                }
            }
        }
        
        return $issues;
    }
    
    /**
     * Analyze Filament resource file
     */
    protected function analyzeFilamentResource(string $filepath): array
    {
        $content = File::get($filepath);
        $issues = [];
        
        // Check for missing modifyQueryUsing
        if (!Str::contains($content, 'modifyQueryUsing')) {
            // Check if there are relationship columns
            if (preg_match_all('/TextColumn::make\([\'"](\w+\.\w+)[\'"]/', $content, $matches)) {
                $issues[] = [
                    'type' => 'missing_modify_query',
                    'relationships' => array_unique(array_map(fn($r) => explode('.', $r)[0], $matches[1])),
                    'severity' => 'high',
                ];
            }
        }
        
        // Check for relationship columns without eager loading
        if (preg_match_all('/(?:TextColumn|BadgeColumn)::make\([\'"](\w+)\.(\w+)[\'"]/', $content, $matches)) {
            $relationships = array_unique($matches[1]);
            
            foreach ($relationships as $relationship) {
                if (!preg_match("/with\([^)]*['\"]?{$relationship}['\"]?/", $content)) {
                    $issues[] = [
                        'type' => 'relationship_column_without_eager_loading',
                        'relationship' => $relationship,
                        'severity' => 'high',
                    ];
                }
            }
        }
        
        return $issues;
    }
    
    /**
     * Analyze Blade file
     */
    protected function analyzeBladeFile(string $filepath): array
    {
        $content = File::get($filepath);
        $issues = [];
        
        // Pattern: @foreach with relationship access
        if (preg_match_all('/@foreach\s*\([^)]+\)\s*(.*?)@endforeach/s', $content, $matches)) {
            foreach ($matches[1] as $loopContent) {
                if (preg_match_all('/\$\w+->(\w+)(?:\(\))?/', $loopContent, $relationMatches)) {
                    foreach ($relationMatches[1] as $potential) {
                        if ($this->looksLikeRelationship($potential)) {
                            $issues[] = [
                                'type' => 'blade_loop_relationship',
                                'relationship' => $potential,
                                'severity' => 'high',
                            ];
                        }
                    }
                }
            }
        }
        
        return $issues;
    }
    
    /**
     * Display findings
     */
    protected function displayFindings(): void
    {
        if (empty($this->findings)) {
            $this->info('✅ No N+1 query issues detected!');
            return;
        }
        
        $totalIssues = 0;
        
        foreach ($this->findings as $category => $items) {
            $this->warn("\n📁 {$category}:");
            
            foreach ($items as $path => $issues) {
                $this->line("  📄 {$path}:");
                
                if (isset($issues['warnings'])) {
                    foreach ($issues['warnings'] as $warning) {
                        $this->line("    ⚠️  {$warning}");
                        $totalIssues++;
                    }
                }
                
                if (is_array($issues) && isset($issues[0]['type'])) {
                    foreach ($issues as $issue) {
                        $icon = $issue['severity'] === 'high' ? '🔴' : '🟡';
                        $this->line("    {$icon} {$issue['type']}: " . $this->formatIssue($issue));
                        $totalIssues++;
                    }
                }
            }
        }
        
        $this->error("\n❌ Total issues found: {$totalIssues}");
    }
    
    /**
     * Suggest fixes for detected issues
     */
    protected function suggestFixes(): void
    {
        $this->info("\n💡 Suggested Fixes:");
        
        foreach ($this->findings as $category => $items) {
            foreach ($items as $path => $issues) {
                if (!is_array($issues) || !isset($issues[0]['type'])) {
                    continue;
                }
                
                foreach ($issues as $issue) {
                    $fix = $this->generateFix($issue);
                    if ($fix) {
                        $this->info("\n{$path}:");
                        $this->line($fix);
                    }
                }
            }
        }
    }
    
    /**
     * Generate fix suggestion
     */
    protected function generateFix(array $issue): ?string
    {
        return match($issue['type']) {
            'loop_relationship_access' => "Add eager loading: ->with('{$issue['relationship']}')",
            'missing_eager_loading' => "Use eager loading: {$issue['model']}::with(['relation1', 'relation2'])->get()",
            'wherehas_without_with' => "Add: ->with('{$issue['relationship']}') after ->whereHas('{$issue['relationship']}')",
            'missing_modify_query' => "Add to table(): ->modifyQueryUsing(fn (\$query) => \$query->with([" . implode(', ', array_map(fn($r) => "'{$r}'", $issue['relationships'])) . "]))",
            'relationship_column_without_eager_loading' => "Add eager loading for '{$issue['relationship']}' relationship",
            'blade_loop_relationship' => "Pass eager loaded data from controller instead of accessing '{$issue['relationship']}' in view",
            default => null,
        };
    }
    
    /**
     * Generate detailed report
     */
    protected function generateReport(): void
    {
        $reportPath = storage_path('logs/n1-query-report-' . now()->format('Y-m-d-H-i-s') . '.json');
        
        File::put($reportPath, json_encode([
            'generated_at' => now()->toIso8601String(),
            'findings' => $this->findings,
            'summary' => $this->generateSummary(),
            'recommendations' => $this->generateRecommendations(),
        ], JSON_PRETTY_PRINT));
        
        $this->info("\n📊 Report generated: {$reportPath}");
    }
    
    /**
     * Helper methods
     */
    protected function looksLikeRelationship(string $name): bool
    {
        $relationshipNames = [
            'user', 'users', 'customer', 'customers', 'staff', 'branch', 'branches',
            'service', 'services', 'appointment', 'appointments', 'company', 'companies',
            'calls', 'integrations', 'eventTypes', 'bookings', 'calcomBooking',
        ];
        
        return in_array($name, $relationshipNames) || Str::endsWith($name, 's');
    }
    
    protected function isEloquentModel(string $class): bool
    {
        $modelClass = "App\\Models\\{$class}";
        return class_exists($modelClass) && is_subclass_of($modelClass, \Illuminate\Database\Eloquent\Model::class);
    }
    
    protected function hasEagerLoading(string $content, string $query): bool
    {
        $position = strpos($content, $query);
        if ($position === false) return false;
        
        $before = substr($content, max(0, $position - 100), 100);
        $after = substr($content, $position, 200);
        
        return Str::contains($before . $after, ['->with(', '::with(']);
    }
    
    protected function getLineNumber(string $content, string $search): int
    {
        $position = strpos($content, $search);
        if ($position === false) return 0;
        
        return substr_count(substr($content, 0, $position), "\n") + 1;
    }
    
    protected function formatIssue(array $issue): string
    {
        $parts = [];
        
        if (isset($issue['relationship'])) {
            $parts[] = "relationship '{$issue['relationship']}'";
        }
        
        if (isset($issue['model'])) {
            $parts[] = "model '{$issue['model']}'";
        }
        
        if (isset($issue['line']) && $issue['line'] > 0) {
            $parts[] = "line {$issue['line']}";
        }
        
        return implode(', ', $parts);
    }
    
    protected function analyzeModelUsages(string $modelClass): void
    {
        // Analyze where this model is used
        $shortName = class_basename($modelClass);
        
        // Check controllers
        $controllerFiles = File::allFiles(app_path('Http/Controllers'));
        foreach ($controllerFiles as $file) {
            $content = File::get($file->getPathname());
            if (Str::contains($content, $shortName)) {
                $issues = $this->analyzeFileForN1Patterns($file->getPathname());
                if (!empty($issues)) {
                    $this->findings['model_usage'][$file->getRelativePathname()] = $issues;
                }
            }
        }
    }
    
    protected function generateSummary(): array
    {
        $summary = [
            'total_issues' => 0,
            'by_severity' => ['high' => 0, 'medium' => 0, 'low' => 0],
            'by_type' => [],
        ];
        
        foreach ($this->findings as $items) {
            foreach ($items as $issues) {
                if (is_array($issues) && isset($issues[0]['type'])) {
                    foreach ($issues as $issue) {
                        $summary['total_issues']++;
                        $summary['by_severity'][$issue['severity']]++;
                        $summary['by_type'][$issue['type']] = ($summary['by_type'][$issue['type']] ?? 0) + 1;
                    }
                }
            }
        }
        
        return $summary;
    }
    
    protected function generateRecommendations(): array
    {
        return [
            'Use repository pattern with eager loading profiles',
            'Implement SmartLoader trait on models',
            'Add modifyQueryUsing to all Filament resources',
            'Move data fetching logic from views to controllers',
            'Use withCount() for relationship counts instead of loading full relations',
            'Enable query logging in development to catch N+1 queries early',
        ];
    }
}