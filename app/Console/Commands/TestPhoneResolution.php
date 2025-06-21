<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PhoneNumberResolver;
use App\Models\PhoneNumber;
use App\Models\Branch;

class TestPhoneResolution extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'phone:test-resolution 
                            {phone : Phone number to test}
                            {--agent= : Optional agent ID to include in test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test phone number resolution for multi-tenancy';

    private PhoneNumberResolver $resolver;

    public function __construct(PhoneNumberResolver $resolver)
    {
        parent::__construct();
        $this->resolver = $resolver;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $phone = $this->argument('phone');
        $agentId = $this->option('agent');
        
        $this->info("Testing phone resolution for: $phone");
        
        // Simulate webhook data
        $webhookData = [
            'to_number' => $phone,
            'to' => $phone,
            'agent_id' => $agentId
        ];
        
        // Test resolution
        $result = $this->resolver->resolveFromWebhook($webhookData);
        
        $this->info("\n=== Resolution Result ===");
        $this->table(
            ['Field', 'Value'],
            [
                ['Branch ID', $result['branch_id'] ?? 'NULL'],
                ['Company ID', $result['company_id'] ?? 'NULL'],
                ['Agent ID', $result['agent_id'] ?? 'NULL'],
                ['Method', $result['resolution_method'] ?? 'unknown'],
                ['Confidence', $result['confidence'] ?? '0'],
            ]
        );
        
        // Show details if branch was found
        if ($result['branch_id']) {
            $branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->find($result['branch_id']);
                
            if ($branch) {
                $this->info("\n=== Branch Details ===");
                $this->table(
                    ['Field', 'Value'],
                    [
                        ['Name', $branch->name],
                        ['Phone', $branch->phone],
                        ['Active', $branch->is_active ? 'Yes' : 'No'],
                        ['Company', $branch->company->name ?? 'N/A'],
                    ]
                );
            }
        }
        
        // Check phone_numbers table
        $this->info("\n=== Phone Numbers Table Check ===");
        $phoneRecords = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('number', 'LIKE', '%' . substr($phone, -10) . '%')
            ->get();
            
        if ($phoneRecords->isEmpty()) {
            $this->warn("No matching records found in phone_numbers table");
        } else {
            $this->info("Found " . $phoneRecords->count() . " matching record(s):");
            foreach ($phoneRecords as $record) {
                $this->line("  - {$record->number} (Type: {$record->type}, Branch: {$record->branch_id}, Active: " . ($record->active ? 'Yes' : 'No') . ")");
            }
        }
        
        return 0;
    }
}