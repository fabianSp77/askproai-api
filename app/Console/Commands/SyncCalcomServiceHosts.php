<?php

namespace App\Console\Commands;

use App\Models\Service;
use App\Services\CalcomServiceHostsResolver;
use Illuminate\Console\Command;

class SyncCalcomServiceHosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calcom:sync-service-hosts {--service-id= : Sync specific service} {--company-id= : Sync by company}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Cal.com hosts for services and create local staff mappings';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $resolver = new CalcomServiceHostsResolver();
        $serviceId = $this->option('service-id');
        $companyId = $this->option('company-id');

        if ($serviceId) {
            $service = Service::find($serviceId);
            if (!$service) {
                $this->error("Service with ID $serviceId not found");
                return 1;
            }

            $count = $resolver->autoSyncHostMappings($service);
            $this->info("✅ Synced $count host mappings for service: {$service->name}");
            return 0;
        }

        if ($companyId) {
            $services = Service::where('company_id', $companyId)
                ->whereNotNull('calcom_event_type_id')
                ->get();

            $totalCount = 0;
            foreach ($services as $service) {
                $count = $resolver->autoSyncHostMappings($service);
                $this->line("  ✅ {$service->name}: $count hosts synced");
                $totalCount += $count;
            }

            $this->info("✅ Total: $totalCount host mappings synced for company $companyId");
            return 0;
        }

        // Sync all services with Cal.com event types
        $services = Service::whereNotNull('calcom_event_type_id')->get();
        $totalCount = 0;

        if ($services->isEmpty()) {
            $this->warn('No services with Cal.com event types found');
            return 0;
        }

        $this->info("Syncing Cal.com hosts for " . $services->count() . " services...\n");

        foreach ($services as $service) {
            $count = $resolver->autoSyncHostMappings($service);
            $this->line("  ✅ [{$service->company_id}] {$service->name}: $count hosts synced");
            $totalCount += $count;
        }

        $this->info("\n✅ Sync complete! Total: $totalCount host mappings created/updated");
        return 0;
    }
}
