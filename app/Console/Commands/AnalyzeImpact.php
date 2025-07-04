<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Analysis\ImpactAnalyzer;
use App\Services\Analysis\SystemUnderstandingService;

class AnalyzeImpact extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analyze:impact 
                            {--component= : Specific component to analyze}
                            {--changes= : JSON file with changes to analyze}
                            {--git : Analyze uncommitted git changes}
                            {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze the impact of proposed changes before deployment';

    protected ImpactAnalyzer $impactAnalyzer;
    protected SystemUnderstandingService $systemUnderstanding;

    public function __construct(
        ImpactAnalyzer $impactAnalyzer,
        SystemUnderstandingService $systemUnderstanding
    ) {
        parent::__construct();
        $this->impactAnalyzer = $impactAnalyzer;
        $this->systemUnderstanding = $systemUnderstanding;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Running impact analysis...');

        // Get changes to analyze
        $changes = $this->getChanges();

        if (empty($changes)) {
            $this->warn('No changes to analyze.');
            return 0;
        }

        // Run impact analysis
        $analysis = $this->impactAnalyzer->analyzeChanges($changes);

        if ($this->option('json')) {
            $this->line(json_encode($analysis, JSON_PRETTY_PRINT));
            return $analysis['risk_level'] === 'critical' ? 1 : 0;
        }

        // Display results
        $this->displayResults($analysis);

        // Return non-zero exit code for high risk
        return in_array($analysis['risk_level'], ['high', 'critical']) ? 1 : 0;
    }

    /**
     * Get changes to analyze
     */
    protected function getChanges(): array
    {
        // Specific component analysis
        if ($component = $this->option('component')) {
            $this->info("Analyzing component: $component");
            
            $understanding = $this->systemUnderstanding->analyzeComponent($component);
            
            if (!$understanding['exists']) {
                $this->error("Component not found: $component");
                return [];
            }

            // Create synthetic changes based on component analysis
            return $this->createChangesFromComponent($component, $understanding);
        }

        // Changes from JSON file
        if ($changesFile = $this->option('changes')) {
            if (!file_exists($changesFile)) {
                $this->error("Changes file not found: $changesFile");
                return [];
            }

            $json = file_get_contents($changesFile);
            $data = json_decode($json, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Invalid JSON in changes file');
                return [];
            }

            return $data['changes'] ?? [];
        }

        // Git changes
        if ($this->option('git')) {
            return $this->getGitChanges();
        }

        // Interactive mode
        return $this->getInteractiveChanges();
    }

    /**
     * Create changes from component analysis
     */
    protected function createChangesFromComponent(string $component, array $understanding): array
    {
        $changes = [];

        // Simulate method changes
        foreach ($understanding['implementation']['methods'] ?? [] as $method => $details) {
            $changes[] = [
                'type' => 'method_signature',
                'class' => $component,
                'method' => $method,
                'action' => 'modify',
                'details' => [
                    'visibility' => $details['visibility'],
                    'parameters' => $details['parameters'],
                ],
            ];
        }

        return $changes;
    }

    /**
     * Get changes from git
     */
    protected function getGitChanges(): array
    {
        $changes = [];

        // Get modified files
        exec('git diff --name-only', $modifiedFiles);
        exec('git diff --cached --name-only', $stagedFiles);

        $allFiles = array_unique(array_merge($modifiedFiles, $stagedFiles));

        foreach ($allFiles as $file) {
            $changes[] = [
                'file' => $file,
                'action' => 'modify',
            ];
        }

        // Get deleted files
        exec('git ls-files --deleted', $deletedFiles);
        foreach ($deletedFiles as $file) {
            $changes[] = [
                'file' => $file,
                'action' => 'delete',
            ];
        }

        return $changes;
    }

    /**
     * Get changes interactively
     */
    protected function getInteractiveChanges(): array
    {
        $this->info('Enter changes to analyze (press Ctrl+D when done):');
        
        $changes = [];
        
        while (true) {
            $type = $this->choice(
                'Change type',
                ['file', 'method', 'database', 'config', 'done'],
                'done'
            );

            if ($type === 'done') {
                break;
            }

            switch ($type) {
                case 'file':
                    $changes[] = [
                        'file' => $this->ask('File path'),
                        'action' => $this->choice('Action', ['create', 'modify', 'delete']),
                    ];
                    break;

                case 'method':
                    $changes[] = [
                        'type' => 'method_signature',
                        'class' => $this->ask('Class name'),
                        'method' => $this->ask('Method name'),
                        'action' => $this->choice('Action', ['create', 'modify', 'delete']),
                    ];
                    break;

                case 'database':
                    $changes[] = [
                        'type' => 'database_schema',
                        'table' => $this->ask('Table name'),
                        'action' => $this->choice('Action', ['create', 'modify', 'delete']),
                    ];
                    break;

                case 'config':
                    $changes[] = [
                        'type' => 'configuration',
                        'file' => $this->ask('Config file'),
                        'key' => $this->ask('Config key (optional)'),
                    ];
                    break;
            }
        }

        return $changes;
    }

    /**
     * Display analysis results
     */
    protected function displayResults(array $analysis): void
    {
        // Header
        $this->line('');
        $riskColor = $this->getRiskColor($analysis['risk_level']);
        $this->line("Risk Level: <fg=$riskColor>{$analysis['risk_level']}</>");
        $this->line("Analysis ID: {$analysis['correlation_id']}");
        $this->line('');

        // Summary
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Changes', $analysis['total_changes']],
                ['Breaking Changes', count($analysis['breaking_changes'])],
                ['Warnings', count($analysis['warnings'])],
                ['Affected Components', count($analysis['affected_components'])],
                ['Deployment Strategy', $analysis['deployment_strategy']],
            ]
        );

        // Breaking changes
        if (!empty($analysis['breaking_changes'])) {
            $this->line('');
            $this->error('⚠️  Breaking Changes Detected:');
            foreach ($analysis['breaking_changes'] as $change) {
                $this->line("  • {$change['type']} - Risk: {$change['risk_level']}");
                foreach ($change['warnings'] as $warning) {
                    $this->line("    - $warning");
                }
            }
        }

        // Warnings
        if (!empty($analysis['warnings'])) {
            $this->line('');
            $this->warn('⚠️  Warnings:');
            foreach ($analysis['warnings'] as $warning) {
                $this->line("  • $warning");
            }
        }

        // Affected components
        if (!empty($analysis['affected_components'])) {
            $this->line('');
            $this->info('📦 Affected Components:');
            $components = array_unique($analysis['affected_components']);
            foreach (array_slice($components, 0, 10) as $component) {
                $this->line("  • $component");
            }
            if (count($components) > 10) {
                $this->line("  ... and " . (count($components) - 10) . " more");
            }
        }

        // Rollback plan
        if (!empty($analysis['rollback_plan'])) {
            $this->line('');
            $this->info('🔄 Rollback Plan:');
            $this->line("Estimated rollback time: {$analysis['rollback_plan']['estimated_time']} minutes");
            
            $this->line('');
            $this->line('Preparation steps:');
            foreach ($analysis['rollback_plan']['preparation'] as $step) {
                $this->line("  • {$step['step']}");
                if (isset($step['command'])) {
                    $this->line("    $ {$step['command']}");
                }
            }
        }

        // Recommendations
        if (!empty($analysis['recommendations'])) {
            $this->line('');
            $this->info('💡 Recommendations:');
            foreach ($analysis['recommendations'] as $rec) {
                $priorityColor = $rec['priority'] === 'high' ? 'red' : 'yellow';
                $this->line("  <fg=$priorityColor>[{$rec['priority']}]</> {$rec['action']}");
                $this->line("    Reason: {$rec['reason']}");
                
                if (!empty($rec['steps'])) {
                    foreach ($rec['steps'] as $step) {
                        $this->line("    - $step");
                    }
                }
                $this->line('');
            }
        }

        // Final advice
        $this->line('');
        switch ($analysis['risk_level']) {
            case 'critical':
                $this->error('🚫 DEPLOYMENT NOT RECOMMENDED!');
                $this->line('Critical issues must be resolved before deployment.');
                break;
                
            case 'high':
                $this->warn('⚠️  PROCEED WITH CAUTION');
                $this->line('Consider implementing recommended safeguards.');
                break;
                
            case 'medium':
                $this->warn('📋 REVIEW RECOMMENDED');
                $this->line('Ensure all stakeholders are informed.');
                break;
                
            case 'low':
                $this->info('✅ LOW RISK - Safe to proceed');
                $this->line('Standard deployment procedures apply.');
                break;
        }
    }

    /**
     * Get color for risk level
     */
    protected function getRiskColor(string $riskLevel): string
    {
        return match ($riskLevel) {
            'critical' => 'red',
            'high' => 'red',
            'medium' => 'yellow',
            'low' => 'green',
            default => 'white',
        };
    }
}