<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('calls', 'retell_agent_id')) {
            
        if (!Schema::hasTable('calls')) {
            return;
        }

        Schema::table('calls', function (Blueprint $table) {
                $table->string('retell_agent_id')->nullable();
            });
        }

        try {
            DB::statement(<<<SQL
                UPDATE calls c
                INNER JOIN phone_numbers p ON c.phone_number_id = p.id
                SET c.retell_agent_id = p.retell_agent_id
                WHERE c.retell_agent_id IS NULL AND p.retell_agent_id IS NOT NULL
            SQL);
        } catch (\Throwable $exception) {
            // Fallback silently if the phone_numbers table is missing or the update fails
            logger()->warning('Unable to backfill retell_agent_id on calls', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('calls', 'retell_agent_id')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->dropColumn('retell_agent_id');
            });
        }
    }
};
