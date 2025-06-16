<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\EncryptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EncryptApiKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'askproai:encrypt-api-keys {--force : Force encryption even if already encrypted}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Encrypt all API keys in the database';

    protected EncryptionService $encryptionService;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->encryptionService = app(EncryptionService::class);
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting API key encryption...');

        $companies = Company::all();
        $encryptedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        $bar = $this->output->createProgressBar($companies->count());
        $bar->start();

        DB::transaction(function () use ($companies, &$encryptedCount, &$skippedCount, &$errorCount, $bar) {
            foreach ($companies as $company) {
                $fieldsToEncrypt = ['calcom_api_key', 'retell_api_key', 'stripe_customer_id', 'stripe_subscription_id'];
                $updated = false;

                foreach ($fieldsToEncrypt as $field) {
                    if (!empty($company->getRawOriginal($field))) {
                        try {
                            $rawValue = $company->getRawOriginal($field);
                            
                            // Check if already encrypted
                            if (!$this->option('force') && $this->encryptionService->isEncrypted($rawValue)) {
                                continue;
                            }

                            // Encrypt the value
                            $encrypted = $this->encryptionService->encrypt($rawValue);
                            DB::table('companies')
                                ->where('id', $company->id)
                                ->update([$field => $encrypted]);
                            
                            $updated = true;
                        } catch (\Exception $e) {
                            $errorCount++;
                            $this->error("\nError encrypting {$field} for company {$company->id}: " . $e->getMessage());
                        }
                    }
                }

                if ($updated) {
                    $encryptedCount++;
                } else {
                    $skippedCount++;
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info("API key encryption completed!");
        $this->info("Encrypted: {$encryptedCount} companies");
        $this->info("Skipped: {$skippedCount} companies (no keys or already encrypted)");
        
        if ($errorCount > 0) {
            $this->warn("Errors: {$errorCount} companies had errors");
        }

        // Clear any cached company data
        $this->call('cache:clear');

        return Command::SUCCESS;
    }
}