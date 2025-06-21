<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up(): void
    {
        Schema::table('integrations', function (Blueprint $table) {
            /* 1  Spaltenname vereinheitlichen */
            if (Schema::hasColumn('integrations', 'kunde_id')) {
                $table->renameColumn('kunde_id', 'customer_id');
            }
            /* 2  JSON-Spalte anlegen */
            if (! Schema::hasColumn('integrations', 'credentials')) {
                $this->addJsonColumn($table, 'credentials', true);
            }
            /* 3  Aktiv-Flag */
            if (! Schema::hasColumn('integrations', 'active')) {
                $table->boolean('active')->default(true);
            }
            /* 4  Alte Longtext-Spalte entfernen */
            if (Schema::hasColumn('integrations', 'zugangsdaten')) {
                $table->dropColumn('zugangsdaten');
            }
        });
    }

    public function down(): void
    {
        Schema::table('integrations', function (Blueprint $table) {
            $table->longText('zugangsdaten')->nullable();
            $table->dropColumn(['credentials', 'active']);
            $table->renameColumn('customer_id', 'kunde_id');
        });
    }
};
