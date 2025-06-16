<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            // Nur hinzufügen wenn nicht existiert
            if (!Schema::hasColumn('companies', 'retell_webhook_url')) {
                $table->string('retell_webhook_url')->nullable()->default('https://api.askproai.de/api/retell/webhook');
            }
            if (!Schema::hasColumn('companies', 'retell_agent_id')) {
                $table->string('retell_agent_id')->nullable();
            }
            if (!Schema::hasColumn('companies', 'retell_voice')) {
                $table->string('retell_voice', 50)->nullable()->default('nova');
            }
            if (!Schema::hasColumn('companies', 'retell_enabled')) {
                $table->boolean('retell_enabled')->default(false);
            }
        });
    }

    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['retell_webhook_url', 'retell_agent_id', 'retell_voice', 'retell_enabled']);
        });
    }
};
