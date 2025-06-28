<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'default_event_type_id')) {
                $table->unsignedBigInteger('default_event_type_id')->nullable();
                $this->addForeignKey($table, 'default_event_type_id', 'calcom_event_types');
            }
        });
    }

    public function down(): void
    {
        // SQLite can't drop columns with indexes present
        if ($this->isSQLite()) {
            // For SQLite, we just skip the drop
            // The columns will remain but won't cause issues
            return;
        }

        // Skip in SQLite due to limitations
        if (!$this->isSQLite()) {
            $this->dropForeignKey('companies', 'companies_default_event_type_id_foreign');
            
            Schema::table('companies', function (Blueprint $table) {
                if (Schema::hasColumn('companies', 'default_event_type_id')) {
                    $table->dropColumn('default_event_type_id');
                }
            });
        }
    }
};