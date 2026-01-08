<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * V128 Config stores Retell AI conversation flow settings:
     * - time_shift_enabled: bool - Show time-shift messages (vormittag→abend)
     * - time_shift_message: string - Custom message template
     * - name_skip_enabled: bool - Skip name question for known customers
     * - full_confirmation_enabled: bool - Include all details in booking confirmation
     * - silence_handling_enabled: bool - Auto-hangup after silence
     */
    public function up(): void
    {
        // Skip if table doesn't exist
        if (!Schema::hasTable('companies')) {
            return;
        }

        // Skip if column already exists
        if (Schema::hasColumn('companies', 'v128_config')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            // Use fallback column if 'settings' doesn't exist
            $afterColumn = Schema::hasColumn('companies', 'settings') ? 'settings' : 'id';
            $table->json('v128_config')->nullable()->after($afterColumn);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('v128_config');
        });
    }
};
