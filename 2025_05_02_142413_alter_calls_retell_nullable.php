<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE calls
                       MODIFY retell_call_id VARCHAR(255) NULL');
        DB::statement('DROP INDEX IF EXISTS calls_retell_call_id_unique ON calls');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_calls_retell
                       ON calls (retell_call_id)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_calls_retell ON calls');
        DB::statement('ALTER TABLE calls
                       MODIFY retell_call_id VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE calls
                       ADD UNIQUE calls_retell_call_id_unique (retell_call_id)');
    }
};
