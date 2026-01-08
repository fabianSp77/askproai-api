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
        // Skip if table doesn't exist
        if (!Schema::hasTable('service_output_configurations')) {
            return;
        }

        // Skip if column already exists
        if (Schema::hasColumn('service_output_configurations', 'email_show_admin_link')) {
            return;
        }

        Schema::table('service_output_configurations', function (Blueprint $table) {
            $afterColumn = Schema::hasColumn('service_output_configurations', 'include_summary')
                ? 'include_summary' : 'id';
            $table->boolean('email_show_admin_link')
                ->default(false)
                ->after($afterColumn)
                ->comment('Show "Ticket bearbeiten" button in email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_output_configurations', function (Blueprint $table) {
            $table->dropColumn('email_show_admin_link');
        });
    }
};
