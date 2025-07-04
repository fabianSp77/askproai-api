<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Company;
use App\Models\PhoneNumber;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreatePhoneNumber extends Command
{
    protected $signature = 'phone:create 
                            {number : The phone number to create}
                            {--branch= : The branch ID to assign}
                            {--company= : The company ID (will use first branch)}
                            {--agent= : The Retell agent ID}
                            {--type=main : The phone type (main/support/sales/mobile/test)}
                            {--primary : Set as primary number}';

    protected $description = 'Create a phone number and link it to a branch';

    public function handle()
    {
        $number = $this->argument('number');
        $branchId = $this->option('branch');
        $companyId = $this->option('company');
        $agentId = $this->option('agent');
        $type = $this->option('type');
        $isPrimary = $this->option('primary');
        
        // Resolve branch
        $branch = null;
        if ($branchId) {
            $branch = Branch::find($branchId);
            if (!$branch) {
                $this->error("Branch with ID {$branchId} not found!");
                return Command::FAILURE;
            }
        } elseif ($companyId) {
            $company = Company::find($companyId);
            if (!$company) {
                $this->error("Company with ID {$companyId} not found!");
                return Command::FAILURE;
            }
            $branch = $company->branches()->where('is_active', true)->first();
            if (!$branch) {
                $this->error("No active branches found for company!");
                return Command::FAILURE;
            }
        } else {
            // Use first active branch
            $branch = Branch::where('is_active', true)->first();
            if (!$branch) {
                $this->error("No active branches found in the system!");
                return Command::FAILURE;
            }
        }
        
        // Check if phone number already exists
        $existing = PhoneNumber::where('number', $number)->first();
        if ($existing) {
            $this->warn("Phone number already exists!");
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $existing->id],
                    ['Number', $existing->number],
                    ['Branch', $existing->branch->name ?? 'N/A'],
                    ['Active', $existing->is_active ? 'Yes' : 'No'],
                    ['Agent ID', $existing->retell_agent_id ?? 'N/A'],
                ]
            );
            
            if ($this->confirm('Do you want to update this phone number?')) {
                $existing->update([
                    'branch_id' => $branch->id,
                    'company_id' => $branch->company_id,
                    'retell_agent_id' => $agentId ?: $existing->retell_agent_id,
                    'type' => $type,
                    'is_primary' => $isPrimary,
                    'is_active' => true,
                ]);
                $this->info("Phone number updated successfully!");
            }
            return Command::SUCCESS;
        }
        
        // Create new phone number
        try {
            $phoneNumber = PhoneNumber::create([
                'id' => Str::uuid(),
                'number' => $number,
                'branch_id' => $branch->id,
                'company_id' => $branch->company_id,
                'retell_agent_id' => $agentId ?: $branch->retell_agent_id,
                'type' => $type,
                'is_primary' => $isPrimary,
                'is_active' => true,
            ]);
            
            $this->info("Phone number created successfully!");
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $phoneNumber->id],
                    ['Number', $phoneNumber->number],
                    ['Branch', $branch->name],
                    ['Company', $branch->company->name ?? 'N/A'],
                    ['Type', $phoneNumber->type],
                    ['Primary', $phoneNumber->is_primary ? 'Yes' : 'No'],
                    ['Agent ID', $phoneNumber->retell_agent_id ?? 'N/A'],
                ]
            );
            
            // Test resolution
            if ($this->confirm('Do you want to test phone resolution now?')) {
                $this->call('phone:test-resolution', ['phone' => $number]);
            }
            
        } catch (\Exception $e) {
            $this->error("Failed to create phone number: " . $e->getMessage());
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
}