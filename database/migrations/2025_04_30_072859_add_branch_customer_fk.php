<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            // falls customer_id noch nicht existiert (sollte sie aber)
            if (! Schema::hasColumn('branches', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable();
            }

            // Fremdschlüssel nur anlegen, wenn er noch nicht existiert
            $fk = 'branches_customer_id_foreign';
            if (! $this->fkExists('branches', $fk)) {
                $table->foreign('customer_id', $fk)
                      ->references('id')->on('customers')
                      ->cascadeOnUpdate()
                      ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });
    }

    private function fkExists(string $table, string $key): bool
    {
        return (bool) DB::selectOne(
            'SELECT 1
             FROM   INFORMATION_SCHEMA.KEY_COLUMN_USAGE
             WHERE  TABLE_SCHEMA = DATABASE()
             AND    TABLE_NAME   = ?
             AND    CONSTRAINT_NAME = ?',
            [$table, $key]
        );
    }
};
