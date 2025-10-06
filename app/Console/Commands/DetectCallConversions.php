<?php

namespace App\Console\Commands;

use App\Services\ConversionTracker;
use Illuminate\Console\Command;

class DetectCallConversions extends Command
{
    protected $signature = 'calls:detect-conversions
                            {--hours=24 : Look back N hours for conversions}
                            {--auto-link : Automatically link detected conversions}';

    protected $description = 'Detect and link calls that resulted in appointments';

    public function handle()
    {
        $hoursBack = (int) $this->option('hours');
        $autoLink = $this->option('auto-link');

        $this->info('🔍 Detecting Call Conversions');
        $this->info('Looking back: ' . $hoursBack . ' hours');
        $this->info('Auto-link: ' . ($autoLink ? 'Yes' : 'No'));
        $this->info(str_repeat('=', 50));

        try {
            // Detect conversions
            $stats = ConversionTracker::detectConversions($hoursBack);

            // Display results
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Calls Checked', $stats['calls_checked']],
                    ['New Conversions Found', $stats['conversions_found']],
                    ['Already Linked', $stats['already_linked']],
                ]
            );

            if ($stats['conversions_found'] > 0) {
                $this->info('');
                $this->info('✅ Successfully linked ' . $stats['conversions_found'] . ' conversions!');
            } else {
                $this->info('');
                $this->info('ℹ️ No new conversions detected.');
            }

            // Show current metrics
            $this->newLine();
            $this->info('📊 Current Conversion Metrics:');

            $metrics = ConversionTracker::getConversionMetrics();
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Calls (This Month)', $metrics['overview']['total_calls']],
                    ['Converted Calls', $metrics['overview']['converted_calls']],
                    ['Conversion Rate', $metrics['overview']['conversion_rate'] . '%'],
                    ['Avg. Time to Conversion', $metrics['overview']['avg_conversion_time_minutes'] . ' minutes'],
                ]
            );

            // Show agent performance
            $this->newLine();
            $this->info('🏆 Agent Performance:');

            $agentPerformance = ConversionTracker::getAgentPerformance();
            if ($agentPerformance['agents']->count() > 0) {
                $this->table(
                    ['Agent', 'Calls', 'Conversions', 'Rate', 'Avg Duration'],
                    $agentPerformance['agents']->take(5)->map(function($agent) {
                        return [
                            $agent->agent_name,
                            $agent->total_calls,
                            $agent->converted_calls,
                            $agent->conversion_rate . '%',
                            $agent->avg_duration_formatted,
                        ];
                    })->toArray()
                );
            }

        } catch (\Exception $e) {
            $this->error('❌ Error detecting conversions: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}