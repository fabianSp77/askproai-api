<?php

namespace App\Console\Commands;

use App\Services\CacheWarmer;
use Illuminate\Console\Command;

class WarmInvoiceCaches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:warm-invoices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm invoice-related caches to improve performance';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Warming invoice caches...');
        
        $warmer = new CacheWarmer();
        $warmer->warmInvoiceCaches();
        
        $this->info('Invoice caches warmed successfully!');
        
        return Command::SUCCESS;
    }
}