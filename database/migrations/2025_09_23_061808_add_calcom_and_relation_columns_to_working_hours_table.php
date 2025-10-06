<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        
        if (!Schema::hasTable('working_hours')) {
            return;
        }

        Schema::table('working_hours', function (Blueprint $table) {
            // Add direct company and branch relationships for query optimization
            $table->unsignedBigInteger('company_id')->nullable();
            $table->uuid('branch_id')->nullable();

            // Cal.com integration columns
            $table->string('calcom_availability_id')->nullable();
            $table->string('calcom_schedule_id')->nullable();
            $table->timestamp('external_sync_at')->nullable();

            // Additional metadata for better functionality
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('timezone')->default('Europe/Berlin');
            $table->boolean('is_recurring')->default(true);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();

            // Break time support
            $table->time('break_start')->nullable();
            $table->time('break_end')->nullable();

            // Indexes for performance
            $table->index('company_id');
            $table->index('branch_id');
            $table->index(['staff_id', 'day_of_week', 'is_active']);
            $table->index('calcom_schedule_id');

            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
        });

        // Update existing records to set company_id and branch_id from staff relationship
        DB::statement('
            UPDATE working_hours wh
            JOIN staff s ON wh.staff_id = s.id
            SET wh.company_id = s.company_id,
                wh.branch_id = s.branch_id
            WHERE wh.company_id IS NULL OR wh.branch_id IS NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('working_hours', function (Blueprint $table) {
            // Drop foreign key constraints first
            $table->dropForeign(['company_id']);
            $table->dropForeign(['branch_id']);

            // Drop indexes
            $table->dropIndex(['company_id']);
            $table->dropIndex(['branch_id']);
            $table->dropIndex(['staff_id', 'day_of_week', 'is_active']);
            $table->dropIndex(['calcom_schedule_id']);

            // Drop columns
            $table->dropColumn([
                'company_id',
                'branch_id',
                'title',
                'description',
                'timezone',
                'is_recurring',
                'valid_from',
                'valid_until',
                'break_start',
                'break_end',
                'calcom_availability_id',
                'calcom_schedule_id',
                'external_sync_at',
            ]);
        });
    }
};