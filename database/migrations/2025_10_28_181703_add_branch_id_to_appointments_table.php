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
        Schema::table('appointments', function (Blueprint $table) {
            // Add branch_id column after company_id for multi-tenant isolation
            $table->uuid('branch_id')->nullable()->after('company_id');

            // Add foreign key constraint
            $table->foreign('branch_id')
                  ->references('id')
                  ->on('branches')
                  ->onDelete('cascade');

            // Add composite index for multi-tenant queries
            $table->index(['company_id', 'branch_id'], 'idx_appointments_company_branch');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Drop foreign key and index first
            $table->dropForeign(['branch_id']);
            $table->dropIndex('idx_appointments_company_branch');

            // Drop column
            $table->dropColumn('branch_id');
        });
    }
};
