<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Models\CalcomEventType;
use App\Services\CalcomV2Service;
use App\Jobs\SyncCompanyEventTypesJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AutoSyncEventTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calcom:auto-sync 
                            {--company= : Sync specific company by ID}
                            {--all : Sync all companies}
                            {--force : Force sync even if recently synced}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize Event Types from Cal.com to local database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Starting Event Type synchronization...');
        
        if ($this->option('company')) {
            // Sync specific company
            $company = Company::find($this->option('company'));
            if (!$company) {
                $this->error('Company not found!');
                return 1;
            }
            
            $this->syncCompany($company);
        } elseif ($this->option('all')) {
            // Sync all companies
            $companies = Company::where('is_active', true)
                ->whereNotNull('calcom_api_key')
                ->get();
                
            $this->info("Found {$companies->count()} active companies to sync");
            
            $bar = $this->output->createProgressBar($companies->count());
            $bar->start();
            
            foreach ($companies as $company) {
                $this->syncCompany($company, false);
                $bar->advance();
            }
            
            $bar->finish();
            $this->newLine();
        } else {
            $this->error('Please specify --company=ID or --all');
            return 1;
        }
        
        $this->info('✅ Synchronization complete!');
        return 0;
    }
    
    protected function syncCompany(Company $company, bool $verbose = true): void
    {
        try {
            // Check if recently synced (within last hour)
            if (!$this->option('force')) {
                $lastSync = CalcomEventType::withoutGlobalScopes()
                    ->where('company_id', $company->id)
                    ->whereNotNull('last_synced_at')
                    ->max('last_synced_at');
                    
                if ($lastSync && Carbon::parse($lastSync)->isAfter(now()->subHour())) {
                    if ($verbose) {
                        $this->info("⏭️  Skipping {$company->name} - recently synced");
                    }
                    return;
                }
            }
            
            if ($verbose) {
                $this->info("🔄 Syncing {$company->name}...");
            }
            
            // Dispatch sync job
            SyncCompanyEventTypesJob::dispatch($company);
            
            if ($verbose) {
                $this->info("✅ Sync job dispatched for {$company->name}");
            }
            
        } catch (\Exception $e) {
            Log::error('Event Type sync error', [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);
            
            if ($verbose) {
                $this->error("❌ Error syncing {$company->name}: {$e->getMessage()}");
            }
        }
    }
}