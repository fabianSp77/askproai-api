<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up(): void
    {
        // Nur anlegen, wenn die Tabelle wirklich fehlt
        if (! Schema::hasTable('integrations')) {
            $this->createTableIfNotExists('integrations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('kunde_id')
                      ->constrained('customers')
                      ->cascadeOnDelete();
                $table->string('system');
                $this->addJsonColumn($table, 'zugangsdaten', true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Nur wieder löschen, falls wir sie gerade angelegt haben
        if (Schema::hasTable('integrations')) {
            $this->dropTableIfExists('integrations');
        }
    }
};
