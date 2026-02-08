<?php

namespace App\Console\Commands;

use App\Models\Call;
use App\Models\Company;
use App\Models\PolicyConfiguration;
use App\Models\ServiceCase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Check Retell Flow Health
 *
 * Monitors Retell agent flows for dead-ends by analyzing recent call patterns.
 * Detects service_desk calls that ended without expected function calls (finalize_ticket).
 *
 * RCA Context: Call #94768 revealed broken flow edges causing agent silence.
 * This command provides proactive detection of similar issues.
 *
 * Usage:
 *   php artisan retell:flow-health                    # Check last 50 calls
 *   php artisan retell:flow-health --hours=24         # Check last 24 hours
 *   php artisan retell:flow-health --company=1658     # Check specific company
 *   php artisan retell:flow-health --json             # JSON output for monitoring
 *
 * @see RetellWebhookController::detectServiceDeskDeadEnd() for real-time detection
 */
class CheckRetellFlowHealth extends Command
{
    protected $signature = 'retell:flow-health
                            {--hours=6 : How many hours back to check}
                            {--limit=100 : Maximum number of calls to analyze}
                            {--company= : Filter to specific company ID}
                            {--json : Output results in JSON format}
                            {--log : Log results to application log}';

    protected $description = 'Check Retell agent flow health by analyzing recent call patterns';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $limit = (int) $this->option('limit');
        $companyId = $this->option('company') ? (int) $this->option('company') : null;
        $jsonOutput = $this->option('json');
        $logOutput = $this->option('log');

        if (!$jsonOutput) {
            $this->info('🔍 Retell Flow Health Check');
            $this->info('═══════════════════════════════════════════');
            $this->info("Period:     Last {$hours} hours");
            $this->info("Limit:      {$limit} calls");
            if ($companyId) {
                $this->info("Company:    {$companyId}");
            }
            $this->newLine();
        }

        // Find service_desk companies
        $serviceDeskCompanyIds = $this->getServiceDeskCompanyIds($companyId);

        if (empty($serviceDeskCompanyIds)) {
            $result = ['status' => 'ok', 'message' => 'No service_desk companies found'];
            if ($jsonOutput) {
                $this->line(json_encode($result, JSON_PRETTY_PRINT));
            } else {
                $this->info('No companies with gateway_mode=service_desk found.');
            }
            return self::SUCCESS;
        }

        // Analyze calls for each company
        $since = now()->subHours($hours);
        $report = [];
        $hasIssues = false;

        foreach ($serviceDeskCompanyIds as $cId) {
            $companyReport = $this->analyzeCompanyCalls($cId, $since, $limit);
            $report[$cId] = $companyReport;

            if ($companyReport['dead_end_count'] > 0) {
                $hasIssues = true;
            }
        }

        // Output
        if ($jsonOutput) {
            $this->line(json_encode([
                'status' => $hasIssues ? 'warning' : 'ok',
                'checked_at' => now()->toIso8601String(),
                'period_hours' => $hours,
                'companies' => $report,
            ], JSON_PRETTY_PRINT));
        } else {
            $this->renderReport($report);
        }

        if ($logOutput && $hasIssues) {
            Log::warning('[FlowHealth] Dead-end calls detected', [
                'period_hours' => $hours,
                'report' => $report,
            ]);
        }

        return $hasIssues ? self::FAILURE : self::SUCCESS;
    }

    private function getServiceDeskCompanyIds(?int $filterCompanyId): array
    {
        $query = PolicyConfiguration::where('policy_type', PolicyConfiguration::POLICY_TYPE_GATEWAY_MODE)
            ->where('configurable_type', Company::class)
            ->whereNotNull('config');

        if ($filterCompanyId) {
            $query->where('company_id', $filterCompanyId);
        }

        return $query->get()
            ->filter(fn($p) => ($p->config['mode'] ?? null) === 'service_desk')
            ->pluck('company_id')
            ->unique()
            ->values()
            ->toArray();
    }

    private function analyzeCompanyCalls(int $companyId, $since, int $limit): array
    {
        $company = Company::find($companyId);

        // Get recent calls for this company
        $calls = Call::where('company_id', $companyId)
            ->where('created_at', '>=', $since)
            ->whereIn('status', ['completed', 'ended'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $totalCalls = $calls->count();
        $deadEndCalls = [];
        $successfulCalls = 0;
        $noTicketCalls = 0;

        // Batch lookup to avoid N+1 queries
        $callIdsWithServiceCase = ServiceCase::whereIn('call_id', $calls->pluck('id'))
            ->pluck('call_id')
            ->toArray();

        foreach ($calls as $call) {
            $hasServiceCase = in_array($call->id, $callIdsWithServiceCase);

            if ($hasServiceCase) {
                $successfulCalls++;
                continue;
            }

            $noTicketCalls++;

            // Classify severity
            $durationSec = $call->duration_sec ?? 0;
            $disconnection = $call->disconnection_reason;

            if ($durationSec < 90 && $disconnection === 'user_hangup') {
                $deadEndCalls[] = [
                    'call_id' => $call->id,
                    'retell_call_id' => $call->retell_call_id,
                    'duration_sec' => $durationSec,
                    'disconnection_reason' => $disconnection,
                    'created_at' => $call->created_at?->toIso8601String(),
                    'agent_id' => $call->retell_agent_id,
                    'severity' => 'high',
                ];
            }
        }

        $deadEndRate = $totalCalls > 0
            ? round(count($deadEndCalls) / $totalCalls * 100, 1)
            : 0;

        return [
            'company_name' => $company?->name ?? "Unknown ({$companyId})",
            'total_calls' => $totalCalls,
            'successful_calls' => $successfulCalls,
            'no_ticket_calls' => $noTicketCalls,
            'dead_end_count' => count($deadEndCalls),
            'dead_end_rate' => $deadEndRate,
            'dead_end_calls' => $deadEndCalls,
            'agent_id' => $company?->retell_agent_id,
        ];
    }

    private function renderReport(array $report): void
    {
        foreach ($report as $companyId => $data) {
            $this->info("Company: {$data['company_name']} (ID: {$companyId})");
            $this->info("Agent:   {$data['agent_id']}");
            $this->info('─────────────────────────────────');

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Calls', $data['total_calls']],
                    ['With Ticket', $data['successful_calls']],
                    ['Without Ticket', $data['no_ticket_calls']],
                    ['Dead-End (short + hangup)', $data['dead_end_count']],
                    ['Dead-End Rate', "{$data['dead_end_rate']}%"],
                ]
            );

            if (!empty($data['dead_end_calls'])) {
                $this->warn("⚠️  Dead-end calls detected!");
                $this->table(
                    ['Call ID', 'Retell Call ID', 'Duration', 'Disconnect', 'Time'],
                    collect($data['dead_end_calls'])->map(fn($c) => [
                        $c['call_id'],
                        substr($c['retell_call_id'] ?? '', 0, 20) . '...',
                        "{$c['duration_sec']}s",
                        $c['disconnection_reason'],
                        $c['created_at'],
                    ])->toArray()
                );

                $this->newLine();
                $this->error('ACTION: Check Retell flow edges for agent ' . ($data['agent_id'] ?? 'unknown'));
                $this->info('  → Verify node_it_classify_issue_v3 has outgoing edges');
                $this->info('  → Verify finalize_ticket function call is reachable');
                $this->info('  → Consider re-deploying from v3.0 template');
            } else {
                $this->info('✅ No dead-end calls detected');
            }

            $this->newLine();
        }
    }
}
