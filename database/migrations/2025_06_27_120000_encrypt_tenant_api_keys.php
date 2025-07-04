<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Services\Security\ApiKeyService;
use App\Models\Tenant;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, change the column type to accommodate encrypted values
        Schema::table('tenants', function (Blueprint $table) {
            // Change api_key from varchar(255) to text (nullable)
            $table->text('api_key')->nullable()->change();
        });

        // Encrypt existing API keys
        $apiKeyService = app(ApiKeyService::class);
        
        Tenant::withoutGlobalScopes()->chunk(100, function ($tenants) use ($apiKeyService) {
            foreach ($tenants as $tenant) {
                if (!empty($tenant->api_key)) {
                    // Check if already encrypted (starts with 'eyJ')
                    if (!str_starts_with($tenant->api_key, 'eyJ')) {
                        $plainKey = $tenant->api_key;
                        $encryptedKey = $apiKeyService->encrypt($plainKey);
                        
                        // Direct DB update to avoid model events
                        DB::table('tenants')
                            ->where('id', $tenant->id)
                            ->update(['api_key' => $encryptedKey]);
                            
                        echo "Encrypted API key for tenant: {$tenant->name}\n";
                    }
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // DANGER: This will decrypt API keys back to plaintext
        // Only use in development/testing
        
        $apiKeyService = app(ApiKeyService::class);
        
        Tenant::withoutGlobalScopes()->chunk(100, function ($tenants) use ($apiKeyService) {
            foreach ($tenants as $tenant) {
                if (!empty($tenant->api_key)) {
                    // Check if encrypted (starts with 'eyJ')
                    if (str_starts_with($tenant->api_key, 'eyJ')) {
                        try {
                            $decryptedKey = $apiKeyService->decrypt($tenant->api_key);
                            
                            // Direct DB update to avoid model events
                            DB::table('tenants')
                                ->where('id', $tenant->id)
                                ->update(['api_key' => $decryptedKey]);
                                
                            echo "Decrypted API key for tenant: {$tenant->name}\n";
                        } catch (\Exception $e) {
                            echo "Failed to decrypt API key for tenant: {$tenant->name}\n";
                        }
                    }
                }
            }
        });
        
        // Change back to varchar
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('api_key', 255)->change();
        });
    }
};