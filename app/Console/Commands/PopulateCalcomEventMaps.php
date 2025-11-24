<?php

namespace App\Console\Commands;

use App\Models\CalcomEventMap;
use App\Models\Company;
use App\Models\Service;
use App\Models\Staff;
use App\Services\CalcomEventTypeManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Populate CalcomEventMap entries for all composite services and active staff
 *
 * This command creates Cal.com event types and mappings for all composite services.
 * Each composite service segment requires a separate Cal.com event type per staff member.
 *
 * Example: Dauerwelle (4 segments: A, B, C, D) × 3 staff = 12 event types
 *
 * Usage:
 *   php artisan calcom:populate-event-maps --dry-run   # Preview changes
 *   php artisan calcom:populate-event-maps             # Execute changes
 *   php artisan calcom:populate-event-maps --force     # Recreate existing mappings
 *
 * @created 2025-11-22
 * @see Phase 2.1 of Composite Sync Implementation Plan
 */
class PopulateCalcomEventMaps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calcom:populate-event-maps
                            {--dry-run : Preview changes without executing}
                            {--force : Recreate existing mappings}
                            {--service= : Only process specific service ID}
                            {--staff= : Only process specific staff ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate CalcomEventMap entries for all composite services and active staff';

    /**
     * Statistics tracking
     */
    protected int $created = 0;
    protected int $skipped = 0;
    protected int $errors = 0;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $serviceFilter = $this->option('service');
        $staffFilter = $this->option('staff');

        $this->info('🔄 Populating CalcomEventMap entries for composite services...');
        $this->newLine();

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Get all companies with Cal.com configured
        $companies = Company::whereNotNull('calcom_team_id')->get();

        if ($companies->isEmpty()) {
            $this->error('❌ No companies found with Cal.com team ID configured');
            return Command::FAILURE;
        }

        foreach ($companies as $company) {
            $this->info("Processing company: {$company->name} (Team ID: {$company->calcom_team_id})");
            $this->processCompany($company, $dryRun, $force, $serviceFilter, $staffFilter);
            $this->newLine();
        }

        // Display summary
        $this->displaySummary($dryRun);

        return Command::SUCCESS;
    }

    /**
     * Process a single company
     */
    protected function processCompany(
        Company $company,
        bool $dryRun,
        bool $force,
        ?string $serviceFilter,
        ?string $staffFilter
    ): void {
        // Get all composite services for this company
        $services = Service::where('company_id', $company->id)
            ->where('composite', true)
            ->where('is_active', true)
            ->when($serviceFilter, fn($q) => $q->where('id', $serviceFilter))
            ->get();

        if ($services->isEmpty()) {
            $this->warn('  No composite services found');
            return;
        }

        $this->info("  Found {$services->count()} composite service(s)");

        // Get all active staff for this company
        $staffMembers = Staff::where('company_id', $company->id)
            ->where('is_active', true)
            ->when($staffFilter, fn($q) => $q->where('id', $staffFilter))
            ->get();

        if ($staffMembers->isEmpty()) {
            $this->warn('  No active staff found');
            return;
        }

        $this->info("  Found {$staffMembers->count()} active staff member(s)");
        $this->newLine();

        $manager = new CalcomEventTypeManager($company);

        // Process each service × staff combination
        foreach ($services as $service) {
            $segments = $service->getSegments();

            if (empty($segments)) {
                $this->warn("  ⚠️  Service '{$service->name}' has no segments defined - skipping");
                continue;
            }

            $this->line("  📋 Service: {$service->name} ({$service->id})");
            $this->line("     Segments: " . collect($segments)->pluck('key')->implode(', '));

            foreach ($staffMembers as $staff) {
                $this->processStaffServiceMapping(
                    $manager,
                    $service,
                    $staff,
                    $segments,
                    $dryRun,
                    $force
                );
            }

            $this->newLine();
        }
    }

    /**
     * Process mapping creation for a service/staff combination
     */
    protected function processStaffServiceMapping(
        CalcomEventTypeManager $manager,
        Service $service,
        Staff $staff,
        array $segments,
        bool $dryRun,
        bool $force
    ): void {
        // Check if staff can perform this service
        $canPerform = DB::table('service_staff')
            ->where('service_id', $service->id)
            ->where('staff_id', $staff->id)
            ->where('is_active', true)
            ->exists();

        if (!$canPerform) {
            $this->line("     ⏭️  {$staff->name}: Cannot perform this service - skipping");
            return;
        }

        // Check existing mappings
        $existingMappings = CalcomEventMap::where('service_id', $service->id)
            ->where('staff_id', $staff->id)
            ->get();

        $existingSegmentKeys = $existingMappings->pluck('segment_key')->toArray();
        $requiredSegmentKeys = collect($segments)->pluck('key')->toArray();

        if (!$force && count($existingSegmentKeys) === count($requiredSegmentKeys)) {
            $this->line("     ✅ {$staff->name}: All segments already mapped ({$existingMappings->count()})");
            $this->skipped += $existingMappings->count();
            return;
        }

        // Create or recreate mappings
        if ($dryRun) {
            $missingSegments = array_diff($requiredSegmentKeys, $existingSegmentKeys);
            $toCreate = $force ? count($requiredSegmentKeys) : count($missingSegments);

            $this->line("     🔍 {$staff->name}: Would create {$toCreate} mapping(s)");
            $this->created += $toCreate;
            return;
        }

        try {
            // Delete existing if forcing recreation
            if ($force && $existingMappings->isNotEmpty()) {
                $this->line("     🗑️  {$staff->name}: Deleting {$existingMappings->count()} existing mapping(s)");
                $existingMappings->each->delete();
            }

            // Create segment event types
            $this->line("     🚀 {$staff->name}: Creating segment mappings...");

            $createdMappings = $manager->createSegmentEventTypes($service, $staff);

            if (!empty($createdMappings)) {
                $this->info("     ✅ {$staff->name}: Created " . count($createdMappings) . " mapping(s)");
                $this->created += count($createdMappings);

                // Resolve child event type IDs for MANAGED event types
                $this->line("     🔍 {$staff->name}: Resolving child event type IDs...");
                $resolver = new \App\Services\CalcomChildEventTypeResolver($service->company);

                foreach ($createdMappings as $mapping) {
                    try {
                        $childId = $resolver->resolveChildEventTypeId(
                            $mapping->event_type_id,
                            $staff->id
                        );

                        if ($childId) {
                            $mapping->update([
                                'child_event_type_id' => $childId,
                                'child_resolved_at' => now()
                            ]);

                            $this->line("        [{$mapping->segment_key}] Parent: {$mapping->event_type_id} → Child: {$childId}");
                        } else {
                            $this->line("        [{$mapping->segment_key}] Parent: {$mapping->event_type_id} (no child needed - not MANAGED)");
                        }
                    } catch (\Exception $e) {
                        $this->warn("        [{$mapping->segment_key}] ⚠️  Failed to resolve child ID: {$e->getMessage()}");
                    }
                }
            } else {
                $this->warn("     ⚠️  {$staff->name}: No mappings created");
            }

        } catch (\Exception $e) {
            $this->error("     ❌ {$staff->name}: Failed - {$e->getMessage()}");
            $this->errors++;
        }
    }

    /**
     * Display summary statistics
     */
    protected function displaySummary(bool $dryRun): void
    {
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('                        SUMMARY');
        $this->info('═══════════════════════════════════════════════════════════');

        if ($dryRun) {
            $this->line("Mode:     DRY RUN (no changes made)");
        } else {
            $this->line("Mode:     LIVE EXECUTION");
        }

        $this->line("Created:  {$this->created} mapping(s)");
        $this->line("Skipped:  {$this->skipped} mapping(s) (already exist)");

        if ($this->errors > 0) {
            $this->line("Errors:   {$this->errors} mapping(s) failed");
        }

        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        // Next steps
        if (!$dryRun && $this->created > 0) {
            $this->info('✅ Next steps:');
            $this->line('   1. Verify mappings in database:');
            $this->line('      SELECT service_id, segment_key, staff_id, event_type_id');
            $this->line('      FROM calcom_event_map ORDER BY service_id, staff_id, segment_key;');
            $this->newLine();
            $this->line('   2. Test composite booking with:');
            $this->line('      php artisan test:composite-booking');
            $this->newLine();
        } elseif ($dryRun && $this->created > 0) {
            $this->info('✅ DRY RUN complete. Run without --dry-run to execute changes.');
        } else {
            $this->info('✅ All mappings already exist - no action needed.');
        }
    }
}
