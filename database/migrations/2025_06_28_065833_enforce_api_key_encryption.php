<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Security\ApiKeyService;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Encrypt any remaining plain text API keys
        $this->encryptCompanyApiKeys();
        $this->encryptPhoneNumberApiKeys();
        $this->encryptIntegrationApiKeys();
        
        // Add check constraint to ensure only encrypted keys are stored (MySQL 8.0+)
        if (DB::getDriverName() === 'mysql') {
            try {
                // These constraints ensure that API keys either start with 'eyJ' (encrypted) or are NULL
                DB::statement("ALTER TABLE companies ADD CONSTRAINT chk_retell_api_key_encrypted CHECK (retell_api_key IS NULL OR retell_api_key LIKE 'eyJ%')");
                DB::statement("ALTER TABLE companies ADD CONSTRAINT chk_calcom_api_key_encrypted CHECK (calcom_api_key IS NULL OR calcom_api_key LIKE 'eyJ%')");
                
                // Only add stripe constraint if column exists
                $columns = Schema::getColumnListing('companies');
                if (in_array('stripe_api_key', $columns)) {
                    DB::statement("ALTER TABLE companies ADD CONSTRAINT chk_stripe_api_key_encrypted CHECK (stripe_api_key IS NULL OR stripe_api_key LIKE 'eyJ%')");
                }
                
                // Check if phone_numbers table has retell_api_key column
                if (Schema::hasColumn('phone_numbers', 'retell_api_key')) {
                    DB::statement("ALTER TABLE phone_numbers ADD CONSTRAINT chk_retell_agent_api_key_encrypted CHECK (retell_api_key IS NULL OR retell_api_key LIKE 'eyJ%')");
                }
                
                // Check if integrations table exists and has api_key column
                if (Schema::hasTable('integrations') && Schema::hasColumn('integrations', 'api_key')) {
                    DB::statement("ALTER TABLE integrations ADD CONSTRAINT chk_integration_api_key_encrypted CHECK (api_key IS NULL OR api_key LIKE 'eyJ%')");
                }
            } catch (\Exception $e) {
                Log::warning('Could not add check constraints (MySQL 8.0+ required)', [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove check constraints
        if (DB::getDriverName() === 'mysql') {
            try {
                DB::statement("ALTER TABLE companies DROP CONSTRAINT IF EXISTS chk_retell_api_key_encrypted");
                DB::statement("ALTER TABLE companies DROP CONSTRAINT IF EXISTS chk_calcom_api_key_encrypted");
                DB::statement("ALTER TABLE companies DROP CONSTRAINT IF EXISTS chk_stripe_api_key_encrypted");
                
                DB::statement("ALTER TABLE phone_numbers DROP CONSTRAINT IF EXISTS chk_retell_agent_api_key_encrypted");
                
                DB::statement("ALTER TABLE integrations DROP CONSTRAINT IF EXISTS chk_integration_api_key_encrypted");
            } catch (\Exception $e) {
                // Constraints might not exist
            }
        }
    }
    
    /**
     * Encrypt company API keys
     */
    private function encryptCompanyApiKeys(): void
    {
        // Check which columns exist
        $columns = Schema::getColumnListing('companies');
        $hasStripeKey = in_array('stripe_api_key', $columns);
        
        $query = DB::table('companies');
        $query->where(function($q) use ($hasStripeKey) {
            $q->whereNotNull('retell_api_key')
              ->orWhereNotNull('calcom_api_key');
            if ($hasStripeKey) {
                $q->orWhereNotNull('stripe_api_key');
            }
        });
        
        $companies = $query->get();
            
        foreach ($companies as $company) {
            $updates = [];
            
            // Check and encrypt Retell API key
            if ($company->retell_api_key && !ApiKeyService::isEncrypted($company->retell_api_key)) {
                $encrypted = ApiKeyService::encrypt($company->retell_api_key);
                if ($encrypted) {
                    $updates['retell_api_key'] = $encrypted;
                    Log::info('Encrypted Retell API key for company', ['company_id' => $company->id]);
                }
            }
            
            // Check and encrypt Cal.com API key
            if ($company->calcom_api_key && !ApiKeyService::isEncrypted($company->calcom_api_key)) {
                $encrypted = ApiKeyService::encrypt($company->calcom_api_key);
                if ($encrypted) {
                    $updates['calcom_api_key'] = $encrypted;
                    Log::info('Encrypted Cal.com API key for company', ['company_id' => $company->id]);
                }
            }
            
            // Check and encrypt Stripe API key (if column exists)
            if ($hasStripeKey && isset($company->stripe_api_key) && $company->stripe_api_key && !ApiKeyService::isEncrypted($company->stripe_api_key)) {
                $encrypted = ApiKeyService::encrypt($company->stripe_api_key);
                if ($encrypted) {
                    $updates['stripe_api_key'] = $encrypted;
                    Log::info('Encrypted Stripe API key for company', ['company_id' => $company->id]);
                }
            }
            
            if (!empty($updates)) {
                DB::table('companies')
                    ->where('id', $company->id)
                    ->update($updates);
            }
        }
    }
    
    /**
     * Encrypt phone number API keys
     */
    private function encryptPhoneNumberApiKeys(): void
    {
        // Check if table exists and has the column
        if (!Schema::hasTable('phone_numbers') || !Schema::hasColumn('phone_numbers', 'retell_api_key')) {
            return;
        }
        
        $phoneNumbers = DB::table('phone_numbers')
            ->whereNotNull('retell_api_key')
            ->get();
            
        foreach ($phoneNumbers as $phoneNumber) {
            if ($phoneNumber->retell_api_key && !ApiKeyService::isEncrypted($phoneNumber->retell_api_key)) {
                $encrypted = ApiKeyService::encrypt($phoneNumber->retell_api_key);
                if ($encrypted) {
                    DB::table('phone_numbers')
                        ->where('id', $phoneNumber->id)
                        ->update(['retell_api_key' => $encrypted]);
                    
                    Log::info('Encrypted API key for phone number', ['phone_number_id' => $phoneNumber->id]);
                }
            }
        }
    }
    
    /**
     * Encrypt integration API keys
     */
    private function encryptIntegrationApiKeys(): void
    {
        // Check if table exists and has the column
        if (!Schema::hasTable('integrations') || !Schema::hasColumn('integrations', 'api_key')) {
            return;
        }
        
        $integrations = DB::table('integrations')
            ->whereNotNull('api_key')
            ->get();
            
        foreach ($integrations as $integration) {
            if ($integration->api_key && !ApiKeyService::isEncrypted($integration->api_key)) {
                $encrypted = ApiKeyService::encrypt($integration->api_key);
                if ($encrypted) {
                    DB::table('integrations')
                        ->where('id', $integration->id)
                        ->update(['api_key' => $encrypted]);
                    
                    Log::info('Encrypted API key for integration', [
                        'integration_id' => $integration->id,
                        'type' => $integration->type ?? 'unknown'
                    ]);
                }
            }
        }
    }
};