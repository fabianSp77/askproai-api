<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\Service;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;

class TestTenantIsolation extends Command
{
    protected $signature = 'tenant:test-isolation {--fix : Attempt to fix issues}';
    protected $description = 'Test tenant isolation and data leakage';
    
    public function handle()
    {
        $this->info('Testing tenant isolation...');
        
        // Get all companies
        $companies = Company::all();
        
        if ($companies->count() < 2) {
            $this->warn('Need at least 2 companies to test isolation. Creating test companies...');
            $this->createTestCompanies();
            $companies = Company::all();
        }
        
        $issues = [];
        
        // Test each model
        foreach ($this->getModelsToTest() as $modelClass => $modelName) {
            $this->line("\nTesting $modelName...");
            $modelIssues = $this->testModel($modelClass, $companies);
            
            if (count($modelIssues) > 0) {
                $issues[$modelName] = $modelIssues;
                $this->error("❌ $modelName has isolation issues!");
            } else {
                $this->info("✅ $modelName is properly isolated");
            }
        }
        
        // Summary
        $this->displaySummary($issues);
        
        // Fix issues if requested
        if ($this->option('fix') && count($issues) > 0) {
            $this->fixIssues($issues);
        }
        
        return count($issues) > 0 ? 1 : 0;
    }
    
    protected function getModelsToTest(): array
    {
        return [
            Customer::class => 'Customers',
            Appointment::class => 'Appointments',
            Call::class => 'Calls',
            Service::class => 'Services',
            Branch::class => 'Branches',
        ];
    }
    
    protected function testModel(string $modelClass, $companies)
    {
        $issues = [];
        
        foreach ($companies as $company) {
            // Set tenant context
            app()->instance('current_company_id', $company->id);
            
            // Get records for this company
            $records = $modelClass::all();
            
            // Check if any record belongs to a different company
            foreach ($records as $record) {
                if ($record->company_id != $company->id) {
                    $issues[] = [
                        'record_id' => $record->id,
                        'expected_company' => $company->id,
                        'actual_company' => $record->company_id,
                        'context' => "When context is Company #{$company->id}",
                    ];
                }
            }
            
            // Clear the scope cache
            $modelClass::flushEventListeners();
        }
        
        // Test without company context (should return nothing for regular users)
        app()->forgetInstance('current_company_id');
        $recordsWithoutContext = $modelClass::all();
        
        if ($recordsWithoutContext->count() > 0) {
            $issues[] = [
                'type' => 'no_context',
                'count' => $recordsWithoutContext->count(),
                'message' => 'Records accessible without company context!',
            ];
        }
        
        return $issues;
    }
    
    protected function createTestCompanies()
    {
        DB::transaction(function () {
            // Create test companies if they don't exist
            Company::firstOrCreate(
                ['slug' => 'test-company-1'],
                [
                    'name' => 'Test Company 1',
                    'is_active' => true,
                ]
            );
            
            Company::firstOrCreate(
                ['slug' => 'test-company-2'],
                [
                    'name' => 'Test Company 2',
                    'is_active' => true,
                ]
            );
        });
    }
    
    protected function displaySummary($issues)
    {
        $this->line("\n" . str_repeat('=', 60));
        $this->line("TENANT ISOLATION TEST SUMMARY");
        $this->line(str_repeat('=', 60));
        
        if (count($issues) == 0) {
            $this->info("✅ All models are properly isolated!");
            $this->info("No tenant data leakage detected.");
        } else {
            $this->error("❌ CRITICAL: Tenant isolation issues detected!");
            $this->error("Found issues in " . count($issues) . " models:");
            
            foreach ($issues as $model => $modelIssues) {
                $this->line("\n$model:");
                foreach ($modelIssues as $issue) {
                    if (isset($issue['type']) && $issue['type'] == 'no_context') {
                        $this->line("  - {$issue['message']} ({$issue['count']} records)");
                    } else {
                        $this->line("  - Record #{$issue['record_id']} accessible from wrong company");
                    }
                }
            }
            
            $this->line("\n" . str_repeat('=', 60));
            $this->error("⚠️  This is a CRITICAL security issue!");
            $this->error("⚠️  Customer data is leaking between companies!");
            $this->line(str_repeat('=', 60));
        }
    }
    
    protected function fixIssues($issues)
    {
        $this->line("\nAttempting to fix issues...");
        
        foreach ($issues as $model => $modelIssues) {
            $this->warn("Fixing $model...");
            
            // Add recommendations based on the model
            $this->line("Recommended fixes:");
            $this->line("1. Ensure model extends TenantModel or uses HasTenantScope trait");
            $this->line("2. Check for any custom scopes that might override tenant filtering");
            $this->line("3. Verify middleware is applied to all routes");
        }
        
        $this->info("\nManual intervention required. Please review the recommendations above.");
    }
}