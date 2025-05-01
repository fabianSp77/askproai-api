<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE branches MODIFY customer_id BIGINT UNSIGNED');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE branches MODIFY customer_id CHAR(36)');
    }
};

