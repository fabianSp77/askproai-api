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
        // First, add retell_agent_id to phone_numbers if it doesn't exist
        if (!Schema::hasColumn('phone_numbers', 'retell_agent_id')) {
            Schema::table('phone_numbers', function (Blueprint $table) {
                $table->string('retell_agent_id')->nullable()->after('retell_phone_id');
                $table->string('retell_agent_version')->nullable()->after('retell_agent_id');
                $table->index('retell_agent_id');
            });
        }
        
        // Copy agent assignments from branches to their phone numbers
        DB::statement('
            UPDATE phone_numbers p
            INNER JOIN branches b ON p.branch_id = b.id
            SET p.retell_agent_id = b.retell_agent_id
            WHERE b.retell_agent_id IS NOT NULL
            AND p.retell_agent_id IS NULL
        ');
        
        // Note: We're keeping retell_agent_id in branches for now as a fallback
        // It can be removed in a future migration after confirming everything works
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Copy agent assignments back from phone numbers to branches (using primary phone)
        DB::statement('
            UPDATE branches b
            INNER JOIN phone_numbers p ON p.branch_id = b.id AND p.is_primary = 1
            SET b.retell_agent_id = p.retell_agent_id
            WHERE p.retell_agent_id IS NOT NULL
            AND b.retell_agent_id IS NULL
        ');
        
        // Remove columns from phone_numbers
        Schema::table('phone_numbers', function (Blueprint $table) {
            $table->dropColumn(['retell_agent_id', 'retell_agent_version']);
        });
    }
};