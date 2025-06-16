<?php

namespace App\Console\Commands;

use App\Services\CalcomSyncService;
use App\Models\Company;
use Illuminate\Console\Command;

class CalcomSync extends Command
{
    protected $signature = 'calcom:sync 
                            {--company= : Specific company ID to sync}
                            {--dry-run : Show what would be synced without making changes}
                            {--force : Force sync even if recently synced}';
    
    protected $description = 'Synchronisiert Cal.com Event Types mit der lokalen Datenbank';
    
    private CalcomSyncService $syncService;
    
    public function __construct(CalcomSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }
    
    public function handle()
    {
        $this->info('🔄 Starte Cal.com Synchronisation...');
        
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $companyId = $this->option('company');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODUS - Keine Änderungen werden gespeichert');
        }
        
        try {
            if ($companyId) {
                // Sync specific company
                $company = Company::findOrFail($companyId);
                $this->syncCompany($company, $dryRun);
            } else {
                // Sync all active companies with Cal.com API key
                $companies = Company::where('active', true)
                    ->whereNotNull('calcom_api_key')
                    ->get();
                
                $this->info("Gefunden: {$companies->count()} Companies mit Cal.com Integration");
                
                foreach ($companies as $company) {
                    $this->syncCompany($company, $dryRun);
                }
            }
            
            $this->info('✅ Synchronisation abgeschlossen!');
            
        } catch (\Exception $e) {
            $this->error('❌ Fehler bei der Synchronisation: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    private function syncCompany(Company $company, bool $dryRun): void
    {
        $this->info("\n📊 Synchronisiere Company: {$company->name} (ID: {$company->id})");
        
        if ($dryRun) {
            $this->table(
                ['Field', 'Value'],
                [
                    ['Company', $company->name],
                    ['Cal.com API Key', substr($company->calcom_api_key, 0, 20) . '...'],
                    ['Calendar Mode', $company->calendar_mode],
                    ['Active Services', $company->services()->where('active', true)->count()],
                    ['Active Staff', $company->staff()->where('active', true)->whereNotNull('calcom_user_id')->count()],
                ]
            );
            return;
        }
        
        try {
            $results = $this->syncService->syncCompanyEventTypes($company->id);
            
            $this->info("✅ Erfolgreich synchronisiert: {$results['synced']}");
            
            if ($results['failed'] > 0) {
                $this->warn("⚠️  Fehlgeschlagen: {$results['failed']}");
                
                foreach ($results['errors'] as $error) {
                    $this->error("   - Service {$error['service_id']}, Staff {$error['staff_id']}: {$error['error']}");
                }
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Fehler bei Company {$company->name}: {$e->getMessage()}");
        }
    }
}
