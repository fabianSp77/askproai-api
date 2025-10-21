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
            // 💾 Appointment Wish Notification Settings
            $table->boolean('notify_on_unfulfilled_wishes')
                ->default(false)
                ->after('id')
                ->comment('Enable email notifications for unfulfilled appointment wishes');

            $table->json('wish_notification_emails')
                ->nullable()
                ->after('notify_on_unfulfilled_wishes')
                ->comment('JSON array of email addresses to notify');

            $table->integer('wish_notification_delay_minutes')
                ->default(5)
                ->after('wish_notification_emails')
                ->comment('Minutes to wait before sending notification (allows for immediate rebook attempts)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'notify_on_unfulfilled_wishes',
                'wish_notification_emails',
                'wish_notification_delay_minutes',
            ]);
        });
    }
};
