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
        Schema::table('services', function (Blueprint $table) {
            // Add priority column only if it doesn't exist (idempotency check)
            if (!Schema::hasColumn('services', 'priority')) {
                // Add priority column (lower number = higher priority)
                // Default 999 = lowest priority
                $table->integer('priority')->default(999)->after('is_default');
            }

            // Check if index already exists before adding
            $indexes = DB::select("
                SHOW INDEX FROM services WHERE Key_name = 'services_priority_index'
            ");

            if (empty($indexes)) {
                $table->index('priority'); // Index for ORDER BY queries
            }
        });

        // Set priority for existing services (only if they still have default priority)
        // Default services get priority 1, others keep 999
        DB::table('services')
            ->where('is_default', true)
            ->where('priority', 999) // Only update services still at default value
            ->update(['priority' => 1]);

        // Log migration
        \Illuminate\Support\Facades\Log::info('Services priority column added', [
            'default_services_updated' => DB::table('services')->where('is_default', true)->where('priority', 1)->count()
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Check if index exists before dropping
            $indexes = DB::select("
                SHOW INDEX FROM services WHERE Key_name = 'services_priority_index'
            ");

            if (!empty($indexes)) {
                $table->dropIndex(['priority']);
            }

            // Check if column exists before dropping
            if (Schema::hasColumn('services', 'priority')) {
                $table->dropColumn('priority');
            }
        });
    }
};
