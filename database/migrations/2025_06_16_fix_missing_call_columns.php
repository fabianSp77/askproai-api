<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('calls', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('calls', 'agent_id')) {
                $table->string('agent_id')->nullable()->after('call_id');
            }
            if (!Schema::hasColumn('calls', 'appointment_requested')) {
                $table->boolean('appointment_requested')->default(false)->after('call_type');
            }
            if (!Schema::hasColumn('calls', 'extracted_date')) {
                $table->string('extracted_date')->nullable()->after('appointment_requested');
            }
            if (!Schema::hasColumn('calls', 'extracted_time')) {
                $table->string('extracted_time')->nullable()->after('extracted_date');
            }
            if (!Schema::hasColumn('calls', 'extracted_name')) {
                $table->string('extracted_name')->nullable()->after('extracted_time');
            }
            if (!Schema::hasColumn('calls', 'webhook_data')) {
                $table->json('webhook_data')->nullable()->after('raw_data');
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