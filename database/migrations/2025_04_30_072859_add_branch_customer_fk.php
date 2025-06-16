<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // In PHPUnit-/GH-Actions-Runs (SQLite) komplett auslassen
        if (config('database.default') === 'sqlite') {
            return;
        }

        Schema::table('branches', function (Blueprint $table) {
            // FK nur hinzufügen, falls sie noch nicht existiert
            $fk = DB::table('INFORMATION_SCHEMA.KEY_COLUMN_USAGE')
                ->where('TABLE_NAME', 'branches')
                ->where('CONSTRAINT_NAME', 'branches_customer_id_foreign')
                ->exists();

            if (! $fk) {
                $table->foreign('customer_id')
                      ->references('id')
                      ->on('customers')
                      ->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (config('database.default') === 'sqlite') {
            return;
        }

        Schema::table('branches', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });
    }
};
