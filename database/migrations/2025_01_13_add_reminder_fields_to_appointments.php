<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                if (!Schema::hasColumn('appointments', 'reminder_24h_sent_at')) {
                    $table->timestamp('reminder_24h_sent_at')->nullable();
                }
                if (!Schema::hasColumn('appointments', 'reminder_2h_sent_at')) {
                    $table->timestamp('reminder_2h_sent_at')->nullable();
                }
                if (!Schema::hasColumn('appointments', 'reminder_30m_sent_at')) {
                    $table->timestamp('reminder_30m_sent_at')->nullable();
                }
                if (!Schema::hasColumn('appointments', 'metadata')) {
                    $this->addJsonColumn($table, 'metadata', true);
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