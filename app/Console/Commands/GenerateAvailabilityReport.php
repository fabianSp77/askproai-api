<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Repositories\AppointmentRepository;
use App\Models\Company;
use Carbon\Carbon;

class GenerateAvailabilityReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'availability:report 
                            {company? : Company ID} 
                            {--from= : Start date (Y-m-d)} 
                            {--to= : End date (Y-m-d)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate availability and utilization report';

    protected AppointmentRepository $appointmentRepository;

    /**
     * Create a new command instance.
     */
    public function __construct(AppointmentRepository $appointmentRepository)
    {
        parent::__construct();
        $this->appointmentRepository = $appointmentRepository;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $companyId = $this->argument('company');
        $from = $this->option('from') ? Carbon::parse($this->option('from')) : Carbon::now()->startOfMonth();
        $to = $this->option('to') ? Carbon::parse($this->option('to')) : Carbon::now()->endOfMonth();
        
        if ($companyId) {
            $this->generateReportForCompany((int) $companyId, $from, $to);
        } else {
            $companies = Company::all();
            foreach ($companies as $company) {
                $this->generateReportForCompany($company->id, $from, $to);
            }
        }
        
        return Command::SUCCESS;
    }
    
    protected function generateReportForCompany(int $companyId, Carbon $from, Carbon $to): void
    {
        $company = Company::find($companyId);
        if (!$company) {
            $this->error("Company {$companyId} not found.");
            return;
        }
        
        $this->info("Generating report for {$company->name}...");
        
        $stats = $this->appointmentRepository->getStatistics($companyId, $from, $to);
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Appointments', $stats['total_appointments']],
                ['Completed', $stats['completed']],
                ['Cancelled', $stats['cancelled']],
                ['No-Show', $stats['no_show']],
                ['Revenue', '€' . number_format($stats['revenue'], 2)],
                ['Completion Rate', $stats['completion_rate'] . '%'],
                ['No-Show Rate', $stats['no_show_rate'] . '%'],
                ['Avg Lead Time', $stats['avg_lead_time_hours'] . ' hours'],
            ]
        );
    }
}