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
        
        if (!Schema::hasTable('services')) {
            return;
        }

        Schema::table('services', function (Blueprint $table) {
            // Add display_name for custom platform display names
            if (!Schema::hasColumn('services', 'display_name')) {
                $table->string('display_name')->nullable()
                    ->comment('Optional custom display name for the platform, if null uses name from cal.com');
            }

            // Add calcom_name to store the original cal.com name
            if (!Schema::hasColumn('services', 'calcom_name')) {
                $table->string('calcom_name')->nullable()
                    ->comment('Original name from cal.com Event Type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['display_name', 'calcom_name']);
        });
    }
};