<?php

use App\Database\CompatibleMigration;
use Illuminate\Support\Facades\DB;

return new class extends CompatibleMigration
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
