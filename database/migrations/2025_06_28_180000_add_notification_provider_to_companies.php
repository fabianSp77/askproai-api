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
        Schema::table('companies', function (Blueprint $table) {
            // Add email_notifications_enabled if it doesn't exist
            if (!Schema::hasColumn('companies', 'email_notifications_enabled')) {
                $table->boolean('email_notifications_enabled')
                    ->default(true)
                    ->after('is_active');
            }
            
            $table->enum('notification_provider', ['calcom', 'twilio', 'none'])
                ->default('calcom')
                ->after('is_active')
                ->comment('Which provider to use for SMS/WhatsApp notifications');
                
            $table->boolean('calcom_handles_notifications')
                ->default(true)
                ->after('notification_provider')
                ->comment('Let Cal.com handle all appointment notifications');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['notification_provider', 'calcom_handles_notifications']);
        });
    }
};