<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use App\Models\Tenant;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Add new hashed API key column
            $table->string('api_key_hash')->nullable()->after('api_key');
        });

        // Migrate existing plain API keys to hashed format
        if (Schema::hasColumn('tenants', 'api_key')) {
            Tenant::whereNotNull('api_key')->chunk(100, function ($tenants) {
                foreach ($tenants as $tenant) {
                    if ($tenant->api_key && empty($tenant->api_key_hash)) {
                        $tenant->api_key_hash = Hash::make($tenant->api_key);
                        $tenant->save();
                    }
                }
            });

            // Remove old plain text api_key column after migration
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropColumn('api_key');
            });
        }
    }

    public function down(): void
    {
        // Note: Cannot restore plain text keys from hashes
        // This migration is irreversible for security reasons
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('api_key')->nullable();
            $table->dropColumn('api_key_hash');
        });
    }
};