<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends CompatibleMigration
{
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $t) {
            /* -----------------------------------------------------------------
             | 1) Spalten umbenennen
             * ----------------------------------------------------------------*/
            if (Schema::hasColumn('calls', 'call_id') && !Schema::hasColumn('calls', 'external_id')) {
                $t->renameColumn('call_id', 'external_id');
            }

            if (Schema::hasColumn('calls', 'raw_data') && !Schema::hasColumn('calls', 'raw')) {
                $t->renameColumn('raw_data', 'raw');
            }

            /* -----------------------------------------------------------------
             | 2) Altlasten entfernen
             |    → erst FK lösen, dann Spalte droppen
             * ----------------------------------------------------------------*/
            if (Schema::hasColumn('calls', 'kunde_id')) {
                // FK-Name per Konvention ⇒ calls_kunde_id_foreign
                $t->dropForeign(['kunde_id']);
                $t->dropColumn('kunde_id');
            }

            foreach ([
                'call_status', 'user_sentiment', 'successful', 'call_time',
                'call_duration', 'type', 'cost', 'phone_number', 'name',
                'email', 'summary', 'disconnect_reason',
            ] as $old) {
                if (Schema::hasColumn('calls', $old)) {
                    $t->dropColumn($old);
                }
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
        
        Schema::table('calls', function (Blueprint $t) {
            // nur das Wesentliche zurückrollen
            if (Schema::hasColumn('calls', 'external_id')) {
                $t->renameColumn('external_id', 'call_id');
            }
            if (Schema::hasColumn('calls', 'raw')) {
                $t->renameColumn('raw', 'raw_data');
            }

            // kunde_id nicht wieder anlegen – falls doch nötig, hier ergänzen
        });
    }
};
