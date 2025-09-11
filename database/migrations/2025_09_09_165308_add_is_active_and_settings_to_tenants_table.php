<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Add is_active column - default to true for existing tenants
            $table->boolean('is_active')->default(true)->after('email');
            
            // Add settings column for JSON configuration data
            $table->json('settings')->nullable()->after('is_active');
            
            // Add index for performance on is_active queries
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropColumn(['is_active', 'settings']);
        });
    }
};