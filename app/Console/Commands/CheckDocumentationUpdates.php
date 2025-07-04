<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class CheckDocumentationUpdates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docs:check-updates 
                            {--auto-fix : Automatically update timestamps}
                            {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if documentation needs updating based on recent code changes';

    /**
     * Documentation files to check
     */
    protected array $documentationFiles = [
        'CLAUDE.md' => ['general', 'mcp', 'config'],
        'ERROR_PATTERNS.md' => ['services', 'api'],
        'TROUBLESHOOTING_DECISION_TREE.md' => ['services'],
        'DEPLOYMENT_CHECKLIST.md' => ['config', 'migrations'],
        'PHONE_TO_APPOINTMENT_FLOW.md' => ['api', 'services'],
        'INTEGRATION_HEALTH_MONITOR.md' => ['mcp', 'services'],
    ];

    /**
     * Code patterns that require documentation updates
     */
    protected array $codePatterns = [
        'services' => [
            'path' => 'app/Services/',
            'description' => 'Service classes changed',
        ],
        'mcp' => [
            'path' => 'app/Services/MCP/',
            'description' => 'MCP servers modified',
        ],
        'api' => [
            'path' => 'routes/',
            'description' => 'API routes changed',
        ],
        'config' => [
            'path' => 'config/',
            'description' => 'Configuration files updated',
        ],
        'migrations' => [
            'path' => 'database/migrations/',
            'description' => 'Database schema changed',
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('📚 Checking documentation status...');

        $results = $this->analyzeDocumentationHealth();

        if ($this->option('json')) {
            $this->line(json_encode($results, JSON_PRETTY_PRINT));
            return $results['health_score'] < 50 ? 1 : 0;
        }

        $this->displayResults($results);

        if ($this->option('auto-fix') && !empty($results['updates_needed'])) {
            $this->autoFixDocumentation($results['updates_needed']);
        }

        return $results['health_score'] < 50 ? 1 : 0;
    }

    /**
     * Analyze documentation health
     */
    protected function analyzeDocumentationHealth(): array
    {
        $results = [
            'health_score' => 100,
            'total_docs' => count($this->documentationFiles),
            'up_to_date' => 0,
            'needs_update' => 0,
            'missing' => 0,
            'updates_needed' => [],
            'recent_changes' => [],
        ];

        // Get recent code changes
        $recentChanges = $this->getRecentCodeChanges();
        $results['recent_changes'] = $recentChanges;

        // Check each documentation file
        foreach ($this->documentationFiles as $file => $categories) {
            $filePath = base_path($file);

            if (!File::exists($filePath)) {
                $results['missing']++;
                $results['updates_needed'][$file] = [
                    'status' => 'missing',
                    'categories' => $categories,
                ];
                continue;
            }

            $docLastModified = File::lastModified($filePath);
            $needsUpdate = false;

            // Check if any related code was changed after doc was updated
            foreach ($categories as $category) {
                if (isset($recentChanges[$category])) {
                    $codeLastModified = $recentChanges[$category]['last_modified'];
                    if ($codeLastModified > $docLastModified) {
                        $needsUpdate = true;
                        $results['updates_needed'][$file] = [
                            'status' => 'outdated',
                            'categories' => $categories,
                            'code_updated' => Carbon::createFromTimestamp($codeLastModified)->toDateTimeString(),
                            'doc_updated' => Carbon::createFromTimestamp($docLastModified)->toDateTimeString(),
                        ];
                        break;
                    }
                }
            }

            if ($needsUpdate) {
                $results['needs_update']++;
            } else {
                $results['up_to_date']++;
            }
        }

        // Calculate health score
        $results['health_score'] = (int) (($results['up_to_date'] / $results['total_docs']) * 100);

        return $results;
    }

    /**
     * Get recent code changes
     */
    protected function getRecentCodeChanges(): array
    {
        $changes = [];

        foreach ($this->codePatterns as $category => $pattern) {
            $path = base_path($pattern['path']);
            
            if (!File::exists($path)) {
                continue;
            }

            $lastModified = 0;
            $files = File::allFiles($path);

            foreach ($files as $file) {
                $modified = $file->getMTime();
                if ($modified > $lastModified) {
                    $lastModified = $modified;
                }
            }

            if ($lastModified > 0) {
                $changes[$category] = [
                    'description' => $pattern['description'],
                    'last_modified' => $lastModified,
                    'human_time' => Carbon::createFromTimestamp($lastModified)->diffForHumans(),
                ];
            }
        }

        return $changes;
    }

    /**
     * Display results
     */
    protected function displayResults(array $results): void
    {
        // Health score with color
        $healthScore = $results['health_score'];
        $healthColor = $healthScore >= 80 ? 'green' : ($healthScore >= 60 ? 'yellow' : 'red');
        
        $this->line('');
        $this->line("Documentation Health Score: <fg=$healthColor>$healthScore%</>");
        $this->line('');

        // Summary table
        $this->table(
            ['Status', 'Count'],
            [
                ['Up to date', $results['up_to_date']],
                ['Needs update', $results['needs_update']],
                ['Missing', $results['missing']],
                ['Total', $results['total_docs']],
            ]
        );

        // Recent changes
        if (!empty($results['recent_changes'])) {
            $this->line('');
            $this->info('Recent code changes:');
            foreach ($results['recent_changes'] as $category => $change) {
                $this->line("  • {$change['description']} - {$change['human_time']}");
            }
        }

        // Files needing update
        if (!empty($results['updates_needed'])) {
            $this->line('');
            $this->warn('Documentation files needing update:');
            foreach ($results['updates_needed'] as $file => $details) {
                if ($details['status'] === 'missing') {
                    $this->error("  ✗ $file - MISSING");
                } else {
                    $this->warn("  ⚠ $file - Outdated");
                    $this->line("    Code updated: {$details['code_updated']}");
                    $this->line("    Doc updated:  {$details['doc_updated']}");
                }
            }
        }

        // Recommendations
        $this->line('');
        if ($healthScore < 60) {
            $this->error('⚠️  Documentation is critically out of date!');
            $this->line('Run with --auto-fix to update timestamps, then manually review content.');
        } elseif ($healthScore < 80) {
            $this->warn('📝 Some documentation needs attention.');
        } else {
            $this->info('✅ Documentation is in good shape!');
        }
    }

    /**
     * Auto-fix documentation timestamps
     */
    protected function autoFixDocumentation(array $updatesNeeded): void
    {
        $this->line('');
        $this->info('🔧 Auto-fixing documentation timestamps...');

        foreach ($updatesNeeded as $file => $details) {
            if ($details['status'] === 'missing') {
                $this->warn("Cannot auto-fix missing file: $file");
                continue;
            }

            $filePath = base_path($file);
            $content = File::get($filePath);

            // Update last modified timestamp in file
            $timestamp = now()->format('Y-m-d H:i:s');
            $updatedContent = preg_replace(
                '/Last Updated: \d{4}-\d{2}-\d{2}.*$/m',
                "Last Updated: $timestamp",
                $content
            );

            // If no timestamp found, add one at the top
            if ($updatedContent === $content) {
                $updatedContent = "> Last Updated: $timestamp\n\n" . $content;
            }

            File::put($filePath, $updatedContent);
            $this->line("  ✓ Updated $file");
        }

        $this->line('');
        $this->warn('⚠️  Timestamps updated. Please review content manually!');
    }
}