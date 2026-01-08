<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add muted_recipients column for temporarily pausing email delivery.
 *
 * This enables testing with specific recipients without modifying
 * the primary email_recipients list. Muted recipients are excluded
 * from delivery but preserved in configuration.
 *
 * @see App\Models\ServiceOutputConfiguration::getActiveRecipients()
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_output_configurations', function (Blueprint $table) {
            $table->json('muted_recipients')
                ->nullable()
                ->after('email_recipients')
                ->comment('Email addresses temporarily paused from receiving notifications');
        });
    }

    public function down(): void
    {
        Schema::table('service_output_configurations', function (Blueprint $table) {
            $table->dropColumn('muted_recipients');
        });
    }
};
