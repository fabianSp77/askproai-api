<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Company;
use App\Models\RetellConfiguration;
use App\Services\Security\ApiKeyEncryptionService;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Increase column size for encrypted values (they're longer than plain text)
        Schema::table('companies', function (Blueprint $table) {
            $table->text('calcom_api_key')->nullable()->change();
            $table->text('retell_api_key')->nullable()->change();
        });
        
        if (Schema::hasTable('retell_configurations')) {
            Schema::table('retell_configurations', function (Blueprint $table) {
                $table->text('webhook_secret')->nullable()->change();
            });
        }
        
        // Encrypt existing keys
        $encryptionService = app(ApiKeyEncryptionService::class);
        
        // Encrypt company API keys
        Company::whereNotNull('calcom_api_key')
            ->orWhereNotNull('retell_api_key')
            ->chunk(100, function ($companies) use ($encryptionService) {
                foreach ($companies as $company) {
                    $updated = false;
                    
                    // Use raw attributes to avoid accessor/mutator
                    if (!empty($company->attributes['calcom_api_key']) && !$encryptionService->isEncrypted($company->attributes['calcom_api_key'])) {
                        $company->attributes['calcom_api_key'] = $encryptionService->encrypt($company->attributes['calcom_api_key']);
                        $updated = true;
                    }
                    
                    if (!empty($company->attributes['retell_api_key']) && !$encryptionService->isEncrypted($company->attributes['retell_api_key'])) {
                        $company->attributes['retell_api_key'] = $encryptionService->encrypt($company->attributes['retell_api_key']);
                        $updated = true;
                    }
                    
                    if ($updated) {
                        // Save without triggering mutators
                        DB::table('companies')
                            ->where('id', $company->id)
                            ->update([
                                'calcom_api_key' => $company->attributes['calcom_api_key'],
                                'retell_api_key' => $company->attributes['retell_api_key'],
                                'updated_at' => now()
                            ]);
                    }
                }
            });
        
        // Encrypt Retell webhook secrets
        if (Schema::hasTable('retell_configurations')) {
            DB::table('retell_configurations')
                ->whereNotNull('webhook_secret')
                ->get()
                ->each(function ($config) use ($encryptionService) {
                    if (!$encryptionService->isEncrypted($config->webhook_secret)) {
                        DB::table('retell_configurations')
                            ->where('id', $config->id)
                            ->update([
                                'webhook_secret' => $encryptionService->encrypt($config->webhook_secret),
                                'updated_at' => now()
                            ]);
                    }
                });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // WARNING: This will decrypt all keys back to plain text
        // Only use this if absolutely necessary
        
        $encryptionService = app(ApiKeyEncryptionService::class);
        
        // Decrypt company API keys
        Company::whereNotNull('calcom_api_key')
            ->orWhereNotNull('retell_api_key')
            ->chunk(100, function ($companies) use ($encryptionService) {
                foreach ($companies as $company) {
                    $updated = false;
                    
                    if (!empty($company->attributes['calcom_api_key']) && $encryptionService->isEncrypted($company->attributes['calcom_api_key'])) {
                        try {
                            $decrypted = $encryptionService->decrypt($company->attributes['calcom_api_key']);
                            DB::table('companies')
                                ->where('id', $company->id)
                                ->update(['calcom_api_key' => $decrypted]);
                        } catch (\Exception $e) {
                            // Skip if decryption fails
                        }
                    }
                    
                    if (!empty($company->attributes['retell_api_key']) && $encryptionService->isEncrypted($company->attributes['retell_api_key'])) {
                        try {
                            $decrypted = $encryptionService->decrypt($company->attributes['retell_api_key']);
                            DB::table('companies')
                                ->where('id', $company->id)
                                ->update(['retell_api_key' => $decrypted]);
                        } catch (\Exception $e) {
                            // Skip if decryption fails
                        }
                    }
                }
            });
        
        // Revert column types
        Schema::table('companies', function (Blueprint $table) {
            $table->string('calcom_api_key', 255)->nullable()->change();
            $table->string('retell_api_key', 255)->nullable()->change();
        });
    }
};