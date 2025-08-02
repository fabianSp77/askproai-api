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
        // Only run if staff table exists
        if (Schema::hasTable('staff')) {
            Schema::table('staff', function (Blueprint $table) {
                // Add is_active column if it doesn't exist
                if (!Schema::hasColumn('staff', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                    $table->index('is_active');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('staff') && Schema::hasColumn('staff', 'is_active')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->dropIndex(['is_active']);
                $table->dropColumn('is_active');
            });
        }
    }
};