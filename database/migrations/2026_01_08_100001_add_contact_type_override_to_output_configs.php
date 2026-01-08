<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add contact_type_override to service_output_configurations.
     *
     * Allows admins to override the auto-mapped ServiceNow contact_type
     * per output configuration. When null, the contact_type is automatically
     * derived from the ServiceCase's source field.
     */
    public function up(): void
    {
        Schema::table('service_output_configurations', function (Blueprint $table) {
            $table->string('contact_type_override', 50)
                ->nullable()
                ->after('webhook_include_transcript')
                ->comment('Override ServiceNow contact_type (null = auto-map from source)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_output_configurations', function (Blueprint $table) {
            $table->dropColumn('contact_type_override');
        });
    }
};
