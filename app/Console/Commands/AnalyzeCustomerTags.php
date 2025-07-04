<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CustomerTaggingService;

class AnalyzeCustomerTags extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customers:analyze-tags {--customer=* : Specific customer IDs to analyze}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze customers and automatically apply tags based on their behavior';

    /**
     * Execute the console command.
     */
    public function handle(CustomerTaggingService $taggingService): int
    {
        $this->info('Starting customer tag analysis...');
        
        $customerIds = $this->option('customer');
        
        if (!empty($customerIds)) {
            // Analyze specific customers
            $customers = \App\Models\Customer::whereIn('id', $customerIds)->get();
            
            $bar = $this->output->createProgressBar($customers->count());
            $bar->start();
            
            foreach ($customers as $customer) {
                $taggingService->analyzeAndTag($customer);
                $bar->advance();
            }
            
            $bar->finish();
            $this->newLine();
            $this->info("Analyzed {$customers->count()} specific customers.");
        } else {
            // Analyze all customers
            $totalCustomers = \App\Models\Customer::count();
            $this->info("Analyzing {$totalCustomers} customers...");
            
            $taggingService->analyzeAllCustomers();
            
            $this->info('Customer tag analysis completed!');
        }
        
        // Show statistics
        $this->newLine();
        $this->table(
            ['Tag', 'Count'],
            \App\Models\Customer::selectRaw('JSON_UNQUOTE(JSON_EXTRACT(tags, "$[*]")) as tag')
                ->whereNotNull('tags')
                ->get()
                ->pluck('tag')
                ->flatten()
                ->countBy()
                ->map(fn ($count, $tag) => [$tag, $count])
                ->sortByDesc(fn ($item) => $item[1])
                ->values()
                ->toArray()
        );
        
        return Command::SUCCESS;
    }
}