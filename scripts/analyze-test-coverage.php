#!/usr/bin/env php
<?php

/**
 * Test Coverage Analysis Script
 * 
 * Analyzes which application files are covered by tests
 * without requiring PCOV or Xdebug extensions
 */

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Finder\Finder;

class TestCoverageAnalyzer
{
    private array $coveredFiles = [];
    private array $uncoveredFiles = [];
    private array $testFiles = [];
    private int $totalLines = 0;
    private int $coveredLines = 0;
    
    public function analyze(): void
    {
        echo "\n🔍 Analyzing Test Coverage (Static Analysis)\n";
        echo "==========================================\n\n";
        
        $this->collectTestFiles();
        $this->analyzeSourceFiles();
        $this->generateReport();
    }
    
    private function collectTestFiles(): void
    {
        echo "📁 Collecting test files...\n";
        
        $finder = new Finder();
        $finder->files()
            ->in(__DIR__ . '/../tests')
            ->name('*Test.php')
            ->notPath('TestCase.php');
        
        foreach ($finder as $file) {
            $this->testFiles[] = $file->getRealPath();
            $this->analyzeTestFile($file);
        }
        
        echo "✅ Found " . count($this->testFiles) . " test files\n\n";
    }
    
    private function analyzeTestFile(\SplFileInfo $file): void
    {
        $content = file_get_contents($file->getRealPath());
        
        // Extract classes being tested
        if (preg_match_all('/use\s+(App\\\\[^;]+);/', $content, $matches)) {
            foreach ($matches[1] as $className) {
                $filePath = $this->classToFilePath($className);
                if ($filePath && file_exists($filePath)) {
                    $this->coveredFiles[$filePath] = true;
                }
            }
        }
        
        // Extract direct instantiations
        if (preg_match_all('/new\s+(\\\\?App\\\\[A-Za-z\\\\]+)/', $content, $matches)) {
            foreach ($matches[1] as $className) {
                $className = ltrim($className, '\\');
                $filePath = $this->classToFilePath($className);
                if ($filePath && file_exists($filePath)) {
                    $this->coveredFiles[$filePath] = true;
                }
            }
        }
        
        // Extract mocked classes
        if (preg_match_all('/createMock\((.*?)::class\)/', $content, $matches)) {
            foreach ($matches[1] as $className) {
                $className = trim($className, '\\ ');
                if (str_starts_with($className, 'App\\')) {
                    $filePath = $this->classToFilePath($className);
                    if ($filePath && file_exists($filePath)) {
                        $this->coveredFiles[$filePath] = true;
                    }
                }
            }
        }
    }
    
    private function classToFilePath(string $className): ?string
    {
        $className = ltrim($className, '\\');
        
        if (!str_starts_with($className, 'App\\')) {
            return null;
        }
        
        $relativePath = str_replace('App\\', '', $className);
        $relativePath = str_replace('\\', '/', $relativePath);
        
        return __DIR__ . '/../app/' . $relativePath . '.php';
    }
    
    private function analyzeSourceFiles(): void
    {
        echo "📊 Analyzing source files...\n";
        
        $finder = new Finder();
        $finder->files()
            ->in(__DIR__ . '/../app')
            ->name('*.php')
            ->notPath('Console/Kernel.php')
            ->notPath('Exceptions/Handler.php')
            ->notPath('Providers/');
        
        foreach ($finder as $file) {
            $path = $file->getRealPath();
            $lines = count(file($path));
            $this->totalLines += $lines;
            
            if (isset($this->coveredFiles[$path])) {
                $this->coveredLines += $lines;
            } else {
                $this->uncoveredFiles[] = [
                    'path' => str_replace(__DIR__ . '/../', '', $path),
                    'lines' => $lines
                ];
            }
        }
        
        echo "✅ Analyzed " . iterator_count($finder) . " source files\n\n";
    }
    
    private function generateReport(): void
    {
        $coverage = $this->totalLines > 0 
            ? round(($this->coveredLines / $this->totalLines) * 100, 2) 
            : 0;
        
        echo "📈 Coverage Summary\n";
        echo "==================\n\n";
        
        echo sprintf("Total Lines: %d\n", $this->totalLines);
        echo sprintf("Covered Lines: %d\n", $this->coveredLines);
        echo sprintf("Coverage: %.2f%%\n\n", $coverage);
        
        // Coverage by directory
        $this->printDirectoryCoverage();
        
        // Top uncovered files
        $this->printUncoveredFiles();
        
        // Generate HTML report
        $this->generateHtmlReport($coverage);
        
        // Generate JSON report
        $this->generateJsonReport($coverage);
    }
    
    private function printDirectoryCoverage(): void
    {
        echo "📂 Coverage by Directory\n";
        echo "=======================\n\n";
        
        $directories = [];
        
        // Group files by directory
        $finder = new Finder();
        $finder->files()
            ->in(__DIR__ . '/../app')
            ->name('*.php');
        
        foreach ($finder as $file) {
            $dir = dirname(str_replace(__DIR__ . '/../app/', '', $file->getRealPath()));
            if ($dir === '.') $dir = 'root';
            
            if (!isset($directories[$dir])) {
                $directories[$dir] = [
                    'total' => 0,
                    'covered' => 0,
                    'lines' => 0,
                    'covered_lines' => 0
                ];
            }
            
            $lines = count(file($file->getRealPath()));
            $directories[$dir]['total']++;
            $directories[$dir]['lines'] += $lines;
            
            if (isset($this->coveredFiles[$file->getRealPath()])) {
                $directories[$dir]['covered']++;
                $directories[$dir]['covered_lines'] += $lines;
            }
        }
        
        // Sort by coverage percentage
        uasort($directories, function($a, $b) {
            $coverageA = $a['lines'] > 0 ? ($a['covered_lines'] / $a['lines']) : 0;
            $coverageB = $b['lines'] > 0 ? ($b['covered_lines'] / $b['lines']) : 0;
            return $coverageB <=> $coverageA;
        });
        
        foreach ($directories as $dir => $stats) {
            $coverage = $stats['lines'] > 0 
                ? round(($stats['covered_lines'] / $stats['lines']) * 100, 2) 
                : 0;
            
            $bar = $this->getProgressBar($coverage);
            
            echo sprintf(
                "%-30s %s %.1f%% (%d/%d files)\n",
                $dir,
                $bar,
                $coverage,
                $stats['covered'],
                $stats['total']
            );
        }
        
        echo "\n";
    }
    
    private function printUncoveredFiles(): void
    {
        echo "⚠️  Top 10 Uncovered Files\n";
        echo "========================\n\n";
        
        // Sort by lines
        usort($this->uncoveredFiles, function($a, $b) {
            return $b['lines'] <=> $a['lines'];
        });
        
        $top10 = array_slice($this->uncoveredFiles, 0, 10);
        
        foreach ($top10 as $file) {
            echo sprintf("%-60s %d lines\n", $file['path'], $file['lines']);
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
    
    private function generateHtmlReport(float $coverage): void
    {
        $reportDir = __DIR__ . '/../coverage/static-analysis';
        if (!is_dir($reportDir)) {
            mkdir($reportDir, 0755, true);
        }
        
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Coverage Report - AskProAI</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2563eb;
            margin-bottom: 30px;
        }
        .coverage-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .metric {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .metric-value {
            font-size: 2.5em;
            font-weight: bold;
            color: #2563eb;
        }
        .metric-label {
            color: #666;
            margin-top: 5px;
        }
        .progress-bar {
            width: 100%;
            height: 30px;
            background: #e0e0e0;
            border-radius: 15px;
            overflow: hidden;
            margin: 20px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #2563eb, #3b82f6);
            transition: width 0.3s ease;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }
        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .coverage-bar {
            display: inline-block;
            width: 100px;
            height: 10px;
            background: #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
            margin-right: 10px;
        }
        .coverage-fill {
            height: 100%;
            background: #4ade80;
        }
        .low { background: #ef4444; }
        .medium { background: #f59e0b; }
        .high { background: #4ade80; }
        .timestamp {
            text-align: center;
            color: #666;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Test Coverage Report</h1>
        
        <div class="coverage-summary">
            <div class="metric">
                <div class="metric-value">{$coverage}%</div>
                <div class="metric-label">Overall Coverage</div>
            </div>
            <div class="metric">
                <div class="metric-value">{$this->coveredLines}</div>
                <div class="metric-label">Covered Lines</div>
            </div>
            <div class="metric">
                <div class="metric-value">{$this->totalLines}</div>
                <div class="metric-label">Total Lines</div>
            </div>
            <div class="metric">
                <div class="metric-value">" . count($this->coveredFiles) . "</div>
                <div class="metric-label">Tested Files</div>
            </div>
        </div>
        
        <div class="progress-bar">
            <div class="progress-fill" style="width: {$coverage}%"></div>
        </div>
        
        <p style="text-align: center; color: #666; margin: 20px 0;">
            <strong>Note:</strong> This is a static analysis report. For accurate line-by-line coverage, install PCOV or Xdebug.
        </p>
        
        <div class="timestamp">
            Generated on " . date('Y-m-d H:i:s') . "
        </div>
    </div>
</body>
</html>
HTML;
        
        file_put_contents($reportDir . '/index.html', $html);
        echo "📄 HTML report generated: coverage/static-analysis/index.html\n";
    }
    
    private function generateJsonReport(float $coverage): void
    {
        $reportDir = __DIR__ . '/../coverage/static-analysis';
        
        $report = [
            'timestamp' => date('c'),
            'coverage' => [
                'percentage' => $coverage,
                'lines' => [
                    'total' => $this->totalLines,
                    'covered' => $this->coveredLines,
                    'uncovered' => $this->totalLines - $this->coveredLines
                ],
                'files' => [
                    'total' => count($this->coveredFiles) + count($this->uncoveredFiles),
                    'covered' => count($this->coveredFiles),
                    'uncovered' => count($this->uncoveredFiles)
                ]
            ],
            'uncovered_files' => array_slice($this->uncoveredFiles, 0, 20)
        ];
        
        file_put_contents(
            $reportDir . '/coverage.json',
            json_encode($report, JSON_PRETTY_PRINT)
        );
        
        echo "📄 JSON report generated: coverage/static-analysis/coverage.json\n";
    }
}

// Run analysis
$analyzer = new TestCoverageAnalyzer();
$analyzer->analyze();