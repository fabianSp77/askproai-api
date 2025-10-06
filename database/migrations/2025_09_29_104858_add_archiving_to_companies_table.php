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
        // Add archived_at timestamp
        if (!Schema::hasColumn('companies', 'archived_at')) {
            
        if (!Schema::hasTable('companies')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
                $table->timestamp('archived_at')->nullable();
                $table->text('archive_reason')->nullable();
                $table->string('archived_by')->nullable();
            });
        }

        // Update company_type enum to include 'archived' if not exists (only if column exists)
        if (Schema::hasColumn('companies', 'company_type')) {
            DB::statement("ALTER TABLE companies MODIFY COLUMN company_type ENUM('standalone', 'reseller', 'client', 'archived') DEFAULT 'client'");
        }

        // Add index for better query performance on archived companies
        Schema::table('companies', function (Blueprint $table) {
            $table->index('archived_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['archived_at']);
            $table->dropColumn(['archived_at', 'archive_reason', 'archived_by']);
        });

        // Revert company_type enum
        DB::statement("ALTER TABLE companies MODIFY COLUMN company_type ENUM('standalone', 'reseller', 'client') DEFAULT 'client'");
    }
};