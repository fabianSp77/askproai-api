<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip in testing environment (SQLite doesn't support INFORMATION_SCHEMA)
        if (config('database.default') === 'sqlite') {
            return;
        }

        Schema::table('integrations', function (Blueprint $table) {
            // 1) FK nur droppen, wenn er existiert
            $fkExists = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->whereRaw('TABLE_SCHEMA = DATABASE()')
                ->where('TABLE_NAME', 'integrations')
                ->where('CONSTRAINT_NAME', 'integrations_kunde_id_foreign')
                ->exists();

            if ($fkExists) {
                $table->dropForeign('integrations_kunde_id_foreign');
            }

            // 2) Spalte-Typ anpassen (Idempotent)
            if (Schema::hasColumn('integrations', 'kunde_id')) {
                $table->unsignedBigInteger('kunde_id')->change();
            }

            // 3) FK neu anlegen, falls noch nicht vorhanden
            if (! $fkExists) {
                $table->foreign('kunde_id')
                    ->references('id')->on('customers')
                    ->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('integrations', function (Blueprint $table) {
            $table->dropForeign('integrations_kunde_id_foreign');
            $table->unsignedInteger('kunde_id')->change();
        });
    }
};
