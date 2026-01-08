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
            // Add priority column (lower number = higher priority)
            // Default 999 = lowest priority
            if (!Schema::hasColumn('services', 'priority')) {
                $table->integer('priority')->default(999)->after('is_default');
                $table->index('priority'); // Index for ORDER BY queries
            }
        });

        // Set priority for existing services
        // Default services get priority 1, others keep 999
        DB::table('services')
            ->where('is_default', true)
            ->update(['priority' => 1]);

        // Log migration
        \Illuminate\Support\Facades\Log::info('Services priority column added', [
            'default_services_updated' => DB::table('services')->where('is_default', true)->count()
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex(['priority']);
            $table->dropColumn('priority');
        });
    }
};
