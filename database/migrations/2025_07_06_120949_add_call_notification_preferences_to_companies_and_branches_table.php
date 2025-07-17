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
        // Add notification preferences to companies table
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'send_call_summaries')) {
                $table->boolean('send_call_summaries')->default(true)->after('is_active');
            }
            if (!Schema::hasColumn('companies', 'call_summary_recipients')) {
                $table->json('call_summary_recipients')->nullable()->after('send_call_summaries');
            }
            if (!Schema::hasColumn('companies', 'include_transcript_in_summary')) {
                $table->boolean('include_transcript_in_summary')->default(false)->after('call_summary_recipients');
            }
            if (!Schema::hasColumn('companies', 'include_csv_export')) {
                $table->boolean('include_csv_export')->default(true)->after('include_transcript_in_summary');
            }
            if (!Schema::hasColumn('companies', 'summary_email_frequency')) {
                $table->enum('summary_email_frequency', ['immediate', 'hourly', 'daily'])->default('immediate')->after('include_csv_export');
            }
            if (!Schema::hasColumn('companies', 'call_notification_settings')) {
                $table->json('call_notification_settings')->nullable()->after('summary_email_frequency')->comment('Additional notification settings');
            }
        });

        // Add notification preferences to branches table
        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'send_call_summaries')) {
                $table->boolean('send_call_summaries')->nullable()->after('notification_email')->comment('Override company setting');
            }
            if (!Schema::hasColumn('branches', 'call_summary_recipients')) {
                $table->json('call_summary_recipients')->nullable()->after('send_call_summaries')->comment('Branch-specific recipients');
            }
            if (!Schema::hasColumn('branches', 'include_transcript_in_summary')) {
                $table->boolean('include_transcript_in_summary')->nullable()->after('call_summary_recipients');
            }
            if (!Schema::hasColumn('branches', 'include_csv_export')) {
                $table->boolean('include_csv_export')->nullable()->after('include_transcript_in_summary');
            }
            if (!Schema::hasColumn('branches', 'summary_email_frequency')) {
                $table->enum('summary_email_frequency', ['immediate', 'hourly', 'daily'])->nullable()->after('include_csv_export');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'send_call_summaries',
                'call_summary_recipients',
                'include_transcript_in_summary',
                'include_csv_export',
                'summary_email_frequency',
                'call_notification_settings'
            ]);
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn([
                'send_call_summaries',
                'call_summary_recipients',
                'include_transcript_in_summary',
                'include_csv_export',
                'summary_email_frequency'
            ]);
        });
    }
};
