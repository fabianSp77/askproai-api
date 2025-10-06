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
        
        if (!Schema::hasTable('calls')) {
            return;
        }

        Schema::table('calls', function (Blueprint $table) {
            // Add language detection field
            $table->string('summary_language', 10)->nullable();

            // Add translations cache field (JSON)
            $table->json('summary_translations')->nullable();

            // Add index for language queries
            $table->index('summary_language');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropIndex(['summary_language']);
            $table->dropColumn(['summary_language', 'summary_translations']);
        });
    }
};
