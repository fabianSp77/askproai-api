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
        Schema::table('companies', function (Blueprint $table) {
            $table->json('v128_config')->nullable()->after('settings');
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
