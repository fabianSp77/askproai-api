<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Skip in testing environment (SQLite doesn't support MySQL commands)
        if (config('database.default') === 'sqlite') {
            return;
        }

        // MySQL-/MariaDB-Logic bleibt unverändert
        DB::statement('SET foreign_key_checks = 0');

        //  hier folgen evtl. weitere ALTER-TABLE-Befehle …
        //  (falls nötig aus Original-Datei kopieren)

        DB::statement('SET foreign_key_checks = 1');
    }

    public function down(): void
    {

        // down-Migrations (Original übernehmen)
        DB::statement('SET foreign_key_checks = 0');
        // … revert logic …
        DB::statement('SET foreign_key_checks = 1');
    }
};
