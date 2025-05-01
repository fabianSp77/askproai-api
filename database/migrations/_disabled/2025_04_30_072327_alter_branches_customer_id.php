<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1) vorhandenen FK (falls vorhanden) ermitteln & löschen
        $fks = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = 'branches'
              AND COLUMN_NAME  = 'customer_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        foreach ($fks as $fk) {
            Schema::table('branches', function (Blueprint $table) use ($fk) {
                $table->dropForeign($fk->CONSTRAINT_NAME);
            });
        }

        // 2) Spalte in CHAR(36) (UUID) ändern, nullable bleibt erhalten
        Schema::table('branches', function (Blueprint $table) {
            $table->char('customer_id', 36)->nullable()->change();
        });
    }

    public function down(): void
    {
        // zurück auf BIGINT unsigned (ohne FK)
        Schema::table('branches', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable()->change();
        });
    }
};
