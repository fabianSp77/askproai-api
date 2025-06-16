<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                if (!Schema::hasColumn('appointments', 'reminder_24h_sent_at')) {
                    $table->timestamp('reminder_24h_sent_at')->nullable()->after('ends_at');
                }
                if (!Schema::hasColumn('appointments', 'reminder_2h_sent_at')) {
                    $table->timestamp('reminder_2h_sent_at')->nullable()->after('reminder_24h_sent_at');
                }
                if (!Schema::hasColumn('appointments', 'reminder_30m_sent_at')) {
                    $table->timestamp('reminder_30m_sent_at')->nullable()->after('reminder_2h_sent_at');
                }
                if (!Schema::hasColumn('appointments', 'metadata')) {
                    $table->json('metadata')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn([
                'reminder_24h_sent_at',
                'reminder_2h_sent_at',
                'reminder_30m_sent_at',
                'metadata'
            ]);
        });
    }
};