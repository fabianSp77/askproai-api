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
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (!Schema::hasColumn('customers', 'push_token')) {
                    $table->string('push_token')->nullable();
                }
                if (!Schema::hasColumn('customers', 'device_platform')) {
                    $table->string('device_platform')->nullable();
                }
                if (!Schema::hasColumn('customers', 'device_id')) {
                    $table->string('device_id')->nullable();
                }
                if (!Schema::hasColumn('customers', 'sms_opt_in')) {
                    $table->boolean('sms_opt_in')->default(false);
                }
                if (!Schema::hasColumn('customers', 'whatsapp_opt_in')) {
                    $table->boolean('whatsapp_opt_in')->default(false);
                }
                if (!Schema::hasColumn('customers', 'created_via')) {
                    $table->string('created_via')->default('web');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // SQLite can't drop columns with indexes present
        if ($this->isSQLite()) {
            // For SQLite, we just skip the drop
            // The columns will remain but won't cause issues
            return;
        }
        
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