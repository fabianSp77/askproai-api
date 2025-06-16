<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class EagerLoadingAnalyzer
{
    protected array $queries = [];
    protected array $suspiciousPatterns = [];
    protected bool $isAnalyzing = false;
    protected array $ignoredTables = ['migrations', 'cache', 'sessions', 'jobs'];
    
    /**
     * Start analyzing queries for N+1 problems
     */
    public function startAnalysis(): void
    {
        $this->isAnalyzing = true;
        $this->queries = [];
        $this->suspiciousPatterns = [];
        
        DB::listen(function (QueryExecuted $query) {
            if ($this->isAnalyzing) {
                $this->recordQuery($query);
            }
        });
    }
    
    /**
     * Stop analyzing and return results
     */
    public function stopAnalysis(): array
    {
        $this->isAnalyzing = false;
        $this->detectN1Queries();
        
        return [
            'total_queries' => count($this->queries),
            'suspicious_patterns' => $this->suspiciousPatterns,
            'recommendations' => $this->generateRecommendations(),
            'query_breakdown' => $this->getQueryBreakdown(),
        ];
    }
    
    /**
     * Record a query for analysis
     */
    protected function recordQuery(QueryExecuted $query): void
    {
        $sql = $query->sql;
        $bindings = $query->bindings;
        $time = $query->time;
        
        // Skip queries on ignored tables
        foreach ($this->ignoredTables as $table) {
            if (Str::contains($sql, $table)) {
                return;
            }
        }
        
        $this->queries[] = [
            'sql' => $sql,
            'bindings' => $bindings,
            'time' => $time,
            'normalized' => $this->normalizeQuery($sql),
            'table' => $this->extractTableName($sql),
            'type' => $this->getQueryType($sql),
        ];
    }
    
    /**
     * Normalize query for pattern matching
     */
    protected function normalizeQuery(string $sql): string
    {
        // Remove specific values and keep only the structure
        $normalized = preg_replace('/\b\d+\b/', '?', $sql);
        $normalized = preg_replace('/\'[^\']*\'/', '?', $normalized);
        $normalized = preg_replace('/\"[^\"]*\"/', '?', $normalized);
        $normalized = preg_replace('/\s+/', ' ', trim($normalized));
        
        return $normalized;
    }
    
    /**
     * Extract table name from query
     */
    protected function extractTableName(string $sql): ?string
    {
        // Match various SQL patterns
        if (preg_match('/from\s+`?(\w+)`?/i', $sql, $matches)) {
            return $matches[1];
        }
        if (preg_match('/update\s+`?(\w+)`?/i', $sql, $matches)) {
            return $matches[1];
        }
        if (preg_match('/into\s+`?(\w+)`?/i', $sql, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Determine query type
     */
    protected function getQueryType(string $sql): string
    {
        $sql = strtolower(trim($sql));
        
        if (Str::startsWith($sql, 'select')) return 'select';
        if (Str::startsWith($sql, 'insert')) return 'insert';
        if (Str::startsWith($sql, 'update')) return 'update';
        if (Str::startsWith($sql, 'delete')) return 'delete';
        
        return 'other';
    }
    
    /**
     * Detect N+1 query patterns
     */
    protected function detectN1Queries(): void
    {
        $queryPatterns = collect($this->queries)
            ->where('type', 'select')
            ->groupBy('normalized')
            ->filter(function ($group) {
                return $group->count() > 1;
            });
        
        foreach ($queryPatterns as $pattern => $queries) {
            $count = $queries->count();
            $table = $queries->first()['table'];
            
            // Check if this looks like an N+1 pattern
            if ($this->looksLikeN1Pattern($pattern, $count)) {
                $this->suspiciousPatterns[] = [
                    'pattern' => $pattern,
                    'table' => $table,
                    'count' => $count,
                    'total_time' => $queries->sum('time'),
                    'avg_time' => $queries->avg('time'),
                    'likely_relationship' => $this->guessRelationship($pattern, $table),
                ];
            }
        }
    }
    
    /**
     * Check if a pattern looks like N+1
     */
    protected function looksLikeN1Pattern(string $pattern, int $count): bool
    {
        // Patterns that typically indicate N+1
        $n1Indicators = [
            'where .* in \(',  // Batch loading
            'where .* = \?',   // Individual loading
            'join .* on',      // Join queries
        ];
        
        foreach ($n1Indicators as $indicator) {
            if (preg_match("/$indicator/i", $pattern) && $count > 5) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Try to guess the relationship from the query pattern
     */
    protected function guessRelationship(string $pattern, ?string $table): ?string
    {
        if (!$table) return null;
        
        // Look for foreign key patterns
        if (preg_match('/where.*?(\w+)_id\s*=/', $pattern, $matches)) {
            return Str::singular($matches[1]);
        }
        
        // Look for join patterns
        if (preg_match('/join\s+`?(\w+)`?\s+on/i', $pattern, $matches)) {
            return Str::singular($matches[1]);
        }
        
        return null;
    }
    
    /**
     * Generate recommendations based on findings
     */
    protected function generateRecommendations(): array
    {
        $recommendations = [];
        
        foreach ($this->suspiciousPatterns as $pattern) {
            $table = $pattern['table'];
            $relationship = $pattern['likely_relationship'];
            $count = $pattern['count'];
            
            $recommendation = [
                'issue' => "Detected {$count} similar queries on table '{$table}'",
                'impact' => $this->calculateImpact($pattern),
                'solution' => null,
            ];
            
            if ($relationship) {
                $recommendation['solution'] = "Consider eager loading the '{$relationship}' relationship";
                $recommendation['code_example'] = $this->generateEagerLoadingExample($table, $relationship);
            } else {
                $recommendation['solution'] = "Consider using eager loading or query optimization";
            }
            
            $recommendations[] = $recommendation;
        }
        
        return $recommendations;
    }
    
    /**
     * Calculate impact level
     */
    protected function calculateImpact(array $pattern): string
    {
        $totalTime = $pattern['total_time'];
        $count = $pattern['count'];
        
        if ($totalTime > 1000 || $count > 100) return 'high';
        if ($totalTime > 500 || $count > 50) return 'medium';
        
        return 'low';
    }
    
    /**
     * Generate eager loading code example
     */
    protected function generateEagerLoadingExample(string $table, string $relationship): string
    {
        $modelName = Str::studly(Str::singular($table));
        
        return <<<CODE
// Instead of:
\$items = {$modelName}::all();
foreach (\$items as \$item) {
    \$item->{$relationship}; // This causes N+1
}

// Use:
\$items = {$modelName}::with('{$relationship}')->get();
CODE;
    }
    
    /**
     * Get query breakdown by type and table
     */
    protected function getQueryBreakdown(): array
    {
        $breakdown = [
            'by_type' => collect($this->queries)->groupBy('type')->map->count(),
            'by_table' => collect($this->queries)->groupBy('table')->map->count(),
            'slow_queries' => collect($this->queries)
                ->where('time', '>', 100)
                ->map(function ($query) {
                    return [
                        'sql' => $query['sql'],
                        'time' => $query['time'],
                        'table' => $query['table'],
                    ];
                })
                ->values()
                ->toArray(),
        ];
        
        return $breakdown;
    }
    
    /**
     * Analyze a specific model for potential N+1 issues
     */
    public function analyzeModel(string $modelClass): array
    {
        $reflection = new \ReflectionClass($modelClass);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        $relationships = [];
        $warnings = [];
        
        foreach ($methods as $method) {
            $methodName = $method->getName();
            
            // Skip non-relationship methods
            if (in_array($methodName, ['__construct', '__destruct', '__call', '__get', '__set'])) {
                continue;
            }
            
            // Try to detect relationship methods
            $source = $method->getFileName();
            $start = $method->getStartLine() - 1;
            $end = $method->getEndLine();
            $lines = array_slice(file($source), $start, $end - $start);
            $body = implode('', $lines);
            
            if (preg_match('/->(?:hasOne|hasMany|belongsTo|belongsToMany|morphTo|morphMany|morphToMany|hasManyThrough)\s*\(/', $body)) {
                $relationships[] = $methodName;
                
                // Check if relationship is in $with array
                if (property_exists($modelClass, 'with') && in_array($methodName, $modelClass::$with)) {
                    $warnings[] = "Relationship '{$methodName}' is always loaded via \$with property - ensure this is intentional";
                }
            }
        }
        
        return [
            'model' => $modelClass,
            'relationships' => $relationships,
            'warnings' => $warnings,
            'suggestions' => $this->generateModelSuggestions($modelClass, $relationships),
        ];
    }
    
    /**
     * Generate model-specific suggestions
     */
    protected function generateModelSuggestions(string $modelClass, array $relationships): array
    {
        $suggestions = [];
        
        if (count($relationships) > 5) {
            $suggestions[] = "Consider creating loading profiles for different use cases since this model has many relationships";
        }
        
        if (property_exists($modelClass, 'with') && !empty($modelClass::$with)) {
            $suggestions[] = "Review the \$with property to ensure only essential relationships are always loaded";
        }
        
        return $suggestions;
    }
}