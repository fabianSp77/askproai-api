<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Services\Security\ApiKeyService;
use App\Models\Branch;
use App\Models\CustomerAuth;
use App\Models\RetellConfiguration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $apiKeyService = app(ApiKeyService::class);

        // 1. Fix RetellConfiguration webhook_secret
        if (Schema::hasTable('retell_configurations')) {
            Schema::table('retell_configurations', function (Blueprint $table) {
                if (Schema::hasColumn('retell_configurations', 'webhook_secret')) {
                    $table->text('webhook_secret')->nullable()->change();
                }
            });

            DB::table('retell_configurations')
                ->whereNotNull('webhook_secret')
                ->where('webhook_secret', '!=', '')
                ->orderBy('id')
                ->chunk(100, function ($configs) use ($apiKeyService) {
                    foreach ($configs as $config) {
                        if (!str_starts_with($config->webhook_secret, 'eyJ')) {
                            DB::table('retell_configurations')
                                ->where('id', $config->id)
                                ->update([
                                    'webhook_secret' => $apiKeyService->encrypt($config->webhook_secret)
                                ]);
                        }
                    }
                });
        }

        // 2. Fix Branch calcom_api_key (if used independently)
        if (Schema::hasTable('branches') && Schema::hasColumn('branches', 'calcom_api_key')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->text('calcom_api_key')->nullable()->change();
            });

            DB::table('branches')
                ->whereNotNull('calcom_api_key')
                ->where('calcom_api_key', '!=', '')
                ->orderBy('id')
                ->chunk(100, function ($branches) use ($apiKeyService) {
                    foreach ($branches as $branch) {
                        if (!str_starts_with($branch->calcom_api_key, 'eyJ')) {
                            DB::table('branches')
                                ->where('id', $branch->id)
                                ->update([
                                    'calcom_api_key' => $apiKeyService->encrypt($branch->calcom_api_key)
                                ]);
                        }
                    }
                });
        }

        // 3. Fix CustomerAuth portal_access_token
        if (Schema::hasTable('customer_auth') && Schema::hasColumn('customer_auth', 'portal_access_token')) {
            Schema::table('customer_auth', function (Blueprint $table) {
                $table->text('portal_access_token')->nullable()->change();
            });

            DB::table('customer_auth')
                ->whereNotNull('portal_access_token')
                ->where('portal_access_token', '!=', '')
                ->orderBy('id')
                ->chunk(100, function ($auths) use ($apiKeyService) {
                    foreach ($auths as $auth) {
                        if (!str_starts_with($auth->portal_access_token, 'eyJ')) {
                            DB::table('customer_auth')
                                ->where('id', $auth->id)
                                ->update([
                                    'portal_access_token' => $apiKeyService->encrypt($auth->portal_access_token)
                                ]);
                        }
                    }
                });
        }

        // 4. Add encrypted column for User two_factor_secret if needed
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'two_factor_secret')) {
            // Laravel Fortify already handles this encryption, so we just ensure the column is text
            Schema::table('users', function (Blueprint $table) {
                $table->text('two_factor_secret')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $apiKeyService = app(ApiKeyService::class);

        // WARNING: This will decrypt sensitive data back to plaintext
        // Only use in development/testing

        // 1. Decrypt RetellConfiguration webhook_secret
        if (Schema::hasTable('retell_configurations')) {
            DB::table('retell_configurations')
                ->whereNotNull('webhook_secret')
                ->where('webhook_secret', 'LIKE', 'eyJ%')
                ->orderBy('id')
                ->chunk(100, function ($configs) use ($apiKeyService) {
                    foreach ($configs as $config) {
                        try {
                            DB::table('retell_configurations')
                                ->where('id', $config->id)
                                ->update([
                                    'webhook_secret' => $apiKeyService->decrypt($config->webhook_secret)
                                ]);
                        } catch (\Exception $e) {
                            \Log::error('Failed to decrypt webhook_secret', ['id' => $config->id]);
                        }
                    }
                });

            Schema::table('retell_configurations', function (Blueprint $table) {
                $table->string('webhook_secret', 255)->change();
            });
        }

        // 2. Decrypt Branch calcom_api_key
        if (Schema::hasTable('branches') && Schema::hasColumn('branches', 'calcom_api_key')) {
            DB::table('branches')
                ->whereNotNull('calcom_api_key')
                ->where('calcom_api_key', 'LIKE', 'eyJ%')
                ->orderBy('id')
                ->chunk(100, function ($branches) use ($apiKeyService) {
                    foreach ($branches as $branch) {
                        try {
                            DB::table('branches')
                                ->where('id', $branch->id)
                                ->update([
                                    'calcom_api_key' => $apiKeyService->decrypt($branch->calcom_api_key)
                                ]);
                        } catch (\Exception $e) {
                            \Log::error('Failed to decrypt branch calcom_api_key', ['id' => $branch->id]);
                        }
                    }
                });

            Schema::table('branches', function (Blueprint $table) {
                $table->string('calcom_api_key', 255)->nullable()->change();
            });
        }

        // 3. Decrypt CustomerAuth portal_access_token
        if (Schema::hasTable('customer_auth') && Schema::hasColumn('customer_auth', 'portal_access_token')) {
            DB::table('customer_auth')
                ->whereNotNull('portal_access_token')
                ->where('portal_access_token', 'LIKE', 'eyJ%')
                ->orderBy('id')
                ->chunk(100, function ($auths) use ($apiKeyService) {
                    foreach ($auths as $auth) {
                        try {
                            DB::table('customer_auth')
                                ->where('id', $auth->id)
                                ->update([
                                    'portal_access_token' => $apiKeyService->decrypt($auth->portal_access_token)
                                ]);
                        } catch (\Exception $e) {
                            \Log::error('Failed to decrypt portal_access_token', ['id' => $auth->id]);
                        }
                    }
                });

            Schema::table('customer_auth', function (Blueprint $table) {
                $table->string('portal_access_token', 255)->nullable()->change();
            });
        }
    }
};