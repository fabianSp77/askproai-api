<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\User;
use App\Models\Call;
use App\Models\RetellAgent;
use App\Models\AgentAssignment;
use App\Models\RetellAICallCampaign;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RetellAIMCPDemoCleanupSeeder extends Seeder
{
    /**
     * Cleanup demo data from the database.
     */
    public function run(): void
    {
        $this->command->info('🧹 Cleaning up Retell AI MCP Demo Data...');
        
        // Define demo company slugs
        $demoSlugs = [
            'demo-hausarzt-schmidt',
            'demo-beauty-lounge',
            'demo-kanzlei-mueller',
        ];
        
        // Get demo companies
        $demoCompanies = Company::whereIn('slug', $demoSlugs)->get();
        
        if ($demoCompanies->isEmpty()) {
            $this->command->warn('No demo data found to clean up.');
            return;
        }
        
        DB::transaction(function () use ($demoCompanies) {
            foreach ($demoCompanies as $company) {
                $this->command->info("Cleaning up company: {$company->name}");
                
                // Delete related data in reverse order of dependencies
                
                // Delete calls
                Call::where('company_id', $company->id)->delete();
                $this->command->line('  ✓ Calls deleted');
                
                // Delete campaigns
                RetellAICallCampaign::where('company_id', $company->id)->delete();
                $this->command->line('  ✓ Campaigns deleted');
                
                // Delete agent assignments
                if (Schema::hasTable('agent_assignments')) {
                    AgentAssignment::where('company_id', $company->id)->delete();
                    $this->command->line('  ✓ Agent assignments deleted');
                }
                
                // Delete agents
                RetellAgent::where('company_id', $company->id)->delete();
                $this->command->line('  ✓ Agents deleted');
                
                // Delete customers
                DB::table('customers')->where('company_id', $company->id)->delete();
                $this->command->line('  ✓ Customers deleted');
                
                // Delete staff
                DB::table('staff')->where('company_id', $company->id)->delete();
                $this->command->line('  ✓ Staff deleted');
                
                // Delete services
                DB::table('services')->where('company_id', $company->id)->delete();
                $this->command->line('  ✓ Services deleted');
                
                // Delete branches
                DB::table('branches')->where('company_id', $company->id)->delete();
                $this->command->line('  ✓ Branches deleted');
                
                // Delete users
                User::where('company_id', $company->id)
                    ->where('email', 'LIKE', '%@%.demo')
                    ->delete();
                $this->command->line('  ✓ Demo users deleted');
                
                // Finally force delete the company (bypass soft deletes)
                $company->forceDelete();
                $this->command->line('  ✓ Company deleted');
            }
        });
        
        $this->command->info('✅ Demo data cleaned up successfully!');
    }
}