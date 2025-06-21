<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up()
    {
        Schema::table('branches', function (Blueprint $table) {
            // calendar_mode existiert bereits, füge nur fehlende Felder hinzu
            if (!Schema::hasColumn('branches', 'calcom_api_key')) {
                $table->string('calcom_api_key')->nullable();
            }
            if (!Schema::hasColumn('branches', 'retell_agent_id')) {
                $table->string('retell_agent_id')->nullable();
            }
            if (!Schema::hasColumn('branches', 'integration_status')) {
                $this->addJsonColumn($table, 'integration_status', true);
            }
        });
    }

    public function down()
    {
        Schema::table('branches', function (Blueprint $table) {
            $columns = ['calcom_api_key', 'retell_agent_id', 'integration_status'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('branches', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
