<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Assignment Groups for Service Gateway
 *
 * ServiceNow-style team-based ticket assignment.
 * Groups can have multiple staff members and cases
 * can be assigned to a group instead of an individual.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Assignment Groups table
        Schema::create('assignment_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('email')->nullable()->comment('Group email for notifications');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index(['company_id', 'is_active']);
            $table->unique(['company_id', 'name']);
        });

        // Pivot table: Assignment Group <-> Staff Members
        // Note: Staff table uses char(36) UUID for id, not bigint
        Schema::create('assignment_group_staff', function (Blueprint $table) {
            $table->foreignId('assignment_group_id')->constrained()->cascadeOnDelete();
            $table->char('staff_id', 36)->charset('utf8mb4')->collation('utf8mb4_unicode_ci');
            $table->foreign('staff_id')->references('id')->on('staff')->cascadeOnDelete();
            $table->boolean('is_lead')->default(false)->comment('Team lead can approve escalations');
            $table->timestamps();

            $table->primary(['assignment_group_id', 'staff_id']);
        });

        // Add assigned_group_id to service_cases
        Schema::table('service_cases', function (Blueprint $table) {
            $table->foreignId('assigned_group_id')
                ->nullable()
                ->after('assigned_to')
                ->constrained('assignment_groups')
                ->nullOnDelete();

            $table->index('assigned_group_id');
        });
    }

    public function down(): void
    {
        Schema::table('service_cases', function (Blueprint $table) {
            $table->dropForeign(['assigned_group_id']);
            $table->dropColumn('assigned_group_id');
        });

        Schema::dropIfExists('assignment_group_staff');
        Schema::dropIfExists('assignment_groups');
    }
};
