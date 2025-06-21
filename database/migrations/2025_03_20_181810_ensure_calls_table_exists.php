<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration {
    public function up(): void
    {
        /* nur anlegen, wenn die Tabelle noch nicht existiert */
        if (! Schema::hasTable('calls')) {
            Schema::create('calls', function (Blueprint $table) {
                $table->id();
                $table->string('external_id')->nullable()->index();
                $table->text('transcript')->nullable();
                $this->addJsonColumn($table, 'raw', true);
                $table->timestamps();
            });
        }
    }
    public function down(): void
    {
        /* nichts löschen – Safety-Migration */
    }
};
