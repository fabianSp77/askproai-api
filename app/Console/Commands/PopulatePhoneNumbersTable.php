<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Branch;
use App\Models\Company;
use App\Models\PhoneNumber;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PopulatePhoneNumbersTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'phone:populate 
                            {--dry-run : Show what would be created without actually creating}
                            {--company= : Only populate for specific company ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate phone_numbers table from existing branch and company data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $companyId = $this->option('company');
        
        $this->info('Starting phone numbers population...');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }
        
        // Disable tenant scope for this operation
        PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class);
        
        $companiesQuery = Company::query();
        if ($companyId) {
            $companiesQuery->where('id', $companyId);
        }
        
        $companies = $companiesQuery->get();
        $totalCreated = 0;
        $totalSkipped = 0;
        
        foreach ($companies as $company) {
            $this->info("\nProcessing company: {$company->name} (ID: {$company->id})");
            
            // Process branches for this company
            $branches = Branch::where('company_id', $company->id)->get();
            
            foreach ($branches as $branch) {
                // Skip if no phone number
                if (empty($branch->phone)) {
                    $this->line("  - Branch '{$branch->name}' has no phone number, skipping");
                    continue;
                }
                
                // Check if phone number already exists
                $exists = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
                    ->where('number', $branch->phone)
                    ->exists();
                    
                if ($exists) {
                    $this->warn("  - Phone number {$branch->phone} already exists, skipping");
                    $totalSkipped++;
                    continue;
                }
                
                // Determine if this is a hotline based on branch settings
                $isHotline = false;
                $routingConfig = [];
                
                // Check if branch has retell_agent_id
                $agentId = $branch->retell_agent_id ?? $company->default_retell_agent_id ?? null;
                
                // If branch is marked as main/primary, it might be a hotline
                if ($branch->is_active && $branches->count() > 1) {
                    $isHotline = true;
                    $routingConfig = [
                        'strategy' => 'menu',
                        'menu_options' => [
                            '1' => ['action' => 'transfer', 'branch_id' => $branch->id],
                            '2' => ['action' => 'info', 'message' => 'opening_hours']
                        ]
                    ];
                }
                
                $phoneData = [
                    'id' => Str::uuid(),
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'number' => $branch->phone,
                    'type' => $isHotline ? 'hotline' : 'direct',
                    'routing_config' => $routingConfig,
                    'agent_id' => $agentId,
                    'active' => $branch->is_active ?? true,
                    'description' => "Phone for {$branch->name}"
                ];
                
                if ($dryRun) {
                    $this->info("  - Would create: {$phoneData['number']} ({$phoneData['type']}) for branch '{$branch->name}'");
                } else {
                    try {
                        PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
                            ->create($phoneData);
                        $this->info("  ✓ Created: {$phoneData['number']} ({$phoneData['type']}) for branch '{$branch->name}'");
                        $totalCreated++;
                    } catch (\Exception $e) {
                        $this->error("  ✗ Failed to create phone number for branch '{$branch->name}': " . $e->getMessage());
                    }
                }
            }
            
            // Check if company has a main phone that's not assigned to any branch
            if (!empty($company->phone)) {
                $companyPhoneExists = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
                    ->where('number', $company->phone)
                    ->exists();
                    
                if (!$companyPhoneExists) {
                    // This is likely a hotline number
                    $phoneData = [
                        'id' => Str::uuid(),
                        'company_id' => $company->id,
                        'branch_id' => null, // Company-level hotline
                        'number' => $company->phone,
                        'type' => 'hotline',
                        'routing_config' => [
                            'strategy' => 'round_robin',
                            'branches' => $branches->pluck('id')->toArray()
                        ],
                        'agent_id' => $company->default_retell_agent_id ?? null,
                        'active' => true,
                        'description' => "Main hotline for {$company->name}"
                    ];
                    
                    if ($dryRun) {
                        $this->info("  - Would create company hotline: {$phoneData['number']}");
                    } else {
                        try {
                            PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
                                ->create($phoneData);
                            $this->info("  ✓ Created company hotline: {$phoneData['number']}");
                            $totalCreated++;
                        } catch (\Exception $e) {
                            $this->error("  ✗ Failed to create company hotline: " . $e->getMessage());
                        }
                    }
                }
            }
        }
        
        $this->info("\n=== Summary ===");
        $this->info("Total phone numbers created: $totalCreated");
        $this->info("Total phone numbers skipped: $totalSkipped");
        
        if ($dryRun) {
            $this->warn("\nThis was a dry run. Run without --dry-run to actually create the phone numbers.");
        }
        
        // Log the operation
        Log::info('Phone numbers population completed', [
            'created' => $totalCreated,
            'skipped' => $totalSkipped,
            'dry_run' => $dryRun
        ]);
        
        return 0;
    }
}