<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip in testing environment (SQLite doesn't support MODIFY)
        if (config('database.default') === 'sqlite') {
            return;
        }

        // For MySQL/MariaDB only
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE branches MODIFY customer_id BIGINT UNSIGNED');
        }
    }

    public function down(): void
    {
        // Skip in testing environment (SQLite doesn't support MODIFY)
        if (config('database.default') === 'sqlite') {
            return;
        }

        // For MySQL/MariaDB only
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE branches MODIFY customer_id INTEGER');
        }
    }
};
