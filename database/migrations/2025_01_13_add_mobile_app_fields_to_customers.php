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
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (!Schema::hasColumn('customers', 'push_token')) {
                    $table->string('push_token')->nullable()->after('phone');
                }
                if (!Schema::hasColumn('customers', 'device_platform')) {
                    $table->string('device_platform')->nullable()->after('push_token');
                }
                if (!Schema::hasColumn('customers', 'device_id')) {
                    $table->string('device_id')->nullable()->after('device_platform');
                }
                if (!Schema::hasColumn('customers', 'sms_opt_in')) {
                    $table->boolean('sms_opt_in')->default(false)->after('device_id');
                }
                if (!Schema::hasColumn('customers', 'whatsapp_opt_in')) {
                    $table->boolean('whatsapp_opt_in')->default(false)->after('sms_opt_in');
                }
                if (!Schema::hasColumn('customers', 'created_via')) {
                    $table->string('created_via')->default('web')->after('whatsapp_opt_in');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'push_token',
                'device_platform',
                'device_id',
                'sms_opt_in',
                'whatsapp_opt_in',
                'created_via'
            ]);
        });
    }
};