<?php

namespace App\Console\Commands;

use App\Services\Monitoring\DuplicatePreventionMonitor;
use Illuminate\Console\Command;

/**
 * Check Duplicate Prevention System Health
 *
 * Artisan command to manually check the health of the 4-layer duplicate prevention system
 *
 * Usage: php artisan duplicate-prevention:health-check
 *
 * @author Claude (SuperClaude Framework)
 * @date 2025-10-06
 */
class CheckDuplicatePreventionHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'duplicate-prevention:health-check
                            {--json : Output results in JSON format}
                            {--prometheus : Output results in Prometheus format}
                            {--log : Log results to application log}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the health of the duplicate booking prevention system';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(DuplicatePreventionMonitor $monitor): int
    {
        $this->info('🔍 Duplicate Prevention System - Health Check');
        $this->info('═══════════════════════════════════════════════════════');

        // Get metrics
        $metrics = $monitor->getHealthMetrics();

        // Handle output format
        if ($this->option('prometheus')) {
            $this->line($monitor->getPrometheusMetrics());
            return 0;
        }

        if ($this->option('json')) {
            $this->line(json_encode($metrics, JSON_PRETTY_PRINT));
            return 0;
        }

        // Display human-readable output
        $this->displayMetrics($metrics);

        // Log if requested
        if ($this->option('log')) {
            $monitor->logHealthStatus();
            $this->info('✅ Results logged to application log');
        }

        // Determine exit code based on health
        $exitCode = 0;
        if ($metrics['database_integrity']['duplicate_count'] > 0) {
            $exitCode = 1; // Critical: duplicates found
        } elseif ($metrics['validation_layer_status']['deployed_layers'] < 3) {
            $exitCode = 2; // Warning: not all layers deployed
        } elseif (!$metrics['constraint_status']['is_unique']) {
            $exitCode = 3; // Critical: constraint not active
        }

        return $exitCode;
    }

    /**
     * Display metrics in human-readable format
     *
     * @param array $metrics
     * @return void
     */
    protected function displayMetrics(array $metrics): void
    {
        // Database Integrity
        $this->newLine();
        $this->line('📊 <fg=cyan>Database Integrity</>');
        $this->line('───────────────────────────────────────────────────────');

        $integrityStatus = $metrics['database_integrity']['status'];
        $statusIcon = $integrityStatus === 'healthy' ? '✅' : '❌';
        $statusColor = $integrityStatus === 'healthy' ? 'green' : 'red';

        $this->line("Status:           {$statusIcon} <fg={$statusColor}>" . strtoupper($integrityStatus) . '</>' );
        $this->line("Integrity Score:  {$metrics['database_integrity']['integrity_score']}/100");
        $this->line("Duplicates:       {$metrics['database_integrity']['duplicate_count']}");
        $this->line("NULL Booking IDs: {$metrics['database_integrity']['null_booking_ids']}");
        $this->line("Total Bookings:   {$metrics['database_integrity']['total_appointments']}");

        if ($metrics['database_integrity']['duplicate_count'] > 0) {
            $this->newLine();
            $this->error('🚨 DUPLICATES DETECTED:');
            foreach ($metrics['database_integrity']['duplicates'] as $dup) {
                $this->line("   - Booking ID: {$dup['booking_id']} (Count: {$dup['count']})");
            }
        }

        // Validation Layers
        $this->newLine();
        $this->line('🛡️  <fg=cyan>Validation Layers</>');
        $this->line('───────────────────────────────────────────────────────');

        $layerStatus = $metrics['validation_layer_status']['status'];
        $layerIcon = $layerStatus === 'healthy' ? '✅' : '⚠️ ';
        $layerColor = $layerStatus === 'healthy' ? 'green' : 'yellow';

        $this->line("Status:           {$layerIcon} <fg={$layerColor}>" . strtoupper($layerStatus) . '</>' );
        $this->line("Deployed Layers:  {$metrics['validation_layer_status']['deployed_layers']}/3");

        foreach ($metrics['validation_layer_status']['layers'] as $key => $layer) {
            $icon = $layer['deployed'] ? '✅' : '❌';
            $line = $layer['line'] ? " (Line {$layer['line']})" : '';
            $this->line("   {$icon} {$layer['name']}{$line}");
        }

        // UNIQUE Constraint
        $this->newLine();
        $this->line('🔒 <fg=cyan>Database UNIQUE Constraint</>');
        $this->line('───────────────────────────────────────────────────────');

        $constraintStatus = $metrics['constraint_status']['status'];
        $constraintIcon = $constraintStatus === 'healthy' ? '✅' : '❌';
        $constraintColor = $constraintStatus === 'healthy' ? 'green' : 'red';

        $this->line("Status:      {$constraintIcon} <fg={$constraintColor}>" . strtoupper($constraintStatus) . '</>' );
        $this->line("Exists:      " . ($metrics['constraint_status']['constraint_exists'] ? 'Yes' : 'No'));
        $this->line("Is Unique:   " . ($metrics['constraint_status']['is_unique'] ? 'Yes' : 'No'));
        if ($metrics['constraint_status']['constraint_name']) {
            $this->line("Name:        {$metrics['constraint_status']['constraint_name']}");
        }

        // Recent Bookings
        $this->newLine();
        $this->line('📈 <fg=cyan>Recent Bookings (24h)</>');
        $this->line('───────────────────────────────────────────────────────');

        $this->line("Total:        {$metrics['recent_bookings']['total_bookings']}");
        $this->line("Successful:   {$metrics['recent_bookings']['successful_bookings']}");

        if (!empty($metrics['recent_bookings']['bookings_by_hour'])) {
            $this->newLine();
            $this->line("Hourly Breakdown:");
            foreach ($metrics['recent_bookings']['bookings_by_hour'] as $hour => $count) {
                $this->line("   {$hour}: {$count} bookings");
            }
        }

        // Potential Issues
        $this->newLine();
        $this->line('⚠️  <fg=cyan>Potential Issues</>');
        $this->line('───────────────────────────────────────────────────────');

        if ($metrics['potential_issues']['total_issues'] === 0) {
            $this->line('✅ No issues detected');
        } else {
            $this->line("Total Issues: {$metrics['potential_issues']['total_issues']}");
            $this->newLine();
            foreach ($metrics['potential_issues']['issues'] as $issue) {
                $severityColor = match($issue['severity']) {
                    'critical' => 'red',
                    'warning' => 'yellow',
                    default => 'white'
                };
                $this->line("<fg={$severityColor}>[{$issue['severity']}]</> {$issue['message']} (Count: {$issue['count']})");
            }
        }

        // Summary
        $this->newLine();
        $this->line('═══════════════════════════════════════════════════════');

        $overallHealthy = $metrics['database_integrity']['status'] === 'healthy'
            && $metrics['validation_layer_status']['status'] === 'healthy'
            && $metrics['constraint_status']['status'] === 'healthy';

        if ($overallHealthy) {
            $this->info('✅ System Status: HEALTHY - All checks passed');
        } else {
            $this->error('❌ System Status: ISSUES DETECTED - Review above');
        }

        $this->line('═══════════════════════════════════════════════════════');
        $this->newLine();
    }
}
