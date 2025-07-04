<?php

namespace App\Console\Commands;

use App\Models\DunningProcess;
use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class DunningStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dunning:status 
                            {--company= : Filter by company ID}
                            {--status= : Filter by status (active, resolved, failed, paused)}
                            {--show-activities : Show recent activities}
                            {--days=30 : Show processes from last N days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show dunning process status and statistics';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $query = DunningProcess::with(['company', 'invoice']);
        
        // Apply filters
        if ($companyId = $this->option('company')) {
            $query->where('company_id', $companyId);
        }
        
        if ($status = $this->option('status')) {
            $query->where('status', $status);
        }
        
        $days = (int) $this->option('days');
        $query->where('created_at', '>=', now()->subDays($days));
        
        // Get processes
        $processes = $query->orderBy('created_at', 'desc')->get();
        
        if ($processes->isEmpty()) {
            $this->info('No dunning processes found matching criteria.');
            return 0;
        }
        
        // Show summary
        $this->showSummary($processes);
        
        // Show detailed list
        $this->showProcessList($processes);
        
        // Show activities if requested
        if ($this->option('show-activities')) {
            $this->showRecentActivities();
        }
        
        return 0;
    }
    
    /**
     * Show summary statistics
     */
    protected function showSummary($processes): void
    {
        $stats = [
            'total' => $processes->count(),
            'active' => $processes->where('status', DunningProcess::STATUS_ACTIVE)->count(),
            'resolved' => $processes->where('status', DunningProcess::STATUS_RESOLVED)->count(),
            'failed' => $processes->where('status', DunningProcess::STATUS_FAILED)->count(),
            'paused' => $processes->where('status', DunningProcess::STATUS_PAUSED)->count(),
            'total_amount' => $processes->sum('original_amount'),
            'recovered_amount' => $processes->where('status', DunningProcess::STATUS_RESOLVED)->sum('original_amount'),
            'outstanding_amount' => $processes->whereIn('status', [DunningProcess::STATUS_ACTIVE, DunningProcess::STATUS_PAUSED])->sum('remaining_amount')
        ];
        
        $this->info('Dunning Process Summary:');
        $this->table(
            ['Status', 'Count', 'Percentage'],
            [
                ['Active', $stats['active'], $this->percentage($stats['active'], $stats['total'])],
                ['Resolved', $stats['resolved'], $this->percentage($stats['resolved'], $stats['total'])],
                ['Failed', $stats['failed'], $this->percentage($stats['failed'], $stats['total'])],
                ['Paused', $stats['paused'], $this->percentage($stats['paused'], $stats['total'])],
                ['Total', $stats['total'], '100%']
            ]
        );
        
        $this->newLine();
        $this->info('Financial Summary:');
        $this->line('Total Amount: €' . number_format($stats['total_amount'], 2));
        $this->line('Recovered: €' . number_format($stats['recovered_amount'], 2));
        $this->line('Outstanding: €' . number_format($stats['outstanding_amount'], 2));
        
        if ($stats['total_amount'] > 0) {
            $recoveryRate = round($stats['recovered_amount'] / $stats['total_amount'] * 100, 1);
            $this->line('Recovery Rate: ' . $recoveryRate . '%');
        }
        
        $this->newLine();
    }
    
    /**
     * Show process list
     */
    protected function showProcessList($processes): void
    {
        $this->info('Dunning Processes:');
        
        $this->table(
            ['ID', 'Company', 'Invoice', 'Status', 'Amount', 'Retries', 'Started', 'Next Retry', 'Service'],
            $processes->map(function ($process) {
                return [
                    $process->id,
                    Str::limit($process->company->name, 20),
                    $process->invoice->number ?? 'N/A',
                    $this->formatStatus($process->status),
                    $process->currency . ' ' . number_format($process->remaining_amount, 2),
                    $process->retry_count . '/' . $process->max_retries,
                    $process->started_at->format('Y-m-d'),
                    $process->next_retry_at?->format('Y-m-d') ?? '-',
                    $process->service_paused ? '⏸️ Paused' : '✅ Active'
                ];
            })
        );
    }
    
    /**
     * Show recent activities
     */
    protected function showRecentActivities(): void
    {
        $activities = \App\Models\DunningActivity::with(['dunningProcess.company'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();
        
        if ($activities->isEmpty()) {
            return;
        }
        
        $this->newLine();
        $this->info('Recent Dunning Activities:');
        
        $this->table(
            ['Time', 'Company', 'Type', 'Description', 'Status'],
            $activities->map(function ($activity) {
                return [
                    $activity->created_at->format('Y-m-d H:i'),
                    Str::limit($activity->dunningProcess->company->name ?? 'N/A', 20),
                    $activity->getIcon() . ' ' . $activity->getTypeLabel(),
                    Str::limit($activity->description, 40),
                    $activity->successful ? '✅' : '❌'
                ];
            })
        );
    }
    
    /**
     * Format status for display
     */
    protected function formatStatus(string $status): string
    {
        return match($status) {
            DunningProcess::STATUS_ACTIVE => '🔄 Active',
            DunningProcess::STATUS_RESOLVED => '✅ Resolved',
            DunningProcess::STATUS_FAILED => '❌ Failed',
            DunningProcess::STATUS_PAUSED => '⏸️ Paused',
            DunningProcess::STATUS_CANCELLED => '🚫 Cancelled',
            default => $status
        };
    }
    
    /**
     * Calculate percentage
     */
    protected function percentage(int $value, int $total): string
    {
        if ($total === 0) {
            return '0%';
        }
        
        return round($value / $total * 100, 1) . '%';
    }
}