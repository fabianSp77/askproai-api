<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up()
    {
        Schema::table('calls', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('calls', 'agent_id')) {
                $table->string('agent_id')->nullable();
            }
            if (!Schema::hasColumn('calls', 'appointment_requested')) {
                $table->boolean('appointment_requested')->default(false);
            }
            if (!Schema::hasColumn('calls', 'extracted_date')) {
                $table->string('extracted_date')->nullable();
            }
            if (!Schema::hasColumn('calls', 'extracted_time')) {
                $table->string('extracted_time')->nullable();
            }
            if (!Schema::hasColumn('calls', 'extracted_name')) {
                $table->string('extracted_name')->nullable();
            }
            if (!Schema::hasColumn('calls', 'webhook_data')) {
                $this->addJsonColumn($table, 'webhook_data', true);
            }
        });
    }

    public function down()
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn([
                'agent_id',
                'appointment_requested',
                'extracted_date',
                'extracted_time',
                'extracted_name',
                'webhook_data'
            ]);
        });
    }
};