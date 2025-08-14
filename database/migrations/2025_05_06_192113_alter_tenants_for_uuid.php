<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For CI/testing environments with fresh databases, we can be more aggressive
        // In production, this would need more careful handling with data migration
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE tenants DROP PRIMARY KEY');
            DB::statement('ALTER TABLE tenants MODIFY id CHAR(36) PRIMARY KEY');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Revert to BIGINT id
            $table->dropPrimary(['id']);
            $table->id()->change();
        });
    }
};
