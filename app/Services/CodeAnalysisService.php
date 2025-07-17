<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\NodeVisitor;
use PhpParser\Node;

class CodeAnalysisService
{
    protected $parser;
    protected array $patterns = [];
    protected array $issues = [];
    
    public function __construct()
    {
        // Initialize PHP parser if available
        if (class_exists(ParserFactory::class)) {
            $this->parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        }
    }
    
    /**
     * Analyze file for common issues
     */
    public function analyzeFile(string $filePath): array
    {
        $content = File::get($filePath);
        
        return [
            'complexity' => $this->analyzeComplexity($content),
            'issues' => $this->findIssues($content),
            'metrics' => $this->calculateMetrics($content),
            'dependencies' => $this->analyzeDependencies($content),
            'suggestions' => $this->generateSuggestions($content, $filePath)
        ];
    }
    
    /**
     * Find code issues
     */
    public function findIssues(string $content): array
    {
        $issues = [];
        
        // Check for common Laravel anti-patterns
        $antiPatterns = [
            [
                'pattern' => '/DB::select\s*\(\s*["\'].*\$/',
                'message' => 'Potential SQL injection vulnerability',
                'severity' => 'high'
            ],
            [
                'pattern' => '/env\s*\([^)]+\)(?!\s*,)/',
                'message' => 'env() should only be used in config files',
                'severity' => 'medium'
            ],
            [
                'pattern' => '/dd\s*\(|dump\s*\(|var_dump\s*\(/',
                'message' => 'Debug statement found',
                'severity' => 'low'
            ],
            [
                'pattern' => '/\$_GET|\$_POST|\$_REQUEST/',
                'message' => 'Direct superglobal access - use Request facade',
                'severity' => 'medium'
            ],
            [
                'pattern' => '/new\s+\w+\s*\(.*\)(?!;)/',
                'message' => 'Consider using dependency injection',
                'severity' => 'low'
            ]
        ];
        
        $lines = explode("\n", $content);
        
        foreach ($antiPatterns as $antiPattern) {
            foreach ($lines as $lineNum => $line) {
                if (preg_match($antiPattern['pattern'], $line)) {
                    $issues[] = [
                        'line' => $lineNum + 1,
                        'message' => $antiPattern['message'],
                        'severity' => $antiPattern['severity'],
                        'code' => trim($line)
                    ];
                }
            }
        }
        
        // Check for missing type hints
        if (preg_match_all('/function\s+(\w+)\s*\((.*?)\)/', $content, $matches)) {
            foreach ($matches[2] as $index => $params) {
                if ($params && !str_contains($params, ':') && !str_contains($params, '...')) {
                    $lineNum = substr_count(substr($content, 0, strpos($content, $matches[0][$index])), "\n") + 1;
                    $issues[] = [
                        'line' => $lineNum,
                        'message' => 'Missing type hints in function parameters',
                        'severity' => 'low',
                        'code' => $matches[0][$index]
                    ];
                }
            }
        }
        
        return $issues;
    }
    
    /**
     * Analyze code complexity
     */
    public function analyzeComplexity(string $content): array
    {
        $metrics = [
            'cyclomatic_complexity' => $this->calculateCyclomaticComplexity($content),
            'cognitive_complexity' => $this->calculateCognitiveComplexity($content),
            'nesting_level' => $this->calculateMaxNestingLevel($content),
            'method_length' => $this->calculateAverageMethodLength($content)
        ];
        
        $score = 100;
        
        // Deduct points for high complexity
        if ($metrics['cyclomatic_complexity'] > 10) {
            $score -= ($metrics['cyclomatic_complexity'] - 10) * 5;
        }
        
        if ($metrics['nesting_level'] > 4) {
            $score -= ($metrics['nesting_level'] - 4) * 10;
        }
        
        if ($metrics['method_length'] > 50) {
            $score -= min(30, ($metrics['method_length'] - 50) / 2);
        }
        
        return [
            'score' => max(0, $score),
            'metrics' => $metrics,
            'rating' => $this->getComplexityRating($score)
        ];
    }
    
    /**
     * Calculate code metrics
     */
    public function calculateMetrics(string $content): array
    {
        $lines = explode("\n", $content);
        $codeLines = 0;
        $commentLines = 0;
        $blankLines = 0;
        
        foreach ($lines as $line) {
            $trimmed = trim($line);
            
            if (empty($trimmed)) {
                $blankLines++;
            } elseif (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '/*') || str_starts_with($trimmed, '*')) {
                $commentLines++;
            } else {
                $codeLines++;
            }
        }
        
        $totalLines = count($lines);
        
        return [
            'total_lines' => $totalLines,
            'code_lines' => $codeLines,
            'comment_lines' => $commentLines,
            'blank_lines' => $blankLines,
            'comment_ratio' => $codeLines > 0 ? round(($commentLines / $codeLines) * 100, 2) : 0,
            'classes' => substr_count($content, 'class '),
            'methods' => substr_count($content, 'function '),
            'interfaces' => substr_count($content, 'interface '),
            'traits' => substr_count($content, 'trait ')
        ];
    }
    
    /**
     * Analyze dependencies
     */
    public function analyzeDependencies(string $content): array
    {
        $dependencies = [
            'namespaces' => [],
            'classes' => [],
            'facades' => [],
            'traits' => []
        ];
        
        // Extract use statements
        if (preg_match_all('/use\s+([\w\\\\]+)(?:\s+as\s+\w+)?;/', $content, $matches)) {
            foreach ($matches[1] as $use) {
                if (str_contains($use, 'Facades')) {
                    $dependencies['facades'][] = $use;
                } elseif (str_ends_with($use, 'Trait')) {
                    $dependencies['traits'][] = $use;
                } else {
                    $dependencies['classes'][] = $use;
                }
            }
        }
        
        // Extract namespace
        if (preg_match('/namespace\s+([\w\\\\]+);/', $content, $match)) {
            $dependencies['namespace'] = $match[1];
        }
        
        return $dependencies;
    }
    
    /**
     * Generate improvement suggestions
     */
    public function generateSuggestions(string $content, string $filePath): array
    {
        $suggestions = [];
        
        // Check for missing return types
        if (preg_match_all('/function\s+\w+\s*\([^)]*\)\s*{/', $content, $matches)) {
            foreach ($matches[0] as $match) {
                if (!str_contains($match, ':')) {
                    $suggestions[] = 'Add return type declarations to methods';
                    break;
                }
            }
        }
        
        // Check for long methods
        if (preg_match_all('/function\s+\w+.*?{(.*?)^}/ms', $content, $matches)) {
            foreach ($matches[1] as $methodBody) {
                $lines = substr_count($methodBody, "\n");
                if ($lines > 50) {
                    $suggestions[] = 'Consider breaking down long methods (>50 lines)';
                    break;
                }
            }
        }
        
        // Check for repository pattern usage
        if (str_contains($filePath, 'Controller') && str_contains($content, 'DB::') && !str_contains($content, 'Repository')) {
            $suggestions[] = 'Consider using Repository pattern for database queries';
        }
        
        // Check for service layer
        if (str_contains($filePath, 'Controller') && $this->calculateMetrics($content)['code_lines'] > 100) {
            $suggestions[] = 'Consider extracting business logic to a Service class';
        }
        
        // Check for tests
        if (!str_contains($filePath, 'test') && !str_contains($filePath, 'Test')) {
            $testFile = str_replace('.php', 'Test.php', $filePath);
            $testFile = str_replace('app/', 'tests/Unit/', $testFile);
            
            if (!File::exists($testFile)) {
                $suggestions[] = 'Add unit tests for this class';
            }
        }
        
        return array_unique($suggestions);
    }
    
    /**
     * Calculate cyclomatic complexity
     */
    protected function calculateCyclomaticComplexity(string $content): int
    {
        $complexity = 1; // Base complexity
        
        // Count decision points
        $decisionKeywords = [
            'if', 'elseif', 'else if', 'for', 'foreach', 'while', 'do',
            'case', 'catch', '?', '&&', '||', '??'
        ];
        
        foreach ($decisionKeywords as $keyword) {
            if (in_array($keyword, ['&&', '||', '??', '?'])) {
                $complexity += substr_count($content, $keyword);
            } else {
                $complexity += preg_match_all('/\b' . $keyword . '\b/', $content);
            }
        }
        
        return $complexity;
    }
    
    /**
     * Calculate cognitive complexity
     */
    protected function calculateCognitiveComplexity(string $content): int
    {
        // Simplified cognitive complexity calculation
        $complexity = 0;
        $nestingLevel = 0;
        
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            // Track nesting
            $openBraces = substr_count($line, '{');
            $closeBraces = substr_count($line, '}');
            
            // Add complexity for control structures
            if (preg_match('/\b(if|for|foreach|while|switch)\b/', $line)) {
                $complexity += 1 + $nestingLevel;
            }
            
            $nestingLevel += $openBraces - $closeBraces;
            $nestingLevel = max(0, $nestingLevel);
        }
        
        return $complexity;
    }
    
    /**
     * Calculate maximum nesting level
     */
    protected function calculateMaxNestingLevel(string $content): int
    {
        $maxLevel = 0;
        $currentLevel = 0;
        
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            $currentLevel += substr_count($line, '{');
            $currentLevel -= substr_count($line, '}');
            $maxLevel = max($maxLevel, $currentLevel);
        }
        
        return $maxLevel;
    }
    
    /**
     * Calculate average method length
     */
    protected function calculateAverageMethodLength(string $content): float
    {
        $methodCount = 0;
        $totalLines = 0;
        
        if (preg_match_all('/function\s+\w+.*?{(.*?)^}/ms', $content, $matches)) {
            foreach ($matches[1] as $methodBody) {
                $methodCount++;
                $totalLines += substr_count($methodBody, "\n");
            }
        }
        
        return $methodCount > 0 ? round($totalLines / $methodCount, 2) : 0;
    }
    
    /**
     * Get complexity rating
     */
    protected function getComplexityRating(float $score): string
    {
        if ($score >= 90) return 'Excellent';
        if ($score >= 80) return 'Good';
        if ($score >= 70) return 'Fair';
        if ($score >= 60) return 'Poor';
        return 'Very Poor';
    }
    
    /**
     * Compare code similarity
     */
    public function calculateSimilarity(string $code1, string $code2): float
    {
        // Normalize code
        $normalized1 = $this->normalizeCode($code1);
        $normalized2 = $this->normalizeCode($code2);
        
        // Use Levenshtein distance for small strings
        if (strlen($normalized1) < 1000 && strlen($normalized2) < 1000) {
            $distance = levenshtein($normalized1, $normalized2);
            $maxLength = max(strlen($normalized1), strlen($normalized2));
            return 1 - ($distance / $maxLength);
        }
        
        // Use token-based comparison for larger strings
        $tokens1 = $this->tokenizeCode($normalized1);
        $tokens2 = $this->tokenizeCode($normalized2);
        
        $intersection = array_intersect($tokens1, $tokens2);
        $union = array_unique(array_merge($tokens1, $tokens2));
        
        return count($intersection) / count($union);
    }
    
    /**
     * Normalize code for comparison
     */
    protected function normalizeCode(string $code): string
    {
        // Remove comments
        $code = preg_replace('/\/\*.*?\*\//s', '', $code);
        $code = preg_replace('/\/\/.*$/m', '', $code);
        
        // Remove whitespace
        $code = preg_replace('/\s+/', ' ', $code);
        
        // Remove variable names (keep structure)
        $code = preg_replace('/\$\w+/', '$var', $code);
        
        return trim($code);
    }
    
    /**
     * Tokenize code
     */
    protected function tokenizeCode(string $code): array
    {
        // Simple tokenization
        $tokens = preg_split('/\s+/', $code);
        
        // Filter out common tokens
        $commonTokens = ['{', '}', '(', ')', ';', ',', '=', '->', '=>'];
        
        return array_diff($tokens, $commonTokens);
    }
}