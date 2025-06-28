<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
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
        // SQLite can't drop columns with indexes present
        if ($this->isSQLite()) {
            // For SQLite, we just skip the drop
            // The columns will remain but won't cause issues
            return;
        }
        
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['retell_webhook_url', 'retell_agent_id', 'retell_voice', 'retell_enabled']);
        });
    }
};
