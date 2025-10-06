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
        if (!Schema::hasTable('appointments')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table) {
            // Composite support fields
            if (!Schema::hasColumn('appointments', 'is_composite')) {
                $table->boolean('is_composite')->default(false)
                    ->comment('Whether this is a composite appointment with segments');
            }

            if (!Schema::hasColumn('appointments', 'composite_group_uid')) {
                $table->uuid('composite_group_uid')->nullable()
                    ->comment('Groups all segments of one composite booking');
            }

            if (!Schema::hasColumn('appointments', 'segments')) {
                $table->json('segments')->nullable()
                    ->comment('[{index,key,staff_id,booking_id,starts_at,ends_at,status}]');
            }

            // Only rename if old columns exist and new ones don't
            if (Schema::hasColumn('appointments', 'start_time') && !Schema::hasColumn('appointments', 'starts_at')) {
                $table->renameColumn('start_time', 'starts_at');
            }

            if (Schema::hasColumn('appointments', 'end_time') && !Schema::hasColumn('appointments', 'ends_at')) {
                $table->renameColumn('end_time', 'ends_at');
            }

            // Add missing columns if they don't exist at all
            if (!Schema::hasColumn('appointments', 'starts_at') && !Schema::hasColumn('appointments', 'start_time')) {
                $table->timestamp('starts_at')->nullable();
            }

            if (!Schema::hasColumn('appointments', 'ends_at') && !Schema::hasColumn('appointments', 'end_time')) {
                $table->timestamp('ends_at')->nullable();
            }

            // Add indexes for performance
            if (!Schema::hasIndex('appointments', 'appointments_composite_group_uid_index')) {
                $table->index('composite_group_uid');
            }

            if (!Schema::hasIndex('appointments', 'appointments_is_composite_status_index')) {
                $table->index(['is_composite', 'status']);
            }

            if (!Schema::hasIndex('appointments', 'appointments_starts_at_ends_at_index')) {
                $table->index(['starts_at', 'ends_at']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Drop indexes first
            if (Schema::hasIndex('appointments', 'appointments_composite_group_uid_index')) {
                $table->dropIndex(['composite_group_uid']);
            }

            if (Schema::hasIndex('appointments', 'appointments_is_composite_status_index')) {
                $table->dropIndex(['is_composite', 'status']);
            }

            if (Schema::hasIndex('appointments', 'appointments_starts_at_ends_at_index')) {
                $table->dropIndex(['starts_at', 'ends_at']);
            }

            // Rename columns back if they were renamed
            if (Schema::hasColumn('appointments', 'starts_at') && !Schema::hasColumn('appointments', 'start_time')) {
                $table->renameColumn('starts_at', 'start_time');
            }

            if (Schema::hasColumn('appointments', 'ends_at') && !Schema::hasColumn('appointments', 'end_time')) {
                $table->renameColumn('ends_at', 'end_time');
            }

            // Drop composite fields
            $columnsToRemove = ['is_composite', 'composite_group_uid', 'segments'];

            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('appointments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};