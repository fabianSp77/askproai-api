<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Jobs\SyncEventTypesJob;
use App\Services\CalcomSyncService;

class SyncCalcomEventTypesCommand extends Command
{
    protected $signature = 'calcom:sync-event-types
                            {company? : The company ID to sync (optional, all if not specified)}
                            {--queue : Use queue for processing}
                            {--force : Force sync even if recently synced}';
    
    protected $description = 'Synchronisiere Event-Types von Cal.com';
    
    private $syncService;
    
    public function __construct(CalcomSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }
    
    public function handle()
    {
        $companyId = $this->argument('company');
        $useQueue = $this->option('queue');
        $force = $this->option('force');
        
        if ($companyId) {
            // Sync specific company
            $company = Company::findOrFail($companyId);
            $this->syncCompany($company, $useQueue, $force);
        } else {
            // Sync all companies with Cal.com API key
            $companies = Company::whereNotNull('calcom_api_key')->get();
            
            if ($companies->isEmpty()) {
                $this->warn('Keine Unternehmen mit Cal.com API Key gefunden.');
                return;
            }
            
            $this->info("Synchronisiere Event-Types für {$companies->count()} Unternehmen...");
            
            foreach ($companies as $company) {
                $this->syncCompany($company, $useQueue, $force);
            }
        }
        
        $this->info('Synchronisation abgeschlossen.');
    }
    
    private function syncCompany($company, $useQueue, $force)
    {
        // Prüfe ob kürzlich synchronisiert wurde (außer bei --force)
        if (!$force) {
            $lastSync = \App\Models\CalcomEventType::where('company_id', $company->id)
                ->whereNotNull('last_synced_at')
                ->orderBy('last_synced_at', 'desc')
                ->first();
            
            if ($lastSync && $lastSync->last_synced_at->diffInMinutes(now()) < 60) {
                $this->info("Überspringe {$company->name} - kürzlich synchronisiert");
                return;
            }
        }
        
        $this->info("Synchronisiere Event-Types für: {$company->name}");
        
        if ($useQueue) {
            SyncEventTypesJob::dispatch($company->id);
            $this->info("  → Job in Queue eingereiht");
        } else {
            try {
                $result = $this->syncService->syncEventTypesForCompany($company->id);
                $this->info("  → {$result['synced_count']} Event-Types synchronisiert");
                
                if (!empty($result['errors'])) {
                    foreach ($result['errors'] as $error) {
                        $this->warn("  → Fehler: " . $error['error']);
                    }
                }
            } catch (\Exception $e) {
                $this->error("  → Fehler: " . $e->getMessage());
            }
        }
    }
}