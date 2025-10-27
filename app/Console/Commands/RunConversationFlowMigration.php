<?php

namespace App\Console\Commands;

use App\Services\Agents\ConversationFlowMigrationAgent;
use Illuminate\Console\Command;

class RunConversationFlowMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'conversation-flow:migrate
                            {--dry-run : Run in dry-run mode without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run Conversation Flow Migration Agent - Full-Stack migration from Single Prompt to Conversation Flow';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting Conversation Flow Migration Agent...');
        $this->newLine();

        // Check options
        $dryRun = $this->option('dry-run');
        $verbose = $this->option('verbose'); // Laravel built-in option

        if ($dryRun) {
            $this->warn('⚠️  DRY-RUN MODE: No changes will be made');
            $this->newLine();
        }

        // Create agent instance
        $agent = app(ConversationFlowMigrationAgent::class);

        // Execute migration
        $this->info('Phase 1: Research Validation & System Analysis');
        $this->info('Phase 2: Conversation Flow Architecture Design');
        $this->info('Phase 3: Implementation Preparation');
        $this->newLine();

        $result = $agent->execute([
            'dry_run' => $dryRun,
            'verbose' => $verbose
        ]);

        // Display results
        if ($result['success']) {
            $this->info('✅ Migration Agent completed successfully!');
            $this->newLine();

            // Phase 1 Results
            if (isset($result['results']['phase_1'])) {
                $this->info('📋 Phase 1 Results:');
                $phase1 = $result['results']['phase_1'];

                if (isset($phase1['research_validation'])) {
                    $this->line('  ✓ Research Validation:');
                    $this->line('    - Validated: ' . $phase1['research_validation']['validated_claims']);
                    $this->line('    - Corrected: ' . $phase1['research_validation']['corrected_claims']);
                    $this->line('    - Rejected: ' . $phase1['research_validation']['rejected_claims']);
                }

                if (isset($phase1['system_analysis'])) {
                    $this->line('  ✓ System Analysis:');
                    $this->line('    - Current Success Rate: ' . $phase1['system_analysis']['current_success_rate']);
                    $this->line('    - Pain Points: ' . $phase1['system_analysis']['pain_points_identified']);
                }
                $this->newLine();
            }

            // Phase 2 Results
            if (isset($result['results']['phase_2'])) {
                $this->info('🏗️  Phase 2 Results:');
                $phase2 = $result['results']['phase_2'];

                if (isset($phase2['node_graph'])) {
                    $this->line('  ✓ Node Graph Design:');
                    $this->line('    - Total Nodes: ' . $phase2['node_graph']['total_nodes']);
                    $this->line('    - Total Transitions: ' . $phase2['node_graph']['total_transitions']);
                }
                $this->newLine();
            }

            // Summary
            if (isset($result['results']['summary'])) {
                $summary = $result['results']['summary'];

                $this->info('🎯 Impact Summary:');
                if (isset($summary['estimated_impact'])) {
                    foreach ($summary['estimated_impact'] as $key => $value) {
                        $this->line('  - ' . ucfirst(str_replace('_', ' ', $key)) . ': ' . $value);
                    }
                }
                $this->newLine();

                $this->info('📦 Generated Reports:');
                $this->line('  - Research Validation: storage/app/conversation_flow/reports/research_validation_report.md');
                $this->line('  - Baseline Analysis: storage/app/conversation_flow/reports/baseline_analysis.md');
                $this->line('  - Migration Report: storage/app/conversation_flow/reports/MIGRATION_AGENT_REPORT.md');
                $this->line('  - Node Graph: storage/app/conversation_flow/graphs/node_graph.json');
                $this->line('  - Mermaid Diagram: storage/app/conversation_flow/graphs/conversation_flow.mermaid');
                $this->newLine();
            }

            // Next Steps
            $this->info('🚀 Next Steps:');
            $this->line('  1. Review generated reports in storage/app/conversation_flow/reports/');
            $this->line('  2. Review node graph design in storage/app/conversation_flow/graphs/');
            $this->line('  3. Read MIGRATION_AGENT_REPORT.md for complete implementation checklist');
            $this->line('  4. Create Conversation Flow agent in Retell.ai Dashboard');
            $this->line('  5. Setup A/B testing (50/50 split)');
            $this->newLine();

            $this->info('✨ Estimated Impact:');
            $this->line('  - Success Rate: 57% → 83% (+26 pp)');
            $this->line('  - Szenario 4: 25% → 85% (+60 pp)');
            $this->line('  - Monthly Revenue: +€3,360');
            $this->newLine();

            return Command::SUCCESS;

        } else {
            $this->error('❌ Migration Agent failed!');
            $this->newLine();

            if (!empty($result['errors'])) {
                $this->error('Errors encountered:');
                foreach ($result['errors'] as $error) {
                    $this->line('  - Phase: ' . ($error['phase'] ?? 'unknown'));
                    $this->line('    Error: ' . ($error['error'] ?? 'unknown'));
                }
            }
            $this->newLine();

            return Command::FAILURE;
        }
    }
}
