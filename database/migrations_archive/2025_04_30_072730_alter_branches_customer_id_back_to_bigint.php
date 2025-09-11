<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Im Test-Environment (SQLite) komplett überspringen

        DB::statement('ALTER TABLE branches MODIFY customer_id BIGINT UNSIGNED');
    }

    public function down(): void
    {

        // Live-DB zurückrollen
        DB::statement('ALTER TABLE branches MODIFY customer_id INTEGER');
    }
};
