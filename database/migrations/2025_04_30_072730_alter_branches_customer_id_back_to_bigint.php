<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Im Test-Environment (SQLite) komplett überspringen
        if (config('database.default') === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE branches MODIFY customer_id BIGINT UNSIGNED');
    }

    public function down(): void
    {
        if (config('database.default') === 'sqlite') {
            return;
        }

        // Live-DB zurückrollen
        DB::statement('ALTER TABLE branches MODIFY customer_id INTEGER');
    }
};
