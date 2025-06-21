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
            if (!Schema::hasColumn('phone_numbers', 'is_primary')) {
                $table->boolean('is_primary')->default(false)->after('active');
            }
            
            if (!Schema::hasColumn('phone_numbers', 'sms_enabled')) {
                $table->boolean('sms_enabled')->default(false)->after('is_primary');
            }
            
            if (!Schema::hasColumn('phone_numbers', 'whatsapp_enabled')) {
                $table->boolean('whatsapp_enabled')->default(false)->after('sms_enabled');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phone_numbers', function (Blueprint $table) {
            $table->dropColumn(['is_primary', 'sms_enabled', 'whatsapp_enabled']);
        });
    }
};