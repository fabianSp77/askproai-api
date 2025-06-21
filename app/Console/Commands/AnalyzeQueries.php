<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Monitoring\QueryMonitor;
use Illuminate\Support\Facades\DB;

class AnalyzeQueries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queries:analyze 
                            {--url= : URL to analyze} 
                            {--model= : Specific model to check}
                            {--fix : Suggest fixes for N+1 queries}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze database queries for N+1 problems and performance issues';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting query analysis...');
        
        if ($url = $this->option('url')) {
            $this->analyzeUrl($url);
        } elseif ($model = $this->option('model')) {
            $this->analyzeModel($model);
        } else {
            $this->analyzeCommonPatterns();
        }
        
        return 0;
    }
    
    /**
     * Analyze queries for a specific URL
     */
    protected function analyzeUrl(string $url): void
    {
        $this->info("Analyzing queries for: {$url}");
        
        // Start monitoring
        QueryMonitor::start();
        
        // Make internal request
        $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
        $request = \Illuminate\Http\Request::create($url, 'GET');
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
        
        // Get results
        $results = QueryMonitor::stop();
        
        $this->displayResults($results);
    }
    
    /**
     * Analyze a specific model for N+1 issues
     */
    protected function analyzeModel(string $model): void
    {
        $modelClass = "App\\Models\\{$model}";
        
        if (!class_exists($modelClass)) {
            $this->error("Model {$modelClass} not found");
            return;
        }
        
        $this->info("Analyzing {$model} model for N+1 queries...");
        
        // Get relationships
        $instance = new $modelClass;
        $relationships = $this->getRelationships($instance);
        
        $this->table(['Relationship', 'Type', 'Related Model', 'Eager Loading'], 
            array_map(function($rel) use ($instance) {
                $method = $rel['method'];
                $relation = $instance->$method();
                
                return [
                    $rel['method'],
                    class_basename(get_class($relation)),
                    $relation->getRelated()::class,
                    $this->checkEagerLoading($instance, $rel['method']) ? '✓' : '✗'
                ];
            }, $relationships)
        );
        
        if ($this->option('fix')) {
            $this->suggestFixes($modelClass, $relationships);
        }
    }
    
    /**
     * Analyze common N+1 patterns
     */
    protected function analyzeCommonPatterns(): void
    {
        $this->info('Analyzing common N+1 patterns in the codebase...');
        
        $patterns = [
            'Filament Resources' => $this->analyzeFilamentResources(),
            'API Controllers' => $this->analyzeControllers(),
            'Blade Views' => $this->analyzeBladeViews(),
        ];
        
        foreach ($patterns as $type => $issues) {
            if (!empty($issues)) {
                $this->warn("\n{$type}:");
                foreach ($issues as $issue) {
                    $this->line("  - {$issue}");
                }
            }
        }
    }
    
    /**
     * Display query analysis results
     */
    protected function displayResults(array $results): void
    {
        $this->info("\nQuery Analysis Results:");
        $this->line("Total Queries: {$results['total_queries']}");
        $this->line("Total Time: {$results['total_time_ms']}ms");
        
        if (!empty($results['n1_queries'])) {
            $this->error("\nN+1 Queries Detected:");
            foreach ($results['n1_queries'] as $n1) {
                $this->warn("  Table: {$n1['table']} - Count: {$n1['count']}");
                $this->line("  Pattern: {$n1['pattern']}");
                if ($this->option('fix')) {
                    $this->info("  Fix: Add ->with('{$n1['table']}') to your query");
                }
            }
        }
        
        if (!empty($results['slow_queries'])) {
            $this->error("\nSlow Queries:");
            foreach ($results['slow_queries'] as $query) {
                $this->warn("  Time: {$query['time']}ms");
                $this->line("  SQL: {$query['sql']}");
            }
        }
        
        if (!empty($results['duplicate_queries'])) {
            $this->warn("\nDuplicate Queries:");
            foreach ($results['duplicate_queries'] as $dup) {
                $this->line("  Count: {$dup['count']} - Total Time: {$dup['total_time']}ms");
                $this->line("  SQL: {$dup['sql']}");
            }
        }
    }
    
    /**
     * Get relationships from a model
     */
    protected function getRelationships($model): array
    {
        $relationships = [];
        $methods = get_class_methods($model);
        
        foreach ($methods as $method) {
            if (method_exists($model, $method) && !method_exists(\Illuminate\Database\Eloquent\Model::class, $method)) {
                try {
                    $return = new \ReflectionMethod($model, $method);
                    $returnType = $return->getReturnType();
                    
                    if ($returnType && in_array($returnType->getName(), [
                        \Illuminate\Database\Eloquent\Relations\HasOne::class,
                        \Illuminate\Database\Eloquent\Relations\HasMany::class,
                        \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
                        \Illuminate\Database\Eloquent\Relations\BelongsToMany::class,
                        \Illuminate\Database\Eloquent\Relations\MorphOne::class,
                        \Illuminate\Database\Eloquent\Relations\MorphMany::class,
                        \Illuminate\Database\Eloquent\Relations\MorphTo::class,
                    ])) {
                        $relationships[] = ['method' => $method];
                    }
                } catch (\Exception $e) {
                    // Skip methods that throw exceptions
                }
            }
        }
        
        return $relationships;
    }
    
    /**
     * Check if eager loading is used for a relationship
     */
    protected function checkEagerLoading($model, string $relationship): bool
    {
        // This is a simplified check - in real scenarios, you'd analyze actual queries
        return in_array($relationship, $model->with ?? []);
    }
    
    /**
     * Suggest fixes for N+1 queries
     */
    protected function suggestFixes(string $modelClass, array $relationships): void
    {
        $this->info("\nSuggested Fixes:");
        
        $eagerLoad = array_map(fn($rel) => "'{$rel['method']}'", $relationships);
        $this->line("\n// In your query:");
        $this->line("{$modelClass}::with([" . implode(', ', $eagerLoad) . "])->get();");
        
        $this->line("\n// In Filament Resource:");
        $this->line("->modifyQueryUsing(fn (\$query) => \$query->with([" . implode(', ', $eagerLoad) . "]))");
    }
    
    /**
     * Analyze Filament resources for N+1 issues
     */
    protected function analyzeFilamentResources(): array
    {
        $issues = [];
        $resourcePath = app_path('Filament/Admin/Resources');
        
        if (!is_dir($resourcePath)) {
            return $issues;
        }
        
        $files = glob($resourcePath . '/*Resource.php');
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            
            // Check for missing eager loading
            if (!str_contains($content, 'modifyQueryUsing') && !str_contains($content, '->with(')) {
                $issues[] = basename($file) . " - No eager loading detected";
            }
        }
        
        return $issues;
    }
    
    /**
     * Analyze controllers for N+1 issues
     */
    protected function analyzeControllers(): array
    {
        $issues = [];
        $controllerPath = app_path('Http/Controllers');
        
        $files = glob($controllerPath . '/**/*Controller.php');
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            
            // Look for potential N+1 patterns
            if (preg_match('/\$\w+->map\(|@foreach.*\$\w+->/', $content)) {
                if (!str_contains($content, '->with(') && !str_contains($content, '->load(')) {
                    $issues[] = basename($file) . " - Potential N+1 in loops";
                }
            }
        }
        
        return $issues;
    }
    
    /**
     * Analyze Blade views for N+1 issues
     */
    protected function analyzeBladeViews(): array
    {
        $issues = [];
        $viewPath = resource_path('views');
        
        $files = glob($viewPath . '/**/*.blade.php');
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            
            // Look for relationship access in loops
            if (preg_match('/@foreach.*\$\w+->(?!count|exists|isEmpty|isNotEmpty)/', $content)) {
                $issues[] = str_replace(resource_path('views/'), '', $file) . " - Relationship access in loop";
            }
        }
        
        return $issues;
    }
}