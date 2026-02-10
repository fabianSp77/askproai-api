<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * H1: Add missing columns used in Customer model $casts array
     * - phone_variants (line 65): array cast but no column
     * - journey_history (line 66): array cast but no column
     * - booking_history_summary (line 72): array cast but no column
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'phone_variants')) {
                $table->json('phone_variants')->nullable()->after('phone');
            }

            if (!Schema::hasColumn('customers', 'journey_history')) {
                $table->json('journey_history')->nullable()->after('journey_status_updated_at');
            }

            if (!Schema::hasColumn('customers', 'booking_history_summary')) {
                $table->json('booking_history_summary')->nullable()->after('journey_history');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'booking_history_summary')) {
                $table->dropColumn('booking_history_summary');
            }

            if (Schema::hasColumn('customers', 'journey_history')) {
                $table->dropColumn('journey_history');
            }

            if (Schema::hasColumn('customers', 'phone_variants')) {
                $table->dropColumn('phone_variants');
            }
        });
    }
};
