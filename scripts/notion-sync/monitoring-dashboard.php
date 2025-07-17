#!/usr/bin/env php
<?php

/**
 * Documentation Monitoring Dashboard
 * 
 * Tracks documentation coverage, outdated docs, and team contributions
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DocumentationMonitor
{
    private array $metrics = [];
    private array $alerts = [];
    private array $documentedFiles = [];
    private array $undocumentedFiles = [];
    
    public function __construct()
    {
        $this->loadDocumentationMapping();
    }
    
    /**
     * Generate monitoring dashboard
     */
    public function generateDashboard(): array
    {
        echo "📊 Generating documentation monitoring dashboard...\n";
        
        // Calculate metrics
        $this->calculateCoverageMetrics();
        $this->findOutdatedDocumentation();
        $this->analyzeMissingDocumentation();
        $this->trackTeamContributions();
        $this->generateAlerts();
        
        // Generate report
        $dashboard = [
            'generated_at' => Carbon::now()->toIso8601String(),
            'metrics' => $this->metrics,
            'alerts' => $this->alerts,
            'coverage' => [
                'documented' => count($this->documentedFiles),
                'undocumented' => count($this->undocumentedFiles),
                'percentage' => $this->metrics['coverage_percentage'],
            ],
            'team_contributions' => $this->metrics['team_contributions'],
            'health_score' => $this->calculateHealthScore(),
        ];
        
        // Save to file
        $this->saveDashboard($dashboard);
        
        // Update Notion
        $this->updateNotionDashboard($dashboard);
        
        return $dashboard;
    }
    
    /**
     * Calculate documentation coverage metrics
     */
    private function calculateCoverageMetrics(): void
    {
        echo "📈 Calculating coverage metrics...\n";
        
        // Get all PHP files
        $allFiles = $this->getAllPhpFiles();
        
        // Load documented files from mapping
        $mappingFile = __DIR__ . '/doc-mapping.json';
        if (file_exists($mappingFile)) {
            $mapping = json_decode(file_get_contents($mappingFile), true);
            $this->documentedFiles = array_keys($mapping);
        }
        
        // Find undocumented files
        $this->undocumentedFiles = array_diff($allFiles, $this->documentedFiles);
        
        // Calculate metrics
        $total = count($allFiles);
        $documented = count($this->documentedFiles);
        $percentage = $total > 0 ? round(($documented / $total) * 100, 2) : 0;
        
        $this->metrics['total_files'] = $total;
        $this->metrics['documented_files'] = $documented;
        $this->metrics['undocumented_files'] = count($this->undocumentedFiles);
        $this->metrics['coverage_percentage'] = $percentage;
        
        // Coverage by type
        $this->metrics['coverage_by_type'] = [
            'controllers' => $this->calculateTypeCoverage('app/Http/Controllers'),
            'services' => $this->calculateTypeCoverage('app/Services'),
            'models' => $this->calculateTypeCoverage('app/Models'),
            'jobs' => $this->calculateTypeCoverage('app/Jobs'),
            'commands' => $this->calculateTypeCoverage('app/Console/Commands'),
        ];
    }
    
    /**
     * Find outdated documentation
     */
    private function findOutdatedDocumentation(): void
    {
        echo "🔍 Finding outdated documentation...\n";
        
        $outdated = [];
        $mappingFile = __DIR__ . '/doc-mapping.json';
        
        if (!file_exists($mappingFile)) {
            return;
        }
        
        $mapping = json_decode(file_get_contents($mappingFile), true);
        
        foreach ($mapping as $file => $docInfo) {
            if (!file_exists($file)) {
                continue;
            }
            
            // Get file modification time
            $fileModTime = filemtime($file);
            
            // Get documentation last update time (from Notion or local cache)
            $docModTime = $this->getDocumentationModTime($docInfo['notion_page_id']);
            
            // Check if file is newer than documentation
            if ($fileModTime > $docModTime) {
                $daysBehind = Carbon::createFromTimestamp($fileModTime)
                    ->diffInDays(Carbon::createFromTimestamp($docModTime));
                
                $outdated[] = [
                    'file' => $file,
                    'file_modified' => Carbon::createFromTimestamp($fileModTime)->toDateTimeString(),
                    'doc_modified' => Carbon::createFromTimestamp($docModTime)->toDateTimeString(),
                    'days_behind' => $daysBehind,
                    'priority' => $this->calculateUpdatePriority($file, $daysBehind),
                ];
            }
        }
        
        // Sort by priority
        usort($outdated, function($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });
        
        $this->metrics['outdated_documentation'] = $outdated;
        $this->metrics['outdated_count'] = count($outdated);
    }
    
    /**
     * Analyze missing documentation patterns
     */
    private function analyzeMissingDocumentation(): void
    {
        echo "🔎 Analyzing missing documentation...\n";
        
        $patterns = [
            'new_features' => [],
            'complex_methods' => [],
            'api_endpoints' => [],
            'critical_services' => [],
        ];
        
        foreach ($this->undocumentedFiles as $file) {
            // Check if it's a new file
            $gitLog = shell_exec("git log --follow --format='%ai' -- {$file} | tail -1");
            $createdDate = $gitLog ? Carbon::parse(trim($gitLog)) : null;
            
            if ($createdDate && $createdDate->greaterThan(Carbon::now()->subDays(30))) {
                $patterns['new_features'][] = $file;
            }
            
            // Check for complex methods
            if (file_exists($file)) {
                $content = file_get_contents($file);
                $methodCount = preg_match_all('/public\s+function/', $content);
                
                if ($methodCount > 10) {
                    $patterns['complex_methods'][] = [
                        'file' => $file,
                        'method_count' => $methodCount,
                    ];
                }
            }
            
            // Check for API endpoints
            if (str_contains($file, 'Controllers/Api')) {
                $patterns['api_endpoints'][] = $file;
            }
            
            // Check for critical services
            if (str_contains($file, 'Service') && $this->isCriticalService($file)) {
                $patterns['critical_services'][] = $file;
            }
        }
        
        $this->metrics['missing_documentation_patterns'] = $patterns;
    }
    
    /**
     * Track team contributions to documentation
     */
    private function trackTeamContributions(): void
    {
        echo "👥 Tracking team contributions...\n";
        
        // Get git log for documentation files
        $docFiles = glob(__DIR__ . '/../../docs/**/*.md');
        $contributions = [];
        
        foreach ($docFiles as $file) {
            $gitLog = shell_exec("git log --format='%an|%ai' -- {$file}");
            $commits = array_filter(explode("\n", $gitLog));
            
            foreach ($commits as $commit) {
                list($author, $date) = explode('|', $commit);
                
                if (!isset($contributions[$author])) {
                    $contributions[$author] = [
                        'total_commits' => 0,
                        'files_touched' => [],
                        'last_contribution' => null,
                        'this_week' => 0,
                        'this_month' => 0,
                    ];
                }
                
                $contributions[$author]['total_commits']++;
                $contributions[$author]['files_touched'][] = basename($file);
                
                $commitDate = Carbon::parse($date);
                
                if (!$contributions[$author]['last_contribution'] || 
                    $commitDate->greaterThan($contributions[$author]['last_contribution'])) {
                    $contributions[$author]['last_contribution'] = $commitDate->toDateTimeString();
                }
                
                if ($commitDate->greaterThan(Carbon::now()->subWeek())) {
                    $contributions[$author]['this_week']++;
                }
                
                if ($commitDate->greaterThan(Carbon::now()->subMonth())) {
                    $contributions[$author]['this_month']++;
                }
            }
        }
        
        // Deduplicate files touched
        foreach ($contributions as &$contrib) {
            $contrib['files_touched'] = count(array_unique($contrib['files_touched']));
        }
        
        // Sort by total commits
        arsort($contributions);
        
        $this->metrics['team_contributions'] = $contributions;
    }
    
    /**
     * Generate alerts for documentation issues
     */
    private function generateAlerts(): void
    {
        echo "🚨 Generating alerts...\n";
        
        // Low coverage alert
        if ($this->metrics['coverage_percentage'] < 60) {
            $this->alerts[] = [
                'type' => 'critical',
                'title' => 'Low Documentation Coverage',
                'message' => "Documentation coverage is at {$this->metrics['coverage_percentage']}% (target: 80%)",
                'action' => 'Review undocumented files and prioritize documentation efforts',
            ];
        }
        
        // Outdated documentation alert
        if ($this->metrics['outdated_count'] > 10) {
            $this->alerts[] = [
                'type' => 'warning',
                'title' => 'Outdated Documentation',
                'message' => "{$this->metrics['outdated_count']} files have outdated documentation",
                'action' => 'Update documentation for recently modified files',
            ];
        }
        
        // Critical services without documentation
        $criticalUndocumented = array_filter($this->undocumentedFiles, function($file) {
            return $this->isCriticalService($file);
        });
        
        if (count($criticalUndocumented) > 0) {
            $this->alerts[] = [
                'type' => 'critical',
                'title' => 'Critical Services Undocumented',
                'message' => count($criticalUndocumented) . ' critical services lack documentation',
                'action' => 'Document critical services immediately',
                'files' => array_slice($criticalUndocumented, 0, 5),
            ];
        }
        
        // No recent contributions alert
        $recentContributors = array_filter($this->metrics['team_contributions'], function($contrib) {
            return $contrib['this_week'] > 0;
        });
        
        if (count($recentContributors) < 2) {
            $this->alerts[] = [
                'type' => 'info',
                'title' => 'Low Documentation Activity',
                'message' => 'Only ' . count($recentContributors) . ' team members contributed to docs this week',
                'action' => 'Encourage team documentation during sprint planning',
            ];
        }
    }
    
    /**
     * Calculate health score for documentation
     */
    private function calculateHealthScore(): int
    {
        $score = 100;
        
        // Coverage impacts score heavily
        $coverageScore = $this->metrics['coverage_percentage'];
        $score = min($score, $coverageScore * 1.2); // Max 100
        
        // Outdated docs reduce score
        $outdatedPenalty = min($this->metrics['outdated_count'] * 2, 30);
        $score -= $outdatedPenalty;
        
        // Critical alerts reduce score
        $criticalAlerts = count(array_filter($this->alerts, fn($a) => $a['type'] === 'critical'));
        $score -= $criticalAlerts * 10;
        
        // Recent contributions boost score
        $recentContribs = count(array_filter($this->metrics['team_contributions'], 
            fn($c) => $c['this_week'] > 0));
        $score += min($recentContribs * 2, 10);
        
        return max(0, min(100, round($score)));
    }
    
    /**
     * Get all PHP files in the project
     */
    private function getAllPhpFiles(): array
    {
        $files = [];
        $directories = [
            'app/Http/Controllers',
            'app/Services',
            'app/Models',
            'app/Jobs',
            'app/Console/Commands',
        ];
        
        foreach ($directories as $dir) {
            $dirPath = base_path($dir);
            if (is_dir($dirPath)) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($dirPath)
                );
                
                foreach ($iterator as $file) {
                    if ($file->isFile() && $file->getExtension() === 'php') {
                        $files[] = str_replace(base_path() . '/', '', $file->getPathname());
                    }
                }
            }
        }
        
        return $files;
    }
    
    /**
     * Calculate coverage for specific type
     */
    private function calculateTypeCoverage(string $directory): array
    {
        $allFiles = array_filter($this->getAllPhpFiles(), function($file) use ($directory) {
            return str_starts_with($file, $directory);
        });
        
        $documented = array_filter($this->documentedFiles, function($file) use ($directory) {
            return str_starts_with($file, $directory);
        });
        
        $total = count($allFiles);
        $docCount = count($documented);
        
        return [
            'total' => $total,
            'documented' => $docCount,
            'percentage' => $total > 0 ? round(($docCount / $total) * 100, 2) : 0,
        ];
    }
    
    /**
     * Check if service is critical
     */
    private function isCriticalService(string $file): bool
    {
        $criticalServices = [
            'RetellService',
            'CalcomService',
            'AppointmentService',
            'PaymentService',
            'AuthService',
            'WebhookService',
        ];
        
        foreach ($criticalServices as $service) {
            if (str_contains($file, $service)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get documentation modification time
     */
    private function getDocumentationModTime(string $notionPageId): int
    {
        // In real implementation, this would query Notion API
        // For now, return a timestamp from cache
        $cacheFile = storage_path("notion-cache/{$notionPageId}.json");
        
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            return $data['last_modified'] ?? time();
        }
        
        return time() - 86400; // Default to 1 day ago
    }
    
    /**
     * Calculate update priority
     */
    private function calculateUpdatePriority(string $file, int $daysBehind): int
    {
        $priority = $daysBehind;
        
        // Boost priority for critical files
        if ($this->isCriticalService($file)) {
            $priority *= 2;
        }
        
        // Boost priority for API controllers
        if (str_contains($file, 'Controllers/Api')) {
            $priority *= 1.5;
        }
        
        // Boost priority for frequently changed files
        $changeCount = (int) shell_exec("git log --oneline -- {$file} | wc -l");
        if ($changeCount > 50) {
            $priority *= 1.3;
        }
        
        return round($priority);
    }
    
    /**
     * Save dashboard to file
     */
    private function saveDashboard(array $dashboard): void
    {
        $file = storage_path('documentation-dashboard.json');
        file_put_contents($file, json_encode($dashboard, JSON_PRETTY_PRINT));
        
        // Also save HTML version
        $html = $this->generateHtmlDashboard($dashboard);
        file_put_contents(public_path('docs/dashboard.html'), $html);
        
        echo "✅ Dashboard saved to: {$file}\n";
        echo "🌐 HTML version: /docs/dashboard.html\n";
    }
    
    /**
     * Generate HTML dashboard
     */
    private function generateHtmlDashboard(array $dashboard): string
    {
        $healthColor = $dashboard['health_score'] > 80 ? 'green' : 
                      ($dashboard['health_score'] > 60 ? 'orange' : 'red');
        
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Documentation Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .metric-card { background: white; padding: 20px; margin: 10px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .metric-title { font-size: 14px; color: #666; }
        .metric-value { font-size: 32px; font-weight: bold; color: #333; }
        .alert { padding: 15px; margin: 10px 0; border-radius: 5px; }
        .alert-critical { background: #fee; border-left: 4px solid #f44; }
        .alert-warning { background: #ffeaa7; border-left: 4px solid #fdcb6e; }
        .alert-info { background: #e3f2fd; border-left: 4px solid #2196f3; }
        .progress-bar { background: #e0e0e0; height: 20px; border-radius: 10px; overflow: hidden; }
        .progress-fill { background: #4caf50; height: 100%; transition: width 0.3s; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        h1, h2 { color: #333; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Documentation Dashboard</h1>
        <p>Generated: {$dashboard['generated_at']}</p>
        
        <div class="grid">
            <div class="metric-card">
                <div class="metric-title">Health Score</div>
                <div class="metric-value" style="color: {$healthColor}">{$dashboard['health_score']}%</div>
            </div>
            
            <div class="metric-card">
                <div class="metric-title">Coverage</div>
                <div class="metric-value">{$dashboard['coverage']['percentage']}%</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {$dashboard['coverage']['percentage']}%"></div>
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-title">Documented Files</div>
                <div class="metric-value">{$dashboard['coverage']['documented']}</div>
                <small>of {$dashboard['metrics']['total_files']} total</small>
            </div>
            
            <div class="metric-card">
                <div class="metric-title">Outdated Docs</div>
                <div class="metric-value">{$dashboard['metrics']['outdated_count']}</div>
            </div>
        </div>
        
        <h2>🚨 Alerts</h2>
HTML;
        
        foreach ($dashboard['alerts'] as $alert) {
            $html .= <<<HTML
        <div class="alert alert-{$alert['type']}">
            <strong>{$alert['title']}</strong><br>
            {$alert['message']}<br>
            <em>Action: {$alert['action']}</em>
        </div>
HTML;
        }
        
        $html .= <<<HTML
        
        <h2>👥 Team Contributions</h2>
        <table>
            <tr>
                <th>Contributor</th>
                <th>Total Commits</th>
                <th>This Week</th>
                <th>This Month</th>
                <th>Last Contribution</th>
            </tr>
HTML;
        
        foreach (array_slice($dashboard['team_contributions'], 0, 10) as $author => $contrib) {
            $html .= <<<HTML
            <tr>
                <td>{$author}</td>
                <td>{$contrib['total_commits']}</td>
                <td>{$contrib['this_week']}</td>
                <td>{$contrib['this_month']}</td>
                <td>{$contrib['last_contribution']}</td>
            </tr>
HTML;
        }
        
        $html .= <<<HTML
        </table>
    </div>
</body>
</html>
HTML;
        
        return $html;
    }
    
    /**
     * Update Notion dashboard
     */
    private function updateNotionDashboard(array $dashboard): void
    {
        // This would update a Notion dashboard page
        // Implementation depends on Notion API integration
        echo "📤 Updating Notion dashboard...\n";
    }
    
    /**
     * Load documentation mapping
     */
    private function loadDocumentationMapping(): void
    {
        $mappingFile = __DIR__ . '/doc-mapping.json';
        if (file_exists($mappingFile)) {
            $mapping = json_decode(file_get_contents($mappingFile), true);
            $this->documentedFiles = array_keys($mapping);
        }
    }
}

// Run monitor if executed directly
if (php_sapi_name() === 'cli') {
    $monitor = new DocumentationMonitor();
    $dashboard = $monitor->generateDashboard();
    
    // Output summary
    echo "\n📊 Dashboard Summary:\n";
    echo "Health Score: {$dashboard['health_score']}%\n";
    echo "Coverage: {$dashboard['coverage']['percentage']}%\n";
    echo "Alerts: " . count($dashboard['alerts']) . "\n";
    echo "\nView full dashboard at: /docs/dashboard.html\n";
}