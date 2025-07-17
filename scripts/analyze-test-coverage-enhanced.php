#!/usr/bin/env php
<?php

/**
 * Enhanced Test Coverage Analysis Script
 * 
 * More accurate detection of test coverage by analyzing:
 * - Test class names and methods
 * - Factory usage
 * - Repository patterns
 * - Service container resolution
 */

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Finder\Finder;

class EnhancedCoverageAnalyzer
{
    private array $coveredClasses = [];
    private array $testCoverage = [];
    private array $sourceFiles = [];
    private array $testStats = [];
    
    public function analyze(): void
    {
        echo "\n🔍 Enhanced Test Coverage Analysis\n";
        echo "==================================\n\n";
        
        $this->collectSourceFiles();
        $this->analyzeTests();
        $this->generateReport();
    }
    
    private function collectSourceFiles(): void
    {
        echo "📁 Indexing source files...\n";
        
        $finder = new Finder();
        $finder->files()
            ->in(__DIR__ . '/../app')
            ->name('*.php')
            ->notPath('Console/Kernel.php')
            ->notPath('Exceptions/Handler.php');
        
        foreach ($finder as $file) {
            $className = $this->extractClassName($file);
            if ($className) {
                $this->sourceFiles[$className] = [
                    'path' => $file->getRealPath(),
                    'relative_path' => str_replace(__DIR__ . '/../', '', $file->getRealPath()),
                    'lines' => count(file($file->getRealPath())),
                    'namespace' => $this->extractNamespace($file),
                    'type' => $this->detectClassType($file)
                ];
            }
        }
        
        echo "✅ Found " . count($this->sourceFiles) . " source classes\n\n";
    }
    
    private function analyzeTests(): void
    {
        echo "🧪 Analyzing test files...\n";
        
        $finder = new Finder();
        $finder->files()
            ->in(__DIR__ . '/../tests')
            ->name('*Test.php')
            ->notPath('TestCase.php');
        
        $totalTests = 0;
        
        foreach ($finder as $file) {
            $testClass = $this->extractClassName($file);
            $testedClasses = $this->findTestedClasses($file);
            
            foreach ($testedClasses as $testedClass) {
                if (!isset($this->testCoverage[$testedClass])) {
                    $this->testCoverage[$testedClass] = [
                        'test_files' => [],
                        'test_methods' => []
                    ];
                }
                
                $this->testCoverage[$testedClass]['test_files'][] = $file->getRelativePathname();
                
                // Count test methods
                $content = file_get_contents($file->getRealPath());
                preg_match_all('/public function (test\w+|it_\w+)/', $content, $matches);
                $totalTests += count($matches[1]);
                
                $this->testCoverage[$testedClass]['test_methods'] = array_merge(
                    $this->testCoverage[$testedClass]['test_methods'],
                    $matches[1]
                );
            }
        }
        
        echo "✅ Found $totalTests test methods across " . iterator_count($finder) . " test files\n\n";
    }
    
    private function findTestedClasses(\SplFileInfo $file): array
    {
        $content = file_get_contents($file->getRealPath());
        $classes = [];
        
        // 1. Extract from test class name (e.g., CustomerRepositoryTest -> CustomerRepository)
        if (preg_match('/class (\w+)Test/', $content, $match)) {
            $baseClass = str_replace(['Unit', 'Feature', 'Integration'], '', $match[1]);
            
            // Try to find matching source class
            foreach ($this->sourceFiles as $className => $info) {
                if (str_ends_with($className, $baseClass)) {
                    $classes[] = $className;
                }
            }
        }
        
        // 2. Extract from use statements
        if (preg_match_all('/use\s+(App\\\\[^;]+);/', $content, $matches)) {
            foreach ($matches[1] as $className) {
                if (isset($this->sourceFiles[$className])) {
                    $classes[] = $className;
                }
            }
        }
        
        // 3. Extract from factory usage (e.g., Customer::factory())
        if (preg_match_all('/(\w+)::factory\(\)/', $content, $matches)) {
            foreach ($matches[1] as $modelName) {
                $fullClass = "App\\Models\\$modelName";
                if (isset($this->sourceFiles[$fullClass])) {
                    $classes[] = $fullClass;
                }
            }
        }
        
        // 4. Extract from service resolution
        if (preg_match_all('/app\((.*?)::class\)/', $content, $matches)) {
            foreach ($matches[1] as $className) {
                $className = trim($className, '\'"\\\\');
                if (str_starts_with($className, 'App\\') && isset($this->sourceFiles[$className])) {
                    $classes[] = $className;
                }
            }
        }
        
        // 5. Extract from new instantiations
        if (preg_match_all('/new\s+(\\\\?App\\\\[\w\\\\]+)/', $content, $matches)) {
            foreach ($matches[1] as $className) {
                $className = ltrim($className, '\\');
                if (isset($this->sourceFiles[$className])) {
                    $classes[] = $className;
                }
            }
        }
        
        // 6. Extract from repository/service names in setUp
        if (preg_match_all('/\$this->(\w+)\s*=\s*new\s+([\w\\\\]+)/', $content, $matches)) {
            foreach ($matches[2] as $className) {
                if (str_starts_with($className, 'App\\') || !str_contains($className, '\\')) {
                    // Try to resolve partial class names
                    foreach ($this->sourceFiles as $fullClass => $info) {
                        if (str_ends_with($fullClass, $className)) {
                            $classes[] = $fullClass;
                        }
                    }
                }
            }
        }
        
        return array_unique($classes);
    }
    
    private function extractClassName(\SplFileInfo $file): ?string
    {
        $content = file_get_contents($file->getRealPath());
        
        $namespace = '';
        if (preg_match('/namespace\s+([^;]+);/', $content, $match)) {
            $namespace = $match[1];
        }
        
        if (preg_match('/class\s+(\w+)/', $content, $match)) {
            return $namespace ? $namespace . '\\' . $match[1] : $match[1];
        }
        
        return null;
    }
    
    private function extractNamespace(\SplFileInfo $file): string
    {
        $content = file_get_contents($file->getRealPath());
        
        if (preg_match('/namespace\s+([^;]+);/', $content, $match)) {
            return $match[1];
        }
        
        return '';
    }
    
    private function detectClassType(\SplFileInfo $file): string
    {
        $path = $file->getRelativePathname();
        
        if (str_contains($path, 'Controllers')) return 'Controller';
        if (str_contains($path, 'Models')) return 'Model';
        if (str_contains($path, 'Services')) return 'Service';
        if (str_contains($path, 'Repositories')) return 'Repository';
        if (str_contains($path, 'Jobs')) return 'Job';
        if (str_contains($path, 'Middleware')) return 'Middleware';
        if (str_contains($path, 'Providers')) return 'Provider';
        if (str_contains($path, 'Resources')) return 'Resource';
        if (str_contains($path, 'Traits')) return 'Trait';
        if (str_contains($path, 'Helpers')) return 'Helper';
        
        return 'Other';
    }
    
    private function generateReport(): void
    {
        $totalLines = 0;
        $coveredLines = 0;
        $totalClasses = count($this->sourceFiles);
        $coveredClasses = count($this->testCoverage);
        
        foreach ($this->sourceFiles as $className => $info) {
            $totalLines += $info['lines'];
            if (isset($this->testCoverage[$className])) {
                $coveredLines += $info['lines'];
            }
        }
        
        $linesCoverage = $totalLines > 0 ? round(($coveredLines / $totalLines) * 100, 2) : 0;
        $classCoverage = $totalClasses > 0 ? round(($coveredClasses / $totalClasses) * 100, 2) : 0;
        
        echo "📊 Coverage Summary\n";
        echo "==================\n\n";
        
        echo sprintf("Class Coverage: %.2f%% (%d/%d classes)\n", $classCoverage, $coveredClasses, $totalClasses);
        echo sprintf("Line Coverage: %.2f%% (%d/%d lines)\n", $linesCoverage, $coveredLines, $totalLines);
        echo "\n";
        
        // Coverage by type
        $this->printCoverageByType();
        
        // Well-tested classes
        $this->printWellTestedClasses();
        
        // Untested critical classes
        $this->printUntestedCriticalClasses();
        
        // Generate detailed report
        $this->generateDetailedReport($classCoverage, $linesCoverage);
    }
    
    private function printCoverageByType(): void
    {
        echo "📈 Coverage by Type\n";
        echo "==================\n\n";
        
        $types = [];
        
        foreach ($this->sourceFiles as $className => $info) {
            $type = $info['type'];
            
            if (!isset($types[$type])) {
                $types[$type] = [
                    'total' => 0,
                    'covered' => 0,
                    'lines' => 0,
                    'covered_lines' => 0
                ];
            }
            
            $types[$type]['total']++;
            $types[$type]['lines'] += $info['lines'];
            
            if (isset($this->testCoverage[$className])) {
                $types[$type]['covered']++;
                $types[$type]['covered_lines'] += $info['lines'];
            }
        }
        
        // Sort by coverage
        uasort($types, function($a, $b) {
            $coverageA = $a['total'] > 0 ? ($a['covered'] / $a['total']) : 0;
            $coverageB = $b['total'] > 0 ? ($b['covered'] / $b['total']) : 0;
            return $coverageB <=> $coverageA;
        });
        
        foreach ($types as $type => $stats) {
            $coverage = $stats['total'] > 0 ? round(($stats['covered'] / $stats['total']) * 100, 2) : 0;
            $bar = $this->getProgressBar($coverage);
            
            echo sprintf(
                "%-15s %s %.1f%% (%d/%d classes)\n",
                $type,
                $bar,
                $coverage,
                $stats['covered'],
                $stats['total']
            );
        }
        
        echo "\n";
    }
    
    private function printWellTestedClasses(): void
    {
        echo "✅ Well-Tested Classes (5+ test methods)\n";
        echo "========================================\n\n";
        
        $wellTested = [];
        
        foreach ($this->testCoverage as $className => $coverage) {
            $testCount = count($coverage['test_methods']);
            if ($testCount >= 5) {
                $wellTested[] = [
                    'class' => $className,
                    'tests' => $testCount,
                    'type' => $this->sourceFiles[$className]['type'] ?? 'Unknown'
                ];
            }
        }
        
        usort($wellTested, fn($a, $b) => $b['tests'] <=> $a['tests']);
        
        foreach (array_slice($wellTested, 0, 10) as $item) {
            $shortClass = str_replace('App\\', '', $item['class']);
            echo sprintf("%-60s %d tests (%s)\n", $shortClass, $item['tests'], $item['type']);
        }
        
        echo "\n";
    }
    
    private function printUntestedCriticalClasses(): void
    {
        echo "⚠️  Untested Critical Classes\n";
        echo "============================\n\n";
        
        $critical = [];
        
        foreach ($this->sourceFiles as $className => $info) {
            if (!isset($this->testCoverage[$className])) {
                // Prioritize Services, Repositories, and Controllers
                if (in_array($info['type'], ['Service', 'Repository', 'Controller'])) {
                    $critical[] = [
                        'class' => $className,
                        'lines' => $info['lines'],
                        'type' => $info['type'],
                        'path' => $info['relative_path']
                    ];
                }
            }
        }
        
        usort($critical, fn($a, $b) => $b['lines'] <=> $a['lines']);
        
        foreach (array_slice($critical, 0, 15) as $item) {
            $shortPath = str_replace('app/', '', $item['path']);
            echo sprintf("%-60s %d lines (%s)\n", $shortPath, $item['lines'], $item['type']);
        }
        
        echo "\n";
    }
    
    private function getProgressBar(float $percentage): string
    {
        $filled = (int) ($percentage / 10);
        $empty = 10 - $filled;
        
        $bar = str_repeat('█', $filled) . str_repeat('░', $empty);
        
        if ($percentage >= 80) {
            return "🟢 $bar";
        } elseif ($percentage >= 60) {
            return "🟡 $bar";
        } else {
            return "🔴 $bar";
        }
    }
    
    private function generateDetailedReport(float $classCoverage, float $linesCoverage): void
    {
        $reportDir = __DIR__ . '/../coverage/enhanced-analysis';
        if (!is_dir($reportDir)) {
            mkdir($reportDir, 0755, true);
        }
        
        // Generate JSON report
        $report = [
            'timestamp' => date('c'),
            'summary' => [
                'class_coverage' => $classCoverage,
                'line_coverage' => $linesCoverage,
                'total_classes' => count($this->sourceFiles),
                'tested_classes' => count($this->testCoverage),
                'total_test_methods' => array_sum(array_map(
                    fn($tc) => count($tc['test_methods']), 
                    $this->testCoverage
                ))
            ],
            'coverage_details' => $this->testCoverage,
            'untested_classes' => array_keys(array_diff_key($this->sourceFiles, $this->testCoverage))
        ];
        
        file_put_contents(
            $reportDir . '/coverage-enhanced.json',
            json_encode($report, JSON_PRETTY_PRINT)
        );
        
        echo "📄 Enhanced report generated: coverage/enhanced-analysis/coverage-enhanced.json\n";
        
        // Generate markdown report
        $this->generateMarkdownReport($reportDir, $classCoverage, $linesCoverage);
    }
    
    private function generateMarkdownReport(string $reportDir, float $classCoverage, float $linesCoverage): void
    {
        $markdown = "# Test Coverage Report\n\n";
        $markdown .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        $markdown .= "## Summary\n\n";
        $markdown .= "- **Class Coverage**: {$classCoverage}%\n";
        $markdown .= "- **Line Coverage**: {$linesCoverage}%\n";
        $markdown .= "- **Total Classes**: " . count($this->sourceFiles) . "\n";
        $markdown .= "- **Tested Classes**: " . count($this->testCoverage) . "\n\n";
        
        $markdown .= "## Coverage by Component\n\n";
        $markdown .= "| Component | Coverage | Tested/Total |\n";
        $markdown .= "|-----------|----------|-------------|\n";
        
        // Add type coverage
        $types = [];
        foreach ($this->sourceFiles as $className => $info) {
            $type = $info['type'];
            if (!isset($types[$type])) {
                $types[$type] = ['total' => 0, 'covered' => 0];
            }
            $types[$type]['total']++;
            if (isset($this->testCoverage[$className])) {
                $types[$type]['covered']++;
            }
        }
        
        foreach ($types as $type => $stats) {
            $coverage = $stats['total'] > 0 ? round(($stats['covered'] / $stats['total']) * 100, 1) : 0;
            $markdown .= "| $type | {$coverage}% | {$stats['covered']}/{$stats['total']} |\n";
        }
        
        file_put_contents($reportDir . '/COVERAGE_REPORT.md', $markdown);
        echo "📄 Markdown report generated: coverage/enhanced-analysis/COVERAGE_REPORT.md\n";
    }
}

// Run enhanced analysis
$analyzer = new EnhancedCoverageAnalyzer();
$analyzer->analyze();