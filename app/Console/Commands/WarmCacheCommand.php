<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Jobs\WarmCacheJob;
use Illuminate\Support\Facades\Log;

class WarmCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:warm 
                            {--company= : Specific company ID to warm cache for}
                            {--type= : Specific cache type (event_types, company_settings, staff_schedules, services)}
                            {--async : Run cache warming asynchronously via queue}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm application caches for frequently accessed data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $companyId = $this->option('company');
        $cacheType = $this->option('type');
        $async = $this->option('async');

        if ($companyId) {
            // Warm cache for specific company
            $this->warmCompanyCache((int) $companyId, $cacheType, $async);
        } else {
            // Warm cache for all active companies
            $this->warmAllCompaniesCache($cacheType, $async);
        }

        return Command::SUCCESS;
    }

    /**
     * Warm cache for a specific company
     */
    protected function warmCompanyCache(int $companyId, ?string $cacheType, bool $async): void
    {
        $company = Company::find($companyId);
        
        if (!$company) {
            $this->error("Company with ID {$companyId} not found.");
            return;
        }

        if (!$company->is_active) {
            $this->warn("Company {$company->name} is not active. Skipping cache warming.");
            return;
        }

        $this->info("Warming cache for company: {$company->name}");

        if ($async) {
            WarmCacheJob::dispatch($companyId, $cacheType);
            $this->info("Cache warming job dispatched to queue.");
        } else {
            // Run synchronously
            try {
                $job = new WarmCacheJob($companyId, $cacheType);
                $job->handle();
                $this->info("Cache warming completed successfully.");
            } catch (\Exception $e) {
                $this->error("Cache warming failed: " . $e->getMessage());
                Log::error('Cache warming command failed', [
                    'company_id' => $companyId,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Warm cache for all active companies
     */
    protected function warmAllCompaniesCache(?string $cacheType, bool $async): void
    {
        $companies = Company::where('is_active', true)->get();
        
        $this->info("Found {$companies->count()} active companies to warm cache for.");
        
        $progressBar = $this->output->createProgressBar($companies->count());
        $progressBar->start();

        foreach ($companies as $company) {
            if ($async) {
                WarmCacheJob::dispatch($company->id, $cacheType);
            } else {
                try {
                    $job = new WarmCacheJob($company->id, $cacheType);
                    $job->handle();
                } catch (\Exception $e) {
                    $this->error("\nCache warming failed for {$company->name}: " . $e->getMessage());
                    Log::error('Cache warming command failed', [
                        'company_id' => $company->id,
                        'company_name' => $company->name,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        
        if ($async) {
            $this->info("All cache warming jobs dispatched to queue.");
        } else {
            $this->info("Cache warming completed for all companies.");
        }
    }
}