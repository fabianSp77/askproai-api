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
        Schema::table('phone_numbers', function (Blueprint $table) {
            // Add company_id for multi-tenancy
            if (!Schema::hasColumn('phone_numbers', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained('companies');
            }
            
            // Add Retell-specific fields
            if (!Schema::hasColumn('phone_numbers', 'retell_phone_id')) {
                $table->string('retell_phone_id')->nullable()->after('number');
            }
            
            if (!Schema::hasColumn('phone_numbers', 'retell_agent_id')) {
                $table->string('retell_agent_id')->nullable()->after('retell_phone_id');
            }
            
            // Add is_primary flag
            if (!Schema::hasColumn('phone_numbers', 'is_primary')) {
                $table->boolean('is_primary')->default(false)->after('active');
            }
            
            // Add type field
            if (!Schema::hasColumn('phone_numbers', 'type')) {
                $table->string('type', 50)->default('office')->after('is_primary');
                // Types: office, mobile, fax, retell, hotline
            }
            
            // Rename active to is_active for consistency
            if (Schema::hasColumn('phone_numbers', 'active') && !Schema::hasColumn('phone_numbers', 'is_active')) {
                $table->renameColumn('active', 'is_active');
            }
            
            // Add capabilities as JSON
            if (!Schema::hasColumn('phone_numbers', 'capabilities')) {
                $table->json('capabilities')->nullable()->after('type');
                // Example: {"sms": true, "voice": true, "whatsapp": false}
            }
            
            // Add metadata for additional information
            if (!Schema::hasColumn('phone_numbers', 'metadata')) {
                $table->json('metadata')->nullable()->after('capabilities');
            }
            
            // Add indexes for performance
            $table->index('retell_phone_id');
            $table->index('retell_agent_id');
            $table->index('type');
            $table->index(['company_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phone_numbers', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['company_id', 'is_active']);
            $table->dropIndex(['type']);
            $table->dropIndex(['retell_agent_id']);
            $table->dropIndex(['retell_phone_id']);
            
            // Drop columns
            if (Schema::hasColumn('phone_numbers', 'metadata')) {
                $table->dropColumn('metadata');
            }
            
            if (Schema::hasColumn('phone_numbers', 'capabilities')) {
                $table->dropColumn('capabilities');
            }
            
            if (Schema::hasColumn('phone_numbers', 'type')) {
                $table->dropColumn('type');
            }
            
            if (Schema::hasColumn('phone_numbers', 'is_primary')) {
                $table->dropColumn('is_primary');
            }
            
            if (Schema::hasColumn('phone_numbers', 'retell_agent_id')) {
                $table->dropColumn('retell_agent_id');
            }
            
            if (Schema::hasColumn('phone_numbers', 'retell_phone_id')) {
                $table->dropColumn('retell_phone_id');
            }
            
            // Rename back to active
            if (Schema::hasColumn('phone_numbers', 'is_active') && !Schema::hasColumn('phone_numbers', 'active')) {
                $table->renameColumn('is_active', 'active');
            }
            
            // Drop foreign key and column
            if (Schema::hasColumn('phone_numbers', 'company_id')) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            }
        });
    }
};